<?php

declare(strict_types=1);

namespace App\Integration\Payments\Adapter\Webpay;

use App\Integration\Payments\Contract\Clp;
use App\Integration\Payments\Contract\ConfirmPayment;
use App\Integration\Payments\Contract\PaymentGatewayInterface;
use App\Integration\Payments\Contract\PaymentNextStep;
use App\Integration\Payments\Contract\PaymentOutcome;
use App\Integration\Payments\Contract\PaymentResult;
use App\Integration\Payments\Contract\PaymentStarted;
use App\Integration\Payments\Contract\ProviderCallback;
use App\Integration\Payments\Contract\ProviderCredentialsInterface;
use App\Integration\Payments\Contract\ProviderUnavailableException;
use App\Integration\Payments\Contract\ReceivesCallbacksInterface;
use App\Integration\Payments\Contract\RefundOutcome;
use App\Integration\Payments\Contract\RefundPayment;
use App\Integration\Payments\Contract\StartPayment;
use Psr\Http\Client\ClientExceptionInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Transbank\Contracts\HttpClientInterface;
use Transbank\Utils\HttpClientRequestService;
use Transbank\Webpay\Exceptions\WebpayRequestException;
use Transbank\Webpay\Options;
use Transbank\Webpay\WebpayPlus\Responses\TransactionStatusResponse;
use Transbank\Webpay\WebpayPlus\Transaction;

/**
 * Webpay Plus (Transbank) con su librería oficial. Ver CLAUDE.md de esta carpeta.
 *
 * - startPayment(): crea la transacción y devuelve a dónde redirigir al paciente.
 * - confirm(): commit en Transbank. Si ya se hizo (retorno repetido), consulta el estado.
 * - refund(): anulación o reversa.
 *
 * Nunca guarda ni devuelve datos de tarjeta.
 */
