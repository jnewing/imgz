#!/usr/bin/env bash
#
# imgz container entrypoint. Applies PHP runtime limits for every command, and
# runs the full application bootstrap (key check, DB wait, migrations, caching)
# only when starting the Octane server, so one-off commands (key:generate,
# tinker) and the queue worker run without it.

set -euo pipefail

# PHP runtime limits from environment, applied to every command so the queue
# worker also gets the memory it needs for image processing.
cat > "${PHP_INI_DIR}/conf.d/zz-imgz.ini" <<INI
upload_max_filesize = ${PHP_UPLOAD_MAX_FILESIZE:-64M}
post_max_size = ${PHP_POST_MAX_SIZE:-64M}
memory_limit = ${PHP_MEMORY_LIMIT:-256M}
max_execution_time = ${PHP_MAX_EXECUTION_TIME:-120}
INI

# Full bootstrap only when starting the Octane server.
if [[ "$*" == *"octane:frankenphp"* ]]; then
    if [ -z "${APP_KEY:-}" ]; then
        echo "ERROR: APP_KEY is not set. Generate one with:" >&2
        echo "  docker run --rm <image> php artisan key:generate --show" >&2
        exit 1
    fi

    if [ "${DB_CONNECTION:-pgsql}" = "pgsql" ]; then
        echo "Waiting for database at ${DB_HOST:-db}:${DB_PORT:-5432}..."
        until php -r '
            $host = getenv("DB_HOST") ?: "db";
            $port = (int) (getenv("DB_PORT") ?: 5432);
            exit(@fsockopen($host, $port) ? 0 : 1);
        ' 2>/dev/null; do
            sleep 1
        done
        echo "Database is reachable."
    fi

    php artisan storage:link --force >/dev/null 2>&1 || true

    if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
        echo "Running migrations..."
        php artisan migrate --force
    fi

    if [ "${CACHE_CONFIG:-true}" = "true" ]; then
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
    fi
fi

exec "$@"
