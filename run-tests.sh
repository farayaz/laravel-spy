#!/bin/bash

# Laravel Spy Test Runner Script
# This script builds and runs tests in a Docker container

set -e

echo "🔍 Laravel Spy - Running Tests in Docker"
echo "========================================"

if ! docker info > /dev/null 2>&1; then
    echo "❌ Error: Docker is not running. Please start Docker and try again."
    exit 1
fi

# Check if image exists and only rebuild if needed
IMAGE_NAME="laravel-spy-test"
if docker image inspect $IMAGE_NAME > /dev/null 2>&1; then
    echo "📦 Using existing Docker image (use --rebuild to force rebuild)"
    if [[ "$1" == "--rebuild" ]]; then
        echo "🏗  Rebuilding Docker image..."
        docker build --no-cache -t $IMAGE_NAME .
    fi
else
    echo "🏗  Building Docker image (first time)..."
    docker build -t $IMAGE_NAME .
fi

echo "📦 Installing dependencies..."
docker run --rm \
    -v "$(pwd)":/var/www \
    -w /var/www \
    -u $(id -u):$(id -g) \
    -e HOME=/tmp \
    $IMAGE_NAME bash -c "
        git config --global --add safe.directory /var/www 2>/dev/null || true && \
        mkdir -p /tmp/composer-cache && \
        export COMPOSER_CACHE_DIR=/tmp/composer-cache && \
        composer install --no-scripts --prefer-dist
    "

echo "🧪 Running PHPUnit tests..."
docker run --rm \
    -v "$(pwd)":/var/www \
    -w /var/www \
    -u $(id -u):$(id -g) \
    -e HOME=/tmp \
    $IMAGE_NAME bash -c "
        git config --global --add safe.directory /var/www 2>/dev/null || true && \
        vendor/bin/phpunit --display-deprecations
    "

echo "✅ Tests completed!"
