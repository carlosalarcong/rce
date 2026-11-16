<?php

declare(strict_types=1);

namespace App\Tests\Unit\Integration\Payments\Adapter\Imed;

use App\Integration\Payments\Adapter\Imed\ImedGateway;
use App\Integration\Payments\Adapter\Imed\ImedRun;
use App\Integration\Payments\Contract\CancelVoucher;
use App\Integration\Payments\Contract\Clp;
use App\Integration\Payments\Contract\ConfirmVoucherSale;
use App\Integration\Payments\Contract\IdempotencyKey;
use App\Integration\Payments\Contract\PaymentNextStep;
use App\Integration\Payments\Contract\ProviderCredentials;
use App\Integration\Payments\Contract\ProviderCredentialsInterface;
use App\Integration\Payments\Contract\ProviderNotConfiguredException;
use App\Integration\Payments\Contract\ProviderUnavailableException;
use App\Integration\Payments\Contract\StartVoucherSale;
use App\Integration\Payments\Contract\VoucherLine;
use App\Integration\Payments\Contract\VoucherSaleResult;
use App\Integration\Payments\Infrastructure\Configuration\NoProviderCredentials;
use PHPUnit\Framework\TestCase;

/** Datos ficticios. Estructuras según melisa_prod; validar con el kit de IMED. */
final class ImedGatewayTest extends TestCase
{
    private const string REFERENCE = '900001|AUD-77';

    private FakeImedSoap $imed;

    protected function setUp(): void
    {
        $this->imed = new FakeImedSoap();
    }

    public function testStartSendsTheSaleAndOpensTheImedWindow(): void
    {
        $this->imed->on('VtaBonInterMul', ['CodError' => 0, 'GloError' => '', 'NumTransac' => '900001', 'CodAuditoria' => 'AUD-77']);

        $started = $this->gateway()->startVoucherSale($this->start());

        self::assertFalse($started->isRejected());
        self::assertSame(PaymentNextStep::Redirect, $started->nextStep);
        self::assertSame(self::REFERENCE, $started->providerReference);
        self::assertSame('https://imed.example.test/login.php', $started->redirectUrl);
        self::assertSame(['NroAuditoria' => 'AUD-77', 'NumTransac' => '900001'], $started->redirectFields);
        self::assertSame(['https://imed.example.test/WsInterfaz.php?wsdl'], $this->imed->wsdlUrls);

        $params = $this->imed->calls[0]['params'];
        self::assertSame('centro-demo', $params['CodUsuario']);
        self::assertSame(1, $params['CodFinanciador']);
        self::assertSame(1234, $params['CodLugar']);

        self::assertIsArray($params['ListConvenios']);
        self::assertIsArray($params['ListConvenios']['ListConveniosType']);
        $agreement = $params['ListConvenios']['ListConveniosType'];
        self::assertSame('0076000000-1', $agreement['RutConvenio']);
        self::assertSame('0011111111-1', $agreement['RutBenef']);
        self::assertSame('0022222222-2', $agreement['RutTratante']);
        self::assertSame('0000000000-0', $agreement['RutSolic']);
        self::assertSame('0033333333-3', $agreement['RutCajero']);
        self::assertSame('N', $agreement['IndUrgencia']);
        self::assertSame([
            ['CodPrestacion' => '0101001', 'CodItem' => '0', 'Cantidad' => 1, 'RecargoHora' => 'N', 'MontoPrest' => 25000, 'InfAdicional' => '0'],
            ['CodPrestacion' => '0305001', 'CodItem' => '0', 'Cantidad' => 2, 'RecargoHora' => 'N', 'MontoPrest' => 3500, 'InfAdicional' => '0'],
        ], \is_array($agreement['LisPrestAut']) ? $agreement['LisPrestAut']['LisPrestAutMulType'] : null);
    }

    public function testSaleRejectedByImedIsAResult(): void
    {
        $this->imed->on('VtaBonInterMul', ['CodError' => 1, 'GloError' => 'CodPrestacion no válido']);

        $started = $this->gateway()->startVoucherSale($this->start());

        self::assertTrue($started->isRejected());
        self::assertSame('CodPrestacion no válido', $started->rejectionReason);
        self::assertSame(PaymentNextStep::None, $started->nextStep);
    }

