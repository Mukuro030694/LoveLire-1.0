#!/bin/bash
set -e

: "${JWT_PRIVATE_KEY:?Need to set JWT_PRIVATE_KEY}"
: "${JWT_PUBLIC_KEY:?Need to set JWT_PUBLIC_KEY}"
: "${JWT_PASSPHRASE:?Need to set JWT_PASSPHRASE}"

echo "Starting app..."

sed -i "s/\$PORT/${PORT:-8080}/g" /etc/nginx/conf.d/default.conf

mkdir -p /var/www/html/config/jwt

echo "$JWT_PRIVATE_KEY" > /var/www/html/config/jwt/private.pem
echo "$JWT_PUBLIC_KEY" > /var/www/html/config/jwt/public.pem

if [ ! -s /var/www/html/config/jwt/private.pem ] || [ ! -s /var/www/html/config/jwt/public.pem ]; then
    echo "ERROR: JWT keys are empty!"
    exit 1
fi

chmod 644 /var/www/html/config/jwt/*.pem
chown www-data:www-data /var/www/html/config/jwt/*.pem

echo "JWT keys created with correct permissions:"
ls -l /var/www/html/config/jwt/

composer install --no-interaction --prefer-dist --optimize-autoloader

php bin/console cache:clear --env=prod
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
