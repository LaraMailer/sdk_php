<?php

namespace LaraMailer\Sdk\Tests;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use LaraMailer\Sdk\Client;
use LaraMailer\Sdk\LaraMailerTransport;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Header\MetadataHeader;
use Symfony\Component\Mailer\Header\TagHeader;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class LaraMailerTransportTest extends PHPUnitTestCase
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

    private function email(): Email
    {
        $email = (new Email())
            ->from(new Address('buyer@braga.pt', 'Braga Buyer'))
            ->to(new Address('supplier@example.com', 'Supplier'))
            ->cc('cc@example.com')
            ->bcc('audit@braga.pt')
            ->replyTo('reply@braga.pt')
            ->subject('Quotation request')
            ->html('<p>Hello</p>')
            ->text('Hello')
            ->attach('PDFDATA', 'quotation.pdf', 'application/pdf');

        $email->getHeaders()->add(new MetadataHeader('contract_id', '42'));
        $email->getHeaders()->add(new MetadataHeader('user_id', '7'));
        $email->getHeaders()->add(new TagHeader('quotation'));
        $email->getHeaders()->addTextHeader('X-Idempotency-Key', 'idem-1');
        $email->getHeaders()->addTextHeader('X-Tracking-Enabled', 'false');
        $email->getHeaders()->addTextHeader('X-Custom', 'kept');

        return $email;
    }

    public function test_maps_email_to_send_mail_payload(): void
    {
        $client = $this->client([
            new Response(201, [], json_encode(['success' => true, 'path' => 'attachments/quotation.pdf', 'name' => 'quotation.pdf', 'mime' => 'application/pdf'])),
            new Response(202, [], json_encode(['success' => true, 'data' => ['id' => 77]])),
        ]);

        $sent = (new LaraMailerTransport($client, 5))->send($this->email());

        $this->assertSame('77', $sent->getMessageId());
        $this->assertCount(2, $this->requests);
        $this->assertSame('/api/v1/attachments', $this->requests[0]->getUri()->getPath());
        $this->assertSame('/api/v1/send-mail/5', $this->requests[1]->getUri()->getPath());
        $this->assertSame('idem-1', $this->requests[1]->getHeaderLine('Idempotency-Key'));

        $payload = json_decode((string) $this->requests[1]->getBody(), true);

        $this->assertSame(['email' => 'buyer@braga.pt', 'name' => 'Braga Buyer'], $payload['from']);
        $this->assertSame([['email' => 'supplier@example.com', 'name' => 'Supplier']], $payload['to']);
        $this->assertSame([['email' => 'cc@example.com', 'name' => null]], $payload['cc']);
        $this->assertSame([['email' => 'audit@braga.pt', 'name' => null]], $payload['bcc']);
        $this->assertSame('reply@braga.pt', $payload['reply_to']);
        $this->assertSame('Quotation request', $payload['subject']);
        $this->assertSame('<p>Hello</p>', $payload['html_body']);
        $this->assertSame('Hello', $payload['text_body']);
        $this->assertSame(['contract_id' => '42', 'user_id' => '7', 'tags' => 'quotation'], $payload['metadata']);
        $this->assertSame(['X-Custom' => 'kept'], $payload['headers']);
        $this->assertFalse($payload['tracking_enabled']);
        $this->assertSame([['path' => 'attachments/quotation.pdf', 'name' => 'quotation.pdf', 'content_type' => 'application/pdf']], $payload['attachments']);
    }

    public function test_account_id_may_be_a_uuid(): void
    {
        $client = $this->client([new Response(202, [], json_encode(['success' => true, 'data' => ['id' => 1]]))]);

        $email = (new Email())->from('a@b.pt')->to('c@d.pt')->subject('S')->text('T');

        $uuid = '01a0819e-8170-73ab-8b1c-94ec32cb390c';

        (new LaraMailerTransport($client, $uuid))->send($email);

        $this->assertSame('/api/v1/send-mail/'.$uuid, $this->requests[0]->getUri()->getPath());
    }

    public function test_tracking_defaults_from_constructor(): void
    {
        $client = $this->client([new Response(202, [], json_encode(['success' => true, 'data' => ['id' => 1]]))]);

        $email = (new Email())->from('a@b.pt')->to('c@d.pt')->subject('S')->text('T');

        (new LaraMailerTransport($client, 5, trackingEnabled: true))->send($email);

        $payload = json_decode((string) $this->requests[0]->getBody(), true);

        $this->assertTrue($payload['tracking_enabled']);
        $this->assertArrayNotHasKey('html_body', $payload);
        $this->assertArrayNotHasKey('attachments', $payload);
    }

    public function test_api_failure_becomes_transport_exception(): void
    {
        $client = $this->client([new Response(500, [], 'boom')]);

        $email = (new Email())->from('a@b.pt')->to('c@d.pt')->subject('S')->text('T');

        $this->expectException(TransportException::class);

        (new LaraMailerTransport($client, 5))->send($email);
    }

    public function test_inline_parts_are_not_uploaded(): void
    {
        $client = $this->client([
            new Response(201, [], json_encode(['success' => true, 'path' => 'attachments/q.pdf', 'name' => 'q.pdf', 'mime' => 'application/pdf'])),
            new Response(202, [], json_encode(['success' => true, 'data' => ['id' => 1]])),
        ]);

        $email = (new Email())
            ->from('a@b.pt')
            ->to('c@d.pt')
            ->subject('S')
            ->html('<img src="cid:logo">')
            ->embed('IMG', 'logo.png', 'image/png')
            ->attach('PDF', 'q.pdf', 'application/pdf');

        (new LaraMailerTransport($client, 5))->send($email);

        $this->assertCount(2, $this->requests);

        $uploadBody = (string) $this->requests[0]->getBody();

        $this->assertStringContainsString('q.pdf', $uploadBody);
        $this->assertStringNotContainsString('logo.png', $uploadBody);

        $payload = json_decode((string) $this->requests[1]->getBody(), true);

        $this->assertCount(1, $payload['attachments']);
        $this->assertSame('q.pdf', $payload['attachments'][0]['name']);
    }
}
