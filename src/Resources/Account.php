<?php

namespace LaraMailer\Sdk\Resources;

use LaraMailer\Sdk\Client;

class Account
{
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function list(): array
    {
        return $this->client->request('GET', 'accounts');
    }

    public function create(array $data): array
    {
        return $this->client->request('POST', 'account', [
            'json' => $data,
        ]);
    }

    public function get(string|int $id): array
    {
        return $this->client->request('GET', "account/{$id}");
    }

    public function update(string|int $id, array $data): array
    {
        return $this->client->request('PUT', "account/{$id}", [
            'json' => $data,
        ]);
    }

    public function delete(string|int $id): array
    {
        return $this->client->request('DELETE', "account/{$id}");
    }
}
