<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

class LocalizationService
{
    private const SUPPORTED_LOCALES = ['en', 'ru', 'de'];
    private const DEFAULT_LOCALE = 'en';

    /**
     * Получить список всех поддерживаемых языков
     */
    public function getSupportedLocales(): array
    {
        return self::SUPPORTED_LOCALES;
    }

    /**
     * Получить язык по умолчанию
     */
    public function getDefaultLocale(): string
    {
        return self::DEFAULT_LOCALE;
    }

    /**
     * Проверить, поддерживается ли язык
     */
    public function isLocaleSupported(string $locale): bool
    {
        return in_array($locale, self::SUPPORTED_LOCALES);
    }

    /**
     * Получить локализованные названия языков
     */
    public function getLanguageNames(): array
    {
        $names = [];
        foreach (self::SUPPORTED_LOCALES as $locale) {
            $names[$locale] = __("languages.{$locale}");
        }
        return $names;
    }

    /**
     * Получить локализованные названия статусов
     */
    public function getStatusNames(): array
    {
        $statuses = User::getAvailableStatuses();
        $names = [];

        foreach ($statuses as $status) {
            $names[$status] = __("statuses.{$status}");
        }

        return $names;
    }

    /**
     * Обновить язык пользователя
     */
    public function updateUserLocale(User $user, string $locale): void
    {
        if (!$this->isLocaleSupported($locale)) {
            throw new \InvalidArgumentException("Locale {$locale} is not supported");
        }

        $user->update(['locale' => $locale]);
        app()->setLocale($locale);
    }

    /**
     * Получить текущий язык приложения
     */
    public function getCurrentLocale(): string
    {
        return app()->getLocale();
    }

    /**
     * Установить язык приложения
     */
    public function setCurrentLocale(string $locale): void
    {
        if ($this->isLocaleSupported($locale)) {
            app()->setLocale($locale);
        }
    }
}

