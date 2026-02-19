<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    private const SUPPORTED_LOCALES = ['en', 'ru', 'de'];
    private const DEFAULT_LOCALE = 'en';

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->detectLocale($request);
        app()->setLocale($locale);
        return $next($request);
    }

    private function detectLocale(Request $request): string
    {
        if (auth()->check()) {
            $userLocale = auth()->user()->locale;
            if ($userLocale && in_array($userLocale, self::SUPPORTED_LOCALES)) {
                return $userLocale;
            }
        }
        $acceptLanguage = $request->header('Accept-Language', '');
        if ($acceptLanguage) {
            $locales = array_map(function ($part) {
                return trim(explode(';', $part)[0]);
            }, explode(',', $acceptLanguage));
            foreach ($locales as $locale) {
                $lang = explode('-', $locale)[0];
                if (in_array($lang, self::SUPPORTED_LOCALES)) {
                    return $lang;
                }
            }
        }
        return self::DEFAULT_LOCALE;
    }
}
