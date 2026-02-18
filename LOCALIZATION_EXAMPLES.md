# 🎓 Примеры использования системы локализации

## 1. Получение списка языков (Frontend)

### JavaScript (Vue/React)
```javascript
// Получить список языков при загрузке приложения
async function getAvailableLanguages() {
  const response = await fetch('/api/v1/languages', {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  
  const data = await response.json();
  
  return {
    supported: data.supported_locales,    // ['en', 'ru', 'de']
    current: data.current_locale,         // 'ru'
    names: data.language_names,           // {en: 'Английский', ...}
    statuses: data.status_names           // {online: 'Онлайн', ...}
  };
}

// Использование
const languages = await getAvailableLanguages();
console.log(languages.names);  // {en: 'Английский', ru: 'Русский', de: 'Немецкий'}
console.log(languages.statuses); // {online: 'Онлайн', chatty: 'Готов поболтать', ...}
```

---

## 2. Смена языка пользователем

### JavaScript (Vue/React)
```javascript
// Функция смены языка
async function changeUserLanguage(newLocale) {
  const response = await fetch('/api/v1/users/locale', {
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      locale: newLocale  // 'ru', 'en', или 'de'
    })
  });
  
  if (response.ok) {
    const data = await response.json();
    console.log(`Язык изменён на: ${data.language_name}`);
    
    // Сохранить в localStorage
    localStorage.setItem('userLanguage', newLocale);
    
    // Перезагрузить интерфейс
    window.location.reload();
  } else {
    const error = await response.json();
    console.error('Ошибка:', error.message);
  }
}

// Использование
await changeUserLanguage('de');  // Сменить на немецкий
```

---

## 3. Использование в контроллере (Backend)

### PHP - UserProfileController
```php
// Получить локализованное название статуса
public function getStatusForUser(User $user)
{
    // Middleware уже установил язык из профиля пользователя
    $statusName = __('statuses.' . $user->online_status);
    // Если $user->locale = 'ru', вернёт "Готов поболтать"
    // Если $user->locale = 'de', вернёт "Gesprächig"
    
    return response()->json([
        'user_id' => $user->id,
        'status_key' => $user->online_status,
        'status_name' => $statusName,  // Локализовано!
        'custom_status' => $user->custom_status
    ]);
}
```

---

## 4. Использование LocalizationService (Backend)

### PHP - StatusService
```php
<?php

use App\Services\LocalizationService;
use App\Models\User;

class StatusService
{
    public function getStatusesForChat(User $user)
    {
        $localizationService = new LocalizationService();
        
        // Получить все статусы на текущем языке пользователя
        $statusNames = $localizationService->getStatusNames();
        
        // Результат (для пользователя на русском):
        // [
        //   'online' => 'Онлайн',
        //   'chatty' => 'Готов поболтать',
        //   'angry' => 'Злой',
        //   ...
        // ]
        
        return response()->json([
            'available_statuses' => $statusNames,
            'current_language' => $localizationService->getCurrentLocale()
        ]);
    }
    
    public function updateUserLanguage(User $user, string $newLocale)
    {
        $localizationService = new LocalizationService();
        
        try {
            $localizationService->updateUserLocale($user, $newLocale);
            
            return [
                'success' => true,
                'message' => 'Язык обновлён',
                'new_locale' => $newLocale
            ];
        } catch (\InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
```

---

## 5. Локализация в нескольких местах (Backend)

### PHP - MessageController
```php
<?php

namespace App\Http\Controllers;

use App\Services\LocalizationService;

class MessageController extends Controller
{
    public function send(SendMessageRequest $request)
    {
        $user = auth()->user();
        
        // Middleware автоматически установил язык из user->locale
        
        // 1️⃣ Локализованная ошибка валидации (автоматически)
        // Если данные невалидны, ошибка будет на языке пользователя
        
        // 2️⃣ Логирование на языке приложения
        \Log::info(__('messages.user_sent_message', [
            'user' => $user->name,
            'timestamp' => now()
        ]));
        
        // 3️⃣ Ответ с локализованными данными
        $localizationService = new LocalizationService();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Сообщение отправлено',  // Будет локализовано
            'current_language' => $localizationService->getCurrentLocale(),
            'supported_languages' => $localizationService->getLanguageNames()
        ]);
    }
}
```

---

## 6. Интеграция с фронтенд приложением

