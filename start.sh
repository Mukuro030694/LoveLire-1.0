#!/bin/bash
set -e

# -----------------------------
# Проверка переменных окружения
# -----------------------------
: "${JWT_PRIVATE_KEY:?Need to set JWT_PRIVATE_KEY}"
: "${JWT_PUBLIC_KEY:?Need to set JWT_PUBLIC_KEY}"
: "${JWT_PASSPHRASE:?Need to set JWT_PASSPHRASE}"

echo "Starting app... ✅"

# -----------------------------
# Настройка порта для Nginx
# -----------------------------
sed -i "s/\$PORT/${PORT:-8080}/g" /etc/nginx/conf.d/default.conf

# -----------------------------
# Создание папки и ключей JWT
# -----------------------------
mkdir -p config/jwt

# Сохраняем ключи в файлы
echo "$JWT_PRIVATE_KEY" > config/jwt/private.pem
echo "$JWT_PUBLIC_KEY" > config/jwt/public.pem

# Проверка и права
if [ ! -s config/jwt/private.pem ] || [ ! -s config/jwt/public.pem ]; then
    echo "ERROR: JWT keys are empty!"
    exit 1
fi

chmod 600 config/jwt/*.pem
ls -l config/jwt/

# -----------------------------
# Установка зависимостей
# -----------------------------
composer install --no-interaction --prefer-dist --optimize-autoloader

# -----------------------------
# Очистка кэша и миграции
# -----------------------------
php bin/console cache:clear --env=prod
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

# -----------------------------
# Запуск supervisord
# -----------------------------
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
