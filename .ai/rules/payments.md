# Payments

Globs: `app/Services/Payments/**`, `app/Http/Controllers/Payments/**`, `config/payments.php`

The store integrates a **custom** gateway whose exact contract is supplied by the operator, not a vendor SDK.

- All gateway access goes through the `App\Contracts\PaymentGateway` interface, resolved by `PaymentGatewayManager`. Drivers: `custom` (real HTTP), `manual` (offline settlement), `fake` (tests).
- Never hardcode remote field names, paths or status strings in a driver. They are configured in `config/payments.php` under `endpoints`, `response_map` and `status_map`, so the contract can change without touching code.
- Authentication is a **static API key** in a header, in both directions: outbound requests carry it, and inbound webhooks are rejected without it. Compare keys with `hash_equals()`.
- **The webhook is the source of truth** for payment state. The browser callback only reads what the webhook already recorded; it must never be the thing that marks an order paid, because a customer can abandon the redirect.
- Webhook handling must be **idempotent**. `payments` has a unique index on `(gateway, gateway_reference)`, and `PaymentStatus::isSettled()` guards against reprocessing. A duplicate delivery must not decrement inventory or redeem a coupon twice.
- Never log the API key or a full card payload. Store gateway request/response bodies in `payments.request_payload` / `response_payload` only.
