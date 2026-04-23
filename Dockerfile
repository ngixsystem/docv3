FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl

RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    zip \
    bcmath \
    opcache

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# OPcache tuning
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=256'; \
    echo 'opcache.interned_strings_buffer=16'; \
    echo 'opcache.max_accelerated_files=20000'; \
    echo 'opcache.validate_timestamps=1'; \
    echo 'opcache.revalidate_freq=2'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'realpath_cache_size=4096K'; \
    echo 'realpath_cache_ttl=600'; \
} > /usr/local/etc/php/conf.d/opcache-tuning.ini

WORKDIR /var/www

# Сначала только composer.json — используем layer cache
COPY src/composer.json src/composer.lock* ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Копируем весь исходный код
COPY src/ .
RUN composer dump-autoload --optimize

# Создаём директории (попадут в образ — named volumes инициализируются из образа)
RUN mkdir -p \
    storage/logs \
    storage/app/public/uploads \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    bootstrap/cache \
    && echo "" > bootstrap/cache/.gitignore \
    && chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache vendor

EXPOSE 9000
CMD ["php-fpm"]
