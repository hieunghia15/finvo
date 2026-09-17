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

| Type                   | Rule                                  | File Extension  | Example                                             |
| :--------------------- | :------------------------------------ | :-------------- | :-------------------------------------------------- |
| **React Components**   | `PascalCase`                          | `.tsx`          | `PrimaryButton.tsx`, `UserList.tsx`                 |
| **Inertia Pages**      | `PascalCase` (reflecting web route)   | `.tsx`          | `Dashboard.tsx`, `Users/Index.tsx`                  |
| **Layouts**            | `PascalCase` + `Layout`               | `.tsx`          | `AppLayout.tsx`, `AuthLayout.tsx`, `MainLayout.tsx` |
| **Custom Hooks**       | `camelCase` (starts with `use`)       | `.ts` / `.tsx`  | `useClickOutside.ts`, `useTheme.ts`                 |
| **Zustand Stores**     | `camelCase` (`use` + `Store`)         | `.ts`           | `useThemeStore.ts`, `useSidebarStore.ts`            |
| **Zod Schemas**        | `camelCase` (Model/Action + `Schema`) | `.ts`           | `userSchema.ts`, `loginSchema.ts`                   |
| **Types & Interfaces** | `camelCase`                           | `.ts` / `.d.ts` | `user.types.ts`, `inertia.d.ts`                     |
| **Utils / Helpers**    | `camelCase`                           | `.ts`           | `formatDate.ts`, `calculateTotal.ts`                |

**Code Conventions**

- **Variables & Functions:** `camelCase` (e.g., `isLoading`, `handleSubmit`).
- **Components & Interfaces/Types:** `PascalCase` (e.g., `interface UserProfile`, `type AuthState`).
- **Constants:** `UPPER_SNAKE_CASE` (e.g., `MAX_UPLOAD_SIZE`).
- **Booleans:** Prefix with `is`, `has`, `should`, `can` (e.g., `isOpen`, `hasPermission`).
- Keep components small and reusable in `resources/js/Components/`.
- Place Inertia page views in `resources/js/Pages/`.
