# Project Overview
This is a modern Full-Stack web application with Finvo name using Laravel 13, ReactJS, TypeScript, and Inertia.js.
You are an expert Full-Stack developer strictly following the conventions and architecture defined below for reading, writing, refactoring code in this project.

## Tech Stack
- **Backend:** Laravel 13, PHP 8.5, MySQL
- **Frontend:** ReactJS 19 (Functional Components, Hooks), TypeScript 7, Inertia.js 3
- **State Management:** Zustand
- **Data Fetching:** Axios
- **Schema Validation:** Zod (Frontend) & Laravel FormRequests (Backend)
- **UI & Styling**: Trezo Bootstrap Admin Template (assets in `public/assets/trezo/`)
- **Build Tooling**: Vite 8 with `@vitejs/plugin-react` and `laravel-vite-plugin`
- **Authentication:** Laravel Sanctum (HttpOnly Session Cookie)

## Architectural Rules (Strictly Enforced)

### 1. Controllers Separation
We strictly separate UI serving from Data processing.

**Main Controllers (Web Controllers)**
- **Location:** `app/Http/Controllers/`
- **Purpose:** Exclusively for returning Inertia views.
- **Rule:** DO NOT perform heavy logic or return JSON here. Only fetch necessary initial data for the page load and return `Inertia::render('PageName', $data)`.

**API Controllers (RESTful API)**
- **Location:** `app/Http/Controllers/Api/`
- **Purpose:** Handle HTTP/API concerns only.
- **Responsibilities:**
  - Receive and authorize requests
  - Accept validated FormRequest data
  - Delegate business operations to Services
  - Return JSON responses
- **Rule:** Must return JSON responses (uses API Resources and follows the pattern in HttpService.). Must be protected by Sanctum middleware (`auth:sanctum`).

### 2. Frontend Conventions (React + TypeScript)
- **TypeScript:** Use strict typing. Define `Interfaces` or `Types` for all component props, Zustand state, and API responses. Avoid `any`.
- **Inertia.js:** 
  - Use the `<Link>` component for internal navigation.
  - Use `usePage()` only for Inertia page props and globally shared server-provided data.
  - Do not use `usePage()` as a replacement for Zustand or a general client-side state store.
- **Zod:**
  - Use Zod for client-side validation of user input and structured API payloads where validation provides value.
  - Do not duplicate every server validation rule blindly in Zod.
  - Laravel FormRequests remain the authoritative validation layer.
  - Client-side validation is for UX and must never replace server-side validation.
- **Axios:** Use Axios for all RESTful API calls to endpoints defined in `routes/api.php`. Ensure CSRF token and Sanctum auth cookies are properly handled.

### 3. Global State Management (Zustand)
- **Scope of Use:**
  - ONLY use Zustand for Client-side UI State (e.g., `isSidebarOpen`, `theme`, `activeModal`).
  - Zustand is not:
    - API cache
    - database state
    - server state
    - form state
- Prefer local React state for component-local state.
- Use Zustand only when UI state must be shared across unrelated components or persist across navigation.
- Do not create a Zustand store for state that can remain inside a component.
- **Anti-pattern:** DO NOT store database data (Server State like User lists, Posts) in Zustand. This data must be managed and passed by Laravel via Inertia props.
- **TypeScript:** Every Zustand store MUST have a clearly defined interface separating State variables and Actions.

### 4. Backend Conventions (Laravel 13)
- **Routing:** 
  - Web routes (`routes/web.php`) point to Main Controllers.
  - API routes (`routes/api.php`) point to API Controllers.
- **Validation:** Always use Laravel FormRequests (`app/Http/Requests`) for backend validation in API Controllers. Do not validate directly inside the controller method.
- **Authentication:**
  - Use Laravel Sanctum for first-party SPA authentication.
  - Authentication must use Sanctum's stateful session/cookie mechanism.
  - Do not implement Bearer tokens for the first-party web application unless explicitly required.
  - Axios must send credentials for API requests when required by the application's Sanctum configuration.
  - CSRF protection must follow Laravel/Sanctum conventions.
- **Skinny Controllers:** Move all business logic to Services.

### 5. Code Style & Formatting
- **PHP:** Follow PSR-12 standards, PER Coding Style.
- **TypeScript/React:** Use PascalCase for components and interfaces, camelCase for functions and variables.
- Keep components small and reusable. Place them in `resources/js/Components`.
- Place Inertia pages in `resources/js/Pages`.

