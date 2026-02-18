<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\StatusService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware для обновления времени последней активности пользователя
 * Вызывает StatusService для проверки и обновления статусов
 */
class UpdateUserActivityStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Если пользователь авторизован и онлайн - обновляем последнюю активность
        if (Auth::check()) {
            $user = Auth::user();
            $statusService = new StatusService();

            // Обновляем время последней активности
            $statusService->updateLastSeen($user);
        }

        $response = $next($request);

        return $response;
    }
}

