# The Slice Report — App Spec v0.3

> Price paid vs. quality earned. No mercy, no nostalgia.

---

## Overview

A web app for logging, rating, and visualizing pizza spots by price-per-slice vs. perceived quality. The core insight: hype and price don't equal quality. The scatter plot makes that obvious at a glance.

Multiple users can rate the same place. Lists are collaborative — anyone with the link can add their own rating (auth required). The scatter plot can show your rating, the crowd average, or both overlaid.

---

## Tech Stack

| Layer | Choice | Rationale |
|---|---|---|
| Frontend | React + Vite | Fast dev, easy deploy; future iOS/Android hit the same API |
| Styling | Tailwind CSS | Utility-first, no bloat |
| Charts | Recharts | Already proven in prototype |
| Places | Google Maps Places API | Autocomplete + lat/lng + address in one shot |
| Backend | Laravel 11 | Your home turf; batteries included |
| ORM | Eloquent | Built into Laravel, no extra setup |
| Auth | Laravel Sanctum | SPA + mobile token auth, first-party Laravel package |
| Database | PostgreSQL | Relational, solid for this shape of data |
| Admin | Filament 3 | Admin panel for moderation — toggle is_active, browse users/places/ratings |
| Deploy | Laravel Forge + DigitalOcean, or Railway | Forge is the Laravel-native choice; Railway is simpler to start |

---

## Data Model

All primary keys are UUIDs. All models use `SoftDeletes` (`deleted_at` timestamp) in addition to `is_active` — `is_active` is a moderation flag, `deleted_at` is for user-initiated or cascade deletes. Nothing is ever hard-deleted except by explicit admin action.

### users
```
id              uuid PK
name            string
email           string unique
email_verified_at  timestamp nullable
password        string (hashed)
is_admin        boolean default false
remember_token  string nullable
timestamps
deleted_at      timestamp nullable
```

### lists
```
id          uuid PK
user_id     uuid FK → users
name        string              // e.g. "NYC June 2026"
city        string nullable     // e.g. "New York"
is_public   boolean default false
slug        string unique       // for shareable URLs
timestamps
deleted_at  timestamp nullable
```

### pizza_places
```
id               uuid PK
google_place_id  string unique   // Google Places ID — dedup key
name             string
address          string nullable
lat              decimal nullable
lng              decimal nullable
currency         char(3)         // ISO 4217 — resolved from Places API country code at creation
is_active        boolean default true
timestamps
deleted_at       timestamp nullable
```

> One record per real-world pizza joint. `currency` is resolved once at creation from the `country` field in the Places API `address_components` response and never changes. Created on first lookup via Places API, shared across all users and lists.

### list_pizza_place  (pivot)
```
id              uuid PK
list_id         uuid FK → lists
pizza_place_id  uuid FK → pizza_places
added_by        uuid FK → users
timestamps
```

### pizza_ratings
```
id              uuid PK
user_id         uuid FK → users
pizza_place_id  uuid FK → pizza_places
list_id         uuid FK → lists   // list context this rating was added in
price           decimal            // price per slice at time of visit
currency        char(3)            // inherited from pizza_place.currency at rating creation time, stored for independence
rating          decimal            // 0.0 – 10.0
note            string nullable    // hot take
is_active       boolean default true
timestamps
deleted_at      timestamp nullable

UNIQUE (user_id, pizza_place_id)   // one rating per user per place, editable
```

> Rating is global per user per place — not per list. If a place appears on two lists, the user's rating is the same on both.

---

## Computed Values

Derived, not stored — compute in query scopes or on the frontend:

- **Value score** = `rating / price` — quality per dollar
- **Average rating** = `AVG(pizza_ratings.rating)` where `pizza_place_id = ?` and `is_active = true` and `deleted_at IS NULL`
- **Average price** = `AVG(pizza_ratings.price)` (same filters)
- **Rating count** = number of active, non-deleted ratings for a place

---

## API Endpoints

All routes prefixed with `/api/v1/` — versioned from day one so future mobile clients can target a stable contract while v2 evolves.

Protected routes require `Authorization: Bearer {token}` (Sanctum).

