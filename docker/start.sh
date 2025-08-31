#!/bin/sh

cd /var/www/html

# Устанавливаем зависимости, если нет vendor/
if [ ! -d vendor ]; then
    composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
fi

# Создаем .env если его нет
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Генерируем APP_KEY, если он пустой
if ! grep -q '^APP_KEY=' .env || grep -q '^APP_KEY=$' .env; then
    php artisan key:generate --force
fi

# Генерируем JWT_SECRET, если он пустой
if ! grep -q '^JWT_SECRET=' .env || grep -q '^JWT_SECRET=$' .env; then
    php artisan jwt:secret --force
fi

# Ждем доступности базы данных
echo "Waiting for database..."
while ! nc -z qms-db 5432; do
  sleep 1
done

# Выполняем миграции
php artisan migrate --force

# Очищаем кэш конфигурации Laravel
php artisan config:clear

# Запускаем supervisor для управления nginx и php-fpm
supervisord -c /etc/supervisord.conf

# Бесконечный цикл чтобы контейнер не завершался
while true; do
    sleep 60
done
