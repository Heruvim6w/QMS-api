<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\LoginToken;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: Пользователь зарегистрировался
 * Содержит пользователя и токен для подтверждения почты
 */
class UserRegistered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user,
        public LoginToken $confirmationToken,
    )
    {
    }
}

