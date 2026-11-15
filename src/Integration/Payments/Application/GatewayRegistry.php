<?php

declare(strict_types=1);

namespace App\Integration\Payments\Application;

use App\Integration\Payments\Contract\PaymentGatewayInterface;
use App\Integration\Payments\Contract\ProviderInterface;
use App\Integration\Payments\Contract\TaxDocumentIssuerInterface;
use App\Integration\Payments\Contract\VoucherProviderInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Entrega el adaptador por su nombre, solo si el tenant lo tiene activo.
 * Los adaptadores se registran solos con su tag (ver src/Integration/CLAUDE.md).
 */
final readonly class GatewayRegistry
{
    /** @var array<string, PaymentGatewayInterface> */
    private array $gateways;
    /** @var array<string, VoucherProviderInterface> */
    private array $voucherProviders;
    /** @var array<string, TaxDocumentIssuerInterface> */
    private array $taxIssuers;

    /**
     * @param iterable<PaymentGatewayInterface>    $gateways
     * @param iterable<VoucherProviderInterface>   $voucherProviders
     * @param iterable<TaxDocumentIssuerInterface> $taxIssuers
     */
    public function __construct(
        private ActiveProvidersInterface $activeProviders,
        #[AutowireIterator('payment.gateway')]
        iterable $gateways = [],
        #[AutowireIterator('payment.voucher_provider')]
        iterable $voucherProviders = [],
        #[AutowireIterator('payment.tax_issuer')]
        iterable $taxIssuers = [],
    ) {
        $this->gateways = self::indexByName($gateways);
        $this->voucherProviders = self::indexByName($voucherProviders);
        $this->taxIssuers = self::indexByName($taxIssuers);
    }

    public function paymentGateway(string $name): PaymentGatewayInterface
    {
        return $this->pick($this->gateways, $name);
    }

    public function voucherProvider(string $name): VoucherProviderInterface
    {
        return $this->pick($this->voucherProviders, $name);
    }

    public function taxDocumentIssuer(string $name): TaxDocumentIssuerInterface
    {
        return $this->pick($this->taxIssuers, $name);
    }

    /**
     * @template T of ProviderInterface
     *
     * @param array<string, T> $adapters
     *
     * @return T
     */
    private function pick(array $adapters, string $name): ProviderInterface
    {
        $adapter = $adapters[$name] ?? throw ProviderNotAvailableException::unknown($name);

        if (!$this->activeProviders->isActive($name)) {
            throw ProviderNotAvailableException::inactive($name);
        }

        return $adapter;
    }

    /**
     * @template T of ProviderInterface
     *
     * @param iterable<T> $adapters
     *
     * @return array<string, T>
     */
    private static function indexByName(iterable $adapters): array
    {
        $indexed = [];
        foreach ($adapters as $adapter) {
            $name = $adapter->name();
            if (1 !== preg_match('/^[a-z0-9_]+$/', $name)) {
                throw new \LogicException(\sprintf('Invalid provider name "%s" in %s.', $name, $adapter::class));
            }
            if (isset($indexed[$name])) {
                throw new \LogicException(\sprintf('Two adapters share the provider name "%s": %s and %s.', $name, $indexed[$name]::class, $adapter::class));
            }
            $indexed[$name] = $adapter;
        }

        return $indexed;
    }
}
