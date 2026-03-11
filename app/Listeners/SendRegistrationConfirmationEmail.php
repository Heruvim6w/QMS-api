<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Mail\RegistrationConfirmationMail;
use Illuminate\Support\Facades\Mail;

/**
 * Слушатель события UserRegistered
 * Отправляет письмо с подтверждением регистрации
 */
class SendRegistrationConfirmationEmail
{
    /**
     * Handle the event.
     */
    public function handle(UserRegistered $event): void
    {
        Mail::to($event->user->email)->send(new RegistrationConfirmationMail(
            $event->user,
            $event->confirmationToken,
        ));
    }
}

