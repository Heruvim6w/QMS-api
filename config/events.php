<?php

declare(strict_types=1);

/**
 * Конфигурация событий и слушателей
 * Сопоставляет события с их слушателями
 */
return [
    \App\Events\UserRegistered::class => [
        \App\Listeners\SendRegistrationConfirmationEmail::class,
    ],
    \App\Events\LoginConfirmationRequested::class => [
        \App\Listeners\SendLoginConfirmationEmail::class,
    ],
    \App\Events\LoginConfirmed::class => [
        \App\Listeners\LogLoginConfirmed::class,
    ],
];

