# Project Overview
This is a modern Full-Stack web application named Finvo built with Laravel 13, ReactJS, TypeScript, and Inertia.js.
You are an expert Full-Stack developer strictly following the conventions and architecture defined below for reading, writing, and refactoring code in this project.

## Tech Stack
- **Backend:** Laravel 13, PHP 8.5, MySQL
- **Frontend:** ReactJS 19 (Functional Components, Hooks), TypeScript 7, Inertia.js 3
- **State Management:** Zustand (UI state only), Inertia page props (Server state)
- **Schema Validation:** Zod / `@tanstack/react-form` (Frontend UX validation) & Laravel FormRequests (Backend server validation)
- **UI & Styling:** Trezo Bootstrap Admin Template (assets in `public/assets/trezo/`)
- **Build Tooling:** Vite 8 with `@vitejs/plugin-react` and `laravel-vite-plugin`
- **Authentication:** Standard Laravel Web Session Authentication (`web` guard, cookie-based session auth managed natively by Inertia and Laravel middleware)

## Architectural Rules (Strictly Enforced)

### 1. Monolithic Inertia Architecture (No Separate REST API / No Sanctum API Controllers)
We fully leverage Inertia.js architecture without building separate RESTful API controllers or using Sanctum tokens/API routes.

- **Controllers:**
  - **Location:** `app/Http/Controllers/` (or sub-namespaces like `app/Http/Controllers/Auth/`)
  - **Purpose:** Handle request validation, delegate logic to Services, and return Inertia responses (`Inertia::render('PageName', $props)`) or Inertia redirects (`redirect()->route(...)` / `back()`).
  - **Rule:** DO NOT build standalone RESTful API controllers or JSON endpoints in `routes/api.php` for internal application features. All form submissions, authentication flows, and data actions must be handled via web routes (`routes/web.php`) using Inertia form requests/visits.

### 2. Frontend Conventions (React + TypeScript + Inertia.js)
- **TypeScript:** Use strict typing. Define `Interfaces` or `Types` for component props, Inertia page props, and form state. Avoid `any`.
- **Inertia.js Navigation & Forms:**
  - Use the `<Link>` component for internal navigation.
  - Use `usePage()` to access server-shared page props (e.g., authenticated user, flash messages, global settings).
  - Use `@tanstack/react-form` or Inertia's form helpers (`router.post`, `router.put`, `router.delete`, etc.) for sending data to Laravel web routes.
  - Rely on standard Laravel web sessions for authentication and CSRF token verification (handled seamlessly by Inertia and Vite).
- **Zod & Validation:**
  - Use Zod schemas with `@tanstack/react-form` for client-side UX validation.
  - Server validation is authoritatively enforced by Laravel FormRequests. Server validation errors return back through Inertia props to display in components.

### 3. Global State Management (Zustand)
- **Scope of Use:**
  - ONLY use Zustand for Client-side UI State (e.g., `isSidebarOpen`, `theme`, `activeModal`).
  - Zustand is NOT for:
    - API/Server state cache
    - Database data
    - Form state
    - Authentication state (auth state is provided by server via Inertia `usePage().props.auth`)
- Prefer local React state for component-local state.
- Use Zustand only when UI state must be shared across unrelated components or persist across client navigation.
- **Anti-pattern:** DO NOT store database entity data (User lists, Posts, etc.) in Zustand. This data must be passed directly from Laravel via Inertia page props.

### 4. Backend Conventions (Laravel 13)
- **Routing:** 
  - All routes are defined in `routes/web.php` protected by standard Laravel web session middleware (`web`, `auth`, `guest`).
  - Do not use `routes/api.php` or Sanctum auth middleware for internal app functionality.
- **Validation:** Always use Laravel FormRequests (`app/Http/Requests`) for backend request validation.
- **Authentication:**
  - Standard Laravel session-based authentication (`Auth::attempt`, `Auth::logout`, web sessions).
  - Auth middleware guards protect web routes.
