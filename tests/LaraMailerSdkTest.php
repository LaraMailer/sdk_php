<?php

namespace LaraMailer\Sdk\Tests;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use LaraMailer\Sdk\Client;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Psr\Http\Message\RequestInterface;

class LaraMailerSdkTest extends PHPUnitTestCase
{
    /** @var array<int, RequestInterface> */
    private array $requests = [];

    private function client(array $responses): Client
    {
        $queue = [];

        foreach ($responses as $response) {
            $queue[] = function (RequestInterface $request) use ($response) {
                $this->requests[] = $request;

                return $response;
            };
        }

        return new Client('tok', 'https://mail.example.test', 'v1', 15, new MockHandler($queue));
    }

    public function test_send_passes_idempotency_key_header(): void
    {
        $client = $this->client([new Response(202, [], json_encode(['success' => true, 'data' => ['id' => 9]]))]);

        $result = $client->mail()->send(3, ['subject' => 'S'], 'key-1');

        $this->assertSame(9, $result['data']['id']);
        $this->assertSame('key-1', $this->requests[0]->getHeaderLine('Idempotency-Key'));
        $this->assertSame('/api/v1/send-mail/3', $this->requests[0]->getUri()->getPath());
        $this->assertSame('Bearer tok', $this->requests[0]->getHeaderLine('Authorization'));
    }

    public function test_send_without_idempotency_key_sends_no_header(): void
    {
        $client = $this->client([new Response(202, [], json_encode(['success' => true, 'data' => ['id' => 3]]))]);

        $client->mail()->send(3, ['subject' => 'S']);

        $this->assertFalse($this->requests[0]->hasHeader('Idempotency-Key'));
    }

    public function test_list_tasks_builds_query_filters(): void
    {
        $client = $this->client([new Response(200, [], json_encode(['success' => true, 'data' => []]))]);

        $client->mail()->listTasks(['status' => 'completed', 'metadata' => ['contract_id' => 42], 'per_page' => 5]);

        parse_str($this->requests[0]->getUri()->getQuery(), $query);

        $this->assertSame(['status' => 'completed', 'metadata' => ['contract_id' => '42'], 'per_page' => '5'], $query);
    }

    public function test_download_eml_returns_raw_body(): void
    {
        $client = $this->client([new Response(200, ['Content-Type' => 'message/rfc822'], "Subject: Hi\r\n\r\nBody")]);

        $eml = $client->mail()->downloadEml(9);

        $this->assertSame("Subject: Hi\r\n\r\nBody", $eml);
        $this->assertSame('/api/v1/send-email-task/9/eml', $this->requests[0]->getUri()->getPath());
    }

    public function test_upload_contents_sends_multipart(): void
    {
        $client = $this->client([new Response(201, [], json_encode(['success' => true, 'path' => 'attachments/x.pdf']))]);

        $result = $client->attachments()->uploadContents('PDFDATA', 'x.pdf', 'application/pdf');

        $this->assertSame('attachments/x.pdf', $result['path']);
        $this->assertStringStartsWith('multipart/form-data', $this->requests[0]->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('filename="x.pdf"', (string) $this->requests[0]->getBody());
        $this->assertStringContainsString('PDFDATA', (string) $this->requests[0]->getBody());
    }

    public function test_upload_from_file_sends_detected_content_type(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'lm');
        file_put_contents($path, '%PDF-1.4');

        try {
            $client = $this->client([new Response(201, [], json_encode(['success' => true, 'path' => 'attachments/doc.pdf']))]);

            $result = $client->attachments()->upload($path, 'doc.pdf');

            $this->assertSame('attachments/doc.pdf', $result['path']);
            $this->assertStringContainsString('filename="doc.pdf"', (string) $this->requests[0]->getBody());

            if (function_exists('mime_content_type')) {
                $this->assertStringContainsString('Content-Type: application/pdf', (string) $this->requests[0]->getBody());
            }
        } finally {
            unlink($path);
        }
    }
}
