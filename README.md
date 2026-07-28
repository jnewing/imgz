# imgz

dead simple image hosting you can run yourself. drop an image in, get a link back.
anonymous by default, or make accounts required if you'd rather.

it's the imgbb / "paste a screenshot, share a link" idea, self-hosted: no ads, no
tracking, your files sitting on your own disk.

## features

- drag, drop or paste to upload, one request per file with live progress
- anonymous uploads, or lock it down to accounts (single config flag)
- automatic thumbnails and webp variants
- clean direct links (`/i/{id}.png`), with an optional cookieless/CDN host for the files
- expiring uploads and a per-upload delete token
- REST API with scoped tokens, including an imgbb-style upload endpoint
- upload straight from your IRC client: IRCv3 FILEHOST, plus a Zipline-compatible
  endpoint for clients like Lurker

## stack

Laravel 13 / PHP 8.5, Vue 3 + Inertia, Postgres, Redis, Tailwind. Runs on
FrankenPHP + Octane in production.

## running it (docker)

```sh
cp .env.docker.example .env

# generate a key and paste it into .env as APP_KEY
docker run --rm dunglas/frankenphp:1-php8.4-bookworm php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"

# then edit .env: set APP_URL and a real DB_PASSWORD
docker compose up -d
```

migrations and the storage symlink run on boot, so that's it. the app comes up on
`:8000`, put it behind whatever handles your https.

## local dev

the database runs in docker, the app runs on your host:

```sh
cp .env.example .env
composer install
npm install
php artisan key:generate
make dev          # brings up the db, then serve + queue + vite together
```

run `make` on its own to see the rest (test, lint, typecheck, psql, ...).

## config

everything imgz-specific lives in `config/imgz.php` and is env driven: guest uploads
on/off, whether an account is required, max file size, allowed types, thumbnail
format, storage disk, direct-url host. point the `images` disk at S3 instead of local
and nothing else has to change.

## license

MIT
