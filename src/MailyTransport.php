<?php

declare(strict_types=1);

namespace Yugo\Maily;

use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\MessageConverter;
use Yugo\Maily\Exceptions\MailyException;
use Yugo\Maily\Exceptions\MailyTransportException;

class MailyTransport extends AbstractTransport
{
    public function __toString(): string
    {
        return 'maily';
    }

    protected function doSend(SentMessage $message): void
    {
        $apiKey = config('services.maily.key');
        throw_if(empty($apiKey), new MailyException('Missing Maily API key. Please set services.maily.key configuration.'));

        $email = MessageConverter::toEmail($message->getOriginalMessage());

        if (count($email->getTo()) > 1) {
            throw new MailyException('Maily currently only supports a single recipient.');
        }

        try {
            $response = Http::baseUrl(config('services.maily.endpoint'))
                ->retry(
                    times: config('services.maily.retry', 0),
                    sleepMilliseconds: config('services.maily.retry_delay', 1_000),
                    throw: false,
                    when: function (Exception $exception): bool {
                        return $exception instanceof ConnectionException;
                    },
                )
                ->timeout(config('services.maily.timeout', 15))
                ->withHeader('X-API-Key', $apiKey)
                ->acceptJson()
                ->asJson()
                ->post('/api/v1/emails/send', [
                    'from' => $this->formatAddress($email->getFrom()[0] ?? null),
                    'to' => $this->formatAddress($email->getTo()[0] ?? null),
                    'subject' => $email->getSubject(),
                    'html' => $email->getHtmlBody(),
                    'text' => $email->getTextBody(),
                ]);

            if ($response->failed()) {
                $message = $response->json('error') ?? $response->body();

                if ($details = $response->json('details')) {
                    $detailMessage = collect($details)
                        ->map(function (array $detail): string {
                            return sprintf('%s: %s', $detail['field'] ?? 'unknown', $detail['message'] ?? 'Unknown error');
                        })
                        ->implode(', ');
                    $message .= ': '.$detailMessage;
                }

                throw new MailyTransportException(
                    sprintf('Maily API request failed with status %s: %s', $response->status(), $message),
                );
            }
        } catch (ConnectionException $e) {
            throw new MailyTransportException('Could not connect to the Maily API.', previous: $e);
        }
    }

    private function formatAddress(?Address $address): ?string
    {
        if (empty($address)) {
            return null;
        }

        if ($address->getName()) {
            return sprintf('%s <%s>', $address->getName(), $address->getAddress());
        }

        return $address->getAddress();
    }
}
