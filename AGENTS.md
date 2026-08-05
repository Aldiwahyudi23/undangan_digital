# AGENTS.md — Undangan Management System

**Laravel 12 + Filament 3.3** — Wedding invitation management: Filament admin panel + 3 separate Sanctum-authenticated API identities.

## Quick start

```bash
composer setup                    # composer install → .env → key:generate → migrate → npm install → npm run build
composer dev                      # runs 4 concurrent processes: serve, queue:listen, pail (logs), vite
composer test                     # config:clear + php artisan test
```

Both `composer setup` and `composer dev` require **Node.js** — setup runs `npm install`/`npm run build`, and dev shells out to `npx concurrently` for the vite process.

`composer setup` migrates but does **not** seed. After a fresh setup, create users with `php artisan db:seed` (runs `AdminUserSeeder` + `ReceptionistUserSeeder`), then run the two excluded seeders manually (see Seeders below). `composer setup` also does **not** run `php artisan storage:link` — media (images, posts, barcode PDFs) lives on the `public` disk served at `APP_URL/storage/...`, so run `storage:link` after setup or uploaded media 404s.

## Architecture

- **Admin Panel** at `/admin` — Filament 3.3, auto-discovered from `app/Filament/`. Panel config in `app/Providers/Filament/AdminPanelProvider.php`.
- **Guest API** at `/api/guest/*` — `auth:sanctum`. Login flow: `GET /api/guest/invitation/{uuid}` returns a Sanctum token (30-day expiry, abilities `['*']`) → send as `Authorization: Bearer`. The `invitation_guests.token` column (auto-generated 40-char string) is **legacy/unused for auth** — auth is only via the Sanctum bearer token; `guests:clean` nulls the column to "lock" a guest.
  - Guest tokens are **device-scoped** (`app/Http/Controllers/InvitationAccessController.php`): a fingerprint is hashed from `x-device-id`/`user-agent`/`x-platform`/`x-app-version` headers, stored as `device_fingerprint` on the token row and in the guest's `device_ids` array, and capped by `max_device`. `/api/guest/{me,logout,devices}` rely on this fingerprint matching — don't strip those headers or the device-limit logic breaks.
  - `loginViaLink` refuses login (403) once `now > lastEvent.date endOfDay + 3 days`.
  - `InvitationGuest.role` is `host` for the live-streaming host; everyone else is effectively a viewer. Live presence is tracked via `is_streaming`/`is_watching_live`/`last_seen_live` (Agora feature).
- **Receptionist API** at `/api/receptionist/*` — `auth:sanctum` + `receptionist` middleware. Login: `POST /api/receptionist/login`.
- **Pengantin API** at `/api/pengantin/*` — `auth:sanctum` + `pengantin` middleware. Login: `POST /api/pengantin/login`. Doorprize spin + barcode→guest linking live here.

All three identities share the `User` model. Roles: `super_admin` (Filament admin), `receptionist`, `pengantin`. Users access invitations via the `user_invitation` pivot (`User::invitations()`).

Middleware aliases are registered in `bootstrap/app.php` (`ability`, `validate.guest.device`, `receptionist`, `pengantin`). Gotcha: `ability` (`CheckTokenAbility`) and `validate.guest.device` are registered but **never wired into routes or controllers** — only `receptionist`/`pengantin` are actually used.

