FROM php:8.4-fpm-alpine

WORKDIR /app

# Install PHP dependencies
RUN set -x \
    && apk update \
    && apk add zlib-dev libzip-dev \
    && docker-php-ext-install zip bcmath \
    && docker-php-ext-enable bcmath \
    && php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer

# Install Supervisor
RUN apk add --no-cache supervisor

# Copy files
COPY headless/supervisord.conf /etc/supervisord.conf
RUN rm headless -rf
COPY --chown="www-data:root" . /app

# Install Composer dependencies
RUN composer install --no-dev

# Start Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
