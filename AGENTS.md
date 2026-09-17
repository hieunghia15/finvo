# AGENTS.md

## Project Overview

Finvo is a full-stack web application built with:

- **Backend**: Laravel 13.x (PHP 8.5)
- **Frontend**: React 19, TypeScript 7, Inertia.js 3.x
- **Form & Validation**: `@tanstack/react-form` with `Zod` schemas (frontend), Laravel Form Requests (backend)
- **API & State**: Axios, Laravel Sanctum (SPA Cookie-based Auth), Custom React Context (`AuthProvider`)
- **UI & Styling**: Trezo Bootstrap Admin Template (assets in `public/assets/trezo/`)
- **Build Tooling**: Vite 8 with `@vitejs/plugin-react` and `laravel-vite-plugin`
- **Database**: MySQL compatible

---

# 1. General Rules

When modifying or creating code:

- Follow the existing project structure and conventions.
- Reuse existing components, utilities, hooks, types, schemas, and services whenever possible.
- Use the `@/` path alias for imports from `resources/js`.
- Do not introduce unnecessary third-party dependencies.
- Do not rewrite existing working code unless required by the task.
- Keep implementations simple, readable, and maintainable (SOLID).
- Prefer small, focused changes over large refactors.
- Do not modify unrelated files.
- Preserve existing functionality.
- Run code-checking tools (`npm run format`, `./vendor/bin/pint`) and the test suite (`php artisan test`) before finalizing your work.

---

# 2. Directory Structure & Architecture

```
finvo/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/   # API endpoints returning JsonResponse via HttpService (Controllers other than the API ones are used to render Inertia views.)
│   │   ├── Middleware/        # HandleInertiaRequests, Sanctum middleware
│   │   ├── Requests/          # Form Requests for backend validation
│   │   └── Resources/         # Eloquent API Resources (e.g. UserResource)
│   ├── Models/                # Eloquent Models (e.g. User)
│   └── Services/              # Core backend services (e.g. HttpService)
├── config/                    # Laravel configuration files
├── database/                  # Migrations, seeders, factories
├── resources/
│   ├── js/
│   │   ├── Components/        # UI components (Auth/, Common/, Layout/)
│   │   ├── Config/            # App navigation and menu configs
│   │   ├── features/          # Feature-sliced modules (e.g. auth/ - providers, hooks, api)
│   │   ├── Layouts/           # Persistent layout components (e.g. MainLayout)
│   │   ├── lib/               # HTTP client (api.ts) & Sanctum config
│   │   ├── pages/             # Inertia page views (e.g. Login/, Register/, Dashboard/)
│   │   ├── Schemas/           # Zod validation schemas
│   │   ├── types/             # TypeScript type definitions
│   │   └── app.tsx            # Inertia app entry point
│   └── views/
│       └── app.blade.php      # Main HTML layout loading Trezo CSS/JS assets & Vite entry
├── routes/
│   ├── api.php                # API routes with Sanctum & throttling
│   └── web.php                # Web routes serving Inertia pages
└── tests/                     # Feature & Unit tests
```

---

# 3. Backend Guidelines (Laravel 13)

- **Controllers**: Keep controllers thin. Route HTTP requests, delegate logic to services when appropriate, and return structured JSON via `HttpService` for API routes or `Inertia::render()` for page views.
- **API Responses**: Standardize JSON responses using `App\Services\HttpService` (e.g., `$this->httpService->success(...)` and `$this->httpService->error(...)`).
- **Validation**: Place request validation logic in Laravel Form Requests (`app/Http/Requests`).
- **Authentication**: Use Laravel Sanctum (`auth:sanctum` middleware) for protected API routes and web session cookies.
- **Code Style**: Format PHP code using Laravel Pint (`./vendor/bin/pint`).

---

# 4. Frontend Guidelines (React 19 + TypeScript + Inertia.js)

- **Imports & Pathing**: Always use the `@/` path alias pointing to `resources/js` (e.g., `import { useAuth } from '@/features/auth/hooks'`).
- **Pages vs Components**:
    - `pages/`: Component targets for Inertia routing.
    - `Components/`: Shared presentational UI elements organized into subfolders (`Auth/`, `Layout/`, `Common/`).
    - `features/`: Business logic, context providers, API integration, and custom hooks scoped by feature.
- **Form Management**: Use `@tanstack/react-form` (`useForm`) combined with `Zod` schemas defined in `resources/js/Schemas/`.
- **API Integration**: Use central Axios instances from `@/lib/api` and handle Sanctum CSRF cookie initialization via `@/lib/sanctum`.
- **Code Style & Formatting**: Format TypeScript/React code using Prettier (`npm run format`).

---

# 5. Useful Commands

- **Asset Building**: `npm run build`
- **Formatting**:
    - JS/TS/CSS: `npm run format` (check with `npm run format:check`)
    - PHP: `./vendor/bin/pint` (check with `./vendor/bin/pint --test`)
- **Testing**: `php artisan test` or `composer test`
