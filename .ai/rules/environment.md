# Local Environment Constraints

Globs: `phpunit.xml`, `config/**`, `.env*`

These are properties of the machine this project is developed on. Do not "fix" them by reintroducing the tooling they rule out.

- **No Redis.** The `redis` PHP extension is not installed and no Redis server runs locally. Cache and queue use the `database` driver.
- **No Laravel Horizon.** Horizon requires Redis and the `pcntl` extension, and `pcntl` does not exist on Windows. Queue work is done with `php artisan queue:work`.
- **No `pdo_sqlite`.** The PHP build has no SQLite driver, so tests cannot use `:memory:`. `phpunit.xml` points at the `medstoyourdoors_testing` MySQL database. Create the databases with `php database/bootstrap-databases.php`.
- Cache invalidation must not rely on cache **tags**, because the database store does not support them. Use `App\Support\CacheKeys`, which embeds a per-domain version number in every key; bumping the version invalidates the domain.
- `SHOP_CACHE_ENABLED=false` in `phpunit.xml` so tests read through to the database.
