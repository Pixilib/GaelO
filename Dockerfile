FROM node:14.15.4 as ohif
RUN apt-get update -qy && \
    apt-get install -y --no-install-recommends apt-utils\
    git
WORKDIR /ohif
RUN git clone --depth 1 --branch v3-stable https://github.com/stackp/Viewers.git
#https://github.com/OHIF/Viewers.git
RUN cd Viewers && yarn config set workspaces-experimental true && yarn install && QUICK_BUILD=true PUBLIC_URL=/ohif/ yarn run build
RUN rm /ohif/Viewers/platform/viewer/dist/app-config.js

FROM alpine as stone
RUN apk --no-cache add wget
RUN apk add --update zip
RUN wget https://lsb.orthanc-server.com/stone-webviewer/2.3/wasm-binaries.zip
RUN mkdir /stone
RUN unzip wasm-binaries.zip -d /stone
RUN rm /stone/wasm-binaries/StoneWebViewer/configuration.json

FROM php:7.4.19-apache

ENV COMPOSER_ALLOW_SUPERUSER=1

EXPOSE 80
WORKDIR /gaelo

RUN apt-get update -qy && \
    apt-get install -y \
    git \
    cron \
    nano \
    libicu-dev \
    unzip \
    libzip-dev \
    zip \
    libc-client-dev \
    libkrb5-dev \
    msmtp \
    msmtp-mta && \
    docker-php-ext-install zip && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* 

RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl && \
    docker-php-ext-install -j$(nproc) imap

RUN docker-php-ext-install -j$(nproc) opcache pdo_mysql
COPY php.ini /usr/local/etc/php/conf.d/app.ini

# Create the cronjob (job has to be set in /data/cron/cron.php)
COPY crontab /etc/cron.d/gaelo
RUN chmod 0600 /etc/cron.d/gaelo
RUN crontab /etc/cron.d/gaelo

COPY msmtprc /etc/msmtprc
RUN chmod 600 /etc/msmtprc

COPY vhost.conf /etc/apache2/sites-available/000-default.conf
COPY apache.conf /etc/apache2/conf-available/gaelo-app.conf
RUN a2enmod rewrite
RUN a2enmod headers
RUN a2enmod remoteip
RUN a2enconf gaelo-app

COPY --chown=www-data:www-data src .
COPY --from=ohif --chown=www-data:www-data /ohif/Viewers/platform/viewer/dist ./ohif/
COPY --from=stone /stone/wasm-binaries/StoneWebViewer ./stone/


RUN composer install --no-dev

RUN service apache2 restart
CMD cron && apache2-foreground


