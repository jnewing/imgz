# syntax=docker/dockerfile:1

# -----------------------------------------------------------------------------
# Stage 1 - build PHP dependencies and frontend assets
# -----------------------------------------------------------------------------
FROM dunglas/frankenphp:1-php8.4-bookworm AS builder

# PHP extensions needed to boot artisan during the build (asset build runs
# Wayfinder generation) and shared with the runtime image.
RUN install-php-extensions pdo_pgsql pgsql gd exif intl zip pcntl opcache redis

# Node (for the Vite build) + Composer + git for dist installs.
RUN apt-get update \
    && apt-get install -y --no-install-recommends curl ca-certificates gnupg git \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install PHP + Node dependencies first for better layer caching.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader
COPY package.json package-lock.json ./
RUN npm ci

# Build the app.
COPY . .
RUN composer dump-autoload --no-dev --optimize \
    && php artisan package:discover --ansi \
    && cp .env.example .env \
    && php artisan key:generate \
    && npm run build \
    && rm -rf node_modules .env

# -----------------------------------------------------------------------------
# Stage 2 - lean runtime
# -----------------------------------------------------------------------------
FROM dunglas/frankenphp:1-php8.4-bookworm AS runtime

RUN install-php-extensions pdo_pgsql pgsql gd exif intl zip pcntl opcache redis

WORKDIR /app

ENV APP_ENV=production \
    APP_DEBUG=false \
    OCTANE_SERVER=frankenphp \
    IMAGE_DRIVER=gd

COPY --from=builder /app /app
COPY docker/Caddyfile /etc/caddy/Caddyfile
COPY docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint

RUN cp vendor/laravel/octane/src/Commands/stubs/frankenphp-worker.php public/frankenphp-worker.php \
    && chmod +x /usr/local/bin/docker-entrypoint \
    && mkdir -p storage/app/images \
        storage/framework/cache storage/framework/sessions storage/framework/views \
        storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

ARG APP_VERSION=dev
ARG GIT_SHA=unknown
ENV APP_VERSION=${APP_VERSION} \
    GIT_SHA=${GIT_SHA}

EXPOSE 8000

ENTRYPOINT ["docker-entrypoint"]
# Octane defaults the log level to WARN when APP_ENV != local.
CMD ["php", "artisan", "octane:frankenphp", \
    "--host=0.0.0.0", "--port=8000", \
    "--caddyfile=/etc/caddy/Caddyfile", \
    "--admin-port=2019", "--workers=auto", "--max-requests=500"]
