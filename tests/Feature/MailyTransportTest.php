<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Yugo\Maily\Events\MailySentEvent;
use Yugo\Maily\Exceptions\MailyException;
use Yugo\Maily\Exceptions\MailyTransportException;
use Yugo\Maily\MailyTransport;

beforeEach(function (): void {
    config()->set('mail.default', 'maily');

    config()->set('mail.mailers.maily', [
        'transport' => 'maily',
    ]);

    config()->set('services.maily', [
        'key' => 'ml_live_test',
        'endpoint' => 'https://maily.id',
        'timeout' => 15,
        'retry' => 0,
        'retry_delay' => 1000,
    ]);
});

it('sends email successfully', function (): void {
    Event::fake();

    Http::fake([
        '*' => Http::response([
            'id' => 'abc123',
            'status' => 'queued',
            'message' => 'Email queued for delivery',
        ]),
    ]);

    Mail::raw('Hello from Maily!', function ($message): void {
        $message->to('user@example.com')
            ->subject('Test Email');
    });

    Http::assertSent(function ($request): bool {
        return $request->url() === 'https://maily.id/api/v1/emails/send'
            && $request->hasHeader('X-API-Key', 'ml_live_test')
            && $request['to'] === 'user@example.com'
            && $request['subject'] === 'Test Email';
    });

    Event::assertDispatched(MailySentEvent::class);
});

it('throws exception when api key is missing', function (): void {
    config()->set('services.maily.key', null);

    expect(fn () => Mail::raw(
        'Hello',
        function ($message): void {
            $message
                ->to('user@example.com')
                ->subject('Test');
        },
    ))->toThrow(MailyException::class, 'Missing Maily API key');
});

it('throws exception for multiple recipients', function (): void {
    expect(fn () => Mail::raw(
        'Hello',
        function ($message): void {
            $message
                ->to('john@example.com')
                ->to('jane@example.com')
                ->subject('Test');
        },
    ))->toThrow(MailyException::class, 'single recipient');
});

it('throws transport exception on api error', function (): void {
    Http::fake([
        '*' => Http::response([
            'error' => 'Domain not registered for this account',
        ], 403),
    ]);

    expect(fn () => Mail::raw(
        'Hello',
        function ($message): void {
            $message
                ->to('user@example.com')
                ->subject('Test');
        },
    ))->toThrow(MailyTransportException::class, 'Domain not registered for this account');
});

it('formats validation error details', function (): void {
    Http::fake([
        '*' => Http::response([
            'error' => 'Validation failed',
            'details' => [
                [
                    'field' => 'to',
                    'message' => 'Invalid to address',
                ],
            ],
        ], 422),
    ]);

    expect(fn () => Mail::raw(
        'Hello',
        function ($message): void {
            $message
                ->to('email@invalid')
                ->subject('Test');
        },
    ))->toThrow(MailyTransportException::class, 'Validation failed: to: Invalid to address');
});

it('throws exception when connection fails', function (): void {
    Http::fake(function (): void {
        throw new ConnectionException('Connection failed');
    });

    expect(fn () => Mail::raw(
        'Hello',
        function ($message): void {
            $message
                ->to('user@example.com')
                ->subject('Test');
        },
    ))->toThrow(MailyTransportException::class, 'Could not connect to the Maily API.');
});

it('formats from address correctly', function (): void {
    Event::fake();

    Http::fake([
        '*' => Http::response([
            'id' => 'mail123',
            'status' => 'queued',
            'message' => 'lorem ipsum dolor sit amet',
        ]),
    ]);

    $email = (new Email)
        ->from(new Address('hello@example.com', 'Your App'))
        ->to('user@example.com')
        ->subject('Test')
        ->text('Hello');

    $transport = new MailyTransport;

    $transport->send($email, Envelope::create($email));

    Http::assertSent(function ($request): bool {
        return $request['from'] === 'Your App <hello@example.com>';
    });

    Event::assertDispatched(MailySentEvent::class);
});
