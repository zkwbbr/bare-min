FROM php:8.1-apache-bullseye

# remove Apache version in headers
RUN echo "ServerTokens Prod\n" >> /etc/apache2/apache2.conf
RUN echo "ServerSignature Off\n" >> /etc/apache2/apache2.conf

RUN docker-php-ext-configure opcache --enable-opcache \
    && docker-php-ext-install opcache

RUN yes | pecl install xdebug \
    && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_autostart=off" >> /usr/local/etc/php/conf.d/xdebug.ini

RUN apt-get update && apt-get install -y \
    nano \
    git \
    zip \
	libzip-dev \
	zlib1g-dev \
	libpng-dev \
	libjpeg-dev \
	libfreetype6-dev \
    libgmp-dev

RUN docker-php-ext-configure gd \
	--with-jpeg=/usr/include/ \
	--with-freetype=/usr/include/ \
	&& docker-php-ext-install gd

RUN docker-php-ext-install \
	zip \
	mysqli \
	pdo_mysql \
    bcmath \
    gmp

COPY resources/docker/dev/vhost.conf /etc/apache2/sites-available/000-default.conf

COPY resources/docker/dev/custom.ini /usr/local/etc/php/conf.d/custom.ini

COPY . /srv/app/

RUN chown -R www-data:www-data /srv/app/ \
    && a2enmod rewrite