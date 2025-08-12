set -e

mkdir -p config/jwt
echo "$JWT_PRIVATE_KEY" > config/jwt/private.pem
echo "$JWT_PUBLIC_KEY" > config/jwt/public.pem
chmod 600 config/jwt/*.pem

composer install --no-interaction --prefer-dist --optimize-autoloader

php bin/console cache:clear --env=prod
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

envsubst '$PORT' < /etc/nginx/conf.d/default.conf > /etc/nginx/conf.d/default_render.conf
mv /etc/nginx/conf.d/default_render.conf /etc/nginx/conf.d/default.conf

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
