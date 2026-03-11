<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\LoginConfirmed;
use App\Events\LoginConfirmationRequested;
use App\Events\UserRegistered;
use App\Listeners\LogLoginConfirmed;
use App\Listeners\SendLoginConfirmationEmail;
use App\Listeners\SendRegistrationConfirmationEmail;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        UserRegistered::class => [
            SendRegistrationConfirmationEmail::class,
        ],
        LoginConfirmationRequested::class => [
            SendLoginConfirmationEmail::class,
        ],
        LoginConfirmed::class => [
            LogLoginConfirmed::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }
}

