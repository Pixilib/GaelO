FROM node:12.16.2 as ohif
RUN apt-get update -qy && \
    apt-get install -y --no-install-recommends apt-utils\
    git
WORKDIR /ohif
RUN git clone --depth 1 --branch @ohif/viewer@4.5.25 https://github.com/OHIF/Viewers.git
WORKDIR /ohif/Viewers
RUN yarn install
RUN QUICK_BUILD=true PUBLIC_URL=/viewer/ yarn run build
WORKDIR /ohif/Viewers/platform/viewer/dist

FROM node:12.16.2 as react
RUN apt-get update -qy && \
    apt-get install -y --no-install-recommends apt-utils\
    git
WORKDIR /FrontEnd
RUN git clone -b dev https://github.com/salimkanoun/GaelO_Frontend.git .
RUN npm install
RUN npm run build


FROM php:7.4.9-apache

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

RUN docker-php-ext-install zip pdo pdo_mysql pdo_pgsql mbstring bcmath ctype fileinfo json tokenizer xml bz2
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
COPY --from=react /FrontEnd/build $APP_HOME/public
COPY --from=ohif /ohif/Viewers/platform/viewer/dist $APP_HOME/public/viewer
RUN mv .env.example .env

RUN composer install --optimize-autoloader --no-dev --no-interaction
RUN chmod u+x /usr/local/bin/start

EXPOSE 80

RUN service apache2 restart
ENTRYPOINT ["/usr/local/bin/start"]