`routes/api.php` has junk plus both deliberate and **accidental** public routes. Junk (safe to ignore): real bearer tokens left in comments, and a `use` of `App\Http\Controllers\API\VehicleController` that doesn't exist (note `API` is capitalized — inconsistent with the `Api\` namespaces used elsewhere; harmless because nothing references it). **Accidental public duplicates that actually work**: an unauthenticated `live-chat` block and a stray public `GET /gift-accounts` — their controllers (`LiveChatController`, `AttendanceController::getGiftAccounts`) don't require auth, so these routes are live endpoints that bypass Sanctum, **not** dead code. Don't delete them as dead, and don't strip their auth'd siblings (`/guest/live-chat`, `/guest/gift-accounts`) as redundant. Deliberately-public and **intentional**: the public `agora` group (`AgoraController::token()` authenticates via `guest_uuid` itself and 403s unless a host is streaming), `GET /test`, guest link routes (`/guest/invitation/{uuid}`, `/guest/invitation-data/{uuid}`, `/guest/refresh`), public barcode routes (`/barcode/search`, `/barcode/verify`, `/barcode/{token}`), photobooth routes (`/photobooth/templates/{invitation}`, `/photobooth/frame/{uuid}`), `POST /posts/moments` + `POST /posts/voice` (unauthenticated creates that rely on `invitation_guest_id` + a `GuestCheckin` without `checkout_at` for gating), and `receptionist/login`/`pengantin/login`. Don't trust that a route is intentional just because it lacks auth middleware; read the controller before deciding.

### Key packages

- `spatie/laravel-permission` + `bezhansalleh/filament-shield` (dev dep) — RBAC. After adding/modifying Filament resources run `php artisan shield:generate --all`.
- `spatie/laravel-medialibrary` — media; `barryvdh/laravel-dompdf` — barcode PDFs; `simplesoftwareio/simple-qrcode` — QR codes; `spatie/eloquent-sortable` — drag-and-drop ordering.
- `pusher/pusher-php-server` — live chat broadcasts; Agora RTM/RTC token builders at `app/Services/Agora/`.
- `predis/predis` is in composer.json but **not actually used**: `config/database.php` defaults to `REDIS_CLIENT=phpredis` (PHP extension) and the current `.env` runs queue/cache/session on the `database` driver.
- `laravel/pail` — log streaming (part of `composer dev`).

### Filament resources

Grouped under `app/Filament/Resources/`:
- `InvitationResource/` — main resource with 17 relation managers (guests, events, couples, images, maps, stories, attendance, gifts, barcodes, doorprizes, posts, photobooth, etc.)
- `ManagementUser/` — `UserResource`, `RoleResource`
- `API/` — `ApiAccountResource` for external service accounts (a `User` with `type='service'`; the resource's query filters `where('type', 'service')`, so ordinary users don't appear). Token form lists `vehicles:*` abilities, but no vehicle controller exists in the repo
- `BarcodePdfTemplateResource/` — top-level (not grouped) — saved label-layout presets for the barcode PDF feature

### Seeders — gotcha

`DatabaseSeeder` only calls `AdminUserSeeder` + `ReceptionistUserSeeder`. **`PengantinUserSeeder` is not included** — run `php artisan db:seed --class=PengantinUserSeeder` manually to create the `pengantin` role/user. **`BarcodePdfTemplateSeeder` is also not included** — run it once (`php artisan db:seed --class=BarcodePdfTemplateSeeder`) or the barcode PDF feature has no label-layout presets. Logins: admin `aldiwahyudi1223@gmail.com`, `resepsionis@undangan.test`, `pengantin@undangan.test` (credentials in the seeders). Receptionist/pengantin seeders also attach the user only to the **first** invitation.

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
| `Post`, `PostLike` | Guest moments (`type='moment'`), 24h statuses (`type='status'`), voice notes (`type='voice'`) & likes |
| `PhotoboothTemplate` | Per-invitation photo-booth frame templates (slot geometry in `layout` JSON) |
| `LiveChat` | Live stream chat messages |

Repo hygiene: leftover `* copy.php` files exist (`app/Models/InvitationGuest copy.php`, `app/Http/Controllers/InvitationAccessController copy.php`). They're inactive (PSR-4 can't autoload filenames with spaces), but don't edit them — edit the originals. RoleResource's relation managers (`UsersRelationManager`, `PermissionsRelationManager`) live in `RoleResource/RelationManagers/` — they were once misplaced under `RoleResource/Pages/RelationManagers/` with the `...\RelationManagers` namespace, which made Filament's `class_exists`-based discovery (`Panel/Concerns/HasComponents.php::discoverComponents`) re-include the files on every boot and fatally redeclare the classes in multi-boot processes (e.g. `php artisan test`). Keep their paths matching their namespaces. Doorprize endpoints previously under receptionist are now pengantin-only.