    public function testSeveralTransactionNumbersAreKept(): void
    {
        $this->imed->on('VtaBonInterMul', ['CodError' => 0, 'NumTransac' => ['900001', '900002'], 'CodAuditoria' => 'AUD-77']);

        self::assertSame('900001,900002|AUD-77', $this->gateway()->startVoucherSale($this->start())->providerReference);
    }

    public function testStartWithoutTransactionNumberIsUnavailable(): void
    {
        $this->imed->on('VtaBonInterMul', ['CodError' => 0, 'CodAuditoria' => 'AUD-77']);

        $this->expectException(ProviderUnavailableException::class);
        $this->gateway()->startVoucherSale($this->start());
    }

    public function testTimeoutIsUnavailable(): void
    {
        $this->imed->on('VtaBonInterMul', ProviderUnavailableException::because('imed', 'SOAP fault on VtaBonInterMul'));

        $this->expectException(ProviderUnavailableException::class);
        $this->gateway()->startVoucherSale($this->start());
    }

    public function testMissingConfigurationFailsBeforeCallingImed(): void
    {
        try {
            new ImedGateway(new NoProviderCredentials(), $this->imed)->startVoucherSale($this->start());
            self::fail('Expected an exception.');
        } catch (ProviderNotConfiguredException) {
            self::assertSame([], $this->imed->calls);
        }
    }

    public function testStillInProcessIsPendingAndDoesNotReadVouchers(): void
    {
        $this->imed->on('VerResulEmi', ['CodEstado' => 1, 'GloEstado' => 'En proceso']);

        $outcome = $this->gateway()->confirmVoucherSale($this->confirm());

        self::assertSame(VoucherSaleResult::Pending, $outcome->result);
        self::assertSame(['VerResulEmi'], $this->imed->operations());
    }

    public function testIssuedWithWarningStaysPendingUntilValidated(): void
    {
        $this->imed->on('VerResulEmi', ['CodEstado' => 3, 'GloEstado' => 'Emitido con observaciones']);

        self::assertSame(VoucherSaleResult::Pending, $this->gateway()->confirmVoucherSale($this->confirm())->result);
    }

    public function testErrorStateIsRejected(): void
    {
        $this->imed->on('VerResulEmi', ['CodEstado' => 4, 'GloEstado' => 'Error', 'GloError' => 'Beneficiario no vigente']);

        $outcome = $this->gateway()->confirmVoucherSale($this->confirm());

        self::assertSame(VoucherSaleResult::Rejected, $outcome->result);
        self::assertSame('Beneficiario no vigente', $outcome->reason);
        self::assertSame([], $outcome->vouchers);
    }

    public function testIssuedSingleVoucher(): void
    {
        $this->imed
            ->on('VerResulEmi', ['CodEstado' => 2, 'GloEstado' => 'Emitido'])
            ->on('ObtBonInterMul', $this->sale([
                $this->bond('5550001', 25000, $this->line('0101001', 25000, 17500, 7500)),
            ]));

        $outcome = $this->gateway()->confirmVoucherSale($this->confirm());

        self::assertSame(VoucherSaleResult::Issued, $outcome->result);
        self::assertSame(['5550001'], $outcome->folios());
        $voucher = $outcome->vouchers[0];
        self::assertSame(25000, $voucher->total->value);
        self::assertSame(17500, $voucher->covered()->value);
        self::assertSame(7500, $voucher->copay()->value);
        self::assertEquals(new \DateTimeImmutable('2026-11-16 03:00:00 UTC'), $voucher->issuedAt);
        self::assertSame(0, $outcome->surplus->value);
    }

