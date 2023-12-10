FROM php:8.2-apache

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

RUN apt update && apt install -y zlib1g-dev libpng-dev && rm -rf /var/lib/apt/lists/*

RUN apt-get update && apt-get install -y libfreetype-dev libjpeg62-turbo-dev libpng-dev 

RUN docker-php-ext-install mysqli pdo pdo_mysql fileinfo gettext opcache

RUN docker-php-ext-enable mysqli pdo pdo_mysql fileinfo gettext opcache

RUN apt-get update && apt-get install -y \
		libfreetype-dev \
		libjpeg62-turbo-dev \
		libpng-dev \
	&& docker-php-ext-configure gd --with-freetype --with-jpeg \
	&& docker-php-ext-install -j$(nproc) gd


RUN a2enmod rewrite
