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

        .info-box {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            color: #004085;
            padding: 12px;
            border-radius: 4px;
            margin: 15px 0;
        }

        .warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 12px;
            border-radius: 4px;
            margin: 15px 0;
        }

        .welcome-section {
            background: #f0f8ff;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🎉 Добро пожаловать в QMS!</h1>
    </div>
    <div class="content">
        <p>Привет, <strong>{{ $user->name }}</strong>!</p>

        <p>Спасибо, что зарегистрировались в нашем приложении. Чтобы активировать ваш аккаунт, пожалуйста, подтвердите ваш адрес электронной почты:</p>

        <a href="{{ $confirmationUrl }}" class="button">✅ Подтвердить почту</a>

        <p>Или скопируйте эту ссылку в браузер:</p>
        <p style="word-break: break-all; background: #f0f0f0; padding: 10px; border-radius: 4px; font-size: 12px;">
            {{ $confirmationUrl }}
        </p>

        <div class="welcome-section">
            <p><strong>Ваш профиль:</strong></p>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li><strong>Имя:</strong> {{ $user->name }}</li>
                <li><strong>Email:</strong> {{ $user->email }}</li>
                <li><strong>UIN:</strong> {{ $user->uin }} (8-значный идентификатор)</li>
            </ul>
        </div>

        <div class="info-box">
            <strong>ℹ️ Информация:</strong> Ссылка подтверждения действительна <strong>3 часа</strong> (до {{ $expiresAt }}). После этого вам нужно будет запросить новое письмо.
        </div>

        <p><strong>Что дальше?</strong></p>
        <p>После подтверждения почты вы сможете:</p>
        <ul>
            <li>Войти в свой аккаунт</li>
            <li>Создавать и участвовать в чатах</li>
            <li>Отправлять зашифрованные сообщения</li>
            <li>Совершать видео и аудио звонки</li>
        </ul>

        <p><strong>Возникли проблемы?</strong></p>
        <p>Если кнопка не работает, скопируйте ссылку выше и откройте её в браузере.</p>

        <div class="footer">
            <p>Это автоматическое письмо. Пожалуйста, не отвечайте на него.</p>
            <p>© {{ date('Y') }} QMS-API. Все права защищены.</p>
        </div>
    </div>
</div>
</body>
</html>

