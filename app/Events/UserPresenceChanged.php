<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserPresenceChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly User $user)
    {
    }

    /**
     * Вещаем в личный канал пользователя.
     * Контакты подписываются на user.{id} тех, с кем общаются,
     * чтобы видеть смену статуса в реальном времени.
     *
     * @return Channel[]
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->user->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'user.presence';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id'      => $this->user->id,
            'status'       => $this->user->status,
            'custom_status'=> $this->user->custom_status,
            'last_seen_at' => $this->user->last_seen_at?->toIso8601String(),
        ];
    }
}

