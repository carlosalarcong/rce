<?php

declare(strict_types=1);

namespace App\Tests\Functional\Integration\Messaging;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/** /_tasks solo acepta llamadas firmadas por la cola. */
final class TaskEndpointTest extends WebTestCase
{
    /** @return iterable<string, array{string, array<string, string>}> */
    public static function untrusted(): iterable
    {
        $outbox = '/_tasks/outbox/clinica-a/0b8f6c2e-1f5a-4c3e-9d7a-2b1c3d4e5f60';

        yield 'outbox without token' => [$outbox, []];
        yield 'outbox with wrong token' => [$outbox, ['HTTP_AUTHORIZATION' => 'Bearer adivinado']];
        yield 'sweep without token' => ['/_tasks/sweep', []];
    }

    /** @param array<string, string> $server */
    #[DataProvider('untrusted')]
    public function testRejectsCallsNotSignedByTheQueue(string $path, array $server): void
    {
        $client = self::createClient();
        $client->request('POST', $path, [], [], $server);

        self::assertResponseStatusCodeSame(401);
    }

    public function testOnlyPostIsAllowed(): void
    {
        $client = self::createClient();
        $client->request('GET', '/_tasks/sweep');

        self::assertResponseStatusCodeSame(405);
    }

    public function testInvalidIdsDoNotReachTheHandler(): void
    {
        $client = self::createClient();
        $client->request('POST', '/_tasks/outbox/../etc/passwd', [], [], ['HTTP_AUTHORIZATION' => 'Bearer x']);

        self::assertResponseStatusCodeSame(404);
    }
}