- **Skinny Controllers:** Move reusable business logic, queries, or domain operations into Services classes. Don't use Repository.

### 5. Code Style & Formatting
- **PHP:** Follow PSR-12 standards and PER Coding Style. Run `./vendor/bin/pint` to format code.
- **TypeScript/React:** Use PascalCase for components/interfaces, camelCase for functions/variables. Run `npm run format` to format JS/TS code.
- Keep components small and reusable in `resources/js/Components/`.
- Place Inertia page views in `resources/js/Pages/` (or `resources/js/pages/`).

### 6. Laravel Naming Conventions (Strict Rules)

**Directories (Folder)**
- **Root-level & Config:** lowercase (e.g., `app/`, `config/`, `routes/`, `database/`).
- **App-level (Namespaces):** Use `PascalCase` inside `app/` (e.g., `app/Http/Controllers/`, `app/Models/`, `app/Services/`).

**Classes & Files**
All class files in Laravel must use `PascalCase` matching their internal class name.

| Component | Rule | Example |
| :--- | :--- | :--- |
| **Models** | Singular, `PascalCase` | `User.php`, `ProductCategory.php` |
| **Controllers** | Singular or plural + `Controller` | `UserController.php`, `AuthController.php` |
| **Form Requests** | Action + Model + `Request` | `StoreUserRequest.php`, `LoginRequest.php` |
| **Services** | Cohesive business logic classes named after capability | `UserAuthService.php`, `PaymentService.php` |
| **Traits** | Adjective or Prefix `Has`/`Is` | `Searchable.php`, `HasRoles.php` |
| **Enums** | Singular, `PascalCase` | `UserStatus.php`, `OrderState.php` |
| **Middleware** | Functionality name | `CheckAdmin.php`, `EnsureEmailIsVerified.php` |
| **Jobs** | Verb + Noun | `SendWelcomeEmail.php`, `ProcessPayment.php` |
| **Events** | Actions that occurred | `UserRegistered.php`, `OrderShipped.php` |
| **Listeners** | Event response action | `SendWelcomeNotification.php` |

**Database & Eloquent Conventions**
- **Tables:** Plural, `snake_case` (e.g., `users`, `product_categories`).
- **Pivot Tables:** Singular, `snake_case`, alphabetical (e.g., `role_user`).
- **Columns:** `snake_case` (e.g., `first_name`, `created_at`).
- **Primary Keys:** `id`.
- **Foreign Keys:** Singular model name + `_id` (e.g., `user_id`, `category_id`).
- Use migrations for schema changes.
- Avoid N+1 queries; use eager loading (`with()`).

### 7. React & TypeScript Naming Conventions (Strict Rules)

**Directories (`resources/js/`)**
- **Components / Pages / Layouts folders:** `PascalCase` (e.g., `Components/`, `Pages/`, `Layouts/`).
- **Utilities / Hooks / Config folders:** `camelCase` (e.g., `hooks/`, `lib/`, `types/`, `features/`).

**Files (`.ts` and `.tsx`)**

| Type | Rule | File Extension | Example |
| :--- | :--- | :--- | :--- |
| **React Components** | `PascalCase` | `.tsx` | `PrimaryButton.tsx`, `UserList.tsx` |
| **Inertia Pages** | `PascalCase` (reflecting web route) | `.tsx` | `Dashboard.tsx`, `Users/Index.tsx` |
| **Layouts** | `PascalCase` + `Layout` | `.tsx` | `AppLayout.tsx`, `AuthLayout.tsx`, `MainLayout.tsx` |
| **Custom Hooks** | `camelCase` (starts with `use`) | `.ts` / `.tsx` | `useClickOutside.ts`, `useTheme.ts` |
| **Zustand Stores** | `camelCase` (`use` + `Store`) | `.ts` | `useThemeStore.ts`, `useSidebarStore.ts` |
| **Zod Schemas** | `camelCase` (Model/Action + `Schema`) | `.ts` | `userSchema.ts`, `loginSchema.ts` |
| **Types & Interfaces** | `camelCase` | `.ts` / `.d.ts` | `user.types.ts`, `inertia.d.ts` |
| **Utils / Helpers** | `camelCase` | `.ts` | `formatDate.ts`, `calculateTotal.ts` |

