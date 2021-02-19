FROM php:7.4-apache

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

RUN rm /etc/apt/preferences.d/no-debian-php \
 && apt-get update -y \
 && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libxml2-dev \
    php-soap \
    git \
 && apt-get clean -y \
 && docker-php-ext-install \
    pdo_mysql \
    mysqli \
    zip \
    exif \
    gd \
    soap \
    intl \
 && pecl install xdebug-2.9.6 \
 && a2enmod rewrite headers \
 && sed -i 's!/var/www/html!/var/www/public!g' /etc/apache2/sites-available/000-default.conf \
 && mv /var/www/html /var/www/public \
 && curl -sS https://getcomposer.org/installer \
    | php -- --install-dir=/usr/local/bin --filename=composer \
 && echo "AllowEncodedSlashes On" >> /etc/apache2/apache2.conf

WORKDIR /var/www
