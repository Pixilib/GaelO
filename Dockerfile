FROM node:12.16.2 as react
RUN apt-get update -qy && \
    apt-get install -y --no-install-recommends apt-utils\
    git
WORKDIR /FrontEnd
RUN git clone -b dev https://github.com/salimkanoun/GaelO_Frontend.git .
RUN npm install
RUN npm run build


FROM php:7.4.4-apache

RUN apt-get update -qy && \
    apt-get install -y --no-install-recommends apt-utils\
    git \
    cron \
    nano \
    libicu-dev \
    libpq-dev \
    libonig-dev \
    unzip \
    libzip-dev \
    zip && \
    supervisor \
    apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN docker-php-ext-install zip opcache pdo pdo_mysql pdo_pgsql pcntl mbstring intl sqlite-3
COPY php.ini /usr/local/etc/php/conf.d/app.ini

RUN curl -s https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer

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
COPY --from=react /FrontEnd/build $APP_HOME/public

RUN composer install --no-dev --no-interaction

EXPOSE 80

RUN service apache2 restart


