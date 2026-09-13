# AGENTS.md

## Project Overview

This project is a full-stack web application built with:

- Laravel 13
- PHP 8.5+
- ReactJS
- TypeScript
- Inertia.js
- Laravel Sanctum
- Vite
- MySQL compatible with Laravel

The project uses Laravel as the backend and ReactJS + Inertia.js as the frontend.

---

# 1. General Rules

When modifying or creating code:

- Follow the existing project structure.
- Reuse existing components, utilities, hooks, types, and services whenever possible.
- Do not introduce unnecessary dependencies.
- Do not rewrite existing working code unless required.
- Keep implementations simple, readable, and maintainable.
- Prefer small, focused changes over large refactors.
- Do not change unrelated files.
- Preserve existing functionality.
- Follow the project's existing coding style.

Before implementing a feature:

1. Inspect the existing codebase.
2. Identify related routes, controllers, models, requests, components, layouts, and utilities.
3. Reuse existing patterns.
4. Implement the smallest clean solution.
5. Run relevant tests, linting, formatting, and TypeScript checks.

---

# 2. Backend - Laravel 13

## Architecture

Follow standard Laravel architecture.

Use:

- Controllers for HTTP request handling.
- Form Requests for complex validation.
- Models for database interaction.
- Services only when business logic is sufficiently complex.
- Resources when API response transformation is required.
- Middleware for cross-cutting request concerns.

Avoid putting large amounts of business logic directly inside controllers.

Controllers should remain thin.

Example:

```php
public function store(StoreUserRequest $request)
{
    $user = $this->userService->create($request->validated());

    return redirect()->route('users.index');
}
```
