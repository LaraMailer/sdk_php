<?php

namespace LaraMailer\Sdk\Resources;

use LaraMailer\Sdk\Client;

class Mail
{
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function send(int $accountId, array $data): array
    {
        return $this->client->request('POST', "send-mail/{$accountId}", [
            'json' => $data,
        ]);
    }

    public function listTasks(): array
    {
        return $this->client->request('GET', 'send-email-tasks');
    }

    public function getTask(int $taskId): array
    {
        return $this->client->request('GET', "send-email-task/{$taskId}");
    }

    public function deleteTask(int $taskId): array
    {
        return $this->client->request('DELETE', "send-email-task/{$taskId}");
    }
}
