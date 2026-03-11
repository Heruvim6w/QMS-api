<?php

use App\Models\Call;
use App\Models\ChatUser;
use Illuminate\Support\Facades\Broadcast;

// Личный канал пользователя (уведомления о входящих звонках, статусы)
Broadcast::channel('user.{userId}', function ($user, string $userId) {
    return $user->id === $userId;
});

// Канал чата (новые сообщения, прочтение)
Broadcast::channel('chat.{chatId}', function ($user, int $chatId) {
    return ChatUser::where('chat_id', $chatId)
        ->where('user_id', $user->id)
        ->where('is_active', true)
        ->exists();
});

// Канал звонка (WebRTC-сигналинг: SDP, ICE-кандидаты)
Broadcast::channel('call.{callUuid}', function ($user, string $callUuid) {
    $call = Call::where('call_uuid', $callUuid)->first();
    if (!$call) {
        return false;
    }
    return $user->id === $call->caller_id || $user->id === $call->callee_id;
});
