<?php

namespace LaraMailer\Sdk\Resources;

use LaraMailer\Sdk\Client;

class Attachment
{
    public function __construct(protected Client $client) {}

    public function upload(string $filePath, ?string $filename = null): array
    {
        return $this->uploadContents(
            contents: (string) file_get_contents($filePath),
            filename: $filename ?? basename($filePath),
            contentType: mime_content_type($filePath) ?: null,
        );
    }

    public function uploadContents(string $contents, string $filename, ?string $contentType = null): array
    {
        $part = [
            'name' => 'file',
            'contents' => $contents,
            'filename' => $filename,
        ];

        if ($contentType !== null) {
            $part['headers'] = ['Content-Type' => $contentType];
        }

        return $this->client->request('POST', 'attachments', ['multipart' => [$part]]);
    }
}
