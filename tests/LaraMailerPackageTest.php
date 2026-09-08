<?php

namespace LaraMailer\Sdk\Tests;

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
}
