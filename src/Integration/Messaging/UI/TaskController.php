<?php

declare(strict_types=1);

namespace App\Integration\Messaging\UI;

use App\Integration\Messaging\Application\ProcessOutboxMessage;
use App\Integration\Messaging\Application\SweepOutbox;
use App\Integration\Messaging\Application\TaskRequestVerifierInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Entrada interna de la cola: la llaman Cloud Tasks / Cloud Scheduler (GCP) o
 * el simulador (local). Nunca un navegador. Sin sesión ni CSRF: se acepta solo
 * con token verificado. Responde 503 ante errores de infraestructura para que
 * la cola reintente; los fallos del handler los maneja ProcessOutboxMessage.
 */
#[AsController]
final readonly class TaskController
{
    public function __construct(
        private TaskRequestVerifierInterface $verifier,
        private LoggerInterface $logger,
    ) {
    }

    #[Route(
        '/_tasks/outbox/{tenant}/{id}',
        name: 'tasks_outbox',
        requirements: ['tenant' => '[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?', 'id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'],
        methods: ['POST'],
    )]
    public function outbox(Request $request, string $tenant, string $id, ProcessOutboxMessage $process): Response
    {
        if (!$this->verifier->isTrusted($request->headers->get('Authorization'))) {
            return new JsonResponse(['error' => 'unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            return new JsonResponse(['outcome' => $process($tenant, $id)->value]);
        } catch (\Throwable $error) {
            $this->logger->error('Outbox task could not be processed.', ['tenant' => $tenant, 'message_id' => $id, 'error_class' => $error::class]);

            return new JsonResponse(['error' => 'unavailable'], Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }

    #[Route('/_tasks/sweep', name: 'tasks_sweep', methods: ['POST'])]
    public function sweep(Request $request, SweepOutbox $sweep): Response
    {
        if (!$this->verifier->isTrusted($request->headers->get('Authorization'))) {
            return new JsonResponse(['error' => 'unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse(['rescheduled' => $sweep()]);
    }
}
