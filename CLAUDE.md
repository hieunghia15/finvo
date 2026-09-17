# Project Overview

This is a modern Full-Stack web application named Finvo built with Laravel 13, ReactJS, TypeScript, and Inertia.js.
You are an expert Full-Stack developer strictly following the conventions and architecture defined below for reading, writing, and refactoring code in this project.

Detailed project rules are organized in `.agents/rules/`:

- [Architecture & Routing Rules](file:///C:/laragon/www/finvo/.agents/rules/architecture.md)
- [Backend Rules (Laravel & PHP)](file:///C:/laragon/www/finvo/.agents/rules/backend.md)
- [Frontend Rules (React, TypeScript & Inertia.js)](file:///C:/laragon/www/finvo/.agents/rules/frontend.md)

## Tech Stack

- **Backend:** Laravel 13, PHP 8.5, MySQL
- **Frontend:** ReactJS 19 (Functional Components, Hooks), TypeScript 7, Inertia.js 3
- **State Management:** Zustand (UI state only), Inertia page props (Server state)
- **Schema Validation:** Zod / `@tanstack/react-form` (Frontend UX validation) & Laravel FormRequests (Backend server validation)
- **UI & Styling:** Trezo Bootstrap Admin Template (assets in `public/assets/trezo/`)
- **Build Tooling:** Vite 8 with `@vitejs/plugin-react` and `laravel-vite-plugin`
- **Authentication:** Standard Laravel Web Session Authentication (`web` guard, cookie-based session auth managed natively by Inertia and Laravel middleware)

## Architectural Rules (Summary)

### 1. Monolithic Inertia Architecture

- Fully leverage Inertia.js monolithic architecture without separate RESTful API controllers or Sanctum API routes.
- Controllers are located in `app/Http/Controllers/` and return Inertia views or redirects.
- All routes are defined in `routes/web.php`. Do not use `routes/api.php` for internal application features.

### 2. Frontend Conventions (React + TypeScript + Inertia.js)

- Use strict typing in TypeScript.
- Use `<Link>` component for internal navigation and `usePage()` for shared page props.
- Use `@tanstack/react-form` or Inertia's form helpers for data mutations via web routes.
- Zod is used for client-side UX validation, while Laravel FormRequests enforce backend server validation.

### 3. Global State Management (Zustand)

- ONLY use Zustand for Client-side UI state (sidebar toggle, themes, modals).
- DO NOT use Zustand for database state, server cache, form state, or auth state. Pass server state directly via Inertia props.

### 4. Backend Conventions (Laravel 13)

- Use standard Laravel session-based authentication (`web` guard).
- Skinny Controllers: Delegate business logic to Service classes. Do not use the Repository pattern.
- Form Requests (`app/Http/Requests`) must be used for backend validation.

### 5. Code Style & Formatting

- **PHP:** PSR-12 and PER Coding Style (`./vendor/bin/pint`).
- **TypeScript/React:** PascalCase for components/interfaces, camelCase for functions/variables (`npm run format`).

## Workflow when generating code:

1. Create Migration, Model, Form Request, and Laravel Service if needed.
2. Create Web route (`routes/web.php`) and Controller method returning Inertia response/redirect.
3. Create React Inertia Page and necessary presentational UI Components.
4. Integrate Zod schema validation for front-end form UX and use standard Inertia visits/form requests for data mutation.