#[AutoconfigureTag('payment.gateway')]
final readonly class WebpayGateway implements PaymentGatewayInterface, ReceivesCallbacksInterface
{
    public const string NAME = 'webpay';

    /** Transbank responde 422 cuando la transacción ya no se puede confirmar (ej.: commit repetido). */
    private const int HTTP_UNPROCESSABLE = 422;

    private const string TOKEN_PATTERN = '/^[A-Za-z0-9]{1,128}$/';

    /** Campos de la respuesta que se guardan para auditoría. card_detail (últimos dígitos) queda fuera. */
    private const array AUDITED_FIELDS = [
        'status' => 'status',
        'responseCode' => 'response_code',
        'buyOrder' => 'buy_order',
        'authorizationCode' => 'authorization_code',
        'paymentTypeCode' => 'payment_type_code',
        'installmentsNumber' => 'installments_number',
        'accountingDate' => 'accounting_date',
        'transactionDate' => 'transaction_date',
    ];

    public function __construct(
        private ProviderCredentialsInterface $credentials,
        #[Autowire('%env(int:WEBPAY_TIMEOUT_SECONDS)%')]
        private int $timeoutSeconds = 10,
        private ?HttpClientInterface $httpClient = null,
    ) {
        if ($timeoutSeconds < 1) {
            throw new \InvalidArgumentException('Webpay timeout must be at least 1 second.');
        }
    }

    public function name(): string
    {
        return self::NAME;
    }

    public function startPayment(StartPayment $request): PaymentStarted
    {
        if (null === $request->returnUrl || '' === $request->returnUrl) {
            throw new \InvalidArgumentException('Webpay needs a return URL.');
        }

        try {
            $response = $this->transaction()->create(
                $request->buyOrder,
                $request->buyOrder,
                $request->amount->value,
                $request->returnUrl,
            );
        } catch (WebpayRequestException $e) {
            throw $this->isProviderDown($e) ? ProviderUnavailableException::because(self::NAME, 'create failed', $e) : new \UnexpectedValueException(\sprintf('Webpay rejected the create request (HTTP %d).', $e->getHttpCode()), 0, $e);
        } catch (ClientExceptionInterface $e) {
            throw ProviderUnavailableException::because(self::NAME, 'network error on create', $e);
        }

        $token = $response->getToken();
        $url = $response->getUrl();
        if (null === $token || '' === $token || null === $url || '' === $url) {
            throw ProviderUnavailableException::because(self::NAME, 'create returned no token or url');
        }

        return new PaymentStarted($token, PaymentNextStep::Redirect, $url, ['token_ws' => $token]);
    }

    public function confirm(ConfirmPayment $request): PaymentOutcome
    {
        $token = $request->providerReference;
        $transaction = $this->transaction();

        try {
            $response = $transaction->commit($token);
        } catch (WebpayRequestException $e) {
            if ($this->isProviderDown($e)) {
                throw ProviderUnavailableException::because(self::NAME, 'commit failed', $e);
            }
            if (self::HTTP_UNPROCESSABLE !== $e->getHttpCode()) {
                throw new \UnexpectedValueException(\sprintf('Webpay rejected the commit (HTTP %d).', $e->getHttpCode()), 0, $e);
            }
            // Ya confirmada, anulada o abandonada: el estado real lo dice status().
            $response = $this->status($transaction, $token);
        } catch (ClientExceptionInterface $e) {
            throw ProviderUnavailableException::because(self::NAME, 'network error on commit', $e);
        }

        return $this->outcomeFrom($response, $token, $request->expectedAmount);
    }

    public function refund(RefundPayment $request): RefundOutcome
    {
        try {
            $response = $this->transaction()->refund($request->providerReference, $request->amount->value);
        } catch (WebpayRequestException $e) {
            if ($this->isProviderDown($e)) {
                throw ProviderUnavailableException::because(self::NAME, 'refund failed', $e);
            }

            // Rechazo de negocio (ej.: fuera de plazo o monto mayor al saldo): es un resultado, no una excepción.
            return new RefundOutcome(false, $request->providerReference, new Clp(0), [
                'http_code' => $e->getHttpCode(),
                'error_message' => $e->getTransbankErrorMessage(),
            ]);
        } catch (ClientExceptionInterface $e) {
            throw ProviderUnavailableException::because(self::NAME, 'network error on refund', $e);
        }

        $nullified = $response->getNullifiedAmount() ?? $request->amount->value;

        return new RefundOutcome($response->success(), $request->providerReference, $this->clp($nullified), [
            'type' => $response->getType(),
            'response_code' => $response->getResponseCode(),
            'authorization_code' => $response->getAuthorizationCode(),
            'nullified_amount' => $response->getNullifiedAmount(),
            'balance' => $response->getBalance(),
        ]);
    }

    /**
     * Webpay no firma el retorno: se valida que traiga un token con forma válida
     * y el estado real se obtiene siempre con confirm(). Sin token (el paciente
     * dejó vencer el formulario), el retorno se descarta y el intento expira.
     */
    public function verifyCallback(ProviderCallback $callback): bool
    {
        $token = $this->tokenFrom($callback);

        return null !== $token && 1 === preg_match(self::TOKEN_PATTERN, $token);
    }

    public function referenceFrom(ProviderCallback $callback): string
    {
        if (!$this->verifyCallback($callback)) {
            throw new \InvalidArgumentException('Webpay callback has no valid token.');
        }

        return (string) $this->tokenFrom($callback);
    }

    /** token_ws: retorno normal. TBK_TOKEN: el paciente anuló el pago en Webpay. */
    private function tokenFrom(ProviderCallback $callback): ?string
    {
        foreach (['token_ws', 'TBK_TOKEN'] as $field) {
            $value = $callback->form[$field] ?? $callback->query[$field] ?? '';
            if ('' !== $value) {
                return $value;
            }
        }

        return null;
    }

    private function status(Transaction $transaction, string $token): TransactionStatusResponse
    {
        try {
            return $transaction->status($token);
        } catch (WebpayRequestException $e) {
            throw $this->isProviderDown($e) ? ProviderUnavailableException::because(self::NAME, 'status failed', $e) : new \UnexpectedValueException(\sprintf('Webpay rejected the status request (HTTP %d).', $e->getHttpCode()), 0, $e);
        } catch (ClientExceptionInterface $e) {
            throw ProviderUnavailableException::because(self::NAME, 'network error on status', $e);
        }
    }

    private function outcomeFrom(TransactionStatusResponse $response, string $token, ?Clp $expectedAmount): PaymentOutcome
    {
        $amount = $this->clp($response->amount);
        if (null !== $expectedAmount && !$expectedAmount->equals($amount)) {
            throw new \UnexpectedValueException(\sprintf('Webpay amount %d differs from the expected %d.', $amount->value, $expectedAmount->value));
        }

        $status = $response->getStatus();
        $approved = 0 === $response->getResponseCode();
        $authorizationCode = $response->getAuthorizationCode();
        $hasAuthorization = null !== $authorizationCode && '' !== $authorizationCode;

        $result = match (true) {
            'INITIALIZED' === $status => PaymentResult::Pending,
            'AUTHORIZED' === $status && $approved && $hasAuthorization => PaymentResult::Confirmed,
            // Pagada y después anulada o capturada: el cobro sí existió. La anulación se registra aparte.
            \in_array($status, ['NULLIFIED', 'PARTIALLY_NULLIFIED', 'CAPTURED'], true) && $hasAuthorization => PaymentResult::Confirmed,
            \in_array($status, ['FAILED', 'REVERSED', 'AUTHORIZED'], true) => PaymentResult::Rejected,
            // Estado desconocido: nunca se confirma.
            default => PaymentResult::Pending,
        };

        return new PaymentOutcome(
            $result,
            $token,
            $amount,
            PaymentResult::Confirmed === $result ? $authorizationCode : null,
            $this->dateFrom($response->transactionDate),
            $this->auditedFields($response),
        );
    }

    /** @return array<string, scalar|null> */
    private function auditedFields(TransactionStatusResponse $response): array
    {
        $fields = [];
        foreach (self::AUDITED_FIELDS as $property => $key) {
            $value = $response->{$property};
            $fields[$key] = \is_scalar($value) ? $value : null;
        }

        return $fields;
    }

    private function dateFrom(?string $value): ?\DateTimeImmutable
    {
        if (null === $value || '' === $value) {
            return null;
        }

        try {
            return new \DateTimeImmutable($value)->setTimezone(new \DateTimeZone('UTC'));
        } catch (\Exception) {
            return null;
        }
    }

    private function clp(int|float|null $amount): Clp
    {
        if (null === $amount || (float) (int) $amount !== (float) $amount) {
            throw new \UnexpectedValueException('Webpay returned a missing or non-integer amount.');
        }

        return new Clp((int) $amount);
    }

    /** Sin respuesta HTTP (0) o error del lado de Transbank (5xx): reintentable. */
    private function isProviderDown(WebpayRequestException $e): bool
    {
        $code = $e->getHttpCode();

        return 0 === $code || $code >= 500;
    }

    private function transaction(): Transaction
    {
        $credentials = $this->credentials->for(self::NAME);
        $options = new Options(
            $credentials->get('api_key'),
            $credentials->get('commerce_code'),
            $credentials->production ? Options::ENVIRONMENT_PRODUCTION : Options::ENVIRONMENT_INTEGRATION,
            $this->timeoutSeconds,
        );

        return new Transaction($options, new HttpClientRequestService($this->httpClient));
    }
}