### Barcode label PDF (DomPDF gotchas)

Rendered by `app/Services/BarcodePdfService.php` → `resources/views/pdf/barcode-batch.blade.php`.

- `barcode_code` format = `BC` + invitation id (zero-padded ≥2 digits) + 4-digit sequence scoped **per invitation** (e.g. `BC010001`, `BC020001`) — the old global `BC000001` scheme collided across invitations. Generation is centralized on `InvitationBarcode` (`codePrefix`/`formatCode`/`nextSequence`/`nextCode`); the three Filament relation managers (digital guests, physical guests, `BarcodePdfBatchesRelationManager::generateBatch`) must keep using those helpers — don't reintroduce ad-hoc code building.

- QR payload = `config('services.barcode.base_url')` (`BARCODE_BASE_URL`, defaults to `https://fixnikah.miraaldi.my.id`) + `/` + `barcode_token`; `BarcodeQrService::extractToken()` parses a scanned value back to the token (`app/Services/BarcodeQrService.php`).
- **DomPDF has NO `box-sizing` support** (`width`/`height` = content box). The blade compensates: card outer size = `label_size − 2×(1.5mm padding + border)` so the *outer* card equals `label_width_mm`/`label_height_mm` exactly. Do not reintroduce `box-sizing` assumptions.
- Card layout (2-line instruction on top, then a real 2-column HTML table below a dashed divider): left cell = `Kepada Yth. :` + guest `name` / `di <location_tag>`, right cell = QR + `barcode_code`. The left cell is a **fixed 3-line template** (verified: same y-position on every card regardless of data) — `Kepada Yth. :` always shows, the name line always shows (renders `&nbsp;` to keep its line height when the guest/name is missing), and the `di` line always renders, defaulting to the literal `Tempat` when `location_tag` is unset. `vertical-align: middle` centers each cell's content.
- **DomPDF table gotcha**: `table-layout: fixed` discards explicit mm widths (Cellmap treats them as `auto`), so tables render short of the requested width. Use **percentage widths on `<td>`** (`$qrCellPct = (qrSize + 2×2mm) / innerW × 100`) with the table at `width: innerW mm` — DomPDF's `_assign_widths` Case 3 scales percents to fill the table exactly (verified: QR right edge lands 2.00mm off the content right edge). Don't reintroduce `table-layout: fixed` or absolute-positioned columns.
- Paper size via `setPaper([0,0,w,h], 'portrait')` in **points** (`mm × 72 / 25.4`). The PDF page is exact; if printed output measures short (e.g. 141→135mm), the print dialog is scaling to fit printable area — print at 100%/Actual size.
- **Paper-size rounding gotcha**: `BarcodePdfService::paperSize()` must pass `mm × 72 / 25.4` **unrounded** to `setPaper()`. DomPDF's `check_page_break()` (`FrameDecorator/Page.php`) compares the `.page` div's CSS height (mm→pt, full precision) against the page's content height; if the page height was `round(..., 2)`-ed, any paper height whose pt value rounds *down* (e.g. 140mm → 396.850394pt vs page 396.85pt) makes the div look ~0.0004pt too tall, so DomPDF splits the **last card** of each page onto its own page — 2 expected pages become 4 (`[11,1,11,1]`). A4 (297mm → 842.126 vs 842.13) rounds *up* and is unaffected; the old `round()` bug hit 140mm exactly. If you ever reintroduce rounding, the page height must stay ≥ the div height.
- **Multi-page pagination gotcha**: `BarcodePdfService` chunks barcodes with `$barcodes->chunk($labelsPerPage, false)`. The `false` is load-bearing — Laravel 12's `Collection::chunk()` now defaults to `$preserveKeys = true`, so without it each page's keys keep the global index and the blade's `$top` continues (page 2 rows start at "row 4" position, stacking every card too low / off-page). Don't drop the `false`.
- QR SVG data URIs embed fine (DomPDF renders SVG vectors). Use `@php` sizing vars at the top of the template (before `<style>`) so the style block can reference them.
- `PhysicalsRelationManager` (Tamu Physical) now has an optional `location_tag` input (the column exists in `invitation_guests`); digital `GuestsRelationManager` has it too.
- `InvitationGuest::booted()` `creating` hook: fixed an undefined-`$permissions` bug that previously nulled the default `color_icon` (and crashed in dev mode).