### Auth
```
POST   /api/v1/register                     → register + send verification email, returns token
POST   /api/v1/login                        → login, returns token
POST   /api/v1/logout                       → revoke token (auth required)
GET    /api/v1/user                         → get current user (auth required)
POST   /api/v1/email/verify/{id}/{hash}     → verify email (Laravel built-in)
POST   /api/v1/email/resend                 → resend verification email
```

### Lists
```
GET    /api/v1/lists                        → get all lists for current user (auth required)
POST   /api/v1/lists                        → create a new list (auth required)
GET    /api/v1/lists/{slug}                 → get list + places + ratings (public or owned; read-only for guests)
PATCH  /api/v1/lists/{id}                   → update list (auth required, must own)
DELETE /api/v1/lists/{id}                   → soft delete list (auth required, must own)
```

### Places (within a list)
```
POST   /api/v1/lists/{list}/places          → add a place to a list (auth required, list must be accessible)
DELETE /api/v1/lists/{list}/places/{place}  → remove a place from list (auth required, must own list)
```

### Ratings
```
GET    /api/v1/places/{place}/ratings       → get all ratings for a place (public)
POST   /api/v1/places/{place}/ratings       → add or update your rating (auth required)
DELETE /api/v1/places/{place}/ratings       → soft delete your rating (auth required)
```

---

## Auth Rules

- **Registration** triggers an email verification. Unverified users can log in but cannot add places or ratings — return a clear `403` with `"message": "Email not verified."` 
- **Guests** (unauthenticated) can view public lists in read-only mode. No add/rate UI shown on the frontend.
- **Collaborative lists** — any authenticated + verified user with the link can add places and submit ratings.
- **List owner** can remove places and delete the list. Users can only remove their own ratings.
- **Account deletion** — soft-deletes the user. Ratings are anonymized (`user_id` set to a system "deleted user" UUID) rather than cascade-deleted, preserving aggregate data integrity.

---

## Rate Limiting

Add to `bootstrap/app.php` or `RouteServiceProvider`:

```php
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

RateLimiter::for('auth', function (Request $request) {
    return Limit::perMinute(10)->by($request->ip()); // tighter on login/register
});
```

Apply in `routes/api.php`:
```php
Route::middleware(['throttle:auth'])->group(function () {
    Route::post('/v1/register', ...);
    Route::post('/v1/login', ...);
});

Route::middleware(['throttle:api'])->group(function () {
    // all other routes
});
```

---

## CORS Configuration

In `config/cors.php`:

```php
'allowed_origins' => [
    'http://localhost:5173',   // Vite dev server
    'https://yourdomain.com',  // production frontend
],
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
'supports_credentials' => true,
```

Set this before writing a single route — forgetting CORS is the most common first-hour headache when the frontend and API are separate apps.

---

## Environment Variables

Two `.env` files — one per app. Document what goes where:

**`/api/.env` (Laravel)**
```
APP_NAME="The Slice Report"
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=slice_report
DB_USERNAME=
DB_PASSWORD=

SANCTUM_STATEFUL_DOMAINS=localhost:5173
FRONTEND_URL=http://localhost:5173

MAIL_MAILER=smtp   # for email verification
MAIL_HOST=
MAIL_PORT=
MAIL_USERNAME=
MAIL_PASSWORD=

FILESYSTEM_DISK=local
```

**`/client/.env` (React/Vite)**
```
VITE_API_BASE_URL=http://localhost:8000/api/v1
VITE_GOOGLE_MAPS_KEY=your_key_here
```

> Never put the Google Maps key in the Laravel `.env` — it's frontend-only and must be restricted to your domain in Google Cloud Console.

---

## Scatter Plot Behaviour

Three render modes (user-selectable toggle):

- **My ratings** — plots only the current user's rating per place
- **Average** — plots crowd average rating + average price per place; dot size = rating count
- **Overlay** — both; user's dot in full colour, average as a ghost dot behind it

Quadrant lines default to `$5 / 5.0` but are adjustable per list (stored in... actually a future v2 concern — hardcode defaults for now).

---

## Core Features (v1)

### 1. Scatter Plot
- X: price per slice, Y: quality (0–10)
- Configurable quadrant midpoints (default $5 / 5.0)
- Quadrant labels: Hidden Gem · Worth It · Skip It · Tourist Trap
- Hover tooltip: name, price, rating, note, rating count, avg rating
- Three render modes: My Ratings / Average / Overlay
- Optimistic UI — plot updates instantly on add/edit, syncs to API in background

