---
paths:
    - 'tests/**'
---

# Tests

- Tests are PHPUnit classes extending `Tests\TestCase` with `public function test_snake_case(): void` methods; never write Pest. Input matrices use a static provider with `#[DataProvider]` and string-named cases.
- Domain CRUD endpoints get one file per action: `tests/Feature/<Domain>/<Action><Domain>Test.php`. Cross-cutting features such as auth or locale keep a single `tests/Feature/<Feature>Test.php`.
- Assert Inertia props with `assertInertia()`. For a whitelisted payload, assert it inside a scoped `has()` closure so any extra key (e.g. `password`, `user_id`) fails the test.
- The suite runs in English (`APP_LOCALE=en` in `phpunit.xml`) and asserts exact message text; to test Vietnamese, send the `locale` cookie explicitly.
