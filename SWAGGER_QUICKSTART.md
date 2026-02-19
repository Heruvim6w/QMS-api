# ⚡ Swagger Documentation - Quick Start Guide

## 🚀 Быстрый старт

### 1. Сгенерировать Swagger документацию
```bash
cd /Users/andrei/projects/QMS-api
php artisan l5-swagger:generate
```

### 2. Открыть в браузере
```
http://localhost:8000/api/documentation
```

### 3. Готово! ✅

---

## 📚 Документация в репозитории

### Главные файлы
- **SWAGGER_DOCUMENTATION.md** - полное руководство
- **API_EXAMPLES.md** - примеры curl команд

### Файлы Swagger аннотаций
```
app/Swagger/
├── OpenAPI.php              # Основная конфигурация
├── Schemas.php              # Response schemas
├── RequestSchemas.php       # Request schemas
├── ModelSchemas.php         # Database models
├── ApiDocumentation.php     # High-level guides
├── HttpResponses.php        # Status codes
└── CompletionSummary.php    # Full summary
```

---

## 🎯 Использование

### Просмотр в Swagger UI
1. Откройте http://localhost:8000/api/documentation
2. Нажмите "Authorize" и введите token
3. Нажмите "Try it out" для любого endpoint'а
4. Отправьте запрос и смотрите результат

### Тестирование с curl
```bash
# Регистрация
curl -X POST http://localhost:8000/api/v1/register \
  -H "Content-Type: application/json" \
  -d '{"name":"John","email":"john@example.com","password":"Pass123!","password_confirmation":"Pass123!"}'

# Получить профиль
curl -X GET http://localhost:8000/api/v1/me \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Экспорт для Postman
1. Откройте http://localhost:8000/documentation?api-docs.json
2. Сохраните JSON файл
3. В Postman: Import → Select File
4. Готово к тестированию!

---

## 🔧 Проблемы и решения

### Если Swagger UI не загружается
```bash
# Перегенерируйте
php artisan l5-swagger:generate

# Очистите кеш
php artisan config:clear
php artisan cache:clear
```

### Если endpoint'ы не отображаются
```bash
# Убедитесь, что файлы находятся в app/Swagger/
ls app/Swagger/

# Перегенерируйте документацию
php artisan l5-swagger:generate
```

### Если JWT не работает
```bash
# Добавьте Authorization header в Swagger UI
1. Нажмите кнопку "Authorize"
2. Скопируйте JWT token
3. Вставьте в формат: Bearer <token>
4. Нажмите "Authorize"
```

---

## ✨ Возможности

- ✅ Полная документация всех API endpoint'ов
- ✅ Примеры запросов и ответов
- ✅ Интерактивное тестирование
- ✅ Автоматическая генерация SDK
- ✅ Поддержка Postman
- ✅ OpenAPI JSON/YAML экспорт

---

## 🎓 Полезные ресурсы

- Swagger UI: http://localhost:8000/api/documentation
- OpenAPI Spec: https://swagger.io/specification/
- L5 Swagger Docs: https://github.com/DarkaOnline/L5-Swagger
