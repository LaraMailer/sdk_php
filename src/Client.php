<?php

namespace LaraMailer\Sdk;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use LaraMailer\Sdk\Exceptions\LaraMailerException;
use LaraMailer\Sdk\Resources\Account;
use LaraMailer\Sdk\Resources\Attachment;
use LaraMailer\Sdk\Resources\Mail;
use LaraMailer\Sdk\Resources\OAuth2;

class Client
{
    protected GuzzleClient $httpClient;

    protected string $domain;

    protected string $apiVersion;

    protected string $apiToken;

    public function __construct(
        string $apiToken,
        string $domain = 'http://localhost',
        string $apiVersion = 'v1',
        int $timeout = 15,
        ?callable $handler = null,
    ) {
        $this->apiToken = $apiToken;
        $this->domain = rtrim($domain, '/');
        $this->apiVersion = $apiVersion;

        $config = [
            'base_uri' => "{$this->domain}/api/{$this->apiVersion}/",
            'timeout' => $timeout,
            'headers' => [
                'Authorization' => "Bearer {$this->apiToken}",
                'Accept' => 'application/json',
            ],
        ];

        if ($handler !== null) {
            $config['handler'] = HandlerStack::create($handler);
        }

        $this->httpClient = new GuzzleClient($config);
    }

    public function accounts(): Account
    {
        return new Account($this);
    }

    public function oauth2(): OAuth2
    {
        return new OAuth2($this);
    }

    public function mail(): Mail
    {
        return new Mail($this);
    }

    public function attachments(): Attachment
    {
        return new Attachment($this);
    }

    /**
     * @throws LaraMailerException
     */
    public function request(string $method, string $uri, array $options = []): array
    {
        return json_decode($this->requestRaw($method, $uri, $options), true) ?: [];
    }

    /**
     * @throws LaraMailerException
     */
    public function requestRaw(string $method, string $uri, array $options = []): string
    {
        try {
            $response = $this->httpClient->request($method, $uri, $options);

            return $response->getBody()->getContents();
        } catch (GuzzleException $e) {
            throw new LaraMailerException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
