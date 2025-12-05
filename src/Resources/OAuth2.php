<?php

namespace LaraMailer\Sdk\Resources;

use LaraMailer\Sdk\Client;

class OAuth2
{
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function list(): array
    {
        return $this->client->request('GET', 'oauth2-configs');
    }

    public function create(array $data): array
    {
        return $this->client->request('POST', 'oauth2-config', [
            'json' => $data,
        ]);
    }

    public function initiate(int $configId): array
    {
        return $this->client->request('GET', "oauth2-initiate/{$configId}");
    }
}
