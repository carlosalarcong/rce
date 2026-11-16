<?php

declare(strict_types=1);

namespace App\Tests\Unit\Integration\Payments\Adapter\Webpay;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Transbank\Contracts\HttpClientInterface;

/** Simula la API de Transbank: entrega las respuestas en orden y guarda lo que se le pidió. */
final class FakeTransbankHttpClient implements HttpClientInterface
{
    /** @var list<array{method: string, url: string, payload: array<mixed>|null, options: array<mixed>|null}> */
    public array $requests = [];

    /** @var list<ResponseInterface|\Throwable> */
    private array $queue = [];

    /** @param array<string, mixed> $body */
    public function respond(int $status, array $body = []): self
    {
        $this->queue[] = new Response($status, ['Content-Type' => 'application/json'], json_encode($body, \JSON_THROW_ON_ERROR));

        return $this;
    }

    public function fail(\Throwable $error): self
    {
        $this->queue[] = $error;

        return $this;
    }

    /**
     * @param array<mixed>|null $payload
     * @param array<mixed>|null $options
     */
    public function request(string $method, string $url, ?array $payload = [], ?array $options = null): ResponseInterface
    {
        $this->requests[] = ['method' => $method, 'url' => $url, 'payload' => $payload, 'options' => $options];
        $next = array_shift($this->queue) ?? throw new \LogicException('No fake response left.');
        if ($next instanceof \Throwable) {
            throw $next;
        }

        return $next;
    }
}