    public function testIssuedSeveralVouchersWithInsuranceSurplusAndGes(): void
    {
        $this->imed
            ->on('VerResulEmi', ['CodEstado' => 2, 'GloEstado' => 'Emitido'])
            ->on('ObtBonInterMul', $this->sale(
                [
                    $this->bond('5550001', 25000, $this->line('0101001', 25000, 17500, 7500, insurance: 3000)),
                    $this->bond(
                        '5550002',
                        7000,
                        $this->line('0305001', 3500, 2000, 1500, ges: true),
                        $this->line('0305002', 3500, 2000, 1500),
                    ),
                ],
                ['ListaForPagType' => [['CodForPag' => 1, 'MtoTransac' => 4500], ['CodForPag' => 6, 'MtoTransac' => 2000]]],
            ));

        $outcome = $this->gateway()->confirmVoucherSale($this->confirm());

        self::assertSame(['5550001', '5550002'], $outcome->folios());
        self::assertSame(3000, $outcome->vouchers[0]->supplementaryInsurance()->value);
        self::assertSame(4000, $outcome->vouchers[1]->covered()->value);
        self::assertTrue($outcome->vouchers[1]->lines[0]->ges);
        self::assertSame('P07', $outcome->vouchers[1]->lines[0]->gesPathologyCode);
        self::assertFalse($outcome->vouchers[1]->lines[1]->ges);
        self::assertSame(2000, $outcome->surplus->value);
        self::assertSame('5550001,5550002', $outcome->providerResponse['folios']);
    }

    public function testRepeatedQueryGivesTheSameOutcomeWithoutSideEffects(): void
    {
        foreach ([1, 2] as $ignored) {
            $this->imed
                ->on('VerResulEmi', ['CodEstado' => 2])
                ->on('ObtBonInterMul', $this->sale([$this->bond('5550001', 25000, $this->line('0101001', 25000, 17500, 7500))]));
        }

        $gateway = $this->gateway();

        self::assertEquals($gateway->confirmVoucherSale($this->confirm()), $gateway->confirmVoucherSale($this->confirm()));
    }

    public function testIssuedButUnreadableIsUnavailableNotIssued(): void
    {
        $this->imed
            ->on('VerResulEmi', ['CodEstado' => 2])
            ->on('ObtBonInterMul', ['CodError' => 1, 'GloError' => 'Error interno']);

        $this->expectException(ProviderUnavailableException::class);
        $this->gateway()->confirmVoucherSale($this->confirm());
    }

    public function testPatientDataIsNotKeptForAudit(): void
    {
        $this->imed
            ->on('VerResulEmi', ['CodEstado' => 2])
            ->on('ObtBonInterMul', $this->sale([$this->bond('5550001', 25000, $this->line('0101001', 25000, 17500, 7500))]));

        $outcome = $this->gateway()->confirmVoucherSale($this->confirm());

        self::assertStringNotContainsString('11111111', (string) json_encode($outcome->providerResponse));
    }

