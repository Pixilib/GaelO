FROM php:8.0.7-apache-buster

RUN apt-get update -qy

# Add Postgres repository as postgres client will be available only in the next major release of debian
RUN apt -y install gnupg gnupg2 wget

RUN wget --quiet -O - https://www.postgresql.org/media/keys/ACCC4CF8.asc | apt-key add -

RUN echo "deb http://apt.postgresql.org/pub/repos/apt/ buster-pgdg main" |tee  /etc/apt/sources.list.d/pgdg.list

RUN cat /etc/apt/sources.list.d/pgdg.list

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
    libpng-dev \
    mariadb-client \
    postgresql-client-13 && \
    apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN docker-php-ext-install gd zip pdo pdo_mysql pdo_pgsql mbstring bcmath ctype fileinfo tokenizer xml bz2 opcache
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
RUN find . -type f -exec chmod 664 {} \;   
RUN find . -type d -exec chmod 775 {} \;

RUN mv .env.example .env

RUN composer install --optimize-autoloader --no-dev --no-interaction

# docker_start.sh
COPY docker_start.sh /usr/local/bin/start
RUN chmod u+x /usr/local/bin/start

EXPOSE 80

RUN service apache2 restart

ENTRYPOINT ["/usr/local/bin/start"]
