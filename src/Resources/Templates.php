<?php

namespace LaraMailer\Sdk\Resources;

use LaraMailer\Sdk\Client;

class Templates
{
    public function __construct(protected Client $client) {}

    public function list(): array
    {
        return $this->client->request('GET', 'templates');
    }

    public function get(int $templateId): array
    {
        return $this->client->request('GET', "templates/{$templateId}");
    }

    /** @param array<string, mixed> $variables */
    public function preview(int $templateId, array $variables, bool $draft = false): array
    {
        return $this->client->request('POST', "templates/{$templateId}/preview", [
            'json' => ['variables' => $variables, 'draft' => $draft],
        ]);
    }
}
