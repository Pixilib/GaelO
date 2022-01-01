FROM php:8.0.14-apache-buster

ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS="0"

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
    libmemcached-dev \
    mariadb-client \
    postgresql-client-13 && \
    apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN pecl install redis && pecl install memcached-3.1.5
RUN docker-php-ext-install gd zip pdo pdo_mysql pdo_pgsql mbstring bcmath ctype fileinfo xml bz2
RUN docker-php-ext-configure opcache --enable-opcache \
    && docker-php-ext-install opcache

RUN docker-php-ext-enable redis memcached

COPY php.ini "$PHP_INI_DIR/php.ini"

RUN curl -s https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer

COPY vhost.conf /etc/apache2/sites-available/000-default.conf
COPY apache.conf /etc/apache2/conf-available/zgaelo.conf

RUN a2enmod rewrite
RUN a2enmod headers
RUN a2enmod remoteip
RUN a2enmod deflate

RUN a2enconf zgaelo

ENV APP_HOME /var/www/html
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR $APP_HOME

COPY docker_start.sh /usr/local/bin/start
COPY --chown=www-data:www-data GaelO2/GaelO2 .

RUN mv .env.example .env

RUN composer install --optimize-autoloader --no-interaction

# docker_start.sh
COPY docker_start.sh /usr/local/bin/start
RUN chmod u+x /usr/local/bin/start

EXPOSE 80

RUN service apache2 restart

ENTRYPOINT ["/usr/local/bin/start"]