### 6. Laravel Naming Conventions (Strict Rules)

**Directories (Folder)**
- **Root-level & Config:** lowercase. Example: `app/`, `config/`, `routes/`, `database/`.
- **App-level (Namespaces):** Use `PascalCase` for directories inside `app/`. For example: `app/Http/`, `app/Models/`, `app/Services/`, `app/Http/Controllers/Api/`.

**Classes & Files**
All class files in Laravel must use `PascalCase` and perfectly match the class names inside.

| Component | Rule | Example |
| :--- | :--- | :--- |
| **Models** | Singular, `PascalCase` | `User.php`, `ProductCategory.php` |
| **Web Controllers** | Singular or plural + `Controller` | `UserController.php`, `PageController.php` |
| **API Controllers** | Place it in the folder `Api/` | `Api/UserController.php` |
| **Form Requests** | Action + Model + `Request` | `StoreUserRequest.php`, `UpdateUserRequest.php` |
| **API Resources** | Model + `Resource` / `Collection` | `UserResource.php`, `UserCollection.php` |
| **Services** (Logic) | Name services after the business capability or domain operation they encapsulate. Prefer cohesive services with a single business responsibility. Do not create generic "God Services". | `PaymentService.php`, `UserAuthService.php` |
| **Traits** | Adjective or Prefix `Has`/`Is` | `Searchable.php`, `HasRoles.php` |
| **Enums** | Singular, `PascalCase` | `UserStatus.php`, `OrderState.php` |
| **Middleware** | Function name | `CheckAdmin.php`, `EnsureEmailIsVerified.php` |
| **Jobs** | Verb + Noun | `SendWelcomeEmail.php`, `ProcessPayment.php` |
| **Events** | Actions that have occurred/are occurring | `UserRegistered.php`, `OrderShipped.php` |
| **Listeners** | Event response verbs | `SendWelcomeNotification.php` |

**Database & Eloquent Conventions**
Although the processing is done in PHP, the Model names are directly linked to the Database and must adhere to the following:
- **Tables:** Many, `snake_case`. (Ex: `users`, `product_categories`).
- **Pivot Tables:** Singular, `snake_case`, arranged in alphabetical order. (Ex: `role_user`, not `user_role`).
- **Columns:** `snake_case`. (Ex: `first_name`, `created_at`).
- **Primary Keys:** Always `id`.
- **Foreign Keys:** Singular model name + `_id`. (Ex: `user_id`, `category_id`).

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
- Do not expose internal model structure directly from API responses; use API Resources.

**Method Naming**
- Use `camelCase` for all methods. (Ex: `getAllUsers()`, `calculateTotal()`).
- **Controller Methods:** Adhere to RESTful verbs: `index`, `show`, `store`, `update`, `destroy`.

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
| **Types & Interfaces** | `camelCase` | `.ts` / `.d.ts` | `user.types.ts`, `api-responses.ts` |
| **Utils / Helpers** | `camelCase` | `.ts` | `formatDate.ts`, `calculateTotal.ts` |
| **API Services (Axios)** | `camelCase` (Resource name + `Api`/`Service`)| `.ts` | `userApi.ts`, `productService.ts` |

**Code Naming Conventions (Inside file TS/TSX)**
- **Variables & Functions:** `camelCase`. (Ex: `const isLoading = false;`, `function handleFetchData() {}`).
- **Components & Interfaces/Types:**
  - `PascalCase`. (Ex: `interface UserProfile {}`, `type AuthState = {}`).
  - Prefer `type` for unions, compositions, and simple object aliases.
  - Use `interface` when declaration merging or object-oriented extension is useful.
  - Do not create types unnecessarily when an existing type can be reused.
- **Constants:** `UPPER_SNAKE_CASE`. (Ex: `const MAX_UPLOAD_SIZE = 5000;`).
- **Boolean Variables:** Start with `is`, `has`, `should`, `can`. (Ex: `isOpen`, `hasPermission`).
- **Event Handlers:** 
  - Prop name (passed down from the father): Start with `on`. (Ex: `onClose`, `onSubmit`).
  - Function name (inside the component): Start with `handle`. (Ex: `handleClose`, `handleSubmit`).

## Workflow when generating code:
1. When asked to create a new feature, first create the Backend API (Migration, Model, Request, API Controller, API Route).
2. Create the Web route and Web Controller to render the Inertia page.
3. Create the React Page and necessary Components.
4. Integrate Zod for validation, Axios for API calls, and Zustand for state if needed.
