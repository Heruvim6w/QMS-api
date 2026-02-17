<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Call;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CallService
{
    public function __construct(
        private readonly ChatService $chatService
    ) {}

    /**
     * Инициировать звонок
     */
    public function initiateCall(int $receiverId, bool $isVideo = false, ?int $chatId = null): Call
    {
        /** @var User $caller */
        $caller = Auth::user();

        if (!$caller) {
            throw new AccessDeniedHttpException();
        }

        $receiver = User::query()->find($receiverId);

        if (!$receiver) {
            throw new NotFoundHttpException('Пользователь не найден');
        }

        // Находим или создаём чат
        if ($chatId) {
            $chat = $this->chatService->findById($chatId, $caller);
        } else {
            $chat = $this->chatService->findOrCreatePrivateChat($receiver);
        }

        // Проверяем, нет ли уже активного звонка
        $existingCall = Call::query()
            ->where('chat_id', $chat->id)
            ->whereIn('status', [Call::STATUS_PENDING, Call::STATUS_RINGING, Call::STATUS_ACTIVE])
            ->first();

        if ($existingCall) {
            throw new BadRequestHttpException('В этом чате уже есть активный звонок');
        }

        return Call::query()->create([
            'chat_id' => $chat->id,
            'caller_id' => $caller->id,
            'callee_id' => $receiver->id,
            'type' => $isVideo ? Call::TYPE_VIDEO : Call::TYPE_AUDIO,
            'status' => Call::STATUS_PENDING,
            'started_at' => now(),
        ]);
    }

    /**
     * Установить SDP offer
     */
    public function setSdpOffer(Call $call, string $sdpOffer): void
    {
        $call->update([
            'sdp_offer' => $sdpOffer,
            'status' => Call::STATUS_RINGING,
        ]);
    }

    /**
     * Ответить на звонок
     */
    public function answerCall(string $callUuid, string $sdpAnswer): Call
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        $call = $this->findByUuid($callUuid);

        if ($call->callee_id !== $user->id) {
            throw new AccessDeniedHttpException('Вы не можете ответить на этот звонок');
        }

        if (!$call->isPending()) {
            throw new BadRequestHttpException('Звонок уже не ожидает ответа');
        }

        $call->answer($sdpAnswer);

        return $call;
    }

    /**
     * Отклонить звонок
     */
    public function declineCall(string $callUuid): Call
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        $call = $this->findByUuid($callUuid);

        if ($call->callee_id !== $user->id) {
            throw new AccessDeniedHttpException('Вы не можете отклонить этот звонок');
        }

        if (!$call->isPending()) {
            throw new BadRequestHttpException('Звонок уже не ожидает ответа');
        }

        $call->decline();

        return $call;
    }

    /**
     * Завершить звонок
     */
    public function endCall(string $callUuid, string $reason = 'normal'): Call
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        $call = $this->findByUuid($callUuid);

        if ($call->caller_id !== $user->id && $call->callee_id !== $user->id) {
            throw new AccessDeniedHttpException('Вы не участвуете в этом звонке');
        }

        if ($call->isEnded()) {
            throw new BadRequestHttpException('Звонок уже завершён');
        }

        $call->end($reason);

        return $call;
    }

    /**
     * Добавить ICE candidate
     */
    public function addIceCandidate(string $callUuid, string $candidate): Call
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        $call = $this->findByUuid($callUuid);

        if ($call->caller_id !== $user->id && $call->callee_id !== $user->id) {
            throw new AccessDeniedHttpException('Вы не участвуете в этом звонке');
        }

        $call->addIceCandidate($candidate);

        return $call;
    }

    /**
     * Получить звонок по UUID
     */
    public function findByUuid(string $callUuid): Call
    {
        $call = Call::query()->where('call_uuid', $callUuid)->first();

        if (!$call) {
            throw new NotFoundHttpException('Звонок не найден');
        }

        return $call;
    }

    /**
     * Получить активный звонок в чате
     */
    public function getActiveCallInChat(Chat $chat): ?Call
    {
        return Call::query()
            ->where('chat_id', $chat->id)
            ->whereIn('status', [Call::STATUS_PENDING, Call::STATUS_RINGING, Call::STATUS_ACTIVE])
            ->first();
    }

    /**
     * Получить историю звонков пользователя
     */
    public function getUserCallHistory(User $user, int $limit = 50): Collection
    {
        return Call::query()
            ->where(function ($query) use ($user) {
                $query->where('caller_id', $user->id)
                    ->orWhere('callee_id', $user->id);
            })
            ->with(['caller', 'callee', 'chat'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Пометить звонок как пропущенный (timeout)
     */
    public function markAsMissed(string $callUuid): Call
    {
        $call = $this->findByUuid($callUuid);

        if (!$call->isPending()) {
            throw new BadRequestHttpException('Звонок уже не ожидает ответа');
        }

        $call->markAsMissed();

        return $call;
    }
}
