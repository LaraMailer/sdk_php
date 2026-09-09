<?php

namespace LaraMailer\Sdk\Tests;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use LaraMailer\Sdk\Client;
use LaraMailer\Sdk\Facades\LaraMailer;
use LaraMailer\Sdk\LaraMailerTransport;

class LaraMailerPackageTest extends TestCase
{
    public function test_config_defaults_are_loaded(): void
    {
        $this->assertSame(15, config('laramailer.timeout'));
        $this->assertTrue(config('laramailer.tracking_enabled'));
    }

    public function test_laramailer_mailer_resolves_transport(): void
    {
        config()->set('laramailer.endpoint', 'https://mail.example.test');
        config()->set('laramailer.token', 'abc');
        config()->set('laramailer.account_id', 5);
        config()->set('mail.mailers.laramailer', ['transport' => 'laramailer']);

        $transport = Mail::mailer('laramailer')->getSymfonyTransport();

        $this->assertInstanceOf(LaraMailerTransport::class, $transport);
        $this->assertSame('laramailer', (string) $transport);
    }

    public function test_facade_resolves_client(): void
    {
        $this->assertInstanceOf(Client::class, LaraMailer::getFacadeRoot());
    }

    public function test_mail_facade_returns_the_task_id_as_the_message_id(): void
    {
        config()->set('laramailer.endpoint', 'https://mail.example.test');
        config()->set('laramailer.token', 'abc');
        config()->set('laramailer.account_id', 5);
        config()->set('mail.mailers.laramailer', ['transport' => 'laramailer']);
        config()->set('mail.from', ['address' => 'sender@example.test', 'name' => 'Sender']);

        $this->app->singleton(Client::class, fn () => new Client(
            apiToken: 'abc',
            domain: 'https://mail.example.test',
            handler: new MockHandler([new Response(202, [], json_encode(['success' => true, 'data' => ['id' => 4321, 'status' => 'pending']]))]),
        ));

        $mailable = new class extends Mailable
        {
            public function build(): self
            {
                return $this->subject('Pedido de cotacao')->html('<p>Ola</p>');
            }
        };

        $sent = Mail::mailer('laramailer')->to('supplier@example.com')->send($mailable);

        $this->assertNotNull($sent);
        $this->assertSame('4321', $sent->getMessageId());
    }
}
