# Application Architecture

Globs: `app/**`, `routes/**`

## Layering

Controllers stay thin: authorize, accept a Form Request, delegate to a service, return a response.

- Business logic lives in `app/Services/<Domain>/`.
- Validation lives in `app/Http/Requests/<Area>/` Form Requests. Do not validate inline in controllers.
- Authorization lives in `app/Policies/`. The admin surface is additionally gated by the `admin` middleware alias (`App\Http\Middleware\EnsureUserIsAdmin`).
- Repositories (`app/Repositories/`) exist only for read-heavy query surfaces such as catalog browsing and admin listings. Do not wrap every model in a repository.

## Controllers

- Admin controllers live in `App\Http\Controllers\Admin` and are resource controllers.
- Storefront controllers live in `App\Http\Controllers\Storefront`.
- Single-action controllers use `__invoke()`.

## Routes

Route files are split by surface and required from `routes/web.php`:

- `routes/storefront.php` — customer-facing Shopify-style URLs.
- `routes/admin.php` — `/admin` prefix, `['auth', 'verified', 'admin']` middleware, `admin.` name prefix.
- `routes/webhooks.php` — server-to-server, CSRF-exempt, API-key authenticated.

Always link with named routes and `route()`.

## Models

- Strict mode is on: `Model::preventLazyLoading()` and `preventSilentlyDiscardingAttributes()` are enabled outside production. **Always eager load** relationships you render, or tests will throw `LazyLoadingViolationException`.
- Enum-backed columns are cast to the enums in `app/Enums/`. Status transition rules live on the enum (see `OrderStatus::canTransitionTo()`), not scattered through services.

## Enums

Use TitleCase case names with snake_case string values, and give each enum a `label()` plus a static `options()` for driving frontend selects.
