#!/bin/sh

cd /var/www/html

# Создаем .env если его нет
if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate --force
    php artisan jwt:secret --force
fi

# Ждем доступности базы данных
echo "Waiting for database..."
while ! nc -z db 5432; do
  sleep 1
done

# Выполняем миграции
php artisan migrate --force

# Запускаем supervisor для управления nginx и php-fpm
supervisord -c /etc/supervisord.conf

# Бесконечный цикл чтобы контейнер не завершался
while true; do
    sleep 60
done
