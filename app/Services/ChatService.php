<?php

namespace App\Services;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ChatService
{
    public function findOrCreate(User $receiver, ?int $chatId = null): Chat
    {
        if (!$sender = Auth::user()) {
            throw new AccessDeniedHttpException();
        }

        if ($chatId) {
            $chat = $this->findById($chatId, $sender, $receiver);
        } else {
            $chat = $this->findByUsers($sender, $receiver) ?? $this->createChat($sender, $receiver);
        }

        return $chat;
    }

    public function findByUser(User|Authenticatable $user): Collection
    {
        if ($user !== Auth::user()) {
            throw new AccessDeniedHttpException();
        }

        return Chat::where('user_id', $user->id)->get();
    }

    /**
     * @throws AccessDeniedHttpException
     */
    private function findById(int $chatId, User|Authenticatable $sender, User $receiver): Chat
    {
        $chat = Chat::query()->findOrFail($chatId);

        if (!($chat->users()->where('user_id', $sender->id)->exists() && $chat->users()->where('user_id', $receiver->id)->exists())) {
            throw new AccessDeniedHttpException();
        }

        return $chat;
    }

    private function findByUsers(User|Authenticatable $sender, User $receiver): ?Chat
    {
            return Chat::query()
                ->whereHas('users', function ($query) use ($sender) {
                    $query->where('user_id', $sender->id);
                })
                ->whereHas('users', function ($query) use ($receiver) {
                    $query->where('user_id', $receiver->id);
                })
                ->first();
    }

    private function createChat(User|Authenticatable $sender, User $receiver): Chat
    {
        $chat = Chat::query()->create();
        $chat->users()->attach([$sender->id, $receiver->id]);

        return $chat;
    }
}
