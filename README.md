# 🧊 AvantroTracker API

Laravel backend API for tracking ice baths. Powers the React frontend app.

Web repo: https://github.com/Wiltzsu/avantotracker-web

## Live Demo

- Website: https://www.avantotracker.com
- API: https://api.avantotracker.com

## API

- Base URL: `https://api.avantotracker.com`
- Health check: `GET /` → `{"message":"AvantoTracker API","status":"healthy", ...}`
- Authentication: Protected endpoints require a Bearer token (Sanctum)

### Avanto Endpoints (v1)

- `GET /api/v1/avanto` → list current user’s avantos (paginated)
- `POST /api/v1/avanto` → create
- `GET /api/v1/avanto/{avanto}` → show
- `PUT/PATCH /api/v1/avanto/{avanto}` → update
- `DELETE /api/v1/avanto/{avanto}` → destroy

Notes:
- All routes above are protected by `auth:sanctum`.
- Pagination default: 10 per page (use `?page=n`).

## Features (so far)

- Auth (register, login, logout) with Bearer tokens (Sanctum)
- Some CRUD for avanto entries
- Pagination and structured resource responses

## Tech

- Laravel API
- Laravel Sanctum (token auth)
- Eloquent ORM, Migrations