### Photo booth

- `PhotoboothTemplate` (table `photobooth_templates`): per-invitation frame templates. `uuid` (HasUuids) is the public API/route key, not the int PK; slot geometry lives in the `layout` JSON (`slots()`); `frame_image`/`thumbnail` are paths on the `public` disk. Managed only via `PhotoboothTemplatesRelationManager` (no top-level resource).
- Public API: `GET /api/photobooth/templates/{invitation}` returns active templates (sortable via `sort_order`), `GET /api/photobooth/frame/{uuid}` streams `frame_image` straight from `Storage::disk('public')->path()` (not medialibrary) — needs `storage:link` or frames 404.
- Instruction PDF: `PhotoboothPdfService` renders `resources/views/pdf/photobooth-instruction.blade.php` (fixed A4, QR encodes `PHOTOBOOTH_VERIFY_URL` + `/{invitation_id}`), downloaded from a row action in the relation manager. Unlike the barcode cards this is a loose A4 layout, so the DomPDF precision gotchas below don't apply.

### Database environment

`.env` uses **MySQL** (db `udangan_management`), but `.env.example` defaults to SQLite and `phpunit.xml` tests use SQLite `:memory:`. `composer setup` scaffolds from `.env.example`.

### Schedule (in `bootstrap/app.php`)

- `guests:clean` runs daily — 3 days after the last event it sets each guest's own `token` column to null + `is_locked=true` (Sanctum tokens are **not** revoked). Note `loginViaLink`'s 403 cutoff is slightly stricter: `lastEvent.date endOfDay + 3 days`.

### Env gotchas

`.env.example` is **incomplete** — manually add:

- `AGORA_APP_ID`, `AGORA_APP_CERTIFICATE` — required for live streaming tokens (`AgoraController` reads them via `env()` directly, plus `AGORA_CHANNEL_PREFIX`/`AGORA_TOKEN_EXPIRE` — don't "refactor" that to `config()`)
- `PUSHER_APP_ID`, `PUSHER_APP_KEY`, `PUSHER_APP_SECRET`, `PUSHER_APP_CLUSTER` — live chat broadcasts (`BROADCAST_DRIVER=pusher`)
- `VEHICLE_API_URL`, `VEHICLE_API_TOKEN` — referenced in `config/services.php`, but the controller that consumed them is gone
- `PHOTOBOOTH_VERIFY_URL` — photobooth QR target in the instruction PDF (defaults to `https://fixnikah.miraaldi.my.id/moment/verify`, from `config('services.photobooth.verify_url')`)

`BARCODE_BASE_URL` (the one key that _is_ in `.env.example`) feeds both barcode QR payloads and the photobooth verify URL default.

### Testing

- PHPUnit with SQLite in-memory (see `phpunit.xml`).
- Tests live in `tests/Unit/` and `tests/Feature/` — currently only scaffold `ExampleTest`s.
- `composer test` runs `config:clear` before `php artisan test`. For a focused run: `php artisan test --filter=ExampleTest` (works for a single class or method name).
- No lint/typecheck/format command configured (`laravel/pint` is a dev dep but no `pint.json`).
