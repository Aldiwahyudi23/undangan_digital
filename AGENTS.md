# AGENTS.md — Undangan Management System

**Laravel 12 + Filament 3.3** — Wedding invitation management admin panel + API-driven guest frontend.

## Quick start

```bash
composer setup                    # full install (composer install, .env, key:generate, migrate, npm install, build)
composer dev                      # runs 4 concurrent processes: serve, queue:listen, pail (logs), vite
composer test                     # config:clear + php artisan test
```

`composer dev` requires **Node.js** — it shells out to `npx concurrently` for the vite process.

## Architecture

- **Admin Panel** at `/admin` — Filament 3.3, auto-discovered from `app/Filament/Resources/`, `app/Filament/Pages/`, `app/Filament/Widgets/`
- **Guest API** at `/api/guest/*` — Sanctum token auth via invitation link. Custom middleware: `ability`, `validate.guest.device` (registered in `bootstrap/app.php`)
- **Guest auth flow**: `GET /api/guest/invitation/{uuid}` returns a Sanctum token → use as Bearer token for all protected endpoints

### Key packages

- `spatie/laravel-permission` + `filament-shield` — RBAC for admin panel. Role `super_admin` seeded via `AdminUserSeeder`
- `spatie/laravel-medialibrary` — image/media management
- `pusher/pusher-php-server` — realtime broadcasts (live chat)
- Agora — video/live streaming (token generation at `app/Services/Agora/`)
- `laravel/pail` — log viewer in CLI (`composer dev` includes it)
- `predis/predis` — Redis client (production dependency, used for queue/cache/session)

### Models

| Model | Purpose |
|---|---|
| `User` | Admin user (Filament login) |
| `Invitation` | An invitation event grouping |
| `InvitationGuest` | Guest user (API consumer via Sanctum) |
| `Couple`, `Event`, `Map`, `Story` | Invitation content components |
| `FamilyMember` | Family members of the couple |
| `Image`, `ImagePlacement` | Image management and layout placement |
| `Attendance`, `GiftAccount`, `GiftTransaction` | RSVP / gift tracking |
| `Post`, `PostLike` | Guest moments & statuses |
| `LiveChat` | Live stream chat messages |

### Key commands

```bash
composer test                     # run tests (config:clear first)
php artisan shield:generate --all # regenerate Filament policies & permissions — run after adding/modifying resources
php artisan guests:clean          # lock guests + revoke tokens 3 days after last event
```

### Schedule (defined in `bootstrap/app.php`)

- `guests:clean` runs daily

### Env gotchas

`.env.example` is **incomplete** — it's missing vars required by the app. You'll need to manually add:

- `AGORA_APP_ID`, `AGORA_APP_CERTIFICATE` — required for live streaming
- `PUSHER_APP_ID`, `PUSHER_APP_KEY`, `PUSHER_APP_SECRET`, `PUSHER_APP_CLUSTER` — required for live chat broadcasts
- `VEHICLE_API_URL`, `VEHICLE_API_TOKEN` — used by `VehicleController` (see `config/services.php`)

### Testing

- PHPUnit with SQLite in-memory (see `phpunit.xml`).
- Tests live in `tests/Unit/` and `tests/Feature/`.
- `composer test` runs `config:clear` before `php artisan test`.
- Tests are currently minimal (only scaffold `ExampleTest`). No lint, typecheck, or formatting tool is configured (`laravel/pint` is a dev dep but no `pint.json` config exists).
