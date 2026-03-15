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
use Illuminate\Support\Facades\DB;

class UserPresenceChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly User $user)
    {
    }

    /**
     * Вещаем в личный канал пользователя И во все его чаты,
     * чтобы участники чатов видели смену статуса в реальном времени.
     *
     * @return Channel[]
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('user.' . $this->user->id),
        ];

        // Прямой запрос к chat_users — не используем relation (withTimestamps() сломает запрос)
        $chatIds = DB::table('chat_users')
            ->where('user_id', $this->user->id)
            ->pluck('chat_id');

        foreach ($chatIds as $chatId) {
            $channels[] = new PrivateChannel('chat.' . $chatId);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'user.presence';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id'       => $this->user->id,
            'status'        => $this->user->status,
            'custom_status' => $this->user->custom_status,
            'last_seen_at'  => $this->user->last_seen_at?->toIso8601String(),
        ];
    }
}
