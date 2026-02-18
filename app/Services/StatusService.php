<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

/**
 * Сервис для управления статусами пользователей
 * Поддерживает автоматическое переключение оффлайн после 3 минут неактивности
 */
class StatusService
{
    /**
     * Время неактивности перед переводом в оффлайн (в минутах)
     */
    public const INACTIVITY_TIMEOUT_MINUTES = 3;

    /**
     * Установить статус пользователю
     */
    public function setStatus(User $user, string $onlineStatus, ?string $customStatus = null): void
    {
        $user->setOnlineStatus($onlineStatus, $customStatus);
    }

    /**
     * Переводит пользователя в оффлайн если он неактивен 3 минуты
     * Вызывается из middleware или scheduler
     */
    public function updateOfflineStatus(): int
    {
        $inactivityThreshold = now()->subMinutes(self::INACTIVITY_TIMEOUT_MINUTES);

        $count = User::where('status', User::STATUS_ONLINE)
            ->where(function ($query) use ($inactivityThreshold) {
                $query->whereNull('last_seen_at')
                    ->orWhere('last_seen_at', '<', $inactivityThreshold);
            })
            ->update([
                'status' => User::STATUS_OFFLINE,
            ]);

        return (int)$count;
    }

    /**
     * Получить список пользователей с их статусами
     * Для отображения в списке контактов
     */
    public function getUsersWithStatus(array $userIds)
    {
        return User::whereIn('id', $userIds)
            ->select('id', 'name', 'uin', 'username', 'status', 'online_status', 'custom_status', 'last_seen_at')
            ->get()
            ->map(function (User $user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'uin' => $user->uin,
                    'username' => $user->username,
                    'is_online' => $user->isOnline(),
                    'status' => $user->getDisplayStatus(),
                    'status_key' => $user->online_status,
                    'last_seen' => $user->getLastSeenFormatted(),
                ];
            });
    }

    /**
     * Обновить время последнего онлайна при активности
     */
    public function updateLastSeen(User $user): void
    {
        // Обновляем только если статус ONLINE
        if ($user->isOnline()) {
            $user->update(['last_seen_at' => now()]);
        }
    }

    /**
     * Получить доступные статусы со своими локализованными названиями
     */
    public static function getAvailableStatuses(): array
    {
        $statuses = User::getAvailableStatuses();
        $localized = [];

        foreach ($statuses as $status) {
            $localized[$status] = __("statuses.{$status}");
        }

        return $localized;
    }

    /**
     * Получить информацию о статусе пользователя для показа его друзьям
     */
    public function getStatusForFriend(User $user): array
    {
        return [
            'is_online' => $user->isOnline(),
            'status' => $user->getDisplayStatus(),
            'status_key' => $user->online_status,
            'last_seen' => $user->getLastSeenFormatted(),
        ];
    }
}

