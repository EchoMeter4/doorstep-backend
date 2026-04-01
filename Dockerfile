FROM php:8.4-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    git \
    curl \
    unzip \
    bash \
    tesseract-ocr \
    tesseract-ocr-data-eng \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install \
        pdo_mysql \
        gd \
        bcmath \
        opcache \
        intl \
        zip \
        exif

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy project files
COPY . .

# Copy config files into place
COPY conf/nginx/nginx-site.conf /etc/nginx/http.d/default.conf
COPY conf/supervisord.conf /etc/supervisord.conf
COPY scripts/00-laravel-deploy.sh /deploy.sh
RUN chmod +x /deploy.sh

# Pre-create writable directories and set ownership
RUN mkdir -p storage/framework/{sessions,views,cache} \
        storage/logs \
        bootstrap/cache && \
    chown -R www-data:www-data storage bootstrap/cache

# Laravel env
ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    COMPOSER_ALLOW_SUPERUSER=1

EXPOSE 80

CMD ["/bin/bash", "-c", "/deploy.sh && /usr/bin/supervisord -c /etc/supervisord.conf"]
