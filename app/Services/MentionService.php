<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

/**
 * Сервис для работы с упоминаниями пользователей (@mention)
 * Поддерживает упоминание как по username, так и по UIN
 */
class MentionService
{
    /**
     * Найти все упоминания в тексте (@username или @12345678)
     * Возвращает массив с найденными пользователями
     *
     * @param string $content - текст с упоминаниями
     * @return array массив найденных пользователей и их данных
     */
    public static function extractMentions(string $content): array
    {
        $mentions = [];

        // Паттерн для поиска @mention (username или UIN)
        // Поддерживает: @username, @12345678
        if (preg_match_all('/@([a-zA-Z0-9_-]{3,20}|\d{8})/', $content, $matches)) {
            foreach ($matches[1] as $identifier) {
                $user = User::findByIdentifier($identifier);

                if ($user && !isset($mentions[$user->id])) {
                    $mentions[$user->id] = [
                        'id' => $user->id,
                        'name' => $user->name,
                        'uin' => $user->uin,
                        'username' => $user->username,
                    ];
                }
            }
        }

        return $mentions;
    }

    /**
     * Заменить @mention на более полное представление
     * Например: @john_doe становится @john_doe (12345678)
     *
     * @param string $content - исходный текст
     * @return string текст с расширенными упоминаниями
     */
    public static function expandMentions(string $content): string
    {
        return preg_replace_callback(
            '/@([a-zA-Z0-9_-]{3,20}|\d{8})/',
            function ($matches) {
                $identifier = $matches[1];
                $user = User::findByIdentifier($identifier);

                if ($user) {
                    // Если это username, добавляем UIN
                    if (!preg_match('/^\d{8}$/', $identifier)) {
                        return "@{$user->username} ({$user->uin})";
                    }
                    // Если это UIN, добавляем username если есть
                    if ($user->username) {
                        return "@{$user->uin} ({$user->username})";
                    }
                }

                return $matches[0];
            },
            $content
        );
    }

    /**
     * Проверить, упомянут ли пользователь в тексте
     *
     * @param string $content - текст
     * @param User $user - пользователь для проверки
     * @return bool
     */
    public static function isMentioned(string $content, User $user): bool
    {
        // Проверяем по UIN
        if (strpos($content, "@{$user->uin}") !== false) {
            return true;
        }

        // Проверяем по username если есть
        if ($user->username && strpos($content, "@{$user->username}") !== false) {
            return true;
        }

        return false;
    }

    /**
     * Получить список всех упомянутых пользователей
     *
     * @param string $content - текст
     * @return \Illuminate\Support\Collection
     */
    public static function getMentionedUsers(string $content)
    {
        $mentions = self::extractMentions($content);

        if (empty($mentions)) {
            return collect([]);
        }

        $userIds = array_keys($mentions);

        return User::whereIn('id', $userIds)->get();
    }
}

