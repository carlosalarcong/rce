<?php

declare(strict_types=1);

namespace App\Integration\Messaging\Infrastructure\Tasks;

use App\Integration\Messaging\Application\TaskQueueInterface;
use Google\Cloud\Tasks\V2\Client\CloudTasksClient;
use Google\Cloud\Tasks\V2\CreateTaskRequest;
use Google\Cloud\Tasks\V2\HttpMethod;
use Google\Cloud\Tasks\V2\HttpRequest;
use Google\Cloud\Tasks\V2\OidcToken;
use Google\Cloud\Tasks\V2\Task;
use Google\Protobuf\Timestamp;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * GCP: crea una tarea HTTP en Cloud Tasks que llama a
 * {TASKS_BASE_URL}/_tasks/outbox/{tenant}/{id} a la hora indicada, firmada con
 * un token OIDC de la cuenta de servicio. Transporte REST: no requiere la extensión grpc.
 */
final class CloudTasksQueue implements TaskQueueInterface
{
    private ?CloudTasksClient $client = null;

    public function __construct(
        #[Autowire('%env(CLOUD_TASKS_PROJECT)%')]
        private readonly string $project,
        #[Autowire('%env(CLOUD_TASKS_LOCATION)%')]
        private readonly string $location,
        #[Autowire('%env(CLOUD_TASKS_QUEUE)%')]
        private readonly string $queue,
        #[Autowire('%env(CLOUD_TASKS_SERVICE_ACCOUNT)%')]
        private readonly string $serviceAccount,
        #[Autowire('%env(TASKS_BASE_URL)%')]
        private readonly string $baseUrl,
        #[Autowire('%env(TASKS_AUDIENCE)%')]
        private readonly string $audience,
    ) {
    }

    public function schedule(string $tenant, string $messageId, \DateTimeImmutable $at): void
    {
        $this->client()->createTask($this->buildRequest($tenant, $messageId, $at));
    }

    public function buildRequest(string $tenant, string $messageId, \DateTimeImmutable $at): CreateTaskRequest
    {
        $http = new HttpRequest()
            ->setUrl(\sprintf('%s/_tasks/outbox/%s/%s', rtrim($this->baseUrl, '/'), rawurlencode($tenant), rawurlencode($messageId)))
            ->setHttpMethod(HttpMethod::POST)
            ->setOidcToken(new OidcToken()->setServiceAccountEmail($this->serviceAccount)->setAudience($this->audience));

        $task = new Task()
            ->setHttpRequest($http)
            ->setScheduleTime(new Timestamp()->setSeconds($at->getTimestamp()));

        return new CreateTaskRequest()
            ->setParent(CloudTasksClient::queueName($this->project, $this->location, $this->queue))
            ->setTask($task);
    }

    private function client(): CloudTasksClient
    {
        return $this->client ??= new CloudTasksClient(['transport' => 'rest']);
    }
}
