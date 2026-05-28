<?php

declare(strict_types=1);

namespace Yugo\Maily;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class MailyServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        config([
            'mail.mailers.maily' => [
                'transport' => 'maily',
            ],
        ]);

        Mail::extend('maily', function (): MailyTransport {
            return new MailyTransport;
        });
    }
}
