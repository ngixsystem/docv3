@echo off
chcp 65001 > nul
echo 🚀 Запуск СЭД DocV3...

echo Сборка и запуск контейнеров Docker...
docker compose up -d --build

echo Ожидание готовности базы данных (20 сек)...
timeout /t 20 /nobreak > nul

echo Перезагрузка автозагрузчика...
docker compose exec app composer dump-autoload

echo Установка зависимостей Composer...
docker compose exec app composer install --no-interaction --prefer-dist

echo Генерация ключа приложения...
docker compose exec app php artisan key:generate --force

echo Запуск миграций базы данных...
docker compose exec app php artisan migrate --force

echo Заполнение демо-данными...
docker compose exec app php artisan db:seed --force

echo Создание символической ссылки для файлов...
docker compose exec app php artisan storage:link

echo.
echo ✅ СЭД DocV3 готова к работе!
echo 🌐 Откройте браузер: http://localhost:8080
echo.
docker compose ps
pause
