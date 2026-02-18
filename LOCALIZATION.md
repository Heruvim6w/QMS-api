# Система локализации QMS API

## Обзор

Реализована полная система локализации приложения, которая позволяет пользователям выбирать язык интерфейса и получать локализованные данные из API.

## Архитектура

### 1. **Middleware `SetLocale`** (`app/Http/Middleware/SetLocale.php`)

Автоматически определяет язык для каждого запроса с приоритетом:

1. **Язык авторизованного пользователя** из БД (`users.locale`)
2. **Заголовок `Accept-Language`** из HTTP-запроса
3. **Язык по умолчанию** - `en` (English)

```php
$middleware->append(\App\Http\Middleware\SetLocale::class);
```

### 2. **Service `LocalizationService`** (`app/Services/LocalizationService.php`)

Центральный сервис для работы с локализацией:

- `getSupportedLocales()` - получить список поддерживаемых языков
- `getLanguageNames()` - получить локализованные названия языков
- `getStatusNames()` - получить локализованные названия статусов
- `updateUserLocale()` - обновить язык пользователя
- `getCurrentLocale()` - получить текущий язык приложения

### 3. **Файлы переводов** (`resources/lang/{locale}/*.php`)

Организованы по языкам:

```
resources/lang/
├── en/
│   ├── languages.php  (названия языков на английском)
│   └── statuses.php   (названия статусов на английском)
├── ru/
│   ├── languages.php  (названия языков на русском)
│   └── statuses.php   (названия статусов на русском)
└── de/
    ├── languages.php  (названия языков на немецком)
    └── statuses.php   (названия статусов на немецком)
```

### 4. **API Endpoints**

#### Получить список поддерживаемых языков и локализованные данные

```http
GET /api/v1/languages
Authorization: Bearer {token}

Response:
{
  "supported_locales": ["en", "ru", "de"],
  "current_locale": "ru",
  "language_names": {
    "en": "Английский",
    "ru": "Русский",
    "de": "Немецкий"
  },
  "status_names": {
    "online": "Онлайн",
    "chatty": "Готов поболтать",
    "angry": "Злой",
    "depressed": "Депрессия",
    "home": "Дома",
    "work": "На работе",
    "eating": "Кушаю",
    "away": "Отошёл",
    "unavailable": "Не доступен",
    "busy": "Занят",
    "do_not_disturb": "Не беспокоить"
  }
}
```

#### Обновить язык пользователя

```http
PUT /api/v1/users/locale
Authorization: Bearer {token}
Content-Type: application/json

Request:
{
  "locale": "ru"
}

Response:
{
  "status": "success",
  "locale": "ru",
  "language_name": "Русский"
}
```

### 5. **Модель User**

Добавлено поле `locale`:

```php
protected $fillable = [
    // ... другие поля
    'locale', // Предпочитаемый язык пользователя
];
```

Миграция: `2026_02_19_000000_add_locale_to_users_table.php`

```php
$table->string('locale')
    ->default('en')
    ->nullable()
    ->after('custom_status')
    ->comment('User preferred language (en, ru, de, etc.)');
```

### 6. **Request валидация** (`app/Http/Requests/UpdateUserLocaleRequest.php`)

Валидирует что `locale` - это один из поддерживаемых языков:

```php
public function rules(): array
{
    return [
        'locale' => ['required', 'string', 'in:en,ru,de'],
    ];
}
```

## Как это работает

### Сценарий 1: Первый вход пользователя

1. Клиент определяет язык ОС
2. Клиент отправляет заголовок `Accept-Language: ru-RU,ru;q=0.9`
3. Middleware парсит заголовок и устанавливает язык на `ru`
4. API возвращает локализованные данные на русском

### Сценарий 2: Авторизованный пользователь

1. Пользователь логинится
2. Middleware проверяет `auth()->user()->locale`
3. Если есть, язык устанавливается на `ru` (из профиля)
4. Все API ответы возвращают локализованные данные

### Сценарий 3: Смена языка в настройках

1. Клиент отправляет `PUT /api/v1/users/locale` с `{"locale": "de"}`
2. Backend обновляет `users.locale` на `de`
3. Все последующие запросы используют немецкий язык

## Использование в коде

### Получить локализованную строку

```php
// В контроллере или сервисе
$statusName = __('statuses.online'); // Получит "Онлайн" или "Online" в зависимости от app()->getLocale()
```

### Получить все локализованные названия статусов

```php
$localizationService = new LocalizationService();
$statusNames = $localizationService->getStatusNames();

// Результат:
[
    'online' => 'Онлайн',
    'chatty' => 'Готов поболтать',
    // ...
]
```

## Добавление нового языка

1. Создать директорию: `resources/lang/{locale}/`
2. Создать файлы переводов:
   - `languages.php`
   - `statuses.php`
3. Обновить `SetLocale` middleware, добавив язык в `SUPPORTED_LOCALES`
4. Обновить `UpdateUserLocaleRequest`, добавив язык в правила валидации
5. Обновить `LocalizationService::SUPPORTED_LOCALES`

Пример для добавления испанского:

```php
// resources/lang/es/languages.php
return [
    'en' => 'Inglés',
    'ru' => 'Ruso',
    'de' => 'Alemán',
    'es' => 'Español',
];

// resources/lang/es/statuses.php
return [
    'online' => 'En línea',
    'chatty' => 'Hablador',
    // ...
];
```

## Фронтенд интеграция

Рекомендуемый подход:

```javascript
// 1. При старте приложения
const systemLanguage = navigator.language.split('-')[0]; // 'ru', 'en', etc
const userLanguage = localStorage.getItem('userLanguage') || systemLanguage;
setAppLanguage(userLanguage);

// 2. Сохранить в localStorage после логина
const userData = await loginUser(email, password);
localStorage.setItem('userLanguage', userData.user.locale);
setAppLanguage(userData.user.locale);

// 3. При смене языка в настройках
const response = await updateLocale('de');
localStorage.setItem('userLanguage', 'de');
setAppLanguage('de');

// 4. Получить список доступных языков
const { language_names } = await fetchLanguages();
// Отобразить в меню выбора языка
```

## Замечания

- Языки кэшируются на фронтенде в `localStorage` для быстродействия
- При каждом запросе с новым устройством используется `Accept-Language` header
- После авторизации используется язык из профиля пользователя
- Все строки на бэкенде локализованы через файлы переводов
- Поддерживаются 3 языка: English, Русский, Deutsch

