FROM php:5-apache
RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libmcrypt-dev \
        libpng12-dev \
        libxml2-dev \
    && docker-php-ext-install -j$(nproc) iconv mysqli\
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd
RUN pecl install xdebug 
RUN docker-php-ext-install pdo_mysql mcrypt
RUN a2enmod rewrite
EXPOSE 80
