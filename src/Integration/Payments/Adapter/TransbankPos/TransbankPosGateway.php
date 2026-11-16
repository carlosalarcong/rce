<?php

declare(strict_types=1);

namespace App\Integration\Payments\Adapter\TransbankPos;

use App\Integration\Payments\Contract\Clp;
use App\Integration\Payments\Contract\ConfirmPayment;
use App\Integration\Payments\Contract\PaymentGatewayInterface;
use App\Integration\Payments\Contract\PaymentNextStep;
use App\Integration\Payments\Contract\PaymentOutcome;
use App\Integration\Payments\Contract\PaymentResult;
use App\Integration\Payments\Contract\PaymentStarted;
use App\Integration\Payments\Contract\RefundOutcome;
use App\Integration\Payments\Contract\RefundPayment;
use App\Integration\Payments\Contract\StartPayment;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * POS de Transbank (máquina física), lado del servidor. Ver CLAUDE.md de esta carpeta.
 *
 * El servidor no habla con la máquina: la pantalla de caja le habla al agente
 * de Transbank en el computador de recepción y reenvía su respuesta, que llega
 * en ConfirmPayment::$terminalResponse. Esa respuesta viene del navegador y
 * Transbank no la puede confirmar desde aquí: se guarda marcada como
 * "verified_by_provider: false" hasta definir la conciliación (docs/adr/0004).
 */
#[AutoconfigureTag('payment.gateway')]
final readonly class TransbankPosGateway implements PaymentGatewayInterface
{
    public const string NAME = 'transbank_pos';

    private const int APPROVED = 0;

    /**
     * Campos de la respuesta de venta que se guardan para auditoría. Quedan fuera
     * last4Digits y accountNumber (datos de tarjeta).
     */
    private const array AUDITED_FIELDS = [
        'functionCode' => 'function_code',
        'responseCode' => 'response_code',
        'responseMessage' => 'response_message',
        'commerceCode' => 'commerce_code',
        'terminalId' => 'terminal_id',
        'ticket' => 'ticket',
        'authorizationCode' => 'authorization_code',
        'amount' => 'amount',
        'sharesNumber' => 'shares_number',
        'operationNumber' => 'operation_number',
        'cardType' => 'card_type',
        'cardBrand' => 'card_brand',
        'accountingDate' => 'accounting_date',
        'realDate' => 'real_date',
        'employeeId' => 'employee_id',
    ];

    public function name(): string
    {
        return self::NAME;
    }

    /** La orden de compra viaja a la máquina como "ticket" y vuelve en su respuesta. */
    public function startPayment(StartPayment $request): PaymentStarted
    {
        return new PaymentStarted($request->buyOrder, PaymentNextStep::WaitForTerminal);
    }

    public function confirm(ConfirmPayment $request): PaymentOutcome
    {
        $terminal = $request->terminalResponse;

        // La máquina todavía no responde (el paciente no termina): sigue pendiente.
        if ([] === $terminal) {
            return new PaymentOutcome(PaymentResult::Pending, $request->providerReference, $request->expectedAmount ?? new Clp(0));
        }

        $ticket = $this->string($terminal, 'ticket');
        if ($ticket !== $request->providerReference) {
            throw new \InvalidArgumentException('Terminal response belongs to another sale (ticket mismatch).');
        }

        $responseCode = $terminal['responseCode'] ?? null;
        if (!is_numeric($responseCode)) {
            throw new \InvalidArgumentException('Terminal response has no response code.');
        }

        $amount = $this->amount($terminal);
        $audited = $this->auditedFields($terminal);

        if (self::APPROVED !== (int) $responseCode) {
            return new PaymentOutcome(PaymentResult::Rejected, $request->providerReference, $amount, null, null, $audited);
        }

        if (null !== $request->expectedAmount && !$request->expectedAmount->equals($amount)) {
            throw new \InvalidArgumentException(\sprintf('Terminal amount %d differs from the expected %d.', $amount->value, $request->expectedAmount->value));
        }

        $authorizationCode = $this->string($terminal, 'authorizationCode');
        if ('' === $authorizationCode) {
            throw new \InvalidArgumentException('Approved terminal response has no authorization code.');
        }

        return new PaymentOutcome(
            PaymentResult::Confirmed,
            $request->providerReference,
            $amount,
            $authorizationCode,
            $this->dateFrom($this->string($terminal, 'realDate')),
            $audited,
        );
    }

    /**
     * La anulación en POS se hace en la máquina, desde la pantalla de caja, con
     * el número de operación. El servidor solo la registrará cuando exista ese flujo.
     */
    public function refund(RefundPayment $request): RefundOutcome
    {
        throw new \LogicException('Transbank POS refunds are made on the terminal, from the cashier screen.');
    }

    /** @param array<string, scalar|null> $terminal */
    private function amount(array $terminal): Clp
    {
        $amount = $terminal['amount'] ?? null;
        if (!\is_int($amount) && !(\is_string($amount) && ctype_digit($amount))) {
            throw new \InvalidArgumentException('Terminal response has no valid amount.');
        }

        return new Clp((int) $amount);
    }

    /** @param array<string, scalar|null> $terminal */
    private function string(array $terminal, string $key): string
    {
        return trim((string) ($terminal[$key] ?? ''));
    }

    /**
     * @param array<string, scalar|null> $terminal
     *
     * @return array<string, scalar|null>
     */
    private function auditedFields(array $terminal): array
    {
        $fields = ['verified_by_provider' => false];
        foreach (self::AUDITED_FIELDS as $source => $key) {
            $fields[$key] = $terminal[$source] ?? null;
        }

        return $fields;
    }

    /** La máquina informa hora local de Chile: "dd/mm/aaaa hh:mm:ss". Se guarda en UTC. */
    private function dateFrom(string $value): ?\DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat('!d/m/Y H:i:s', $value, new \DateTimeZone('America/Santiago'));

        return false === $date ? null : $date->setTimezone(new \DateTimeZone('UTC'));
    }
}
