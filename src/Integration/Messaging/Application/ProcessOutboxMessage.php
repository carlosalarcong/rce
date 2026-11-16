<?php

declare(strict_types=1);

namespace App\Integration\Messaging\Application;

use App\Platform\Tenancy\Application\ActiveTenantInterface;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Exception\UnrecoverableExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

/**
 * Procesa un encargo de la bandeja cuando suena el "timbre" (Cloud Tasks o el
 * simulador): conecta la base de su tenant, lo toma de forma atómica, lo
 * entrega a su handler de Messenger y anota el resultado.
 *
 * Los reintentos los decide el RCE (OutboxRetryPolicy), no Cloud Tasks: un
 * fallo del handler se anota y se programa el siguiente aviso.
 */
final readonly class ProcessOutboxMessage
{
    public const string TRANSPORT = 'outbox';

    public function __construct(
        private ActiveTenantInterface $activeTenant,
        private OutboxStoreInterface $store,
        private MessageBusInterface $bus,
        private SerializerInterface $serializer,
        private TaskQueueInterface $tasks,
        private OutboxRetryPolicy $retryPolicy,
        private ClockInterface $clock,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(string $tenant, string $messageId): ProcessOutcome
    {
        $this->activeTenant->switchTo($tenant);
        $now = $this->clock->now();

        $record = $this->store->claim($messageId, $now);
        if (null === $record) {
            // Hecho, en manos de otro proceso, aún no le toca o no existe (la transacción que lo creó se deshizo).
            return ProcessOutcome::NothingToDo;
        }

        if ($record->tenant !== $tenant) {
            // Defensa: un encargo nunca se procesa en otra clínica.
            $this->store->markFailed($messageId, $now, 'TenantMismatch');
            $this->logger->critical('Outbox message tenant mismatch.', ['message_id' => $messageId, 'tenant' => $tenant]);

            return ProcessOutcome::Failed;
        }

        try {
            $envelope = $this->serializer->decode(['body' => $record->body, 'headers' => $record->headers]);
            $this->bus->dispatch($envelope->with(new ReceivedStamp(self::TRANSPORT)));
        } catch (\Throwable $error) {
            return $this->failed($record, $this->rootCause($error));
        }

        $this->store->markDone($messageId, $this->clock->now());

        return ProcessOutcome::Done;
    }

    private function failed(OutboxRecord $record, \Throwable $error): ProcessOutcome
    {
        $now = $this->clock->now();
        // Solo la clase: el mensaje del error podría traer datos del paciente.
        $errorClass = $error::class;
        $retryAt = $error instanceof UnrecoverableExceptionInterface ? null : $this->retryPolicy->nextAttemptAt($record->attempts, $now);

        if (null !== $retryAt) {
            $this->store->markRetry($record->id, $retryAt, $errorClass);
            $this->tasks->schedule($record->tenant, $record->id, $retryAt);

            return ProcessOutcome::RetryScheduled;
        }

        $this->store->markFailed($record->id, $now, $errorClass);
        $this->logger->critical('Outbox message failed for good.', [
            'message_id' => $record->id,
            'tenant' => $record->tenant,
            'message_class' => $record->messageClass,
            'attempts' => $record->attempts,
            'error_class' => $errorClass,
        ]);

        return ProcessOutcome::Failed;
    }

    private function rootCause(\Throwable $error): \Throwable
    {
        if ($error instanceof HandlerFailedException) {
            $wrapped = $error->getWrappedExceptions();
            $first = reset($wrapped);

            return false === $first ? $error : $first;
        }

        return $error;
    }
}
