<?php

namespace LaraMailer\Sdk\Resources;

use LaraMailer\Sdk\Client;

class Mail
{
    public function __construct(protected Client $client) {}

    public function send(int $accountId, array $data, ?string $idempotencyKey = null): array
    {
        $options = ['json' => $data];

        if ($idempotencyKey !== null) {
            $options['headers'] = ['Idempotency-Key' => $idempotencyKey];
        }

        return $this->client->request('POST', "send-mail/{$accountId}", $options);
    }

    /**
     * @param array{status?: string, since?: string, metadata?: array<string, scalar>, per_page?: int, page?: int} $filters
     */
    public function listTasks(array $filters = []): array
    {
        return $this->client->request('GET', 'send-email-tasks', ['query' => $filters]);
    }

    public function getTask(int $taskId): array
    {
        return $this->client->request('GET', "send-email-task/{$taskId}");
    }

    public function deleteTask(int $taskId): array
    {
        return $this->client->request('DELETE', "send-email-task/{$taskId}");
    }

    public function downloadEml(int $taskId): string
    {
        return $this->client->requestRaw('GET', "send-email-task/{$taskId}/eml", [
            'headers' => ['Accept' => 'message/rfc822'],
        ]);
    }
}
