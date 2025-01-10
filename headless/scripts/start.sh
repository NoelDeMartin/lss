#!/usr/bin/env bash

if [[ $(type -t lss-cli) != function ]]; then
    echo "Don't call scripts directly, use the lss binary!"

    exit;
fi

lss-docker-compose up -d

# Publish assets
if ! lss_is_running; then
    exit
fi

lss-docker-compose exec app php artisan storage:unlink
lss-docker-compose exec app php artisan storage:link --relative

rm $base_dir/public -rf
lss-docker-compose cp "app:/app/public/." "$base_dir/public"
