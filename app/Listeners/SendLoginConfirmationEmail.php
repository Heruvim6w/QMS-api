<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\LoginConfirmationRequested;
use App\Mail\LoginConfirmationMail;
use Illuminate\Support\Facades\Mail;

/**
 * Слушатель события LoginConfirmationRequested
 * Отправляет письмо с подтверждением входа для нового устройства
 */
class SendLoginConfirmationEmail
{
    /**
     * Handle the event.
     */
    public function handle(LoginConfirmationRequested $event): void
    {
        Mail::to($event->user->email)->send(new LoginConfirmationMail(
            $event->user,
            $event->loginToken,
            $event->deviceName,
        ));
    }
}

