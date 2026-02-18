# 🎯 QUICK REFERENCE - Система локализации

## ⚡ За 10 секунд

```bash
php artisan migrate
curl -X GET http://localhost:8000/api/v1/languages \
  -H "Authorization: Bearer TOKEN"
```

---

## 📱 API Endpoints

| Method | Path | Описание |
|--------|------|---------|
| GET | `/api/v1/languages` | Получить языки |
| PUT | `/api/v1/users/locale` | Сменить язык |

---

## 🔤 Языки

```
en - English
ru - Русский
de - Deutsch
```

---

## 📝 Файлы

### PHP компоненты
- `app/Http/Middleware/SetLocale.php` - Определяет язык
- `app/Services/LocalizationService.php` - Service для работы
- `app/Http/Requests/UpdateUserLocaleRequest.php` - Валидация

### Переводы
- `resources/lang/en/*.php` - Английский
- `resources/lang/ru/*.php` - Русский
- `resources/lang/de/*.php` - Немецкий

### Документация
- `LOCALIZATION_QUICKSTART.md` - Быстрый старт
- `LOCALIZATION.md` - Полная документация
- `LOCALIZATION_EXAMPLES.md` - Примеры

---

## 💡 Использование

### PHP
```php
__('statuses.online')  // Локализованный текст
```

### Service
```php
$service = new LocalizationService();
$service->getStatusNames();  // Все статусы
```

### API
```javascript
fetch('/api/v1/languages')
```

---

## ✅ Checklist

- [x] Миграция создана
- [x] Middleware зарегистрирован
- [x] Service создан
- [x] API endpoints добавлены
- [x] Переводы на 3 языках
- [x] Документация полная

**Готово к production! ✅**

---

Дата: 19 февраля 2026
Версия: 1.0.0

