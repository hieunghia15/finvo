---
paths:
    - 'resources/js/Pages/**'
    - 'resources/js/Schemas/**'
---

# Forms

- Build forms with TanStack Form: `useForm({ defaultValues, validationLogic: revalidateLogic(), validators: { onDynamic: schema }, onSubmit })`. Read `isSubmitting` with `useStore(form.store, …)`, not `<form.Subscribe>`. `onSubmit` returns a `new Promise<void>` resolved in the visit's `onFinish`, and `<form onSubmit>` calls `e.preventDefault()` and `e.stopPropagation()` before `form.handleSubmit()`. Do not use Inertia's `useForm` or `<Form>`.
- Show field errors with `fieldError(t, field.state.meta.errors, serverError)`; typing in a field clears its server error.
- A mutation that keeps the user on the same page spreads `...IN_PLACE_SUBMIT` into the visit options.
- `FormField` is only for the Account pages. Other forms write label / input / `invalid-feedback` markup inline following the Trezo "Basic Form", with no icons inside inputs, selects or buttons.
- Each Zod schema file opens with a comment naming the FormRequest it must match.
