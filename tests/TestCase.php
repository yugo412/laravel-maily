<?php

namespace Tests;

use Orchestra\Testbench\TestCase as TestbenchTestCase;
use Yugo\Maily\MailyServiceProvider;

abstract class TestCase extends TestbenchTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            MailyServiceProvider::class,
        ];
    }
}