**Database**
- Use migrations for all schema changes.
- Never modify production schema manually.
- Use foreign key constraints where appropriate.
- Use indexes for frequently queried/filtering columns.
- Use database transactions for multi-step writes that must succeed or fail atomically.
- Avoid N+1 queries; use eager loading where appropriate.
- Do not perform database queries inside React presentation logic.

**Eloquent**
- Define relationships explicitly in Models.
- Prefer relationships over manually joining tables when appropriate.
- Use scopes for reusable query constraints.
- Use casts for typed attributes.
- Use `$fillable` / `$guarded` consistently according to project policy.
- Avoid unnecessary `DB::raw()`.

**Method Naming**
- Use `camelCase` for all methods. (Ex: `getAllUsers()`, `calculateTotal()`).
- **Controller Methods:** Adhere to verbs: `index`, `show`, `store`, `update`, `destroy`.

### 7. React & TypeScript Naming Conventions (Strict Rules)

**Directories (Frontend Folder - `resources/js/`)**
- **The folder containing the Component/Page:** `PascalCase`. (Ex: `Components/`, `Pages/`, `Layouts/`).
- **The folder containing the logic/configuration:** `camelCase`. (Ex: `hooks/`, `utils/`, `stores/`, `types/`).
- **Sub folder of Page/Component:** If a complex component requires many supporting files, create a folder named `PascalCase` with the same name as the main component. (Ex: `Components/DataTable/`).

**Files (File `.ts` and `.tsx`)**

| Type | Rule | File extension | Example |
| :--- | :--- | :--- | :--- |
| **React Components** | `PascalCase` (Duplicate component name) | `.tsx` | `PrimaryButton.tsx`, `UserList.tsx` |
| **Inertia Pages** | `PascalCase` (Reflect route) | `.tsx` | `Dashboard.tsx`, `Users/Index.tsx` |
| **Layouts** | `PascalCase` + `Layout` | `.tsx` | `AppLayout.tsx`, `AuthLayout.tsx`, `MainLayout.tsx` |
| **Custom Hooks** | `camelCase` (Start with the word `use`) | `.ts` / `.tsx` | `useClickOutside.ts`, `useFetch.ts` |
| **Zustand Stores** | `camelCase` (Start with `use` + `Store`) | `.ts` | `useAuthStore.ts`, `useThemeStore.ts` |
| **Zod Schemas** | `camelCase` (Model Name + `Schema`) | `.ts` | `userSchema.ts`, `loginSchema.ts` |
| **Types & Interfaces** | `camelCase` | `.ts` / `.d.ts` | `user.types.ts` |
| **Utils / Helpers** | `camelCase` | `.ts` | `formatDate.ts`, `calculateTotal.ts` |

**Code Naming Conventions (Inside file TS/TSX)**
- **Variables & Functions:** `camelCase`. (Ex: `const isLoading = false;`, `function handleFetchData() {}`).

**Code Naming Conventions**
- **Variables & Functions:** `camelCase` (e.g., `isLoading`, `handleSubmit`).
- **Components & Interfaces/Types:** `PascalCase` (e.g., `interface UserProfile`, `type AuthState`).
- **Constants:** `UPPER_SNAKE_CASE` (e.g., `MAX_UPLOAD_SIZE`).
- **Booleans:** Prefix with `is`, `has`, `should`, `can` (e.g., `isOpen`, `hasPermission`).

## Workflow when generating code:
1. Create Migration, Model, Form Request, and Laravel Service if needed.
2. Create Web route (`routes/web.php`) and Controller method returning Inertia response/redirect.
3. Create React Inertia Page and necessary presentational UI Components.
4. Integrate Zod schema validation for front-end form UX and use standard Inertia visits/form requests for data mutation.
