FROM node:14.15.4 as react
RUN apt-get update -qy && \
    apt-get install -y --no-install-recommends apt-utils\
    git
WORKDIR /FrontEnd
RUN git clone -b dev https://${GITHUB_TOKEN}@github.com/salimkanoun/GaelO_Frontend.git .
RUN npm install
RUN npm run build

FROM node:14.15.4 as ohif
RUN apt-get update -qy && \
    apt-get install -y --no-install-recommends apt-utils\
    git
WORKDIR /ohif
RUN git clone https://github.com/OHIF/Viewers.git
RUN cd Viewers && yarn install && QUICK_BUILD=true PUBLIC_URL=/viewer-ohif/ yarn run build



FROM alpine as stone
RUN apk --no-cache add wget
RUN apk add --update zip
RUN wget https://lsb.orthanc-server.com/stone-webviewer/1.0/wasm-binaries.zip
RUN mkdir /stone
RUN unzip wasm-binaries.zip -d /stone


FROM php:8.0.1-apache

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
    libbz2-dev \
    libmcrypt-dev \
    libxml2-dev \
    openssl \
    sqlite3 \
    zip \
    mariadb-client && \
    apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN docker-php-ext-install zip pdo pdo_mysql pdo_pgsql mbstring bcmath ctype fileinfo tokenizer xml bz2
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


COPY docker_start.sh /usr/local/bin/start
COPY --chown=www-data:www-data GaelO2/GaelO2 .

RUN ls
RUN mkdir $APP_HOME/public/viewer-ohif && mkdir $APP_HOME/public/viewer-stone

COPY --from=react /FrontEnd/build $APP_HOME/public
COPY --from=ohif /ohif/Viewers/platform/viewer/dist $APP_HOME/public/viewer-ohif/
COPY --from=stone /stone/wasm-binaries/StoneWebViewer $APP_HOME/public/viewer-stone/
COPY --from=react /FrontEnd/build/viewers/OHIF/app-config.js $APP_HOME/public/viewer-ohif/
COPY --from=react /FrontEnd/build/viewers/Stone/configuration.json $APP_HOME/public/viewer-stone/
RUN mv .env.example .env

RUN composer install --optimize-autoloader --no-dev --no-interaction
RUN chmod u+x /usr/local/bin/start

EXPOSE 80

RUN service apache2 restart
ENTRYPOINT ["/usr/local/bin/start"]