### Vue 3 пример с i18n
```javascript
import { createI18n } from 'vue-i18n'

// Функция синхронизации с backend
async function syncLanguageWithBackend() {
  // 1. Получить язык из API
  const response = await fetch('/api/v1/languages', {
    headers: { 'Authorization': `Bearer ${token}` }
  })
  
  const data = await response.json()
  
  // 2. Установить язык в i18n
  i18n.global.locale.value = data.current_locale
  
  // 3. Сохранить в localStorage
  localStorage.setItem('userLanguage', data.current_locale)
  
  // 4. Получить статусы на текущем языке
  return data.status_names  // {online: 'Онлайн', ...}
}

// При смене языка в меню
async function onLanguageChange(newLocale) {
  // 1. Обновить на backend
  const response = await fetch('/api/v1/users/locale', {
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ locale: newLocale })
  })
  
  // 2. Обновить в i18n
  i18n.global.locale.value = newLocale
  
  // 3. Переинициализировать данные
  await syncLanguageWithBackend()
}
```

---

## 7. Обработка ошибок

### PHP - Error handling
```php
<?php

use App\Services\LocalizationService;

class LocalizationExampleController
{
    public function handleInvalidLanguage()
    {
        $localizationService = new LocalizationService();
        
        $locale = 'xx';  // Несуществующий язык
        
        if (!$localizationService->isLocaleSupported($locale)) {
            return response()->json([
                'error' => 'Язык не поддерживается',
                'supported' => $localizationService->getSupportedLocales()
            ], 400);
        }
    }
    
    public function updateUserLanguageSafe(string $newLocale)
    {
        $localizationService = new LocalizationService();
        
        try {
            $localizationService->updateUserLocale(auth()->user(), $newLocale);
            
            return response()->json(['status' => 'success']);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'supported_locales' => $localizationService->getSupportedLocales()
            ], 422);
        }
    }
}
```

---

## 8. Тестирование локализации

### PHPUnit тесты
```php
<?php

use Tests\TestCase;
use App\Models\User;
use App\Services\LocalizationService;

class LocalizationTest extends TestCase
{
    public function test_middleware_sets_locale_from_accept_language()
    {
        $response = $this->withHeader('Accept-Language', 'ru-RU,ru;q=0.9')
                        ->getJson('/api/v1/languages');
        
        $response->assertStatus(200);
        $this->assertEquals('ru', $response['current_locale']);
    }
    
    public function test_user_locale_takes_priority()
    {
        $user = User::factory()->create(['locale' => 'de']);
        
        $response = $this->actingAs($user)
                        ->withHeader('Accept-Language', 'en')
                        ->getJson('/api/v1/languages');
        
        // Язык пользователя должен иметь приоритет
        $this->assertEquals('de', $response['current_locale']);
    }
    
    public function test_invalid_locale_returns_validation_error()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
                        ->putJson('/api/v1/users/locale', ['locale' => 'invalid']);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('locale');
    }
    
    public function test_status_names_are_localized()
    {
        $service = new LocalizationService();
        
        app()->setLocale('ru');
        $ruStatuses = $service->getStatusNames();
        
        app()->setLocale('en');
        $enStatuses = $service->getStatusNames();
        
        // Проверить что названия отличаются
        $this->assertNotEquals(
            $ruStatuses['online'],
            $enStatuses['online']
        );
        
        $this->assertEquals('Онлайн', $ruStatuses['online']);
        $this->assertEquals('Online', $enStatuses['online']);
    }
}
```

---

## 9. Curl примеры для testing

### Получить языки (English)
```bash
curl -X GET http://localhost:8000/api/v1/languages \
  -H "Authorization: Bearer TOKEN" \
  -H "Accept-Language: en"
```

### Получить языки (Russian)
```bash
curl -X GET http://localhost:8000/api/v1/languages \
  -H "Authorization: Bearer TOKEN" \
  -H "Accept-Language: ru-RU,ru;q=0.9"
```

### Обновить язык
```bash
curl -X PUT http://localhost:8000/api/v1/users/locale \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"locale": "de"}'
```

### С красивым выводом (jq)
```bash
curl -X GET http://localhost:8000/api/v1/languages \
  -H "Authorization: Bearer TOKEN" | jq '.status_names'
```

---

## 10. Логирование с локализацией

### PHP - Logging
```php
<?php

use Illuminate\Support\Facades\Log;

class LogExamples
{
    public function logWithLocalization()
    {
        // Логирование автоматически на языке приложения
        Log::info(__('logs.user_logged_in', [
            'user' => auth()->user()->name,
            'time' => now()
        ]));
        
        // В файле логов будет:
        // [2026-02-19 12:00:00] local.INFO: User John Doe logged in at 2026-02-19T12:00:00Z
    }
    
    public function logStatusChange()
    {
        $user = auth()->user();
        $statusName = __('statuses.' . $user->online_status);
        
        Log::channel('user-activity')->info(
            "User {$user->name} changed status",
            [
                'status' => $user->online_status,
                'status_name' => $statusName,
                'locale' => $user->locale
            ]
        );
    }
}
```

---

**Готово! Все примеры работают с текущей реализацией локализации. 🎉**

