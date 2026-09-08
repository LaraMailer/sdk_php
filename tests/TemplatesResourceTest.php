<?php

namespace LaraMailer\Sdk\Tests;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use LaraMailer\Sdk\Client;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class TemplatesResourceTest extends TestCase
{
    /** @var array<int, RequestInterface> */
    private array $requests = [];

    private function client(array $responses): Client
    {
        $queue = array_map(fn ($response) => function (RequestInterface $request) use ($response) {
            $this->requests[] = $request;

            return $response;
        }, $responses);

        return new Client('tok', 'https://mail.example.test', 'v1', 15, new MockHandler($queue));
    }

    public function test_list_get_and_preview(): void
    {
        $client = $this->client([
            new Response(200, [], json_encode(['success' => true, 'data' => [['id' => 1, 'name' => 'order']]])),
            new Response(200, [], json_encode(['success' => true, 'data' => ['id' => 1]])),
            new Response(200, [], json_encode(['success' => true, 'data' => ['subject' => 'Order PO-1']])),
        ]);

        $this->assertSame('order', $client->templates()->list()['data'][0]['name']);
        $this->assertSame(1, $client->templates()->get(1)['data']['id']);
        $this->assertSame('Order PO-1', $client->templates()->preview(1, ['ref' => 'PO-1'], true)['data']['subject']);

        $this->assertSame('/api/v1/templates', $this->requests[0]->getUri()->getPath());
        $this->assertSame('/api/v1/templates/1', $this->requests[1]->getUri()->getPath());
        $this->assertSame('/api/v1/templates/1/preview', $this->requests[2]->getUri()->getPath());
        $this->assertSame(['variables' => ['ref' => 'PO-1'], 'draft' => true], json_decode((string) $this->requests[2]->getBody(), true));
    }

    public function test_send_template_builds_payload(): void
    {
        $client = $this->client([new Response(202, [], json_encode(['success' => true, 'data' => ['id' => 5]]))]);

        $client->mail()->sendTemplate(3, 'quotation-request', ['ref' => 'CP/1'], ['to' => [['email' => 'a@b.pt']], 'metadata' => ['contract_id' => 1]], 'k-1');

        $body = json_decode((string) $this->requests[0]->getBody(), true);
        $this->assertSame('quotation-request', $body['template']);
        $this->assertSame(['ref' => 'CP/1'], $body['variables']);
        $this->assertSame([['email' => 'a@b.pt']], $body['to']);
        $this->assertSame(['contract_id' => 1], $body['metadata']);
        $this->assertSame('k-1', $this->requests[0]->getHeaderLine('Idempotency-Key'));
        $this->assertSame('/api/v1/send-mail/3', $this->requests[0]->getUri()->getPath());
    }
}
