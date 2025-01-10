#!/usr/bin/env bash

if [[ $(type -t lss-cli) != function ]]; then
    echo "Don't call scripts directly, use the lss binary!"

    exit;
fi

# Abort on errors
set -e

# Pull new code
git -C $base_dir pull

# Update nginx-agora
# TODO if nginx-agora is configured, regenerate and copy nginx config

# Update containers
lss-docker-compose pull

if lss_is_running; then
    lss-cli restart
    lss-docker-compose exec app php artisan config:cache
    lss-docker-compose exec app php artisan event:cache
    lss-docker-compose exec app php artisan optimize
    lss-docker-compose exec app php artisan route:cache
    lss-docker-compose exec app php artisan view:cache
    lss-docker-compose exec app php artisan migrate --force
else
    lss-docker-compose run --rm app php artisan config:cache
    lss-docker-compose run --rm app php artisan event:cache
    lss-docker-compose run --rm app php artisan optimize
    lss-docker-compose run --rm app php artisan route:cache
    lss-docker-compose run --rm app php artisan view:cache
    lss-docker-compose run --rm app php artisan migrate --force
fi

echo "Updated successfully!"
