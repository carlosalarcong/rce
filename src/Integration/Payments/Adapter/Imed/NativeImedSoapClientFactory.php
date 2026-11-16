<?php

declare(strict_types=1);

namespace App\Integration\Payments\Adapter\Imed;

use App\Integration\Payments\Contract\ProviderUnavailableException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Cliente SOAP nativo de PHP (extensión soap), con timeout corto. Todo fault o
 * error de red se convierte en ProviderUnavailableException.
 */
#[AsAlias(ImedSoapClientFactoryInterface::class)]
final readonly class NativeImedSoapClientFactory implements ImedSoapClientFactoryInterface
{
    public function __construct(
        #[Autowire('%env(int:IMED_TIMEOUT_SECONDS)%')]
        private int $timeoutSeconds = 10,
    ) {
        if ($timeoutSeconds < 1) {
            throw new \InvalidArgumentException('IMED timeout must be at least 1 second.');
        }
    }

    public function create(string $wsdlUrl): ImedSoapClientInterface
    {
        $timeout = $this->timeoutSeconds;

        return new class($wsdlUrl, $timeout) implements ImedSoapClientInterface {
            private ?\SoapClient $client = null;

            public function __construct(private readonly string $wsdlUrl, private readonly int $timeout)
            {
            }

            public function call(string $operation, array $params): array
            {
                try {
                    $answer = $this->client()->__call($operation, $params);
                } catch (\SoapFault $e) {
                    throw ProviderUnavailableException::because(ImedGateway::NAME, \sprintf('SOAP fault on %s', $operation), $e);
                }

                $normalized = json_decode((string) json_encode($answer), true);
                if (!\is_array($normalized)) {
                    throw ProviderUnavailableException::because(ImedGateway::NAME, \sprintf('unexpected answer on %s', $operation));
                }

                $result = [];
                foreach ($normalized as $key => $value) {
                    $result[(string) $key] = $value;
                }

                return $result;
            }

            private function client(): \SoapClient
            {
                if (null !== $this->client) {
                    return $this->client;
                }

                // SoapClient emite un warning de libxml además del SoapFault: el fault basta.
                set_error_handler(static fn (): bool => true, \E_WARNING);
                try {
                    return $this->client = new \SoapClient($this->wsdlUrl, [
                        'exceptions' => true,
                        'connection_timeout' => $this->timeout,
                        'cache_wsdl' => \WSDL_CACHE_MEMORY,
                        'stream_context' => stream_context_create(['http' => ['timeout' => $this->timeout]]),
                    ]);
                } catch (\SoapFault $e) {
                    throw ProviderUnavailableException::because(ImedGateway::NAME, 'WSDL not reachable', $e);
                } finally {
                    restore_error_handler();
                }
            }
        };
    }
}
