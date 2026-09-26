# Architecture & Routing Rules

## Monolithic Inertia Architecture

- Fully leverage Inertia.js monolithic architecture.
- DO NOT build standalone RESTful API controllers or JSON endpoints in `routes/api.php` for internal application features.
- All form submissions, authentication flows, and data actions must be handled via web routes (`routes/web.php`) using Inertia form requests/visits.

## Controllers

- **Location:** `app/Http/Controllers/` (or sub-namespaces like `app/Http/Controllers/Auth/`)
- **Purpose:** Handle request validation, delegate logic to Service classes, and return Inertia responses (`Inertia::render('PageName', $props)`) or Inertia redirects (`redirect()->route(...)` / `back()`).
- **Skinny Controllers:** Keep controllers skinny. Move reusable business logic, queries, or domain operations into Service classes. Do not use Repository pattern.

## Authentication & Authorization

- **Authentication:** Standard Laravel Web Session Authentication (`web` guard, cookie-based session auth managed natively by Inertia and Laravel middleware).
- Protect web routes using standard Laravel `auth` and `guest` middleware.
- Exception: a route both guests and signed-in users need, such as `PUT /locale`, sits outside both groups on purpose.
