<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Message $message)
    {
    }

    /**
     * Вещаем в приватный канал чата.
     * Зашифрованный контент НЕ передаётся — только метаданные-триггер.
     *
     * @return Channel[]
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->message->chat_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * Только метаданные — клиент сам запросит зашифрованный контент через REST.
     */
    public function broadcastWith(): array
    {
        return [
            'message_id'  => $this->message->id,
            'chat_id'     => $this->message->chat_id,
            'sender_id'   => $this->message->sender_id,
            'type'        => $this->message->type,
            'created_at'  => $this->message->created_at->toIso8601String(),
        ];
    }
}

