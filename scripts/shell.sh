#!/usr/bin/env bash

if [[ $(type -t lss-cli) != function ]]; then
    echo "Don't call scripts directly, use the lss binary!"

    exit;
fi

if ! lss_is_running; then
    echo "App is not running!"

    exit
fi

service=${1:-app}

lss-docker-compose exec $service sh
