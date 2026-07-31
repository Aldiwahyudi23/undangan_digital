# AGENTS.md — Undangan Management System

**Laravel 12 + Filament 3.3** — Wedding invitation management: Filament admin panel + 3 separate Sanctum-authenticated API identities.

## Quick start

```bash
composer setup                    # composer install → .env → key:generate → migrate → npm install → npm run build
composer dev                      # runs 4 concurrent processes: serve, queue:listen, pail (logs), vite
composer test                     # config:clear + php artisan test
```

`composer dev` requires **Node.js** — it shells out to `npx concurrently` for the vite process.

## Architecture

- **Admin Panel** at `/admin` — Filament 3.3, auto-discovered from `app/Filament/`. Panel config in `app/Providers/Filament/AdminPanelProvider.php`.
- **Guest API** at `/api/guest/*` — `auth:sanctum`. Login flow: `GET /api/guest/invitation/{uuid}` returns a Sanctum token (30-day expiry, abilities `['*']`) → send as `Authorization: Bearer`.
- **Receptionist API** at `/api/receptionist/*` — `auth:sanctum` + `receptionist` middleware. Login: `POST /api/receptionist/login`.
- **Pengantin API** at `/api/pengantin/*` — `auth:sanctum` + `pengantin` middleware. Login: `POST /api/pengantin/login`. Doorprize spin + barcode→guest linking live here.

All three identities share the `User` model. Roles: `super_admin` (Filament admin), `receptionist`, `pengantin`. Users access invitations via the `user_invitation` pivot (`User::invitations()`).

Middleware aliases are registered in `bootstrap/app.php` (`ability`, `validate.guest.device`, `receptionist`, `pengantin`). Gotcha: `ability` (`CheckTokenAbility`) and `validate.guest.device` are registered but **never wired into routes or controllers** — only `receptionist`/`pengantin` are actually used.

`routes/api.php` contains leftover dead code: an unauthenticated duplicate `live-chat` block (~lines 86-90), a stray public `GET /api/gift-accounts`, bearer tokens left in comments, and a `use` of `App\Http\Controllers\API\VehicleController` that doesn't exist. Don't trust that a route is intentional just because it lacks auth middleware.

### Key packages

- `spatie/laravel-permission` + `bezhansalleh/filament-shield` (dev dep) — RBAC. After adding/modifying Filament resources run `php artisan shield:generate --all`.
- `spatie/laravel-medialibrary` — media; `barryvdh/laravel-dompdf` — barcode PDFs; `simplesoftwareio/simple-qrcode` — QR codes; `spatie/eloquent-sortable` — drag-and-drop ordering.
- `pusher/pusher-php-server` — live chat broadcasts; Agora RTM/RTC token builders at `app/Services/Agora/`.
- `predis/predis` is in composer.json but **not actually used**: `config/database.php` defaults to `REDIS_CLIENT=phpredis` (PHP extension) and the current `.env` runs queue/cache/session on the `database` driver.
- `laravel/pail` — log streaming (part of `composer dev`).

### Filament resources

Grouped under `app/Filament/Resources/`:
- `InvitationResource/` — main resource with 16 relation managers (guests, events, couples, images, maps, stories, attendance, gifts, barcodes, doorprizes, etc.)
- `ManagementUser/` — `UserResource`, `RoleResource`
- `API/` — `ApiAccountResource` for external API tokens (lists `vehicles:*` abilities, but no vehicle controller exists in the repo)

### Seeders — gotcha

`DatabaseSeeder` only calls `AdminUserSeeder` + `ReceptionistUserSeeder`. **`PengantinUserSeeder` is not included** — run `php artisan db:seed --class=PengantinUserSeeder` manually to create the `pengantin` role/user. Logins: admin `aldiwahyudi1223@gmail.com`, `resepsionis@undangan.test`, `pengantin@undangan.test` (credentials in the seeders). Receptionist/pengantin seeders also attach the user only to the **first** invitation.

### Models

