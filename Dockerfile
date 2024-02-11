FROM php:8.2.13-fpm-bullseye

ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS="0"
ENV TZ="UTC"

RUN apt-get update -qy && \
    apt-get install -y --no-install-recommends apt-utils\
    nginx \
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
    libcurl4-gnutls-dev \
    openssl \
    sqlite3 \
    supervisor \
    zip \
    libpng-dev \
    libmemcached-dev \
    mariadb-client \
    postgresql-client-13 && \
    apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN pecl install pcov redis memcached
RUN docker-php-ext-install gd zip pdo pdo_mysql pdo_pgsql mbstring bcmath ctype fileinfo xml bz2 pcntl curl
RUN docker-php-ext-configure opcache --enable-opcache \
    && docker-php-ext-install opcache

RUN docker-php-ext-enable redis memcached pcov

RUN curl -s https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer

# Copy configuration files.
COPY php.ini /usr/local/etc/php/php.ini
COPY php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
COPY nginx.conf /etc/nginx/nginx.conf

ENV APP_HOME /var/www
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR $APP_HOME

COPY docker_start.sh /usr/local/bin/start
COPY GaelO2 .
COPY laravel-worker.conf /etc/supervisor/conf.d

RUN mv .env.example .env

RUN composer install --optimize-autoloader --no-interaction

COPY docker_start.sh /usr/local/bin/start
RUN chmod u+x /usr/local/bin/start

# Adjust user permission & group
RUN usermod --uid 1000 www-data
RUN groupmod --gid 1001 www-data

# Set correct permission.
RUN chown -R www-data:www-data $APP_HOME
RUN chmod -R 755 /var/www/storage
RUN chmod -R 755 /var/www/bootstrap

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/start"]
