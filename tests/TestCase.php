<?php

namespace LaraMailer\Sdk\Tests;

use LaraMailer\Sdk\Facades\LaraMailer;
use LaraMailer\Sdk\LaraMailerServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    /** @return array<int, class-string> */
    protected function getPackageProviders($app): array
    {
        return [LaraMailerServiceProvider::class];
    }

    /** @return array<string, class-string> */
    protected function getPackageAliases($app): array
    {
        return ['LaraMailer' => LaraMailer::class];
    }
}
