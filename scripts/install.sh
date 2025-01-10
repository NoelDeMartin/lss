#!/usr/bin/env bash

if [[ $(type -t lss-cli) != function ]]; then
    echo "Don't call scripts directly, use the lss binary!"

    exit;
fi

# Check if installing is necessary
if [ -f "$base_dir/.env" ]; then
    echo "Already installed!"
    exit
fi

# Abort and clean up on error
trap "clean_up" ERR

function clean_up() {
    if [ -d "$base_dir/nginx-agora" ]; then
        rm $base_dir/nginx-agora -rf
    fi

    if [ -f "$base_dir/.env" ]; then
        rm $base_dir/.env
    fi

    # TODO uninstall site from nginx-agora if installed

    exit
}

# Prepare .env
cp .env.example .env

# Prepare nginx-agora
nginx-agora install "$base_dir/nginx/lss.conf" "$base_dir" lss
nginx-agora enable lss

# Prepare database
touch database/database.sqlite

# Prepare storage
lss-cli permissions
lss-docker-compose run --rm app php artisan key:generate
lss-docker-compose run --rm app php artisan config:cache
lss-docker-compose run --rm app php artisan event:cache
lss-docker-compose run --rm app php artisan optimize
lss-docker-compose run --rm app php artisan route:cache
lss-docker-compose run --rm app php artisan view:cache
lss-docker-compose run --rm app php artisan migrate --force

echo "Installed successfully!"
