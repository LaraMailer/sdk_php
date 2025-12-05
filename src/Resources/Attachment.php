<?php

namespace LaraMailer\Sdk\Resources;

use LaraMailer\Sdk\Client;

class Attachment
{
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function upload(string $filePath, string $filename = null): array
    {
        $options = [
            'multipart' => [
                [
                    'name'     => 'file',
                    'contents' => fopen($filePath, 'r'),
                    'filename' => $filename ?? basename($filePath),
                ],
            ],
        ];

        return $this->client->request('POST', 'attachments', $options);
    }
}
