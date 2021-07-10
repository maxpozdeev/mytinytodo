
FROM php:7.0-apache

RUN docker-php-ext-install mysqli && \
  mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
