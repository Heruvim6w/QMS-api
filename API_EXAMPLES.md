# QMS API - Примеры использования

## 🔐 Аутентификация

### 1. Регистрация пользователя

```bash
curl -X POST http://localhost:8000/api/v1/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "SecurePass123!",
    "password_confirmation": "SecurePass123!"
  }'
```

**Ответ (201 Created):**
```json
{
  "user": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "name": "John Doe",
    "email": "john@example.com",
    "uin": "12345678",
    "username": null
  },
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "token_type": "bearer",
  "expires_in": 3600
}
```

### 2. Вход (новое устройство)

```bash
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{
    "login": "john@example.com",
    "password": "SecurePass123!",
    "device_name": "iPhone 13"
  }'
```

**Ответ (200 OK) - новое устройство:**
```json
{
  "message": "Confirmation email sent. Link is valid for 3 hours.",
  "requires_confirmation": true
}
```

### 3. Подтверждение по email

```bash
curl -X POST http://localhost:8000/api/v1/login/confirm \
  -H "Content-Type: application/json" \
  -d '{
    "token": "550e8400-e29b-41d4-a716-446655440000"
  }'
```

**Ответ (200 OK):**
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "token_type": "bearer",
  "expires_in": 3600
}
```

### 4. Получение текущего профиля

```bash
curl -X GET http://localhost:8000/api/v1/me \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
```

**Ответ (200 OK):**
```json
{
  "id": "550e8400-e29b-41d4-a716-446655440000",
  "name": "John Doe",
  "email": "john@example.com",
  "uin": "12345678",
  "username": "john_doe",
  "status": "online",
  "online_status": "chatty",
  "custom_status": "На встрече 🎯",
  "last_seen_at": null,
  "locale": "ru",
  "created_at": "2026-02-19T10:00:00Z"
}
```

## 👤 Управление профилем

### 1. Установка юзернейма

```bash
curl -X POST http://localhost:8000/api/v1/users/username \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "john_doe"
  }'
```

**Ответ (200 OK):**
```json
{
  "status": "success",
  "username": "john_doe"
}
```

### 2. Поиск пользователя

```bash
# По UIN
curl -X GET "http://localhost:8000/api/v1/users/search?query=12345678" \
  -H "Authorization: Bearer <token>"

# По username
curl -X GET "http://localhost:8000/api/v1/users/search?query=john_doe" \
  -H "Authorization: Bearer <token>"
```

**Ответ (200 OK):**
```json
{
  "id": "550e8400-e29b-41d4-a716-446655440000",
  "name": "John Doe",
  "uin": "12345678",
  "username": "john_doe",
  "status": "online",
  "online_status": "chatty",
  "custom_status": "На встрече 🎯",
  "last_seen_at": null
}
```

### 3. Установка статуса

```bash
curl -X POST http://localhost:8000/api/v1/users/status \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "online_status": "chatty",
    "custom_status": "На встрече 🎯"
  }'
```

**Ответ (200 OK):**
```json
{
  "status": "success",
  "online_status": "chatty",
  "display_status": "Готов поболтать - На встрече 🎯"
}
```

### 4. Список доступных статусов

```bash
curl -X GET http://localhost:8000/api/v1/users/status/available \
  -H "Authorization: Bearer <token>"