| Model | Purpose |
|---|---|
| `User` | All admin/receptionist/pengantin identities (Filament login + API) |
| `Invitation` | An invitation event grouping |
| `InvitationGuest` | Guest user (API consumer via Sanctum) |
| `InvitationBarcode` | Barcode per guest for check-in |
| `BarcodePdfBatch` | Batch PDF generation of barcodes (stores `pdf_settings` JSON) |
| `BarcodePdfTemplate` | Saved label-layout presets (paper size, margins, card size, gaps) |
| `Couple`, `Event`, `Map`, `Story` | Invitation content components |
| `FamilyMember` | Family members of the couple |
| `Image`, `ImagePlacement` | Image management and layout placement |
| `Attendance`, `GiftAccount`, `GiftTransaction` | RSVP / gift tracking |
| `GuestCheckin` | Physical check-in records |
| `DoorprizeWinner` | Doorprize spin winners |
| `Post`, `PostLike` | Guest moments & statuses |
| `LiveChat` | Live stream chat messages |

Repo hygiene: leftover `* copy.php` files exist (`app/Models/InvitationGuest copy.php`, `app/Http/Controllers/InvitationAccessController copy.php`). They're inactive (PSR-4 can't autoload filenames with spaces), but don't edit them — edit the originals. Doorprize endpoints previously under receptionist are now pengantin-only.

### Barcode label PDF (DomPDF gotchas)

Rendered by `app/Services/BarcodePdfService.php` → `resources/views/pdf/barcode-batch.blade.php`.

- **DomPDF has NO `box-sizing` support** (`width`/`height` = content box). The blade compensates: card outer size = `label_size − 2×(1.5mm padding + border)` so the *outer* card equals `label_width_mm`/`label_height_mm` exactly. Do not reintroduce `box-sizing` assumptions.
- Card layout (2-line instruction on top, then a real 2-column HTML table below a dashed divider): left cell = `Kepada YTH :` + guest `name` / `di` + `location_tag`, right cell = QR + `barcode_code`. The left cell is a **fixed 3-line template** (verified: same y-position on every card regardless of data) — `Kepada YTH :` always shows, the name line always shows (renders `&nbsp;` to keep its line height when the guest/name is missing), and `di <location_tag>` always shows with the location appended only if set. `vertical-align: middle` centers each cell's content.
- **DomPDF table gotcha**: `table-layout: fixed` discards explicit mm widths (Cellmap treats them as `auto`), so tables render short of the requested width. Use **percentage widths on `<td>`** (`$qrCellPct = (qrSize + 2×2mm) / innerW × 100`) with the table at `width: innerW mm` — DomPDF's `_assign_widths` Case 3 scales percents to fill the table exactly (verified: QR right edge lands 2.00mm off the content right edge). Don't reintroduce `table-layout: fixed` or absolute-positioned columns.
- Paper size via `setPaper([0,0,w,h], 'portrait')` in **points** (`mm × 72 / 25.4`). The PDF page is exact; if printed output measures short (e.g. 141→135mm), the print dialog is scaling to fit printable area — print at 100%/Actual size.
- QR SVG data URIs embed fine (DomPDF renders SVG vectors). Use `@php` sizing vars *before* `<head>` so the `<style>` block can reference them.
- `PhysicalsRelationManager` (Tamu Physical) now has an optional `location_tag` input (the column exists in `invitation_guests`); digital `GuestsRelationManager` has it too.
- `InvitationGuest::booted()` `creating` hook: fixed an undefined-`$permissions` bug that previously nulled the default `color_icon` (and crashed in dev mode).

### Database environment

`.env` uses **MySQL** (db `udangan_management`), but `.env.example` defaults to SQLite and `phpunit.xml` tests use SQLite `:memory:`. `composer setup` scaffolds from `.env.example`.

### Schedule (in `bootstrap/app.php`)

- `guests:clean` runs daily — locks guests + revokes tokens 3 days after last event.

### Env gotchas

`.env.example` is **incomplete** — manually add:

- `AGORA_APP_ID`, `AGORA_APP_CERTIFICATE` — required for live streaming tokens
- `PUSHER_APP_ID`, `PUSHER_APP_KEY`, `PUSHER_APP_SECRET`, `PUSHER_APP_CLUSTER` — live chat broadcasts (`BROADCAST_DRIVER=pusher`)
- `VEHICLE_API_URL`, `VEHICLE_API_TOKEN` — referenced in `config/services.php`, but the controller that consumed them is gone

### Testing

- PHPUnit with SQLite in-memory (see `phpunit.xml`).
- Tests live in `tests/Unit/` and `tests/Feature/` — currently only scaffold `ExampleTest`s.
- `composer test` runs `config:clear` before `php artisan test`.
- No lint/typecheck/format command configured (`laravel/pint` is a dev dep but no `pint.json`).
