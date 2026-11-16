<?php

declare(strict_types=1);

namespace App\Tests\Unit\Integration\Messaging;

use App\Integration\Messaging\Application\SweepOutbox;
use App\Integration\Messaging\Application\TaskQueueInterface;
use App\Integration\Messaging\Infrastructure\Outbox\PendingTaskNotifier;
use App\Integration\Messaging\Infrastructure\Tasks\CloudTasksQueue;
use App\Integration\Messaging\Infrastructure\Tasks\GoogleOidcTaskVerifier;
use App\Integration\Messaging\Infrastructure\Tasks\LocalTaskVerifier;
use Google\Cloud\Tasks\V2\HttpMethod;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Clock\MockClock;

final class SweepAndTasksTest extends TestCase
{
    public function testSweepReschedulesOverdueMessagesOfEveryTenant(): void
    {
        $tenant = new FakeActiveTenant();
        $store = new InMemoryOutboxStore($tenant);
        $store->overdue = ['clinica-a' => ['id-1', 'id-2'], 'clinica-b' => ['id-3']];
        $tasks = new FakeTaskQueue();

        $count = new SweepOutbox(new FakeTenantDirectory(['clinica-a', 'clinica-b']), $tenant, $store, $tasks, new MockClock(), new NullLogger())();

        self::assertSame(3, $count);
        self::assertSame([['clinica-a', 'id-1'], ['clinica-a', 'id-2'], ['clinica-b', 'id-3']], array_map(static fn (array $t): array => [$t[0], $t[1]], $tasks->scheduled));
    }

    public function testOneBrokenTenantDoesNotStopTheSweep(): void
    {
        $tenant = new FakeActiveTenant(null, ['clinica-a']);
        $store = new InMemoryOutboxStore($tenant);
        $store->overdue = ['clinica-b' => ['id-3']];
        $tasks = new FakeTaskQueue();

        self::assertSame(1, new SweepOutbox(new FakeTenantDirectory(['clinica-a', 'clinica-b']), $tenant, $store, $tasks, new MockClock(), new NullLogger())());
    }

    public function testNotifierSendsAfterTheRequestAndSurvivesQueueErrors(): void
    {
        $queue = new FakeTaskQueue();
        $notifier = new PendingTaskNotifier($queue, new MockClock(), new NullLogger());
        $notifier->add('clinica-a', 'id-1');
        $notifier->add('clinica-a', 'id-1');

        self::assertSame(0, $queue->count());
        $notifier->flush();
        self::assertSame(1, $queue->count());

        $broken = new class implements TaskQueueInterface {
            public function schedule(string $tenant, string $messageId, \DateTimeImmutable $at): void
            {
                throw new \RuntimeException('Cloud Tasks caído');
            }
        };
        $failing = new PendingTaskNotifier($broken, new MockClock(), new NullLogger());
        $failing->add('clinica-a', 'id-2');
        $failing->flush(); // no lanza: la barrida lo reenvía
        $this->addToAssertionCount(1);
    }

    public function testLocalVerifierAcceptsOnlyTheSharedToken(): void
    {
        $verifier = new LocalTaskVerifier('token-local-de-prueba-123');

        self::assertTrue($verifier->isTrusted('Bearer token-local-de-prueba-123'));
        self::assertFalse($verifier->isTrusted('Bearer otro'));
        self::assertFalse($verifier->isTrusted(null));

        $this->expectException(\InvalidArgumentException::class);
        new LocalTaskVerifier('corto');
    }

    public function testGoogleVerifierChecksSignatureAudienceIssuerAndServiceAccount(): void
    {
        $claims = ['iss' => 'https://accounts.google.com', 'email' => 'tasks@rce.iam.gserviceaccount.com', 'email_verified' => true];
        $seen = [];
        $verify = static function (string $token, array $options) use (&$seen, $claims): array|false {
            $seen = [$token, $options];

            return 'valid' === $token ? $claims : false;
        };
        $verifier = new GoogleOidcTaskVerifier('https://rce.example.cl', 'tasks@rce.iam.gserviceaccount.com', $verify);

        self::assertTrue($verifier->isTrusted('Bearer valid'));
        self::assertSame(['valid', ['audience' => 'https://rce.example.cl']], $seen);
        self::assertFalse($verifier->isTrusted('Bearer forged'));
        self::assertFalse($verifier->isTrusted('Basic abc'));

        $otherAccount = new GoogleOidcTaskVerifier('https://rce.example.cl', 'tasks@rce.iam.gserviceaccount.com', static fn (): array => [...$claims, 'email' => 'intruso@gmail.com']);
        self::assertFalse($otherAccount->isTrusted('Bearer valid'));

        $notConfigured = new GoogleOidcTaskVerifier('', '', $verify);
        self::assertFalse($notConfigured->isTrusted('Bearer valid'));
    }

    public function testCloudTaskTargetsTheOutboxEndpointSignedWithOidc(): void
    {
        $queue = new CloudTasksQueue('rce-prod', 'southamerica-west1', 'outbox', 'tasks@rce.iam.gserviceaccount.com', 'https://rce.example.cl/', 'https://rce.example.cl');
        $at = new \DateTimeImmutable('2026-11-16 12:05:00 UTC');

        $request = $queue->buildRequest('clinica-a', '0b8f6c2e-1f5a-4c3e-9d7a-2b1c3d4e5f60', $at);

        self::assertSame('projects/rce-prod/locations/southamerica-west1/queues/outbox', $request->getParent());
        $task = $request->getTask();
        self::assertNotNull($task);
        $http = $task->getHttpRequest();
        self::assertNotNull($http);
        self::assertSame('https://rce.example.cl/_tasks/outbox/clinica-a/0b8f6c2e-1f5a-4c3e-9d7a-2b1c3d4e5f60', $http->getUrl());
        self::assertSame(HttpMethod::POST, $http->getHttpMethod());
        $oidc = $http->getOidcToken();
        self::assertNotNull($oidc);
        self::assertSame('tasks@rce.iam.gserviceaccount.com', $oidc->getServiceAccountEmail());
        self::assertSame('https://rce.example.cl', $oidc->getAudience());
        self::assertSame($at->getTimestamp(), (int) $task->getScheduleTime()?->getSeconds());
    }
}
