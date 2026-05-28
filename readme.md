# Maily for Laravel

SMTP-free Laravel mail transport for [Maily.id](https://maily.id).

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

## Features

* Native Laravel Mail integration
* Queue compatible
* Notification compatible
* API-based delivery
* SMTP-free setup
* Retry support

## Limitations

Currently, the following features are not supported due to limitations in the Maily API:

* Attachments
* Multiple recipients
* CC and BCC recipients

## Requirements

* PHP 8.2+
* Laravel 11 or newer

## Testing

Run the test suite using:

```bash
composer test
```

## License

This package is open-sourced software licensed under the MIT license.
