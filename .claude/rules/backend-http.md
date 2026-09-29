---
paths:
    - 'app/Http/**'
    - 'app/Services/**'
    - 'app/Providers/**'
    - 'routes/web.php'
---

# HTTP Layer

- Ownership: resolve user-owned records through the user's relation (`$request->user()->categories()->findOrFail($id)`) with the raw id in the route. No Policies and no implicit route model binding for owned records, so another user's record is a 404. A FormRequest that needs the record resolves and memoizes it in a trait under `app/Http/Requests/Concerns/`.
- One controller per domain: extra actions such as `updateStatus` or `updatePassword` are methods on the domain's controller, not separate controllers.
- In new code, controllers pass `$request->validated()` as one array to the service, whose method takes `array $data` with its shape in the docblock. Do not add per-field accessors to FormRequests. Do not rewrite existing controllers just to match.
- Normalize text input in `prepareForValidation()` behind an `is_string()` guard: `Str::squish()` for names, `Str::lower()` for emails.
- A mutation always answers with a redirect: `back()` from list pages so query-string filters survive, `redirect()->route()` otherwise. Flash `status` for success and `error` for a failure no form field owns; field failures stay validation errors.
- Rate limits are named `RateLimiter::for()` limiters in `AppServiceProvider`, used as `throttle:name`. When one trips, redirect back with an error on a form field (never a bare 429) and drop password fields from the old input.
