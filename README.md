# Mistborne Private Highland Travel

**Mistborne** is a **private highland trip website** built with **Laravel**. Visitors can browse curated journeys to Dieng, Bromo, and Ijen, read detailed itineraries, view the gallery and guest reviews, and book directly through **WhatsApp**, with no payment gateway required.

## Background

This project was built as a UKOM project around the concept of exclusive, comfortable, and flexible private travel. The interface was designed in Google Stitch and implemented in Laravel Blade with a modern, minimalist, and elegant look.

## Features

**Public site**
- Hero section with a journey search filter
- Popular journeys catalog with region filters (Central Java, East Java, West Java)
- Journey detail modal with trip facts and an itinerary timeline
- Photo gallery of highland destinations
- Travel articles
- Guest reviews
- Booking and price inquiries via WhatsApp
- Responsive layout for desktop, tablet, and mobile

**Admin panel (in development)**
- Admin-only login on a separate URL
- Management of journeys and packages, bookings, trip calendar, articles, and reviews

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.3, Laravel 13 |
| Frontend | Blade, Tailwind CSS v4, Alpine.js, Vite |
| Database | PostgreSQL |
| Typography | Montserrat |

## Project Structure

- `app/` application logic (models, controllers)
- `routes/` web routes
- `resources/` Blade views, CSS, and JavaScript
- `database/` migrations and seeders
- `public/` public assets (images, etc.)
- `docs/` documentation and design references
- `tests/` automated tests

## Getting Started

```bash
git clone https://github.com/CHIEE444/Mistborne-Private-Tour-Highland.git
cd Mistborne-Private-Tour-Highland

composer install
npm install

cp .env.example .env
php artisan key:generate

# configure your PostgreSQL connection in .env, then:
php artisan migrate --seed

npm run dev
php artisan serve
```

Open `http://localhost:8000` in your browser.

## License

Released under the [MIT License](LICENSE).

## Author

Built by [CHIEE444](https://github.com/CHIEE444).
