# 🍕 The Slice Report

> Price paid vs. quality earned. No mercy, no nostalgia.

A collaborative web app for tracking pizza spots by price-per-slice vs. perceived quality. Add places, rate them, and instantly see who's a hidden gem and who's just tourist trap hype — plotted on a scatter chart.

---

## What It Does

- **Log pizza joints** via Google Places Autocomplete — name, address, and coordinates filled in automatically
- **Rate each place** with a price per slice and a quality score (0–10)
- **Visualise** your ratings on a scatter plot with four quadrants: Hidden Gem, Worth It, Skip It, Tourist Trap
- **Share lists** — make a list public and anyone with the link can add places and submit their own ratings
- **Compare** your rating against the crowd average, or overlay both on the same chart
- **Multi-currency** — currency is resolved automatically from the place's country, so your NYC list is in USD and your Naples list is in EUR

---

## Tech Stack

| Layer    | Tech                         |
|----------|------------------------------|
| Frontend | React + Vite + Tailwind CSS  |
| Charts   | Recharts                     |
| Places   | Google Maps Places API       |
| Backend  | Laravel 11                   |
| Auth     | Laravel Sanctum              |
| Admin    | Filament 3                   |
| Database | PostgreSQL                   |
| Deploy   | Laravel Forge + DigitalOcean |

---

## Project Structure

```
slice-report/
├── api/        # Laravel 11 REST API
└── client/     # React + Vite frontend
```

---

## Getting Started

### Prerequisites

- PHP 8.2+
- Composer
- Node 20+
- PostgreSQL
- A Google Cloud project with **Places API** and **Maps JavaScript API** enabled

### API Setup

```bash
cd api
composer install
cp .env.example .env
php artisan key:generate
```

Configure your `.env`:
```
DB_CONNECTION=pgsql
DB_DATABASE=slice_report
DB_USERNAME=your_user
DB_PASSWORD=your_password

SANCTUM_STATEFUL_DOMAINS=localhost:5173
FRONTEND_URL=http://localhost:5173

MAIL_MAILER=smtp
# ... your mail config for email verification
```

Run migrations and start the server:
```bash
php artisan migrate
php artisan serve
```

### Frontend Setup

```bash
cd client
npm install
cp .env.example .env
```

Configure your `.env`:
```
VITE_API_BASE_URL=http://localhost:8000/api/v1
VITE_GOOGLE_MAPS_KEY=your_google_maps_key
```

Start the dev server:
```bash
npm run dev
```

### Filament Admin

The admin panel is available at `/admin`. To make yourself an admin:

```bash
php artisan tinker
> App\Models\User::where('email', 'you@example.com')->update(['is_admin' => true]);
```

---

## Environment Variables

### `/api/.env`

| Key                        | Description                                    |
|----------------------------|------------------------------------------------|
| `APP_URL`                  | Laravel app URL (e.g. `http://localhost:8000`) |
| `DB_*`                     | PostgreSQL connection details                  |
| `SANCTUM_STATEFUL_DOMAINS` | Frontend domain for SPA auth                   |
| `FRONTEND_URL`             | Frontend URL for CORS and email links          |
| `MAIL_*`                   | Mail config for email verification             |

### `/client/.env`

| Key                    | Description                                                           |
|------------------------|-----------------------------------------------------------------------|
| `VITE_API_BASE_URL`    | Laravel API base URL including `/api/v1`                              |
| `VITE_GOOGLE_MAPS_KEY` | Google Maps API key — restrict to your domain in Google Cloud Console |

> ⚠️ Never put the Google Maps key in the Laravel `.env`. It's frontend-only.

---

## API Overview

All endpoints are prefixed `/api/v1/`. Protected routes require `Authorization: Bearer {token}`.

| Method | Endpoint                  | Description                        |
|--------|---------------------------|------------------------------------|
| POST   | `/register`               | Register + send verification email |
| POST   | `/login`                  | Login, returns token               |
| POST   | `/logout`                 | Revoke token                       |
| GET    | `/lists`                  | Get current user's lists           |
| POST   | `/lists`                  | Create a list                      |
| GET    | `/lists/{slug}`           | Get a list (public or owned)       |
| POST   | `/lists/{list}/places`    | Add a place to a list              |
| POST   | `/places/{place}/ratings` | Add or update your rating          |
| DELETE | `/places/{place}/ratings` | Remove your rating                 |

Full API spec: see `slice-report-spec.md`.

---

## The Quadrants

|                  | Low Price     | High Price      |
|------------------|---------------|-----------------|
| **High Quality** | 💎 Hidden Gem | ✅ Worth It      |
| **Low Quality**  | 🚫 Skip It    | 🪤 Tourist Trap |

Default midpoints: **$5.00 / 5.0**

---

## Roadmap

- [ ] Map view — plot spots on a city map, coloured by quadrant
- [ ] The Hype Index — your rating vs. Google/Yelp rating
- [ ] Cross-city comparison with currency conversion
- [ ] The Holy Grail filter — show only Hidden Gems
- [ ] Native iOS + Android apps (API is already ready)
- [ ] Email digest — weekly summary of new ratings on your lists

---

## Contributing

This is a personal project but PRs are welcome. Open an issue first if you're planning something substantial.

---

## License

MIT


Change the rating scale from /10 to /5 across the entire app, and add a Hype Index feature.
Rating scale change:

Update the rating column validation to accept 0.0–5.0 instead of 0–10
Update all frontend inputs, labels, and tooltips to reflect /5
Update the scatter plot Y axis domain to [0, 5]
Default quadrant midpoint changes from 5.0 to 2.5
Update any seeded or hardcoded example data

Hype Index:

On the PizzaPlace model, add a google_rating field (decimal, nullable) — populated from the Places API rating field at place creation time. Add it to the fields array in the Autocomplete config: fields: [..., 'rating']
Add google_rating to the pizza_places migration (nullable decimal)
Compute hype_index = google_rating - avg_user_rating — derived, not stored
Expose it in the place ratings API response alongside avg_rating and rating_count
On the scatter plot, encode Hype Index as dot colour:

Red (overhyped): hype_index > 0.5
Grey (consensus): -0.5 to 0.5
Green (underrated): hype_index < -0.5


Add Hype Index to the hover tooltip: show Google rating, your avg, and the delta
In the Filament PizzaPlace resource, add google_rating as a visible column
