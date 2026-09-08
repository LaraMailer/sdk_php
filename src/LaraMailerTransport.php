<?php

namespace LaraMailer\Sdk;

use LaraMailer\Sdk\Exceptions\LaraMailerException;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Header\MetadataHeader;
use Symfony\Component\Mailer\Header\TagHeader;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Component\Mime\Part\DataPart;

class LaraMailerTransport extends AbstractTransport
{
    private const RESERVED_HEADERS = [
        'from', 'to', 'cc', 'bcc', 'subject', 'date', 'message-id', 'mime-version', 'reply-to', 'sender', 'return-path',
    ];

    public function __construct(
        private Client $client,
        private string|int $accountId,
        private bool $trackingEnabled = true,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $original = $message->getOriginalMessage();

        $email = $original instanceof Email ? $original : MessageConverter::toEmail($original);

        $payload = $this->basePayload($email);
        $idempotencyKey = null;
        $tracking = $this->trackingEnabled;
        $metadata = [];
        $tags = [];
        $headers = [];

        foreach ($email->getHeaders()->all() as $header) {
            $name = strtolower($header->getName());

            if ($header instanceof MetadataHeader) {
                $metadata[$header->getKey()] = $header->getValue();

                continue;
            }

            if ($header instanceof TagHeader) {
                $tags[] = $header->getValue();

                continue;
            }

            if ($name === 'x-idempotency-key') {
                $idempotencyKey = $header->getBodyAsString();

                continue;
            }

            if ($name === 'x-tracking-enabled') {
                $tracking = filter_var($header->getBodyAsString(), FILTER_VALIDATE_BOOLEAN);

                continue;
            }

            if (in_array($name, self::RESERVED_HEADERS, true) || str_starts_with($name, 'content-')) {
                continue;
            }

            $headers[$header->getName()] = $header->getBodyAsString();
        }

        if ($tags !== []) {
            $metadata['tags'] = implode(',', $tags);
        }

        if ($metadata !== []) {
            $payload['metadata'] = $metadata;
        }

        if ($headers !== []) {
            $payload['headers'] = $headers;
        }

        $payload['tracking_enabled'] = $tracking;

        try {
            $attachments = $this->uploadAttachments($email->getAttachments());

            if ($attachments !== []) {
                $payload['attachments'] = $attachments;
            }

            $response = $this->client->mail()->send($this->accountId, $payload, $idempotencyKey);
        } catch (LaraMailerException $e) {
            throw new TransportException("LaraMailer send failed: {$e->getMessage()}", 0, $e);
        }

        $taskId = $response['data']['id'] ?? null;

        if ($taskId !== null) {
            $message->setMessageId((string) $taskId);
        }
    }

    public function __toString(): string
    {
        return 'laramailer';
    }

    /**
     * @return array<string, mixed>
     */
    private function basePayload(Email $email): array
    {
        $payload = [
            'to' => $this->addresses($email->getTo()),
            'subject' => $email->getSubject() ?? '',
        ];

        $from = $email->getFrom()[0] ?? null;

        if ($from) {
            $payload['from'] = $this->address($from);
        }

        if ($email->getCc() !== []) {
            $payload['cc'] = $this->addresses($email->getCc());
        }

        if ($email->getBcc() !== []) {
            $payload['bcc'] = $this->addresses($email->getBcc());
        }

        $replyTo = $email->getReplyTo()[0] ?? null;

        if ($replyTo) {
            $payload['reply_to'] = $replyTo->getAddress();
        }

        if ($email->getHtmlBody() !== null) {
            $payload['html_body'] = (string) $email->getHtmlBody();
        }

        if ($email->getTextBody() !== null) {
            $payload['text_body'] = (string) $email->getTextBody();
        }

        return $payload;
    }

    /**
     * @param  array<int, Address>  $addresses
     * @return array<int, array{email: string, name: ?string}>
     */
    private function addresses(array $addresses): array
    {
        return array_values(array_map(fn (Address $address) => $this->address($address), $addresses));
    }

    /**
     * @return array{email: string, name: ?string}
     */
    private function address(Address $address): array
    {
        return [
            'email' => $address->getAddress(),
            'name' => $address->getName() !== '' ? $address->getName() : null,
        ];
    }

    /**
     * @param  array<int, DataPart>  $parts
     * @return array<int, array{path: string, name: string, content_type: string}>
     */
    private function uploadAttachments(array $parts): array
    {
        $attachments = [];

        foreach ($parts as $part) {
            if ($part->getDisposition() === 'inline') {
                continue;
            }

            $filename = $part->getFilename() ?? 'attachment';
            $contentType = $part->getContentType();

            $uploaded = $this->client->attachments()->uploadContents($part->getBody(), $filename, $contentType);

            $attachments[] = [
                'path' => $uploaded['path'],
                'name' => $filename,
                'content_type' => $contentType,
            ];
        }

        return $attachments;
    }
}
