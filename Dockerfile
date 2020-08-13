FROM php:7.4.4-apache

RUN apt-get update -qy && \
    apt-get install -y \
    git \
    cron \
    nano \
    libicu-dev \
    unzip \
    libzip-dev \
    zip \
    msmtp \
    msmtp-mta && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN docker-php-ext-install -j$(nproc) zip opcache pdo_mysql pgsql pdo_pgsql pcntl mcrypt mbstring intl
COPY php.ini /usr/local/etc/php/conf.d/app.ini

COPY vhost.conf /etc/apache2/sites-available/000-default.conf
COPY apache.conf /etc/apache2/conf-available/gaelo-app.conf

RUN a2enmod rewrite
RUN a2enmod headers
RUN a2enmod remoteip
RUN a2enconf gaelo-app

ENV APP_HOME /var/www/html
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR $APP_HOME

COPY --chown=www-data:www-data GaelO2/GaelO2 .

RUN composer install --no-dev --no-interaction

EXPOSE 80

RUN service apache2 restart


