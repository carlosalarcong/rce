<?php

declare(strict_types=1);

namespace App\Integration\Payments\Adapter\Imed;

use App\Integration\Payments\Contract\CancelVoucher;
use App\Integration\Payments\Contract\Clp;
use App\Integration\Payments\Contract\ConfirmVoucherSale;
use App\Integration\Payments\Contract\IssuedVoucher;
use App\Integration\Payments\Contract\IssuedVoucherLine;
use App\Integration\Payments\Contract\PaymentNextStep;
use App\Integration\Payments\Contract\ProviderCredentials;
use App\Integration\Payments\Contract\ProviderCredentialsInterface;
use App\Integration\Payments\Contract\ProviderUnavailableException;
use App\Integration\Payments\Contract\StartVoucherSale;
use App\Integration\Payments\Contract\VoucherCancelled;
use App\Integration\Payments\Contract\VoucherLine;
use App\Integration\Payments\Contract\VoucherProviderInterface;
use App\Integration\Payments\Contract\VoucherSaleOutcome;
use App\Integration\Payments\Contract\VoucherSaleResult;
use App\Integration\Payments\Contract\VoucherSaleStarted;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Bono electrónico IMED (SOAP). Ver CLAUDE.md de esta carpeta.
 *
 * Construido a partir de cómo lo usa melisa_prod, NO del kit oficial: validar
 * nombres de campos, formatos y códigos de estado con IMED antes de activarlo.
 *
 * 1. startVoucherSale(): VtaBonInterMul → transacción y auditoría; la caja abre la ventana de IMED.
 * 2. En la ventana, IMED identifica al paciente y cobra el copago.
 * 3. confirmVoucherSale(): VerResulEmi (estado) y, si emitió, ObtBonInterMul (bonos). Sin efectos.
 * 4. cancel(): AnulBonInterfaz, un folio por llamada.
 */
