# Mistborne Private Highland Travel

**Mistborne** is a **private highland trip service website** for Java (Dieng, Bromo, Ijen, and surroundings), built with **Laravel 13**. This single application includes three parts:

1. **Public website** for guests: journey catalog, itineraries, partner hotels, gallery, articles, reviews, and booking.
2. **Admin panel** for managing all operations.
3. **Guide panel** for guides who receive trip assignments.

Everything lives in **one repository, one database, and one deployment**.

## Background

This project was built as a UKOM project around the concept of exclusive, comfortable, and flexible private travel. The interface was designed in Google Stitch and implemented with Laravel Blade in a modern, minimalist, and elegant style.

## Access and Demo Accounts

| Part | URL | Users |
|---|---|---|
| Public website | `/` | Guests (no login) |
| Admin panel | `/admin/masuk` | Admin and Operations Admin |
| Guide panel | `/personel/masuk` | Guides |

**Demo admin account** (created automatically by `php artisan migrate --seed`):

| | |
|---|---|
| Email | `admin@mistborne.id` |
| Password | `password` |

This account is intended for assessment and can be changed through `ADMIN_SEED_EMAIL` and `ADMIN_SEED_PASSWORD` in the `.env` file. Public registration is disabled, so the admin panel can only be accessed through this account.

Guide panel accounts are created by the admin from the Operational Personnel menu. Their initial password is set by `MISTBORNE_SANDI_BAWAAN_GUIDE` in `.env` (default: `password`).

## Features

**Public website**
- Journey catalog with region filters and detail pages (description, highlights, itinerary, practical info, available dates)
- Partner hotels: gallery, facilities, room types, FAQ, and guest reviews
- Photo gallery and travel journal articles
- Direct booking without login, guest details form, order form via token link, and one rating per order
- Custom Trip Planner for special travel requests
- Prospective guest inquiries are recorded before WhatsApp opens
- **9 languages**: Indonesian (default), English, Malay, Chinese, Japanese, Korean, Spanish, French, and Arabic
- Responsive layout for desktop, tablet, and mobile
- Rate limiting (throttle) on all public forms

**Admin panel**
- Dashboard, Journeys, Departure Schedules, and Trip Calendar
- Orders, Custom Orders, and Order Requests
- Hotels (including per-order hotel completion), Vehicles, and Vehicle Servicing
- Operational Personnel (guides), Guest Inquiries, Reviews, and Articles
- Notifications, Settings, and Profile
- Printable departure recap
- Access restricted by role-based middleware (`hak-admin`)

**Guide panel**
- Assignment dashboard, activity history, and profile
- Respond to assignments and mark trips as completed
- Automatic assignment and cancellation emails to guides

**Scheduled automation** (via the Laravel scheduler): cancellation of orders past the payment deadline, D-day and D-1 reminders, Google hotel rating sync, and daily database backup.

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.3+, Laravel 13 |
| Frontend | Blade, Tailwind CSS v4, Alpine.js, Vite |
| Database | PostgreSQL |
| Typography | Montserrat (and Noto Sans Arabic for `/ar` pages) |
| Testing | PHPUnit (in-memory SQLite) |

## Requirements

Make sure the following are installed:

- PHP **8.3 or newer** with extensions: `pdo_pgsql`, `mbstring`, `openssl`, `dom`, `fileinfo`, `tokenizer`, `ctype`, `json`, and `gd` (with WebP support) so photo uploads in the admin panel work
- [Composer](https://getcomposer.org/)
- [Node.js](https://nodejs.org/) and npm
- [PostgreSQL](https://www.postgresql.org/)
- **Internet connection** during `npm install` and `npm run build` (fonts are downloaded from `fonts.bunny.net`)

## Getting Started

```bash
# 1. Get the project
git clone https://github.com/CHIEE444/Mistborne-Private-Tour-Highland.git
cd Mistborne-Private-Tour-Highland

# 2. Install dependencies
composer install
npm install

# 3. Prepare the configuration file
cp .env.example .env
php artisan key:generate
```

**4. Prepare the PostgreSQL database.** Create an empty database named `mistborne`, for example with psql:

```sql
CREATE DATABASE mistborne;
```

Then open the `.env` file and set `DB_USERNAME` and `DB_PASSWORD` to match your local PostgreSQL.

```bash
# 5. Create tables and seed initial data (including the demo admin account)
php artisan migrate --seed

# 6. Link the storage folder so admin-uploaded photos are displayed
php artisan storage:link

# 7. Build frontend assets (required, do not skip)
npm run build

# 8. Start the server
php artisan serve
```

Open **http://127.0.0.1:8000** for the public website and **http://127.0.0.1:8000/admin/masuk** for the admin panel.

> **Note:** use the steps above, not `composer setup`. The `composer setup` script does not run the seeders, so the admin account would not be created.

### Optional extra data

```bash
# Sample data marked "(Contoh)": draft articles, custom orders, completed orders, reviews
php artisan db:seed --class=DataContohSeeder

# Two test guides for trying the assignment flow
php artisan db:seed --class=GuideUjiSeeder
```

## Running Tests

```bash
php artisan test
```

The project has 98 test files (Feature and Unit). Tests use in-memory SQLite, so they **do not require PostgreSQL** and never touch the main database.

## Troubleshooting

| Symptom | Cause and fix |
|---|---|
| `Vite manifest not found` error page | Assets have not been built. Run `npm run build`. |
| `npm run build` fails with `ENOTFOUND fonts.bunny.net` | No internet access to the font provider. Make sure you are online, then retry. |
| `could not find driver` during migrate | The `pdo_pgsql` extension is not enabled in `php.ini`. |
| Cannot connect to the database | Check `DB_USERNAME` and `DB_PASSWORD`, and make sure the `mistborne` database exists. |
| Cannot log in to admin | The seeder has not run. Run `php artisan migrate --seed`. |
| Uploaded photos do not appear | Run `php artisan storage:link`. |
| Guide assignment emails are not sent | SMTP is not configured. The app keeps working; to try it out, set `MAIL_MAILER=log` in `.env` and read the output in `storage/logs`. |

## Project Structure

```
app/            Application logic (controllers, models, actions, services, enums, policies, mail)
bootstrap/      Routing and middleware configuration
config/         Configuration (config/mistborne.php holds business settings)
database/       Migrations and seeders
docs/           Documentation: diagrams, database design, test cases, Postman collection
lang/           Translations for 9 languages
public/         Public assets (images, icons, robots.txt, sitemap.xml)
resources/      Blade views, CSS, and JavaScript
routes/         web.php (public site and guide panel), admin.php (admin panel)
tests/          Automated tests
```

## Documentation

The [`docs/`](docs/) folder contains:

- `diagram/`: ERD, CDM, PDM, DFD level 0 and 1, and Activity Diagram (PNG, draw.io, and Mermaid sources)
- Backend database design proposal and final design
- `MISTBORNE-Test-Cases.xlsx` and the Postman collection in `testing/`
- Competency unit documentation and code documentation

## Development Notes

Some values in this project are still placeholders because the site has not been published: the domain in `public/robots.txt` and `public/sitemap.xml` (`GANTI-DOMAIN`), and the business contact data on the home page. Both must be replaced before the site is used in production.

## License

All rights reserved. © 2026 CHIEE444.

## Author

Built by [CHIEE444](https://github.com/CHIEE444).
