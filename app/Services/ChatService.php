<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ChatService
{
    public function __construct(
        private readonly EncryptionService $encryptionService,
    ) {}

    /**
     * Найти или создать личный чат между пользователями
     * @throws Throwable
     */
    public function findOrCreatePrivateChat(User $receiver, ?int $chatId = null): Chat
    {
        if (!$sender = Auth::user()) {
            throw new AccessDeniedHttpException();
        }

        if ($chatId) {
            return $this->findById($chatId, $sender);
        }

        return $this->findPrivateChatByUsers($sender, $receiver)
            ?? $this->createPrivateChat($sender, $receiver);
    }

    /**
     * Создать групповой чат
     */
    public function createGroupChat(string $name, array $userIds): Chat
    {
        if (!$creator = Auth::user()) {
            throw new AccessDeniedHttpException();
        }

        return DB::transaction(function () use ($creator, $name, $userIds) {
            $chat = Chat::query()->create([
                'type' => Chat::TYPE_GROUP,
                'name' => $name,
                'creator_id' => $creator->id,
            ]);

            // Добавляем создателя и указанных пользователей
            $usersToAttach = array_unique(array_merge([$creator->id], $userIds));
            $attachData = [];
            foreach ($usersToAttach as $userId) {
                $attachData[$userId] = [
                    'joined_at' => now(),
                    'is_active' => true,
                ];
            }
            $chat->users()->attach($attachData);

            return $chat;
        });
    }

    /**
     * Создать чат "Избранное" для пользователя
     */
    public function createFavoritesChat(User $user): Chat
    {
        return DB::transaction(function () use ($user) {
            $chat = Chat::query()->create([
                'type' => Chat::TYPE_FAVORITES,
                'name' => 'Избранное',
                'creator_id' => $user->id,
            ]);

            $chat->users()->attach($user->id, [
                'joined_at' => now(),
                'is_active' => true,
            ]);

            return $chat;
        });
    }

    /**
     * Получить чат "Избранное" пользователя
     */
    public function getFavoritesChat(User $user): ?Chat
    {
        return Chat::query()
            ->where('type', Chat::TYPE_FAVORITES)
            ->where('creator_id', $user->id)
            ->first();
    }

    /**
     * Получить или создать чат "Избранное"
     */
    public function getOrCreateFavoritesChat(User $user): Chat
    {
        return $this->getFavoritesChat($user) ?? $this->createFavoritesChat($user);
    }

    /**
     * Получить все чаты пользователя.
     * last_message для текстовых сообщений содержит расшифрованный content.
     */
    public function getUserChats(User|Authenticatable $user): Collection
    {
        $chats = Chat::query()
            ->whereHas('users', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('is_active', true);
            })
            ->with(['lastMessage.keys', 'users'])
            ->orderByDesc(
                Chat::query()
                    ->join('messages', 'chats.id', '=', 'messages.chat_id')
                    ->whereColumn('chats.id', 'messages.chat_id')
                    ->select('messages.created_at')
                    ->latest()
                    ->limit(1)
            )
            ->get();

        // Расшифровываем content только для текстовых сообщений — ключи уже
        // загружены eager-load'ом, дополнительных запросов к БД нет.
        foreach ($chats as $chat) {
            $msg = $chat->lastMessage;
            if (!$msg || $msg->type !== \App\Models\Message::TYPE_TEXT) {
                // Добавляем unread_count
                $chat->setAttribute('unread_count', $chat->getUnreadCountForUser($user));
                continue;
            }

            try {
                $encryptedKey = $msg->keys->firstWhere('user_id', $user->id)?->encrypted_key;
                if ($encryptedKey === null) {
                    // Добавляем unread_count
                    $chat->setAttribute('unread_count', $chat->getUnreadCountForUser($user));
                    continue;
                }

                $decrypted = $this->encryptionService->decryptForUser(
                    $msg->encrypted_content,
                    $encryptedKey,
                    $msg->iv,
                    $user->private_key,
                );

                // Добавляем виртуальное поле — в JSON будет отдаваться как "content"
                $msg->setAttribute('content', $decrypted);
            } catch (\Throwable) {
                // Не удалось расшифровать — пропускаем, frontend покажет заглушку
            }

            // Добавляем unread_count для каждого чата
            $chat->setAttribute('unread_count', $chat->getUnreadCountForUser($user));
        }

        return $chats;
    }

    /**
     * Добавить пользователя в групповой чат
     */
    public function addUserToChat(Chat $chat, User $user): void
    {
        if (!$chat->isGroup()) {
            throw new \InvalidArgumentException('Можно добавлять пользователей только в групповые чаты');
        }

        if ($chat->hasUser($user)) {
            // Реактивируем, если был удалён
            $chat->users()->updateExistingPivot($user->id, [
                'is_active' => true,
                'joined_at' => now(),
            ]);
        } else {
            $chat->users()->attach($user->id, [
                'joined_at' => now(),
                'is_active' => true,
            ]);
        }
    }

    /**
     * Удалить пользователя из группового чата
     */
    public function removeUserFromChat(Chat $chat, User $user): void
    {
        if (!$chat->isGroup()) {
            throw new \InvalidArgumentException('Можно удалять пользователей только из групповых чатов');
        }

        $chat->users()->updateExistingPivot($user->id, [
            'is_active' => false,
        ]);
    }

    /**
     * Покинуть чат
     */
    public function leaveChat(Chat $chat, User $user): void
    {
        if ($chat->isFavorites()) {
            throw new \InvalidArgumentException('Нельзя покинуть чат "Избранное"');
        }

        $chat->users()->updateExistingPivot($user->id, [
            'is_active' => false,
        ]);
    }

    /**
     * Найти чат по ID с проверкой доступа
     */
    public function findById(int $chatId, User|Authenticatable $user): Chat
    {
        $chat = Chat::query()->find($chatId);

        if (!$chat) {
            throw new NotFoundHttpException('Чат не найден');
        }

        if (!$chat->hasUser($user)) {
            throw new AccessDeniedHttpException('Нет доступа к чату');
        }

        return $chat;
    }

    /**
     * Найти личный чат между двумя пользователями
     */
    private function findPrivateChatByUsers(User|Authenticatable $sender, User $receiver): ?Chat
    {
        return Chat::query()
            ->where('type', Chat::TYPE_PRIVATE)
            ->whereHas('users', function ($query) use ($sender) {
                $query->where('user_id', $sender->id);
            })
            ->whereHas('users', function ($query) use ($receiver) {
                $query->where('user_id', $receiver->id);
            })
            ->first();
    }

    /**
     * Создать личный чат
     * @throws Throwable
     */
    private function createPrivateChat(User|Authenticatable $sender, User $receiver): Chat
    {
        return DB::transaction(static function () use ($sender, $receiver) {
            $chat = Chat::query()->create([
                'type' => Chat::TYPE_PRIVATE,
            ]);

            $chat->users()->attach([
                $sender->id => ['joined_at' => now(), 'is_active' => true],
                $receiver->id => ['joined_at' => now(), 'is_active' => true],
            ]);

            return $chat;
        });
    }

    /**
     * Обновить название группового чата
     */
    public function updateGroupName(Chat $chat, string $name): void
    {
        if (!$chat->isGroup()) {
            throw new \InvalidArgumentException('Можно изменять название только у групповых чатов');
        }

        $chat->update(['name' => $name]);
    }

    /**
     * Переключить режим mute для чата
     */
    public function toggleMute(Chat $chat, User $user, bool $isMuted): void
    {
        $chat->users()->updateExistingPivot($user->id, [
            'is_muted' => $isMuted,
        ]);
    }

    /**
     * Пометить все сообщения чата как прочитанные для пользователя
     */
    public function markChatAsRead(Chat $chat, User $user): void
    {
        // Получаем все непрочитанные сообщения в чате (не от этого пользователя)
        $unreadMessages = $chat->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereDoesntHave('readStatuses', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->whereNotNull('read_at');
            })
            ->get();

        // Помечаем каждое как прочитанное
        foreach ($unreadMessages as $message) {
            $message->markAsReadBy($user);
        }
    }
}