### 2. Place + Rating Management
- Add a place via Google Places Autocomplete
- Rate separately from adding — you can add a place without rating it yet
- Edit your own rating at any time
- List owner removes places; users remove their own ratings
- Unauthenticated users see read-only view of public lists — no add/rate UI

### 3. Google Maps Setup

**APIs to enable** in Google Cloud Console:
- Places API
- Maps JavaScript API (for map view in v2)

**Frontend integration:**
```html
<script src="https://maps.googleapis.com/maps/api/js?key=YOUR_KEY&libraries=places"></script>
```

**Country → currency mapping** (resolve at place creation in the frontend before POSTing to the API):

```js
const COUNTRY_CURRENCY = {
  US: 'USD', CA: 'CAD', GB: 'GBP',
  IT: 'EUR', FR: 'EUR', DE: 'EUR', ES: 'EUR', PT: 'EUR', NL: 'EUR',
  JP: 'JPY', MX: 'MXN', AU: 'AUD', NZ: 'NZD', BR: 'BRL',
  // extend as needed
};

const country = place.address_components
  .find(c => c.types.includes('country'))?.short_name;

const currency = COUNTRY_CURRENCY[country] ?? 'USD'; // fallback to USD
```

Pass `currency` alongside the other place fields when POSTing to `/api/v1/lists/{list}/places`.

```js
const autocomplete = new google.maps.places.Autocomplete(inputRef.current, {
  types: ['establishment'],
  fields: ['name', 'place_id', 'formatted_address', 'geometry', 'address_components'],
});

autocomplete.addListener('place_changed', () => {
  const place = autocomplete.getPlace();
  const country = place.address_components
    .find(c => c.types.includes('country'))?.short_name;
  setPlaceData({
    name: place.name,
    googlePlaceId: place.place_id,
    address: place.formatted_address,
    lat: place.geometry.location.lat(),
    lng: place.geometry.location.lng(),
    currency: COUNTRY_CURRENCY[country] ?? 'USD',
  });
});
```

**Key security:** restrict to your domain in Google Cloud Console. Frontend-only — never pass to the Laravel API.

### 4. Lists
- Multiple lists per user (one per city/trip)
- Public lists accessible via slug — read-only for guests, ratable for any verified user
- Only owner can remove places or delete the list

### 5. Auth
- Register / login via email + password
- Email verification required before adding places or ratings
- Sanctum token stored in `localStorage` on the React side
- Account deletion anonymizes ratings rather than cascade-deleting

---

## Filament Admin Panel

Install alongside the Laravel API — same app, `/admin` route, zero impact on the public API.

**Install:**
```bash
composer require filament/filament
php artisan filament:install --panels
php artisan make:filament-resource User --generate
php artisan make:filament-resource PizzaPlace --generate
php artisan make:filament-resource PizzaRating --generate
php artisan make:filament-resource PizzaList --generate
```

**Resources:**

| Resource | Key columns | Key actions |
|---|---|---|
| Users | name, email, is_admin, created_at | Toggle is_admin, view lists + ratings |
| PizzaPlaces | name, address, is_active, rating count | Toggle is_active, view all ratings |
| PizzaRatings | user, place, rating, price, is_active | Toggle is_active, soft delete |
| Lists | name, owner, is_public, slug | Toggle is_public, view places |
| PizzaPlaces | name, address, currency, is_active | Toggle is_active, view all ratings |

**Access control:**
```php
->authorization(fn () => auth()->user()?->is_admin)
```

---

## Out of Scope for v1

- Map view (lat/lng stored, not rendered)
- Mobile app (API is ready for it)
- Social features (following, comments, notifications)
- Photo uploads
- Multiple currencies on the same list
- Quadrant midpoint customisation per list (hardcode defaults)

---

## Project Structure

