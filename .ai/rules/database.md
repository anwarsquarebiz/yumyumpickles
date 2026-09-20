# Database and Migrations

Globs: `database/**`

- Migrations must run on **MySQL** (development and testing). Avoid constructs the project does not need, and keep schema definitions plain so the schema stays portable.
- **Migration ordering matters.** `php artisan make:migration` timestamps to the second, so several migrations generated in one batch can collide and sort alphabetically, placing `create_product_variants_table` *before* `create_products_table` and breaking foreign keys. After batch-generating, rename files into explicit dependency order.
- Store enum-backed columns as `string` with an explicit length plus an index, not `$table->enum()`. This keeps later changes cheap and avoids driver-specific check constraints.
- Money columns are `unsignedBigInteger`. See [money.md](money.md).
- Historical records must not change when catalog data changes:
  - `order_items` denormalises `product_title`, `variant_title` and `sku`, and its `product_id` / `product_variant_id` are nullable with `nullOnDelete()`.
  - `orders` stores `shipping_address` and `billing_address` as JSON snapshots rather than referencing `addresses`.
- Ledger tables (`inventory_movements`, `order_status_events`, `coupon_redemptions`) are **append-only**. Never update or delete rows; write a new row instead.
- Every model gets a factory. Factories should produce a valid, publishable record by default, with named states for variations (`admin()`, `draft()`, `outOfStock()`).
