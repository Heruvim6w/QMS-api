<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\UserRegistered;
use App\Models\LoginToken;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Сервис для управления регистрацией пользователей
 * Отвечает только за создание пользователя и генерацию токена подтверждения
 */
class RegistrationService
{
    /**
     * Зарегистрировать нового пользователя
     * Вызывает событие UserRegistered для отправки писем (через слушателя)
     */
    public function register(string $name, string $email, string $password): User
    {
        // Создаём пользователя с гарантированно уникальным UIN
        $user = User::createWithUniqueUin([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        // Генерируем RSA ключи для E2E шифрования
        $user->generateKeyPair();
        $user->save();

        // Создаём токен подтверждения почты
        $confirmationToken = LoginToken::create([
            'user_id' => $user->id,
            'token' => LoginToken::generateToken(),
            'device_name' => 'Registration Confirmation',
            'ip_address' => null,
            'user_agent' => null,
            'is_confirmed' => false,
            'expires_at' => now()->addHours(24),
        ]);

        // Генерируем событие - слушатель отправит письмо
        event(new UserRegistered($user, $confirmationToken));

        return $user;
    }
}

