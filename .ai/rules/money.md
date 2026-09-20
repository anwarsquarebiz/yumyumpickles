# Money Handling

Globs: `app/**`, `database/migrations/**`, `resources/js/**`

- Every monetary value is stored as an **unsigned integer in minor units** (cents). Never use `float`, `double` or `decimal` for money.
- Column naming: use the `*_amount` suffix (`subtotal_amount`, `shipping_amount`, `unit_price_amount`). Tables whose single money column *is* the amount use plain `amount` (`payments.amount`, `refunds.amount`).
- Cast money columns with `App\Casts\MoneyCast`, which hydrates `App\Support\Money`.
- Do arithmetic through `Money` (`plus`, `minus`, `multipliedBy`, `percentage`, `cappedAt`, `atLeastZero`). Do not pull `->amount` out to do raw integer math in services.
- Percentages are stored as **basis points**, where `10000` equals 100%. `Money::percentage()` expects basis points.
- `Money` serialises to JSON as `{ amount, currency, formatted, decimal }`. On the frontend use the `Money` TypeScript interface: `formatted` for display, `decimal` for form inputs, `amount` only for comparisons.
- A discount must never make a total negative. Cap it with `cappedAt($subtotal)` and clamp with `atLeastZero()`.
