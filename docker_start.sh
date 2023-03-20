#!/usr/bin/env bash
set -e

role=${CONTAINER_ROLE:-app}
env=${APP_ENV:-production}
migrate=${MIGRATE:-false}

if [ "$migrate" = "true" ]; then
    echo "Migration..."
    (cd /var/www/html && php artisan migrate --force)
fi

#only the app container with production settings will set the cache (ideally in redis)
if [ "$env" = "production" ] && [ "$role" = "app" ]; then
    echo "Caching configuration..."
    (cd /var/www/html && php artisan config:cache && php artisan route:cache && php artisan view:cache)
fi

if [ "$role" = "app" ]; then
    echo "App started"
    apache2-foreground

elif [ "$role" = "queue" ]; then
    echo "Queue role"
    supervisord

elif [ "$role" = "scheduler" ]; then
    echo "Scheduler role"
    while [ true ]
    do
      php artisan schedule:run --quiet --no-interaction &
      sleep 60
    done

else
    echo "Could not match the container role \"$role\""
    exit 1
fi
