<?php

declare(strict_types=1);

namespace Yugo\Maily\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MailySentEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $id,
        public readonly string $status,
        public readonly string $message,
        public readonly array $data = [],
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('maily-event'),
        ];
    }
}
