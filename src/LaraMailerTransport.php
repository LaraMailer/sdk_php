<?php

namespace LaraMailer\Sdk;

use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

class LaraMailerTransport extends AbstractTransport
{
    public function __construct(
        private Client $client,
        private int $accountId,
        private bool $trackingEnabled = true,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        throw new \LogicException('Not implemented yet');
    }

    public function __toString(): string
    {
        return 'laramailer';
    }
}
