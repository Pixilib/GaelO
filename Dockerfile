FROM php:8.1.3-apache-bullseye

ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS="0"

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
    supervisor \
    zip \
    libpng-dev \
    libmemcached-dev \
    mariadb-client \
    postgresql-client-13 && \
    apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN pecl install pcov redis memcached-3.1.5
RUN docker-php-ext-install gd zip pdo pdo_mysql pdo_pgsql mbstring bcmath ctype fileinfo xml bz2 pcntl
RUN docker-php-ext-configure opcache --enable-opcache \
    && docker-php-ext-install opcache

RUN docker-php-ext-enable redis memcached pcov

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
COPY --chown=www-data:www-data GaelO2 .
COPY /etc/supervisor/conf.d laravel-worker.conf

RUN mv .env.example .env

RUN composer install --optimize-autoloader --no-interaction

# docker_start.sh
COPY docker_start.sh /usr/local/bin/start
RUN chmod u+x /usr/local/bin/start

EXPOSE 80

RUN service apache2 restart

ENTRYPOINT ["/usr/local/bin/start"]
