<?php

declare(strict_types=1);

namespace App\Tests\Unit\Integration\Payments\Adapter\Imed;

use App\Integration\Payments\Adapter\Imed\ImedSoapClientFactoryInterface;
use App\Integration\Payments\Adapter\Imed\ImedSoapClientInterface;

/** Simula el servicio SOAP de IMED: respuestas por operación, en orden, y registro de lo pedido. */
final class FakeImedSoap implements ImedSoapClientFactoryInterface, ImedSoapClientInterface
{
    /** @var list<array{operation: string, params: array<string, mixed>}> */
    public array $calls = [];

    /** @var list<string> */
    public array $wsdlUrls = [];

    /** @var array<string, list<array<string, mixed>|\Throwable>> */
    private array $answers = [];

    /** @param array<string, mixed>|\Throwable $answer */
    public function on(string $operation, array|\Throwable $answer): self
    {
        $this->answers[$operation][] = $answer;

        return $this;
    }

    public function create(string $wsdlUrl): ImedSoapClientInterface
    {
        $this->wsdlUrls[] = $wsdlUrl;

        return $this;
    }

    public function call(string $operation, array $params): array
    {
        $this->calls[] = ['operation' => $operation, 'params' => $params];
        $answer = array_shift($this->answers[$operation]) ?? throw new \LogicException(\sprintf('No fake answer for %s.', $operation));
        if ($answer instanceof \Throwable) {
            throw $answer;
        }

        return $answer;
    }

    /** @return list<string> */
    public function operations(): array
    {
        return array_column($this->calls, 'operation');
    }
}
