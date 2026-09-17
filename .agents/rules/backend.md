# Backend Conventions (Laravel 13 & PHP 8.5)

## Routing & Validation
- **Routes:** All routes must be defined in `routes/web.php` protected by standard Laravel web session middleware.
- **Validation:** Always use Laravel FormRequests (`app/Http/Requests`) for backend request validation. Do not validate directly inside controller methods.

## Naming & File Conventions
- Class files must use `PascalCase` matching their internal class name.

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

## Database & Eloquent
- **Tables:** Plural, `snake_case` (e.g., `users`, `product_categories`).
- **Pivot Tables:** Singular, `snake_case`, alphabetical order (e.g., `role_user`).
- **Columns:** `snake_case` (e.g., `first_name`, `created_at`).
- **Primary Keys:** `id`.
- **Foreign Keys:** Singular model name + `_id` (e.g., `user_id`, `category_id`).
- Use migrations for all schema changes. Never modify production schema manually.
- Avoid N+1 queries; always use eager loading (`with()`) where appropriate.
- Define relationships explicitly in Models.
- Use scopes for reusable query constraints and casts for typed attributes.
- Use database transactions (`DB::transaction`) for multi-step writes that must succeed or fail atomically.

## Code Style
- Follow PSR-12 standards and PER Coding Style.
- Run `./vendor/bin/pint` for formatting PHP code.
