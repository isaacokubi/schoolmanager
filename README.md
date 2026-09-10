# School Manager

Laravel 8 school management platform for Kenyan schools, including admissions, learner records, attendance, CBC results/report cards, portal access and M-Pesa school-fee payments.

## Local setup

Requirements already used by this project:

- PHP 7.4+
- Composer
- MySQL or SQLite

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan route:list
php artisan test
```

Do not run `php artisan migrate:fresh` against a production database. Use `php artisan migrate --force` for an existing production database after reviewing the migration plan.

## Production configuration

Set these environment values in the deployment platform rather than committing secrets:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://your-domain.example`
- `DB_CONNECTION=mysql`
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `SESSION_DRIVER=database`
- `CACHE_STORE=database`
- `QUEUE_CONNECTION=database`
- SMTP mail settings
- `MPESA_ENV=production`
- `MPESA_CONSUMER_KEY`, `MPESA_CONSUMER_SECRET`, `MPESA_SHORTCODE`, `MPESA_PASSKEY`
- `MPESA_CALLBACK_URL=https://your-domain.example/api/mpesa/callback`

M-Pesa callbacks must use the public HTTPS application URL. Never place consumer keys, passkeys, database passwords or application keys in source control.

## Production verification

Run after pulling the production branch locally:

```bash
composer install --no-interaction --prefer-dist
composer dump-autoload
php artisan optimize:clear
php artisan migrate --force
php artisan route:list
php artisan test
```

The feature suite covers authentication boundaries, active portal profiles, parent/student access isolation, admissions, student validation, attendance upserts, CBC missed assessments, M-Pesa callback integrity/idempotency, duplicate receipts, admin payment auditing and report notification idempotency.

## Vercel

The repository contains `Dockerfile.vercel` for Vercel container deployment. The application is stateless at the container level; persistent data belongs in MySQL and database-backed session/cache/queue storage.

Before enabling production traffic, configure all production environment variables in Vercel and verify the M-Pesa callback URL from the deployed domain.
