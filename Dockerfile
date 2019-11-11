FROM php:7.3-apache

ENV COMPOSER_ALLOW_SUPERUSER=1

EXPOSE 80
WORKDIR /gaelo

RUN apt-get update -qy && \
    apt-get install -y \
    git \
    libicu-dev \
    unzip \
    libzip-dev \
    zip && \
    docker-php-ext-install zip &&\
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN docker-php-ext-install -j$(nproc) opcache pdo_mysql
ADD php.ini /usr/local/etc/php/conf.d/app.ini

ADD vhost.conf /etc/apache2/sites-available/000-default.conf
ADD apache.conf /etc/apache2/conf-available/z-app.conf
RUN a2enmod rewrite
RUN a2enmod headers
RUN a2enconf z-app

COPY --chown=root:www-data src .
RUN composer install --no-dev

RUN service apache2 restart


