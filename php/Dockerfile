FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git unzip zip curl libpq-dev libzip-dev libssl-dev libonig-dev libxml2-dev libicu-dev \
    && docker-php-ext-install pdo pdo_pgsql zip intl \
    && pecl install mongodb-2.1.1 \
    && docker-php-ext-enable mongodb \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --optimize-autoloader

RUN git config --global --add safe.directory /var/www/html

RUN chown -R www-data:www-data var

COPY php/start.sh /start.sh
RUN chmod +x /start.sh

CMD ["php-fpm"]
