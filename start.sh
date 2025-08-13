set -e

sed -i "s/\$PORT/${PORT:-8080}/g" /etc/nginx/conf.d/default.conf

mkdir -p config/jwt
echo "$JWT_PRIVATE_KEY" > config/jwt/private.pem
echo "$JWT_PUBLIC_KEY" > config/jwt/public.pem
chmod 600 config/jwt/*.pem

composer install --no-interaction --prefer-dist --optimize-autoloader

php bin/console cache:clear --env=prod
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
