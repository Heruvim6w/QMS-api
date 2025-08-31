# Используем многоступенчатую сборку
FROM php:8.2-fpm-alpine AS base

# Устанавливаем системные зависимости
RUN apk update && apk add --no-cache \
    nginx \
    libzip-dev \
    zip \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    postgresql-dev \
    libxml2-dev \
    oniguruma-dev \
    nodejs \
    npm \
    curl \
    openssl \
    bash \
    netcat-openbsd \
    autoconf \
    g++ \
    make

# Устанавливаем расширения PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo pdo_pgsql pgsql zip bcmath mbstring exif pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis

# Устанавливаем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Создаем директорию приложения
RUN mkdir -p /var/www/html

# Устанавливаем рабочую директорию
WORKDIR /var/www/html

# Копируем конфигурации
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Копируем только файлы, необходимые для установки зависимостей
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Копируем остальные файлы приложения
COPY . .

# Копируем скрипт запуска
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Устанавливаем supervisor
RUN apk add --no-cache supervisor

# Копируем конфиг supervisor
COPY docker/supervisor/supervisord.conf /etc/supervisord.conf

# Создаем недостающие директории и устанавливаем права
RUN mkdir -p storage/framework/{sessions,views,cache} \
    && mkdir -p bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# Создаем директорию для логов supervisor
RUN mkdir -p /var/log/supervisor && chown -R www-data:www-data /var/log/supervisor

# Открываем порты
EXPOSE 8000

# Запускаем приложение через скрипт
CMD ["/usr/local/bin/start.sh"]
