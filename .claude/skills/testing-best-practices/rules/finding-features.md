# How to Find Test Framework Features

This project uses PHPUnit 12 with Laravel's testing layer (see `SKILL.md`). Find an existing feature before implementing the behavior by hand.

- Look the capability up in the installed code (`vendor/phpunit/phpunit`, `vendor/laravel/framework/src/Illuminate/Testing`, `.../Foundation/Testing`) or the Laravel 13 testing docs, rather than relying on the name of a function you remember.
- If the installed version does not provide the feature, tell the user. Do not write an API that you have not confirmed.

The table below comes from Pest. Rows marked "Pest only" have no PHPUnit equivalent here; do not install Pest to get them.

| Work that you need                             | In this project                                       |
| ---------------------------------------------- | ----------------------------------------------------- |
| Run one test with many input values            | `#[DataProvider]` static provider                     |
| Assert over many values or over a collection   | a loop of assertions, or `assertEqualsCanonicalizing` |
| Remove the same setup from each test in a file | `setUp()` or a private helper method                  |
| Apply a convention to the complete codebase    | Pest only (architecture testing)                      |
| Measure if the suite finds a defect            | Pest only (mutation testing)                          |
| Reduce the time of a slow suite                | `php artisan test --parallel`                         |
| Run one test while you debug                   | `php artisan test --filter=…`, `--stop-on-failure`    |

## Built-in Laravel Assertion Methods

Laravel provides assertions for each part of the framework. Fetch `https://laravel.com/framework/docs/testing` for the complete list, and search for an assertion before building a check by hand. Examples include `assertDatabaseHas()`, `assertModelExists()`, `assertSoftDeleted()`, response assertions such as `assertRedirectToRoute()` and `assertJsonPath()`, and fake assertions such as `Queue::assertPushed()` and `Notification::assertSentTo()`.

A hand-built check fails with `false is not true`, which identifies nothing. A framework assertion names the incorrect table, value, or response, so the failure indicates what to fix.

```php
// The failure says that false is not true. Instead of this...
expect(User::where('email', 'taylor@laravel.com')->exists())->toBeTrue();

// Use this... the failure names the table and the attributes that it did not find...
$this->assertDatabaseHas('users', ['email' => 'taylor@laravel.com']);
```
