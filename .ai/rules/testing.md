# Testing

Globs: `tests/**`, `phpunit.xml`

- Pest. Create tests with `php artisan make:test --pest {Name}`; run with `php artisan test --compact`, filtering by file or `--filter` to keep runs fast.
- Tests run against the `medstoyourdoors_testing` MySQL database (see [environment.md](environment.md)), not SQLite.
- Feature tests get `RefreshDatabase` automatically via `tests/Pest.php`. Unit tests do not touch the database.
- Because `Model::preventLazyLoading()` is enabled, a feature test that renders a page will throw if a relationship was not eager loaded. Treat `LazyLoadingViolationException` as a real bug in the controller, not a test problem.
- Every module needs both:
  - **Unit tests** for calculation and rule logic: money arithmetic, coupon validation, shipping rates, order status transitions, inventory ledger maths.
  - **Feature tests** for the HTTP surface: storefront rendering, cart mutations, checkout success and failure, webhook idempotency, and authorization.
- Authorization coverage is mandatory for admin routes: assert a customer receives 403 and a guest is redirected.
- Use factories and their named states rather than hand-building models. Use `User::factory()->admin()` for staff.
- The payment gateway is set to the `fake` driver in `phpunit.xml`. Never let a test make a real outbound HTTP call; use the fake driver or `Http::fake()`.
