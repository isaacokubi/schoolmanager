# School Manager — simplified student and M-Pesa project

This is a small Laravel 8 project intended to be easy to demonstrate and explain. The application exposes only **two core features**:

1. **Student records** — create, view, search, edit and archive student records.
2. **M-Pesa school-fee payments** — request an STK Push, receive Safaricom's callback, verify the receipt and amount, and update the student's fee balance.

Login and logout are included only to protect the administrator pages.

## How the project works

- `routes/web.php` lists the small set of pages used by the project.
- `StudentController` validates and stores student records.
- `PaymentController` displays payment history and loads the list of students for the payment form.
- `MpesaController` starts the STK Push and processes Safaricom's callback. A payment is not confirmed until the callback contains the receipt and amount.
- Blade templates in `resources/views/admin/students` and `resources/views/admin/payments` render the pages.
- Laravel's database layer stores students and payment records.

The code includes short comments at the main learning points. The existing database migrations and Laravel framework files are retained so the project can still run with its current database schema. Older controllers and templates are no longer exposed through the simplified web routes; keeping those files avoids an unsafe bulk deletion of code or data definitions.

## Requirements

- PHP 7.4 or newer
- Composer
- MySQL or SQLite
- A Safaricom Daraja developer account for M-Pesa testing

## Run locally

```bash
composer install
cp .env.example .env
php artisan key:generate
# Configure the database settings in .env before migrating.
php artisan migrate
php artisan serve
```

Open `http://127.0.0.1:8000`. Sign in with an administrator account already present in your database. Do not commit the real `.env` file or payment credentials.

## M-Pesa configuration

Configure the values in the deployment environment or local `.env` file:

- `MPESA_ENV=sandbox` for sandbox testing
- `MPESA_CONSUMER_KEY`
- `MPESA_CONSUMER_SECRET`
- `MPESA_SHORTCODE`
- `MPESA_PASSKEY`
- `MPESA_CALLBACK_URL=https://your-public-host.example/api/mpesa/callback`

The callback URL must be publicly reachable over HTTPS. Use sandbox credentials until end-to-end testing is complete. The STK request being accepted does not prove the payment succeeded; the callback must verify the transaction metadata first.

## Quick checks

```bash
php artisan route:list
php artisan test
```

Use a test database for development. Never run `php artisan migrate:fresh` against a database that contains real school records.
