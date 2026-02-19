<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\LoginConfirmationMail;
use App\Models\LoginToken;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Сервис для управления аутентификацией и подтверждением логинов
 */
class LoginService
{
    /**
     * Создать токен логина для подтверждения
     * Отправить письмо с ссылкой подтверждения
     */
    public function createLoginToken(
        User    $user,
        string  $deviceName,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): LoginToken
    {
        // Удаляем истекшие токены
        LoginToken::deleteExpired();

        // Создаем новый токен
        $loginToken = LoginToken::create([
            'user_id' => $user->id,
            'token' => LoginToken::generateToken(),
            'device_name' => $deviceName,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'is_confirmed' => false,
            'expires_at' => now()->addHours(3), // Действителен 3 часа
        ]);

        // Отправляем письмо с ссылкой подтверждения
        Mail::to($user->email)->send(new LoginConfirmationMail(
            $user,
            $loginToken,
            $deviceName,
        ));

        return $loginToken;
    }

    /**
     * Подтвердить логин и выдать JWT токен
     */
    public function confirmLoginAndGetToken(string $token): ?string
    {
        $loginToken = LoginToken::findValidToken($token);

        if (!$loginToken) {
            return null;
        }

        // Подтверждаем логин
        $loginToken->confirm();

        // Генерируем JWT токен
        return JWTAuth::fromUser($loginToken->user);
    }

    /**
     * Проверить, нужно ли подтверждение для нового устройства
     * Возвращает true если это новое устройство (нету подтвержденного логина на этом UA/IP)
     */
    public function isNewDevice(User $user, string $userAgent, string $ipAddress): bool
    {
        $confirmedLoginOnDevice = LoginToken::where('user_id', $user->id)
            ->where('is_confirmed', true)
            ->where('user_agent', $userAgent)
            ->where('ip_address', $ipAddress)
            ->where('expires_at', '>', now())
            ->exists();

        return !$confirmedLoginOnDevice;
    }

    /**
     * Получить список неподтвержденных логинов пользователя
     */
    public function getUnconfirmedLogins(User $user)
    {
        return LoginToken::getUnconfirmedLogins($user->id);
    }

    /**
     * Получить список подтвержденных сессий пользователя
     */
    public function getConfirmedSessions(User $user)
    {
        return LoginToken::where('user_id', $user->id)
            ->where('is_confirmed', true)
            ->where('expires_at', '>', now())
            ->orderBy('confirmed_at', 'desc')
            ->get();
    }

    /**
     * Завершить сессию (удалить токен логина)
     */
    public function endSession(User $user, int $loginTokenId): bool
    {
        $loginToken = LoginToken::where('id', $loginTokenId)
            ->where('user_id', $user->id)
            ->first();

        if ($loginToken) {
            $loginToken->delete();
            return true;
        }

        return false;
    }
}

