---
paths:
    - 'app/Enums/**'
    - 'app/Models/**'
    - 'database/migrations/**'
---

# Enums, Money and Constraints

- Enums are string-backed, with TitleCase cases and snake_case values; they use `HasOptions` with a `label()` and hold the domain predicates about their cases. Store them in `string` columns cast to the enum on the model, never a database `enum()` column.
- Money is `decimal(19, 4)` in the schema and cast `'decimal:4'` (a string) on the model, never a float. Totals are computed with SQL `SUM()`, not in PHP or JavaScript.
- Value invariants such as sign or range are also enforced by a CHECK constraint added in `up()` with `DB::statement('ALTER TABLE … ADD CONSTRAINT … CHECK (…)')`, on top of the FormRequest rule.