#[AutoconfigureTag('payment.voucher_provider')]
final readonly class ImedGateway implements VoucherProviderInterface
{
    public const string NAME = 'imed';

    /** CodError de IMED: 0 = sin error, 1 = error con glosa. */
    private const int NO_ERROR = 0;

    /** CodEstado de VerResulEmi (según melisa_prod; validar con el manual). 1 y 3 = en proceso o con advertencia. */
    private const int ISSUED_STATE = 2;
    private const array REJECTED_STATES = [0, 4];

    /** Forma de pago "excedente" en ListaForPag. */
    private const int SURPLUS_PAYMENT_FORM = 6;

    private const string REFERENCE_SEPARATOR = '|';

    public function __construct(
        private ProviderCredentialsInterface $credentials,
        private ImedSoapClientFactoryInterface $soapClients,
    ) {
    }

    public function name(): string
    {
        return self::NAME;
    }

    public function startVoucherSale(StartVoucherSale $request): VoucherSaleStarted
    {
        $credentials = $this->credentials->for(self::NAME);

        $answer = $this->client($credentials)->call('VtaBonInterMul', [
            ...$this->login($credentials),
            'CodFinanciador' => $this->code($request->insurerCode),
            'CodLugar' => $this->code($credentials->get('place_code')),
            'UrlRetExito' => $request->returnUrl,
            'UrlRetError' => $request->returnUrl,
            'ListConvenios' => ['ListConveniosType' => [
                'RutConvenio' => ImedRun::format($credentials->get('agreement_run')),
                'CorrConvenio' => 0,
                'RutTratante' => ImedRun::formatOrEmpty($request->professionalRun),
                'RutSolic' => ImedRun::formatOrEmpty($request->requesterRun),
                'NomSolic' => $request->requesterName ?? '',
                'CodEspecia' => $request->specialtyCode,
                'RutBenef' => ImedRun::format($request->patientRun),
                'RutCajero' => ImedRun::format($request->cashierRun),
                'IndUrgencia' => $request->emergency ? 'S' : 'N',
                'CodTipoTratamiento' => 1,
                'FecIniTratamiento' => '19000101',
                'FecTerTratamiento' => '19000101',
                'CantDias' => 0,
                'FolioAntecedente' => 0,
                'LisPrestAut' => ['LisPrestAutMulType' => array_map($this->lineParams(...), $request->lines)],
            ]],
        ]);

        if (self::NO_ERROR !== $this->int($answer['CodError'] ?? self::NO_ERROR)) {
            return VoucherSaleStarted::rejected($this->text($answer['GloError'] ?? null, 'IMED rechazó la venta.'));
        }

        $transaction = $this->transactionNumber($answer['NumTransac'] ?? null);
        $audit = $this->text($answer['CodAuditoria'] ?? null, '');
        if ('' === $transaction || '' === $audit) {
            throw ProviderUnavailableException::because(self::NAME, 'sale started without transaction or audit number');
        }

        return new VoucherSaleStarted(
            $transaction.self::REFERENCE_SEPARATOR.$audit,
            PaymentNextStep::Redirect,
            $credentials->get('window_url'),
            ['NroAuditoria' => $audit, 'NumTransac' => $transaction],
        );
    }

    public function confirmVoucherSale(ConfirmVoucherSale $request): VoucherSaleOutcome
    {
        [$transaction, $audit] = $this->parseReference($request->providerReference);
        $credentials = $this->credentials->for(self::NAME);
        $client = $this->client($credentials);
        $params = [...$this->login($credentials), 'NumTransac' => $transaction, 'NroAuditoria' => $audit];

        $status = $client->call('VerResulEmi', $params);
        $state = $this->int($status['CodEstado'] ?? null);
        $audited = ['num_transac' => $transaction, 'cod_estado' => $state, 'glo_estado' => $this->nullableText($status['GloEstado'] ?? null)];

        if (\in_array($state, self::REJECTED_STATES, true)) {
            $reason = $this->text($status['GloError'] ?? null, $this->text($status['GloEstado'] ?? null, 'IMED no emitió el bono.'));

            return new VoucherSaleOutcome(VoucherSaleResult::Rejected, $request->providerReference, [], new Clp(0), $reason, $audited);
        }

        if (self::ISSUED_STATE !== $state) {
            // En proceso (1), con advertencia (3) o desconocido: nunca se da por emitido sin ver los bonos.
            return new VoucherSaleOutcome(VoucherSaleResult::Pending, $request->providerReference, [], new Clp(0), null, $audited);
        }

        $sale = $client->call('ObtBonInterMul', $params);
        if (self::NO_ERROR !== $this->int($sale['CodError'] ?? null)) {
            throw ProviderUnavailableException::because(self::NAME, 'issued sale could not be read');
        }

        $vouchers = $this->vouchers($sale);
        if ([] === $vouchers) {
            throw ProviderUnavailableException::because(self::NAME, 'issued sale has no vouchers');
        }

        $audited['folios'] = implode(',', array_map(static fn (IssuedVoucher $v): string => $v->folio, $vouchers));

        return new VoucherSaleOutcome(VoucherSaleResult::Issued, $request->providerReference, $vouchers, $this->surplus($sale), null, $audited);
    }

    public function cancel(CancelVoucher $request): VoucherCancelled
    {
        $credentials = $this->credentials->for(self::NAME);

        $answer = $this->client($credentials)->call('AnulBonInterfaz', [
            ...$this->login($credentials),
            'CodFinanciador' => $this->code($request->insurerCode),
            'CodLugar' => $this->code($credentials->get('place_code')),
            'FolioBono' => $request->folio,
            'RutCajero' => ImedRun::format($request->cashierRun),
        ]);

        // Sin CodError no hay certeza: el bono puede seguir vigente. Nunca se da por anulado.
        if (!\array_key_exists('CodError', $answer)) {
            throw ProviderUnavailableException::because(self::NAME, 'cancellation answer without error code');
        }

        $error = $this->int($answer['CodError']);
        $audited = ['cod_error' => $error, 'glo_error' => $this->nullableText($answer['GloError'] ?? null)];

        if (self::NO_ERROR !== $error) {
            return new VoucherCancelled(false, $request->folio, null, $this->text($answer['GloError'] ?? null, 'IMED no anuló el bono.'), $audited);
        }

        return new VoucherCancelled(true, $request->folio, new \DateTimeImmutable('now', new \DateTimeZone('UTC')), null, $audited);
    }

    /**
     * @param array<string, mixed> $sale
     *
     * @return list<IssuedVoucher>
     */
    private function vouchers(array $sale): array
    {
        $bonds = $this->path($sale, 'LisVenConv', 'LisVenConvType', 'ListaBonosMul', 'ListaBonosMulType');
        if (!\is_array($bonds)) {
            return [];
        }

        $vouchers = [];
        foreach ($this->listOf($bonds) as $bond) {
            $lines = $this->path($bond, 'LisPrestVta', 'LisPrestVtaType');
            $vouchers[] = new IssuedVoucher(
                $this->text($bond['FolioBono'] ?? null, ''),
                new Clp($this->int($bond['MontoTotal'] ?? null)),
                array_map($this->issuedLine(...), \is_array($lines) ? $this->listOf($lines) : []),
                $this->date($bond['FecEmi'] ?? null),
            );
        }

        return $vouchers;
    }

    /** @param array<mixed> $line */
    private function issuedLine(array $line): IssuedVoucherLine
    {
        $insurance = 0;
        foreach ($this->nodesWith($line['ListaOtrasBon'] ?? [], 'MtoBonAdic') as $extra) {
            $insurance += $this->int($extra['MtoBonAdic']);
        }

        $ges = 'S' === strtoupper($this->text($line['EsGes'] ?? null, 'N'));

        return new IssuedVoucherLine(
            $this->text($line['CodPrestacion'] ?? null, ''),
            $this->text($line['CodItem'] ?? null, '0'),
            max(1, $this->int($line['Cantidad'] ?? 1)),
            new Clp($this->int($line['MontoPrest'] ?? null)),
            new Clp($this->int($line['MontoBon'] ?? null)),
            new Clp($this->int($line['MontoCopago'] ?? null)),
            new Clp($insurance),
            $ges,
            $ges ? $this->nullableText($line['CodPatologia'] ?? null) : null,
        );
    }

    /**
     * Excedente: suma de las formas de pago 6.
     *
     * @param array<string, mixed> $sale
     */
    private function surplus(array $sale): Clp
    {
        $total = 0;
        foreach ($this->nodesWith($this->path($sale, 'LisVenConv', 'LisVenConvType', 'ListaForPag'), 'CodForPag') as $form) {
            if (self::SURPLUS_PAYMENT_FORM === $this->int($form['CodForPag'])) {
                $total += $this->int($form['MtoTransac'] ?? null);
            }
        }

        return new Clp($total);
    }

    /** @return array<string, mixed> */
    private function lineParams(VoucherLine $line): array
    {
        return [
            'CodPrestacion' => $line->benefitCode,
            'CodItem' => $line->itemCode,
            'Cantidad' => $line->quantity,
            'RecargoHora' => 'N',
            'MontoPrest' => $line->amount->value,
            'InfAdicional' => '0',
        ];
    }

    /** @return array{CodUsuario: string, CodClave: string} */
    private function login(ProviderCredentials $credentials): array
    {
        return ['CodUsuario' => $credentials->get('username'), 'CodClave' => $credentials->get('password')];
    }

    private function client(ProviderCredentials $credentials): ImedSoapClientInterface
    {
        return $this->soapClients->create($credentials->get('wsdl_url'));
    }

    /** @return array{string, string} */
    private function parseReference(string $reference): array
    {
        $parts = explode(self::REFERENCE_SEPARATOR, $reference);
        if (2 !== \count($parts) || '' === $parts[0] || '' === $parts[1]) {
            throw new \InvalidArgumentException('Invalid IMED reference.');
        }

        return [$parts[0], $parts[1]];
    }

    /** IMED puede devolver una o varias transacciones. */
    private function transactionNumber(mixed $value): string
    {
        if (\is_array($value)) {
            return implode(',', array_map(fn (mixed $v): string => $this->text($v, ''), $value));
        }

        return $this->text($value, '');
    }

    /**
     * SOAP entrega un solo elemento como objeto y varios como lista: siempre lista.
     *
     * @param array<mixed> $node
     *
     * @return list<array<mixed>>
     */
    private function listOf(array $node): array
    {
        if ([] === $node) {
            return [];
        }
        if (!array_is_list($node)) {
            return [$node];
        }

        return array_values(array_filter($node, 'is_array'));
    }

    /**
     * Todos los sub-arreglos que tienen la clave buscada, a cualquier profundidad
     * (IMED anida ListaOtrasBon y ListaForPag de formas distintas).
     *
     * @return list<array<mixed>>
     */
    private function nodesWith(mixed $node, string $key): array
    {
        if (!\is_array($node)) {
            return [];
        }
        if (\array_key_exists($key, $node)) {
            return [$node];
        }

        $found = [];
        foreach ($node as $child) {
            array_push($found, ...$this->nodesWith($child, $key));
        }

        return $found;
    }

    /** @param array<mixed> $data */
    private function path(array $data, string ...$keys): mixed
    {
        $node = $data;
        foreach ($keys as $key) {
            if (!\is_array($node) || !\array_key_exists($key, $node)) {
                return null;
            }
            $node = $node[$key];
        }

        return $node;
    }

    private function code(string $value): int|string
    {
        return ctype_digit($value) ? (int) $value : $value;
    }

    private function int(mixed $value): int
    {
        if (\is_int($value)) {
            return $value;
        }
        if (\is_string($value) && 1 === preg_match('/^-?\d+$/', trim($value))) {
            return (int) trim($value);
        }
        if (\is_float($value) && floor($value) === $value) {
            return (int) $value;
        }

        throw new \UnexpectedValueException('IMED returned a missing or non-integer number.');
    }

    private function text(mixed $value, string $default): string
    {
        $text = \is_scalar($value) ? trim((string) $value) : '';

        return '' === $text ? $default : $text;
    }

    private function nullableText(mixed $value): ?string
    {
        $text = $this->text($value, '');

        return '' === $text ? null : $text;
    }

    /** FecEmi llega como AAAAMMDD (validar con el manual). */
    private function date(mixed $value): ?\DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat('!Ymd', $this->text($value, ''), new \DateTimeZone('America/Santiago'));

        return false === $date ? null : $date->setTimezone(new \DateTimeZone('UTC'));
    }
}