```
slice-report/
├── api/                                        # Laravel 11
│   ├── app/
│   │   ├── Filament/Resources/
│   │   │   ├── UserResource.php
│   │   │   ├── PizzaPlaceResource.php
│   │   │   ├── PizzaRatingResource.php
│   │   │   └── PizzaListResource.php
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── ListController.php
│   │   │   │   ├── PlaceController.php
│   │   │   │   └── RatingController.php
│   │   │   └── Requests/
│   │   │       ├── StoreListRequest.php
│   │   │       ├── StorePlaceRequest.php
│   │   │       └── StoreRatingRequest.php
│   │   └── Models/
│   │       ├── User.php
│   │       ├── PizzaList.php
│   │       ├── PizzaPlace.php
│   │       └── PizzaRating.php
│   ├── database/migrations/
│   ├── routes/api.php
│   ├── config/cors.php
│   └── .env
│
├── client/                                     # React + Vite
│   ├── src/
│   │   ├── components/
│   │   │   ├── ScatterPlot.jsx
│   │   │   ├── AddPlaceForm.jsx
│   │   │   ├── RatingForm.jsx
│   │   │   ├── PlaceList.jsx
│   │   │   └── Tooltip.jsx
│   │   ├── pages/
│   │   │   ├── Home.jsx
│   │   │   ├── List.jsx          # /list/:slug — read-only for guests
│   │   │   └── Dashboard.jsx
│   │   ├── api/                  # Axios client, token management
│   │   └── main.jsx
│   ├── .env
│   └── package.json
│
└── README.md
```

---

## Getting Started (Claude Code Prompt)

```
Build a web app called "The Slice Report" — a collaborative pizza value tracker.

Stack: Laravel 11 (REST API), React + Vite (frontend), PostgreSQL, Laravel Sanctum, Filament 3 admin, Google Maps Places API.

Follow slice-report-spec.md exactly. Build in this order:

1. Migrations — all PKs UUIDs, all models with SoftDeletes + timestamps:
   - users (name, email, email_verified_at, password, is_admin, remember_token)
   - lists (user_id, name, city, is_public, slug unique)
   - pizza_places (google_place_id unique, name, address, lat, lng, currency char(3), is_active)
   - list_pizza_place pivot (list_id, pizza_place_id, added_by)
   - pizza_ratings (user_id, pizza_place_id, list_id, price, currency char(3), rating, note, is_active — UNIQUE user_id+pizza_place_id; currency copied from pizza_place at creation)

2. Eloquent models with relationships and SoftDeletes on all models.

3. CORS config — allow localhost:5173 and production domain.

4. Rate limiting — 10/min on auth routes, 60/min on everything else.

5. API routes in routes/api.php under /api/v1/ prefix:
   - Auth: register (triggers email verification), login, logout, user, verify email
   - Lists: CRUD, soft delete
   - Places: add/remove from list
   - Ratings: add-or-update (upsert on user_id+pizza_place_id), soft delete

6. Form Request validation on all write operations. Unverified users get 403 on writes.

7. Sanctum token auth middleware on all protected routes. Public list slug route is read-only without auth.

8. Filament 3 — install, generate resources for User, PizzaPlace, PizzaRating, PizzaList. Gate /admin to is_admin users.

9. React frontend:
   - Axios client with token from localStorage, base URL from VITE_API_BASE_URL
   - Auth pages: register, login, verify email prompt
   - Dashboard: list of user's lists
   - List page (/list/:slug): scatter plot + place list. Read-only UI for guests, full UI for verified users.
   - AddPlaceForm: Google Places Autocomplete → auto-fills name, google_place_id, address, lat, lng, currency (resolved from address_components country code via COUNTRY_CURRENCY map)
   - RatingForm: price, rating (0–10), note. Upserts on submit.
   - ScatterPlot (Recharts): three modes — My Ratings / Average / Overlay. Optimistic UI on add/edit.
   - Quadrant lines at $5 / 5.0. Labels: Hidden Gem, Worth It, Skip It, Tourist Trap.

10. Wire everything with Axios. Store Sanctum token in localStorage.
```

---

## Future Ideas

- **Multi-city mode** — compare NYC vs. Toronto vs. Naples on one chart
- **The Hype Index** — plot Google/Yelp rating vs. yours to surface overrated spots
- **Map mode** — entries on a city map, coloured by quadrant
- **The Holy Grail filter** — one-click to show only Hidden Gems
- **Native iOS + Android** — API is already ready
- **Email digest** — weekly summary of new ratings on your lists
- **Quadrant customisation** — adjustable midpoints per list
- **Cross-city comparison** — convert ratings to a base currency for apples-to-apples value scores across cities
