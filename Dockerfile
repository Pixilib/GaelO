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
    msmtp && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN docker-php-ext-install -j$(nproc) opcache pdo_mysql
COPY php.ini /usr/local/etc/php/conf.d/app.ini

COPY msmtprc /etc/msmtprc
RUN chmod 600 /etc/msmtprc

COPY vhost.conf /etc/apache2/sites-available/000-default.conf
COPY apache.conf /etc/apache2/conf-available/gaelo-app.conf
RUN a2enmod rewrite
RUN a2enmod headers
RUN a2enmod remoteip
RUN a2enconf gaelo-app

COPY --chown=www-data:www-data src .
RUN composer install --no-dev

RUN service apache2 restart


