# Single Dockerfile reused for every PHP version via build arg.
ARG PHP_VERSION=8.2
FROM php:${PHP_VERSION}-fpm

# mlocati's installer handles extension + arch + version differences uniformly
# across PHP 7.2 -> 8.4, so we don't hand-roll gd/freetype flags per version.
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions \
      pdo_mysql \
      mysqli \
      gd \
      zip \
      intl \
      bcmath \
      mbstring \
      opcache \
      exif \
      pcntl \
      soap \
      xdebug \
      @composer

WORKDIR /var/www/html
