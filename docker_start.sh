#!/usr/bin/env bash
set -e

role=${CONTAINER_ROLE:-app}
env=${APP_ENV:-production}
migrate=${MIGRATE:-false}
seed=${SEED:-false}

if [ "$migrate" = "true" ]; then
    echo "Migration..."
    (cd /var/www && php artisan migrate --force)
fi

if [ "$seed" = "true" ]; then
    echo "Seeding..."
    (cd /var/www && php artisan db:seed && php artisan db:seed --class=Database\\Seeders\\Studies\\TestSeeder )
fi

#only the app container with production settings will set the cache (ideally in redis)
if [ "$env" = "production" ] && [ "$role" = "app" ]; then
    echo "Caching configuration..."
    (cd /var/www && sudo -u www-data php artisan config:cache && sudo -u www-data php artisan route:cache && sudo -u www-data php artisan view:cache)
fi

if [ "$role" = "app" ]; then
    echo "App started"
    php-fpm -D
    nginx -g "daemon off;"

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