```

**Ответ (200 OK):**
```json
{
  "statuses": {
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

### 5. Смена языка

```bash
curl -X PUT http://localhost:8000/api/v1/users/locale \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "locale": "ru"
  }'
```

**Ответ (200 OK):**
```json
{
  "status": "success",
  "locale": "ru",
  "language_name": "Русский"
}
```

### 6. Список активных сеансов

```bash
curl -X GET http://localhost:8000/api/v1/sessions \
  -H "Authorization: Bearer <token>"
```

**Ответ (200 OK):**
```json
[
  {
    "id": 1,
    "device_name": "iPhone 13",
    "ip_address": "192.168.1.1",
    "confirmed_at": "2026-02-19T10:00:00Z",
    "expires_at": "2026-02-26T10:00:00Z"
  },
  {
    "id": 2,
    "device_name": "MacBook Pro",
    "ip_address": "192.168.1.2",
    "confirmed_at": "2026-02-18T15:30:00Z",
    "expires_at": "2026-02-25T15:30:00Z"
  }
]
```

### 7. Завершение сеанса (выход с устройства)

```bash
curl -X DELETE http://localhost:8000/api/v1/sessions/1 \
  -H "Authorization: Bearer <token>"
```

**Ответ (200 OK):**
```json
{
  "status": "Session ended"
}
```

## 💬 Чаты

### 1. Список всех чатов

```bash
curl -X GET http://localhost:8000/api/v1/chats \
  -H "Authorization: Bearer <token>"
```

**Ответ (200 OK):**
```json
[
  {
    "id": 1,
    "type": "private",
    "name": null,
    "users": [
      {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "name": "John Doe",
        "uin": "12345678"
      },
      {
        "id": "550e8400-e29b-41d4-a716-446655440001",
        "name": "Jane Smith",
        "uin": "87654321"
      }
    ],
    "last_message": {
      "id": 1,
      "content": "Hello!",
      "created_at": "2026-02-19T10:30:00Z"
    },
    "unread_count": 2,
    "is_muted": false
  }
]
```

### 2. Создание группового чата

```bash
curl -X POST http://localhost:8000/api/v1/chats \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Project Team",
    "user_ids": [
      "550e8400-e29b-41d4-a716-446655440001",
      "550e8400-e29b-41d4-a716-446655440002"
    ]
  }'
```

**Ответ (201 Created):**
```json
{
  "id": 2,
  "type": "group",
  "name": "Project Team",
  "users": [...],
  "created_at": "2026-02-19T11:00:00Z"
}
```

### 3. Добавление пользователя в группу

```bash
curl -X POST http://localhost:8000/api/v1/chats/2/add-user \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": "550e8400-e29b-41d4-a716-446655440003"
  }'
```

**Ответ (200 OK):**
```json
{
  "status": "user_added"
}
```

### 4. Отключение уведомлений для чата

```bash
curl -X POST http://localhost:8000/api/v1/chats/1/mute \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "is_muted": true
  }'
```

**Ответ (200 OK):**
```json
{
  "status": "mute_updated"
}
```

## 💌 Сообщения

### 1. Отправка сообщения в существующий чат

```bash
curl -X POST http://localhost:8000/api/v1/messages \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "chat_id": 1,
    "content": "Hello! How are you?",
    "type": "text"
  }'
```

**Ответ (201 Created):**
```json
{
  "id": 1,
  "chat_id": 1,
  "status": "sent",
  "created_at": "2026-02-19T11:15:00Z"
}
```

### 2. Отправка сообщения новому пользователю (создание приватного чата)

```bash
curl -X POST http://localhost:8000/api/v1/messages \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "receiver_id": "550e8400-e29b-41d4-a716-446655440001",
    "content": "Hi Jane!",
    "type": "text"
  }'
```

**Ответ (201 Created):**
```json
{
  "id": 2,
  "chat_id": 3,
  "status": "sent",
  "created_at": "2026-02-19T11:20:00Z"
}
```

### 3. Получение истории чата

```bash
curl -X GET "http://localhost:8000/api/v1/messages?chat_id=1&limit=50&offset=0" \
  -H "Authorization: Bearer <token>"
```

**Ответ (200 OK):**
```json
[
  {
    "id": 1,
    "chat_id": 1,
    "sender": {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "name": "John Doe",
      "uin": "12345678"
    },
    "content": "Hello! How are you?",
    "type": "text",
    "attachments": [],
    "is_read": true,
    "created_at": "2026-02-19T11:15:00Z"
  }
]
```

### 4. Загрузка файла к сообщению

```bash
curl -X POST http://localhost:8000/api/v1/messages/1/upload \
  -H "Authorization: Bearer <token>" \
  -F "file=@/path/to/file.jpg"
```

**Ответ (200 OK):**
```json
{
  "status": "file_uploaded",
  "attachment_id": 1,
  "file_path": "uploads/messages/file.jpg",
  "file_size": 102400,
  "mime_type": "image/jpeg"
}
```

## 📞 Звонки (WebRTC)

### 1. Инициация звонка

```bash
curl -X POST http://localhost:8000/api/v1/calls/initiate \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "chat_id": 1,
    "callee_id": "550e8400-e29b-41d4-a716-446655440001",
    "type": "video",
    "sdp_offer": "v=0\r\no=- 1234567890 1 IN IP4 127.0.0.1\r\n..."
  }'
```

**Ответ (201 Created):**
```json
{
  "call_uuid": "550e8400-e29b-41d4-a716-446655440100",
  "chat_id": 1,
  "caller_id": "550e8400-e29b-41d4-a716-446655440000",
  "callee_id": "550e8400-e29b-41d4-a716-446655440001",
  "type": "video",
  "status": "ringing"
}
```

### 2. Ответ на звонок

```bash
curl -X POST http://localhost:8000/api/v1/calls/answer \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "call_uuid": "550e8400-e29b-41d4-a716-446655440100",
    "sdp_answer": "v=0\r\no=- 1234567890 1 IN IP4 127.0.0.2\r\n..."
  }'
```

**Ответ (200 OK):**
```json
{
  "status": "active",
  "call_uuid": "550e8400-e29b-41d4-a716-446655440100"
}
```

### 3. Обмен ICE кандидатами

```bash
curl -X POST http://localhost:8000/api/v1/calls/ice-candidate \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "call_uuid": "550e8400-e29b-41d4-a716-446655440100",
    "candidate": "candidate:1 1 UDP 2122252543 192.168.1.1 56789 typ host"
  }'
```

**Ответ (200 OK):**
```json
{
  "status": "candidate_added",
  "call_uuid": "550e8400-e29b-41d4-a716-446655440100"
}
```

## 📎 Вложения

### 1. Скачивание файла

```bash
curl -X GET http://localhost:8000/api/v1/attachments/1/download \
  -H "Authorization: Bearer <token>" \
  -o file.jpg
```

### 2. Удаление вложения

```bash
curl -X DELETE http://localhost:8000/api/v1/attachments/1 \
  -H "Authorization: Bearer <token>"
```

**Ответ (200 OK):**
```json
{
  "status": "deleted"
}
```

## 🔄 Управление токеном

### 1. Обновление токена

```bash
curl -X POST http://localhost:8000/api/v1/refresh \
  -H "Authorization: Bearer <old_token>"
```

**Ответ (200 OK):**
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "token_type": "bearer",
  "expires_in": 3600
}
```

### 2. Выход (инвалидация токена)

```bash
curl -X POST http://localhost:8000/api/v1/logout \
  -H "Authorization: Bearer <token>"
```

**Ответ (200 OK):**
```json
{
  "message": "Successfully logged out"
}
```

## ⚠️ Обработка ошибок

### Ошибка валидации (422)

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

### Отсутствие прав доступа (403)

```json
{
  "error": "Access denied - not a member of this chat"
}
```

### Ресурс не найден (404)

```json
{
  "error": "Resource not found"
}
```

### Без аутентификации (401)

```json
{
  "message": "Unauthenticated."
}
```

## 💡 Полезные советы

1. **Всегда используйте Authorization заголовок:**
   ```
   Authorization: Bearer <your_jwt_token>
   ```

2. **Для новых чатов используйте receiver_id:**
   - Если chat_id пустой, используйте receiver_id
   - Приватный чат создастся автоматически

3. **UIN vs Username:**
   - UIN: 8 цифр, выдаётся при регистрации
   - Username: 3-20 символов, опционально
