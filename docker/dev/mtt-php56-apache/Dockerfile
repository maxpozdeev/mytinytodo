
FROM php:5.6-apache

RUN docker-php-ext-install mysqli opcache && \
	rm -f /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini && \
	mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
