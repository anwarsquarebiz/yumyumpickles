# Frontend

Globs: `resources/js/**`

- Inertia v2 + React 19 + TypeScript, Tailwind v4, shadcn/ui in `resources/js/components/ui`. Reuse those primitives before writing new ones.
- Pages live in `resources/js/pages`, mirroring the backend surface: `pages/admin/**` and `pages/storefront/**`.
- Two layouts:
  - `layouts/admin-layout.tsx` — sidebar shell, takes `title`, optional `description` and `actions`, renders flash messages and the page heading for you. Do not add your own `<Head>` title in an admin page; the layout sets it.
  - `layouts/storefront-layout.tsx` — customer header, mobile sheet nav, cart badge and footer. Header links come from shared `navigation.header` (admin Navigation menu), not hardcoded routes.
- Navigate with Inertia's `<Link>`, never a bare `<a>` for internal routes. Add `prefetch` to primary navigation.
- Forms use the `useForm` hook (the `<Form>` component requires Inertia 2.1+). Always `e.preventDefault()` in the submit handler and surface `errors.<field>` with `InputError`.
- Dark mode is supported throughout: every colour utility needs its `dark:` counterpart.
- Brand chrome (announcement bar, primary CTAs, cart badge, selected pickers, pagination) uses `bg-primary` / `text-primary-foreground` and accent wells use `bg-accent`. Do not hardcode `bg-neutral-900` for those.
- Use `gap-*` for spacing between siblings rather than margins.
- Shared props are typed in `resources/js/types/index.ts` as `SharedData`. `auth.user` is **nullable** because the storefront serves guests, so narrow it before use.
- Money arrives as the `Money` interface, never a raw number. Render `money.formatted`; use `money.decimal` to seed form inputs.
