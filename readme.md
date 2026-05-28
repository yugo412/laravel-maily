# Maily for Laravel

SMTP-free Laravel mail transport for [Maily.id](https://maily.id).

## Why this package?

Maily currently provides an API-only email service without SMTP support.
This package allows Laravel applications to use Maily through Laravel Mail without changing existing mail implementations.

## Features

* Native Laravel Mail integration
* Queue compatible
* Notification compatible
* API-based delivery
* SMTP-free setup
* Retry support

## Requirements

* PHP 8.2+
* Laravel 11 or newer

## Installation

Install the package via Composer:

```bash
composer require yugo/laravel-maily
```

## Configuration

Add the following configuration to your `config/services.php` file:

```php
'maily' => [
    'key' => env('MAILY_KEY'),
    'endpoint' => env('MAILY_ENDPOINT', 'https://maily.id'),
    'timeout' => (int) env('MAILY_TIMEOUT', 15),
    'retry' => (int) env('MAILY_RETRY', 3),
    'retry_delay' => (int) env('MAILY_RETRY_DURATION', 1_000),
],
```

Then add your Maily credentials to `.env`:

```env
MAIL_MAILER=maily

MAILY_KEY=ml_live_your_api_key_here

# optional
MAILY_ENDPOINT=https://maily.id
MAILY_TIMEOUT=15
MAILY_RETRY=3
MAILY_RETRY_DURATION=1000
```

## Usage

Once configured, Laravel Mail will automatically use the Maily transport.

### Raw Email

```php
use Illuminate\Support\Facades\Mail;

Mail::raw('Hello from Maily!', function ($message) {
    $message
        ->to('user@example.com')
        ->subject('Test Email');
});
```

### Mailables

```php
Mail::to($user)->send(new WelcomeMail());
```

### Queued Mail

```php
Mail::to($user)->queue(new WelcomeMail());
```

## Events

The package dispatches a `MailySentEvent` event after a successful email request.

This can be useful for:

* logging
* analytics
* quota monitoring
* debugging
* admin dashboards

### Listening to the Event

```php
<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Yugo\Maily\Events\MailySentEvent;

class GetMailyResponse
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(MailySentEvent $event): void
    {
        Log::debug('Maily response', [
            'id' => $event->id,
            'status' => $event->status,
            'message' => $event->message,
            'data' => $event->data,
        ]);
    }
}

```

### Available Properties

```php
$event->id;
$event->status;
$event->message;
$event->data;
```

The `data` property contains the full raw response returned by the Maily API.

## Limitations

Currently, the following features are not supported due to limitations in the Maily API:

* Attachments
* Multiple recipients
* CC and BCC recipients

## Testing

Run the test suite using:

```bash
composer test
```

## License

This package is open-sourced software licensed under the MIT license.
