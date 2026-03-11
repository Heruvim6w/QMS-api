<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Call;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Call $call)
    {
    }

    /**
     * Вещаем в канал звонка И в личный канал собеседника.
     * Для WebRTC-сигналинга критична минимальная задержка → ShouldBroadcastNow.
     *
     * @return Channel[]
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('call.' . $this->call->call_uuid),
            new PrivateChannel('user.' . $this->call->callee_id),
            new PrivateChannel('user.' . $this->call->caller_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'call.updated';
    }

    public function broadcastWith(): array
    {
        $payload = [
            'call_uuid'  => $this->call->call_uuid,
            'chat_id'    => $this->call->chat_id,
            'caller_id'  => $this->call->caller_id,
            'callee_id'  => $this->call->callee_id,
            'type'       => $this->call->type,
            'status'     => $this->call->status,
            'updated_at' => now()->toIso8601String(),
        ];

        // SDP передаётся только при статусах, где он нужен
        if (in_array($this->call->status, [Call::STATUS_RINGING, Call::STATUS_ACTIVE], true)) {
            $payload['sdp_offer']  = $this->call->sdp_offer;
            $payload['sdp_answer'] = $this->call->sdp_answer;
        }

        return $payload;
    }
}

