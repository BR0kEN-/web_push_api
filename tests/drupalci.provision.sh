#!/usr/bin/env bash

set -e

if ! php -m | grep gmp > /dev/null && command -v docker-php-ext-install > /dev/null; then
    echo "==> Installing GMP PHP extension..."
    docker-php-ext-install gmp
fi
