#!/bin/bash
# Скрипт первоначальной инициализации СЭД DocV3
set -e

echo "🚀 Запуск СЭД DocV3..."

# Запуск контейнеров
docker compose up -d --build

echo "⏳ Ожидание готовности базы данных..."
sleep 10

# Проверка статуса PostgreSQL
docker compose exec db pg_isready -U docuser -d docv3 || (sleep 5 && docker compose exec db pg_isready -U docuser -d docv3)

echo "📦 Установка зависимостей Composer..."
docker compose exec app composer install --no-interaction --prefer-dist

echo "🔑 Генерация ключа приложения..."
docker compose exec app php artisan key:generate --force

echo "🗄️  Запуск миграций..."
docker compose exec app php artisan migrate --force

echo "🌱 Заполнение демо-данными..."
docker compose exec app php artisan db:seed --force

echo "🔗 Создание символической ссылки для файлов..."
docker compose exec app php artisan storage:link

echo ""
echo "✅ СЭД DocV3 готова к работе!"
echo "🌐 Откройте в браузере: http://localhost:8080"
echo ""
echo "Контейнеры:"
docker compose ps
