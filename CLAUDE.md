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

<!-- gitnexus:start -->

# GitNexus — Code Intelligence

This project is indexed by GitNexus as **finvo** (4389 symbols, 12080 relationships, 383 execution flows).

> Index stale? Run `node .gitnexus/run.cjs analyze --index-only` from the project root — it auto-selects an available runner. No `.gitnexus/run.cjs` yet? Bootstrap with `npx`, `bunx`, or `pnpm dlx` — e.g. `bunx gitnexus@latest analyze` (npm 11 npx crash; #1939).

## Always Do

- **MUST run impact before editing.** Use `impact({target: "symbolName", direction: "upstream"})` or `node .gitnexus/run.cjs impact "symbolName" --direction upstream --repo .`; report callers, processes, and risk. Never substitute grep for graph analysis.
- **MUST analyze graph changes before committing.** Use `detect_changes({scope: "all"})` (MCP) or `node .gitnexus/run.cjs detect-changes --scope all --repo .` (CLI fallback). `partial: true` or `truncated: true` is not a clean check — a zero means unseen, not unaffected; re-run it. For regression review: `detect_changes({scope: "compare", base_ref: "main"})` or `node .gitnexus/run.cjs detect-changes --scope compare --base-ref "main" --repo .`.
- MUST warn on HIGH/CRITICAL `risk` pre-edit; never use `riskSharedAxes` to waive a HIGH/CRITICAL `risk` warning. Compare File/symbol: MCP File omits axes; Graph-RAG expands File.
- **MUST treat `risk: UNKNOWN` as unresolved, not as low.** An empty caller set is not evidence the symbol is unused — it can also mean the callers are not resolvable by the index (plain-object property access, dynamic dispatch, cross-language calls). `impact` pairs `UNKNOWN` with a `riskNote` saying so. Confirm with a text search before treating the symbol as safe to change or delete; do not proceed on the strength of a zero.
- **MUST use `query({search_query: "concept"})` for concepts/flows, `context({name: "symbolName"})` for a named symbol, or `impact` for blast radius, on read-only callers, dependencies, imports, or execution flow.** Graph first; text search only for empty/`UNKNOWN`/literals.
- For security review, `explain({target: "fileOrSymbol"})` lists taint findings (source→sink flows; needs `analyze --pdg`).

## Never Do

- NEVER edit a function, class, or method before MCP/CLI impact analysis.
- NEVER ignore HIGH or CRITICAL risk warnings from impact analysis, and never read `UNKNOWN` as an all-clear — it means the walk could not answer, which is the one verdict that requires confirming by other means.
- NEVER rename symbols with find-and-replace — use `rename` which understands the call graph.
- NEVER commit before MCP/CLI graph change analysis.

## Resources

| Resource                               | Use for                                  |
| -------------------------------------- | ---------------------------------------- |
| `gitnexus://repo/finvo/context`        | Codebase overview, check index freshness |
| `gitnexus://repo/finvo/clusters`       | All functional areas                     |
| `gitnexus://repo/finvo/processes`      | All execution flows                      |
| `gitnexus://repo/finvo/process/{name}` | Step-by-step execution trace             |

## CLI

| Task                                         | Read this skill file                               |
| -------------------------------------------- | -------------------------------------------------- |
| Understand architecture / "How does X work?" | `.claude/skills/gitnexus-exploring/SKILL.md`       |
| Blast radius / "What breaks if I change X?"  | `.claude/skills/gitnexus-impact-analysis/SKILL.md` |
| Trace bugs / "Why is X failing?"             | `.claude/skills/gitnexus-debugging/SKILL.md`       |
| Rename / extract / split / refactor          | `.claude/skills/gitnexus-refactoring/SKILL.md`     |
| Tools, resources, schema reference           | `.claude/skills/gitnexus-guide/SKILL.md`           |
| Index, status, clean, wiki CLI commands      | `.claude/skills/gitnexus-cli/SKILL.md`             |

<!-- gitnexus:end -->
