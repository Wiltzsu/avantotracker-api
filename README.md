# AvantoTracker API

Laravel backend API for tracking ice baths. Powers the React frontend app.

Web repo: https://github.com/Wiltzsu/avantotracker-web

## Live demo

- Website: https://www.avantotracker.com
- API: https://api.avantotracker.com

## Local setup

1. Copy `.env.example` to `.env` and set `APP_KEY` (`php artisan key:generate`).
2. For local dev, SQLite is the default (`DB_CONNECTION=sqlite`).
3. Run migrations: `php artisan migrate`
4. Start the server: `php artisan serve`
5. Run tests: `php artisan test`

Production uses MySQL credentials from the server `.env` only. Do not commit secrets.

## API

- Base URL: `https://api.avantotracker.com`
- Health check: `GET /`
- Authentication: Bearer token (Sanctum), prefix `avt_`

### Auth

- `POST /api/register` - rate limited (5/min per IP)
- `POST /api/login` - rate limited after failed attempts (5/min per email+IP)
- `POST /api/logout` - requires auth
- `GET /api/me` - requires auth

Passwords must include upper and lower case letters and a number.

### Avanto endpoints (v1)

All routes below require `auth:sanctum`.

- `GET /api/v1/avanto` - list current user's avantos (paginated)
- `POST /api/v1/avanto` - create
- `GET /api/v1/avanto/{avanto}` - show (404 for other users' records)
- `PUT/PATCH /api/v1/avanto/{avanto}` - update
- `DELETE /api/v1/avanto/{avanto}` - destroy
- `GET /api/v1/stats` - aggregated stats for current user

## Security notes

- CORS is restricted to configured frontend origins (`CORS_ALLOWED_ORIGINS`).
- Login replaces existing API tokens on success.
- `user_id` cannot be set via create/update requests.
- CI runs `composer audit` and PHPUnit on push/PR.

## Tech

- Laravel API
- Laravel Sanctum (token auth)
- Eloquent ORM, migrations
