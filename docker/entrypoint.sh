#!/bin/sh
set -eu

cd /var/www

mkdir -p \
  storage/logs \
  storage/app/public/uploads \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/views \
  bootstrap/cache

chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true

if [ ! -f .env ] && [ -f .env.example ]; then
  cp .env.example .env
fi

lock_hash() {
  if [ -f composer.lock ]; then
    sha1sum composer.json composer.lock | sha1sum | awk '{print $1}'
  else
    sha1sum composer.json | awk '{print $1}'
  fi
}

clear_bootstrap_cache() {
  find bootstrap/cache -mindepth 1 -maxdepth 1 -type f -name '*.php' -delete 2>/dev/null || true
}

ensure_dependencies() {
  if [ ! -f composer.json ]; then
    return
  fi

  current_hash="$(lock_hash)"
  stored_hash="$(cat vendor/.composer-state 2>/dev/null || true)"
  install_flags="${COMPOSER_INSTALL_FLAGS:---no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts}"

  if [ ! -f vendor/autoload.php ] || [ "$current_hash" != "$stored_hash" ]; then
    clear_bootstrap_cache
    composer install $install_flags
    mkdir -p vendor
    printf '%s' "$current_hash" > vendor/.composer-state
  fi
}

wait_for_database() {
  if [ ! -f .env ]; then
    return
  fi

  php <<'PHP'
<?php
$env = @parse_ini_file('.env');
if (!$env) {
    exit(0);
}

$connection = $env['DB_CONNECTION'] ?? null;
if (!in_array($connection, ['pgsql', 'mysql'], true)) {
    exit(0);
}

$host = $env['DB_HOST'] ?? 'db';
$port = (int) ($env['DB_PORT'] ?? ($connection === 'pgsql' ? 5432 : 3306));

for ($attempt = 0; $attempt < 60; $attempt++) {
    $socket = @fsockopen($host, $port, $errno, $errstr, 2);
    if ($socket) {
        fclose($socket);
        exit(0);
    }

    sleep(2);
}

fwrite(STDERR, "Database {$host}:{$port} is not reachable.\n");
exit(1);
PHP
}

ensure_app_key() {
  if [ ! -f .env ]; then
    return
  fi

  if ! grep -Eq '^APP_KEY=base64:' .env; then
    php artisan key:generate --force
  fi
}

discover_packages() {
  clear_bootstrap_cache
  php artisan package:discover --ansi
}

run_migrations() {
  if [ "${APP_AUTO_MIGRATE:-true}" != "true" ]; then
    return
  fi

  php artisan migrate --force
}

seed_if_needed() {
  if [ "${APP_AUTO_SEED:-true}" != "true" ]; then
    return
  fi

  user_count="$(
    php <<'PHP'
<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
echo \App\Models\User::count();
PHP
  )"

  if [ "${user_count:-0}" = "0" ]; then
    php artisan db:seed --force
  fi
}

ensure_storage_link() {
  php artisan storage:link || true
}

ensure_dependencies
wait_for_database
ensure_app_key
discover_packages
run_migrations
seed_if_needed
ensure_storage_link

exec "$@"
