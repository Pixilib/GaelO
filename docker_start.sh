#!/usr/bin/env bash
set -e

role=${CONTAINER_ROLE:-app}
env=${APP_ENV:-production}

if [ "$env" != "local" ]; then
    echo "Caching configuration..."
    (cd /var/www/html && php artisan migrate && php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan passport:install)
fi

if [ "$role" = "app" ]; then
    echo "App started"
    apache2-foreground

elif [ "$role" = "queue" ]; then

    echo "Queue role"
    php artisan queue:work --verbose --tries=3 --timeout=90

elif [ "$role" = "scheduler" ]; then

    echo "Scheduler role"
    while [ true ]
    do
      php artisan schedule:run --verbose --no-interaction &
      sleep 60
    done

else
    echo "Could not match the container role \"$role\""
    exit 1
fi