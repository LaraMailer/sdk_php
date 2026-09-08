<?php

namespace LaraMailer\Sdk\Facades;

use Illuminate\Support\Facades\Facade;
use LaraMailer\Sdk\Client;

/**
 * @method static \LaraMailer\Sdk\Resources\Mail mail()
 * @method static \LaraMailer\Sdk\Resources\Account accounts()
 * @method static \LaraMailer\Sdk\Resources\Attachment attachments()
 * @method static \LaraMailer\Sdk\Resources\OAuth2 oauth2()
 * @method static \LaraMailer\Sdk\Resources\Templates templates()
 */
class LaraMailer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Client::class;
    }
}
