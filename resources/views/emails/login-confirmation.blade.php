<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: #f9f9f9;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background: white;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 4px;
            margin: 20px 0;
            font-weight: bold;
        }
        .footer {
            font-size: 12px;
            color: #666;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .device-info {
            background: #f0f0f0;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
            font-size: 14px;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 12px;
            border-radius: 4px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Подтверждение входа</h1>
        </div>
        <div class="content">
            <p>Привет, <strong>{{ $user->name }}</strong>!</p>

            <p>Мы обнаружили попытку входа в ваш аккаунт с нового устройства:</p>

            <div class="device-info">
                <strong>📱 Устройство:</strong> {{ $deviceName }}<br>
                <strong>🕐 Время:</strong> {{ now()->format('d.m.Y H:i') }}<br>
            </div>

            <p>Если это были вы, подтвердите вход, нажав на кнопку ниже:</p>

            <a href="{{ $confirmationUrl }}" class="button">✅ Подтвердить вход</a>

            <p>Или скопируйте эту ссылку в браузер:</p>
            <p style="word-break: break-all; background: #f0f0f0; padding: 10px; border-radius: 4px; font-size: 12px;">
                {{ $confirmationUrl }}
            </p>

            <div class="warning">
                <strong>⚠️ Внимание:</strong> Ссылка действительна только <strong>3 часа</strong> (до {{ $expiresAt }})
            </div>

            <p><strong>Это не ваша попытка входа?</strong></p>
            <p>Если вы не авторизовались, проигнорируйте это письмо. Ваш аккаунт остается защищен.</p>

            <div class="footer">
                <p>Это автоматическое письмо. Пожалуйста, не отвечайте на него.</p>
                <p>© {{ date('Y') }} QMS-API. Все права защищены.</p>
            </div>
        </div>
    </div>
</body>
</html>

