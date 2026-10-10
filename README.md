# Bookish

Bookish is a Laravel application for tracking reading progress, challenges, highlights, book releases, and book listings.

## Requirements

- PHP 8.3 or newer with Laravel's required extensions and `pdo_sqlite` for the default SQLite database.
- Composer 2.
- Node.js 20.19+ or 22.12+ and npm (required by Vite 7).

On Windows, enable `pdo_sqlite` and `sqlite3` in the PHP configuration used by both the web server and CLI. Verify the CLI setup with `php -m`.

## Local Setup

From the project directory, run:

```powershell
composer run setup
```

The setup script installs PHP and JavaScript dependencies, creates `.env` and the SQLite file when needed, generates the application key, runs migrations and baseline seeders, and builds frontend assets. Review `.env` and set `APP_URL` to the URL used by your local server.

Start the application and its local queue worker with:

```powershell
composer run dev
```

This starts the Laravel server, queue listener, log viewer, and Vite development server. To start only the web server, use `php artisan serve`; compile production assets with `npm run build`.

The baseline seeders create the curated BookTok catalog and public sample highlights. They do not create an administrator account or use a known login password. The catalog seed is local and deterministic; it does not make external API requests.

## Configuration

The checked-in `.env.example` uses SQLite, file sessions, database cache and queue, and log mail. Common settings:

- `APP_NAME`, `APP_ENV`, `APP_DEBUG`, and `APP_URL` identify the deployment. Set `APP_DEBUG=false` in production.
- `DB_CONNECTION` defaults to `sqlite`. For MySQL, configure `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`.
- `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, and `MAIL_FROM_NAME` configure outbound mail. For Railway, set `MAIL_MAILER=resend-api`, `RESEND_API_KEY`, and a verified `MAIL_FROM_ADDRESS`; keep API keys private.
- `GOOGLE_BOOKS_API_KEY` is optional. It can improve Google Books API quota availability for book metadata and search; keep the value private.
- `SESSION_DRIVER`, `CACHE_STORE`, and `QUEUE_CONNECTION` control session, cache, and queue backends. The default database cache and queue require the included migrations.

After changing environment values on a cached deployment, run `php artisan config:clear` before rebuilding the config cache.

## Administrator Account

Do not seed an administrator or use a shared default password. Register a personal account through the application and verify its email. From the project directory, promote that account with:

```sh
php artisan bookish:make-admin you@example.com
```

Replace the address with the account's actual email, then sign in with that account. Do not commit credentials or put passwords into shell history.

## Queue and Scheduler

The scheduled command `bookish:send-release-reminders` runs daily. In production, keep a queue worker running when using the database queue:

```sh
php artisan queue:work --tries=1 --timeout=90
```

Configure the host scheduler to invoke Laravel once per minute:

```cron
* * * * * cd /path/to/bookish && php artisan schedule:run >> /dev/null 2>&1
```

On Windows, create a Task Scheduler task that runs `php artisan schedule:run` every minute from the project directory. Check registered schedules with `php artisan schedule:list`.

## Book Data and API

`GOOGLE_BOOKS_API_KEY` is read by the application when calling Google Books. It is not required to migrate or seed the local curated catalog. After seeding, optional metadata enrichment can be run with:

```sh
php artisan booktok:fetch-google-data
```

This command needs outbound network access; API results and availability are controlled by Google Books. The Google Books top-books endpoint is registered in `routes/web.php` at `/api/google-books/top`; this project does not currently define a separate `routes/api.php` file.

## Database and Seeders

For a new database, `composer run setup` applies migrations and seeders. For a disposable local database that needs a full rebuild:

```sh
php artisan migrate:fresh --seed
```

`migrate:fresh` deletes all database tables and data. Never run it against a database containing user data. The manual challenge progress and failed-status columns are part of the original challenge table migration, so they are created for new databases. Laravel does not rerun an edited migration on an existing database; check that both columns exist before using the feature, and add them manually if they are missing. Older installations may still have legacy author columns because Laravel does not rerun recorded migrations; the application remains compatible with them, and rerunning `php artisan db:seed --class=BooktokTopBookSeeder` fills missing author IDs without replacing book metadata.

## Tests

Run the test suite with:

```sh
composer test
```

The default test database is in-memory SQLite, so the CLI PHP installation must have `pdo_sqlite` enabled.
