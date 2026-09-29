---
paths:
    - 'app/**'
    - 'resources/js/**'
    - 'lang/**'
---

# Localization

- Translation keys are the English source sentence: `__('Account updated.')` in PHP, `t('Add New Category')` in React. Translations live in `lang/vi.json`; English needs no file. Group files are only Laravel's own `validation`, `auth`, `pagination` and `passwords`.
- In React, call `t()` from `useTranslation()` inside components. Module-level strings (Zod messages, label maps, menu config) are wrapped in the no-op `trans()` and passed through `t()` where they are displayed.
- Use `:name` placeholders, never string concatenation. Do not translate user data or messages that already come translated from the server.
- Every new key goes into `lang/vi.json`, keeping keys sorted; `npm run lang:check` must pass.
