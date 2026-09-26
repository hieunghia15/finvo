# Frontend Conventions (React 19, TypeScript 7, Inertia.js 3)

## Navigation & Inertia Forms

- Use the `<Link>` component from `@inertiajs/react` for internal navigation.
- Use `usePage()` to access server-shared page props (e.g., authenticated user, flash messages, global settings). Do not use `usePage()` as a replacement for component/UI state management.
- Use `@tanstack/react-form` or Inertia's form helpers (`router.post`, `router.put`, `router.delete`, etc.) for sending data to Laravel web routes.
- Rely on standard Laravel web session cookies for authentication and CSRF token verification.

## Validation

- Use Zod schemas with `@tanstack/react-form` for client-side UX validation.
- Server validation is authoritatively enforced by Laravel FormRequests. Server validation errors automatically return back through Inertia props to display in components.

## State Management (Zustand)

- **Scope of Use:** ONLY use Zustand for Client-side UI State (e.g., `isSidebarOpen`, `theme`, `activeModal`).
- **Zustand is NOT for:** API/Server state cache, Database data, Form state, or Auth state.
- Prefer local React state (`useState`, `useReducer`) for component-local state.
- **Anti-pattern:** DO NOT store database entity data (User lists, Posts, etc.) in Zustand. This data must be passed directly from Laravel via Inertia page props.

## Naming Conventions & File Structure

**Directories (`resources/js/`)**

- **Components / Pages / Layouts folders:** `PascalCase` (e.g., `Components/`, `Pages/`, `Layouts/`).
- **Utilities / Hooks / Config folders:** `camelCase` (e.g., `hooks/`, `lib/`, `types/`, `features/`).

**Files (`.ts` and `.tsx`)**

| Type                   | Rule                                | File Extension  | Example                                             |
| :--------------------- | :---------------------------------- | :-------------- | :-------------------------------------------------- |
| **React Components**   | `PascalCase`                        | `.tsx`          | `PrimaryButton.tsx`, `UserList.tsx`                 |
| **Inertia Pages**      | `PascalCase` (reflecting web route) | `.tsx`          | `Dashboard.tsx`, `Users/Index.tsx`                  |
| **Layouts**            | `PascalCase` + `Layout`             | `.tsx`          | `AppLayout.tsx`, `AuthLayout.tsx`, `MainLayout.tsx` |
| **Custom Hooks**       | `camelCase` (starts with `use`)     | `.ts` / `.tsx`  | `useClickOutside.ts`, `useTheme.ts`                 |
| **Zustand Stores**     | `camelCase` (`use` + `Store`)       | `.ts`           | `useThemeStore.ts`, `useSidebarStore.ts`            |
| **Zod Schemas**        | Domain + `.schema`                  | `.ts`           | `auth.schema.ts`, `category.schema.ts`              |
| **Types & Interfaces** | `camelCase`                         | `.ts` / `.d.ts` | `user.types.ts`, `inertia.d.ts`                     |
| **Utils / Helpers**    | `camelCase`                         | `.ts`           | `formatDate.ts`, `calculateTotal.ts`                |

**Code Conventions**

- **Variables & Functions:** `camelCase` (e.g., `isLoading`, `handleSubmit`).
- **Components & Interfaces/Types:** `PascalCase` (e.g., `interface UserProfile`, `type AuthState`).
- **Constants:** `UPPER_SNAKE_CASE` (e.g., `MAX_UPLOAD_SIZE`).
- **Booleans:** Prefix with `is`, `has`, `should`, `can` (e.g., `isOpen`, `hasPermission`).
- Keep components small. Components shared across pages go in `resources/js/Components/`; a component used by a single page lives beside it (see Project Patterns).
- Place Inertia page views in `resources/js/Pages/`.

## Code Style

- Run `npm run format` for formatting code.

## Project Patterns

- Components are `export default function Name(props: NameProps)`; `React.FC` only remains in the layout components ported from the template.
- A component used by one page lives beside it in `Pages/<Domain>/`; only components shared across pages go in `Components/`.
- Types of a new domain go in `types/<domain>.types.ts`, re-exported from `types/index.d.ts`.
- Dropdowns and modals are driven by React state, never Bootstrap's JS (`data-bs-*`, `window.bootstrap`). Modals use `Components/Common/Modal`, mounted by the parent to open and unmounted to close. A modal closes only through its × button or a Cancel button, never by clicking the backdrop or pressing Esc.
- New pages show the result of a create, update or delete as a `sonner` toast through `<FlashToasts />`, not as an inline alert. The inline alerts left on Account and Login are legacy.
