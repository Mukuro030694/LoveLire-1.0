FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libicu-dev libzip-dev libssl-dev pkg-config gnupg curl supervisor nginx \
    && docker-php-ext-install intl pdo pdo_pgsql opcache \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN mkdir -p var/uploads/covers && \
    mkdir -p public && \
    rm -rf public/uploads && \
    ln -s ../var/uploads public/uploads && \
    chown -R www-data:www-data var/uploads && \
    chmod -R 775 var/uploads

COPY nginx/default.conf /etc/nginx/conf.d/default.conf
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY start.sh /usr/local/bin/start.sh

RUN chmod +x /usr/local/bin/start.sh

CMD ["/usr/local/bin/start.sh"]