    public function testInvalidReferenceIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->gateway()->confirmVoucherSale(new ConfirmVoucherSale($this->key(), 'sin-auditoria'));
    }

    public function testCancel(): void
    {
        $this->imed->on('AnulBonInterfaz', ['CodError' => 0, 'GloError' => '']);

        $cancelled = $this->gateway()->cancel($this->cancelRequest());

        self::assertTrue($cancelled->cancelled);
        self::assertNotNull($cancelled->cancelledAt);
        self::assertSame('5550001', $this->imed->calls[0]['params']['FolioBono']);
        self::assertSame('0033333333-3', $this->imed->calls[0]['params']['RutCajero']);
    }

    public function testCancellationRefusedByImedLeavesTheVoucherValid(): void
    {
        $this->imed->on('AnulBonInterfaz', ['CodError' => 1, 'GloError' => 'Bono ya cobrado']);

        $cancelled = $this->gateway()->cancel($this->cancelRequest());

        self::assertFalse($cancelled->cancelled);
        self::assertSame('Bono ya cobrado', $cancelled->reason);
    }

    public function testCancellationWithoutAnswerIsNeverTakenAsCancelled(): void
    {
        $this->imed->on('AnulBonInterfaz', []);

        $this->expectException(ProviderUnavailableException::class);
        $this->gateway()->cancel($this->cancelRequest());
    }

    public function testCancellationNetworkErrorIsNeverTakenAsCancelled(): void
    {
        $this->imed->on('AnulBonInterfaz', ProviderUnavailableException::because('imed', 'SOAP fault on AnulBonInterfaz'));

        $this->expectException(ProviderUnavailableException::class);
        $this->gateway()->cancel($this->cancelRequest());
    }

    public function testRunFormat(): void
    {
        self::assertSame('0012345678-K', ImedRun::format('12345678-K'));
        self::assertSame('000001234567-9', '00'.ImedRun::format('1234567-9'));
        self::assertSame('0000000000-0', ImedRun::formatOrEmpty(null));

        $this->expectException(\InvalidArgumentException::class);
        ImedRun::format('12.345.678-9');
    }

    private function gateway(): ImedGateway
    {
        $credentials = new class implements ProviderCredentialsInterface {
            public function for(string $provider): ProviderCredentials
            {
                return new ProviderCredentials(false, [
                    'username' => 'centro-demo',
                    'password' => 'clave-demo',
                    'wsdl_url' => 'https://imed.example.test/WsInterfaz.php?wsdl',
                    'window_url' => 'https://imed.example.test/login.php',
                    'place_code' => '1234',
                    'agreement_run' => '76000000-1',
                ]);
            }
        };

        return new ImedGateway($credentials, $this->imed);
    }

    private function key(): IdempotencyKey
    {
        return new IdempotencyKey('voucher-0001');
    }

    private function start(): StartVoucherSale
    {
        return new StartVoucherSale(
            $this->key(),
            '1',
            '11111111-1',
            '33333333-3',
            [new VoucherLine('0101001', 1, Clp::positive(25000)), new VoucherLine('0305001', 2, Clp::positive(3500))],
            'https://demo.rce.localhost/caja/imed/retorno',
            professionalRun: '22222222-2',
            specialtyCode: '01',
        );
    }

    private function confirm(): ConfirmVoucherSale
    {
        return new ConfirmVoucherSale($this->key(), self::REFERENCE);
    }

    private function cancelRequest(): CancelVoucher
    {
        return new CancelVoucher($this->key(), '5550001', 'Paciente no se atendió', '1', '33333333-3');
    }

    /**
     * @param list<array<string, mixed>> $bonds
     * @param array<string, mixed>       $paymentForms
     *
     * @return array<string, mixed>
     */
    private function sale(array $bonds, array $paymentForms = []): array
    {
        return [
            'CodError' => 0,
            'GloError' => '',
            'LisVenConv' => ['LisVenConvType' => [
                'CodFinanciador' => 1,
                'RutBenef' => '0011111111-1',
                // Un solo bono llega como objeto, no como lista.
                'ListaBonosMul' => ['ListaBonosMulType' => 1 === \count($bonds) ? $bonds[0] : $bonds],
                'ListaForPag' => $paymentForms,
            ]],
        ];
    }

    /**
     * @param array<string, mixed> ...$lines
     *
     * @return array<string, mixed>
     */
    private function bond(string $folio, int $total, array ...$lines): array
    {
        return [
            'FolioBono' => $folio,
            'FecEmi' => '20261116',
            'NumPrestBon' => \count($lines),
            'MontoTotal' => $total,
            'LisPrestVta' => ['LisPrestVtaType' => 1 === \count($lines) ? $lines[0] : $lines],
        ];
    }

    /** @return array<string, mixed> */
    private function line(string $code, int $amount, int $covered, int $copay, int $insurance = 0, bool $ges = false): array
    {
        return [
            'CodPrestacion' => $code,
            'CodItem' => '0',
            'Cantidad' => 1,
            'MontoPrest' => $amount,
            'MontoBon' => $covered,
            'MontoCopago' => $copay,
            'EsGes' => $ges ? 'S' : 'N',
            'CodPatologia' => $ges ? 'P07' : '',
            'ListaOtrasBon' => 0 === $insurance ? null : ['ListaOtrasBonType' => ['CodBonAdic' => 'SC1', 'GloBonAdic' => 'Seguro', 'MtoBonAdic' => $insurance]],
        ];
    }
}
