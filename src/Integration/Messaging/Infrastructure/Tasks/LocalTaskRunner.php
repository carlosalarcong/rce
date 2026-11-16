<?php

declare(strict_types=1);

namespace App\Integration\Messaging\Infrastructure\Tasks;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Hace en local lo que en GCP hacen Cloud Tasks y Cloud Scheduler:
 * - entrega por HTTP los avisos que ya tocan (POST /_tasks/outbox/{tenant}/{id});
 * - si la respuesta no es 2xx, lo reintenta en 10 s (como el reintento de transporte de Cloud Tasks);
 * - cada minuto llama a la barrida (POST /_tasks/sweep).
 */
final class LocalTaskRunner
{
    private const int TRANSPORT_RETRY_SECONDS = 10;
    private const int SWEEP_EVERY_SECONDS = 60;

    private ?\DateTimeImmutable $lastSweep = null;

    /** @var \Closure(string, string): int */
    private \Closure $post;

    /**
     * @param (\Closure(string $url, string $authorization): int)|null $post devuelve el código HTTP (0 si no hubo respuesta); para tests
     */
    public function __construct(
        private readonly LocalTaskQueue $queue,
        private readonly LoggerInterface $logger,
        #[Autowire('%env(TASKS_BASE_URL)%')]
        private readonly string $baseUrl,
        #[Autowire('%env(TASKS_LOCAL_TOKEN)%')]
        #[\SensitiveParameter]
        private readonly string $token,
        ?\Closure $post = null,
    ) {
        $this->post = $post ?? self::httpPost(...);
    }

    /** @return array{delivered: int, failed: int, swept: bool} */
    public function tick(\DateTimeImmutable $now): array
    {
        $delivered = 0;
        $failed = 0;

        foreach ($this->queue->takeDue($now) as [$tenant, $messageId]) {
            $status = $this->call(\sprintf('/_tasks/outbox/%s/%s', rawurlencode($tenant), rawurlencode($messageId)));
            if ($status >= 200 && $status < 300) {
                ++$delivered;
                continue;
            }
            ++$failed;
            $this->logger->warning('Local task delivery failed; retrying.', ['tenant' => $tenant, 'message_id' => $messageId, 'status' => $status]);
            $this->queue->schedule($tenant, $messageId, $now->modify(\sprintf('+%d seconds', self::TRANSPORT_RETRY_SECONDS)));
        }

        $swept = false;
        if (null === $this->lastSweep || $now->getTimestamp() - $this->lastSweep->getTimestamp() >= self::SWEEP_EVERY_SECONDS) {
            $this->lastSweep = $now;
            $swept = true;
            $status = $this->call('/_tasks/sweep');
            if ($status < 200 || $status >= 300) {
                $this->logger->warning('Local sweep call failed.', ['status' => $status]);
            }
        }

        return ['delivered' => $delivered, 'failed' => $failed, 'swept' => $swept];
    }

    private function call(string $path): int
    {
        return ($this->post)(rtrim($this->baseUrl, '/').$path, 'Bearer '.$this->token);
    }

    private static function httpPost(string $url, string $authorization): int
    {
        $context = stream_context_create(['http' => [
            'method' => 'POST',
            'header' => "Authorization: {$authorization}\r\nContent-Length: 0\r\n",
            'timeout' => 30,
            'ignore_errors' => true,
        ]]);

        $body = @file_get_contents($url, false, $context);
        $headers = http_get_last_response_headers() ?? [];
        if (false === $body || [] === $headers || 1 !== preg_match('#^HTTP/\S+\s+(\d{3})#', $headers[0], $match)) {
            return 0;
        }

        return (int) $match[1];
    }
}
