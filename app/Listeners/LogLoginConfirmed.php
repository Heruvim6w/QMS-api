<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\LoginConfirmed;

/**
 * Слушатель события LoginConfirmed
 * Логирует или отправляет уведомление об успешном входе
 * (опционально: отправить письмо об активности аккаунта)
 */
class LogLoginConfirmed
{
    /**
     * Handle the event.
     */
    public function handle(LoginConfirmed $event): void
    {
        // TODO: Можно логировать или отправить уведомление об входе
        // Например: Mail::to($event->user->email)->send(new LoginSuccessfulMail(...));
        // или: Log::info("User {$event->user->id} logged in from device: {$event->deviceName}");
    }
}

