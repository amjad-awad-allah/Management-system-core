# 01 - Architectural Constitution

This section represents the engineering law of the project.

Any code violating these rules must be rejected during review.

---

# 1. System Architecture Rules

## Rule 1: Single Application Boundary

The whole system runs as one Laravel application.

Structure:

```
app/
├── Core/
├── Modules/
└── Shared/
```

There is:

- One Laravel application.
- One PostgreSQL database.
- One deployment.
- One authentication system.

---

## Rule 2: Core Must Never Depend On Business Modules

The Core layer provides shared capabilities only. Core is strictly business-agnostic.

Allowed Core responsibilities:

```
Authentication
Authorization (RBAC)
Users
Roles
Permissions
Settings
Notifications
Audit Logging
System Events
Contracts
Shared Utilities
```

Forbidden:

```
Core -> Modules/Nachhilfe
Core -> Modules/Beauty
Core -> Modules/Restaurant
```

Example forbidden code:

```php
if ($module === 'restaurant') {
    ...
}
```

Core must never contain product names, business rules, or module-specific conditions.

---

## Rule 3: Business Modules Are Isolated

Every module represents an independent bounded context and owns its database tables, business logic, models, services, and controllers.

Example:
```
Modules/
├── Nachhilfe
├── Beauty
├── Restaurant
└── Billing
```

Modules cannot:
* Query another module's database tables directly.
* Join another module's tables in SQL queries.
* Import another module's Models.
* Share internal business logic.

Communication happens exclusively through:
1. Shared Contracts (placed in `Shared/Contracts/` for stable cross-module integration).
2. Domain Events.
3. Application Services exposed through interfaces.

---

## Rule 4: Pragmatic Clean Architecture

Clean Architecture is applied based on complexity. Not every table requires full enterprise abstraction.

### Complex Business Aggregates
Must use full layered separation:
- `Domain/`: Entities, Value Objects, Events, Repository Contracts, Services.
- `Application/`: Actions, DTOs, Handlers.
- `Infrastructure/`: Models, Repositories, Migrations, Providers.
- `Presentation/`: Controllers, Requests, Resources.

Examples:
- Lesson Booking
- Appointments
- Orders
- Payments
- Invoices

---

### Simple CRUD Resources
Do NOT create unnecessary abstraction.
Allowed:
```
Controller -> Action -> Eloquent Model
```

Examples:
- Subjects
- Categories
- Settings
- Countries
- Basic Configuration

---

## Rule 5: Domain Purity

When Domain folders exist:

Allowed:
- PHP Classes
- Value Objects
- Business Rules
- Interfaces
- Events

Forbidden:
- Laravel framework imports
- Eloquent Models
- Database Queries
- HTTP Requests
- Queues, Mail classes

Example forbidden:
```php
use Illuminate\Database\Eloquent\Model; // Forbidden inside Domain
```

---

## Rule 6: Repository Policy

Repositories are required only where they add value.

Required:
- Complex Aggregates
- External Integrations
- Multiple Persistence Strategies
- Critical Business Processes

Optional (CRUD/Direct Eloquent allowed):
- Simple CRUD
- Configuration Tables
- Reference Data

---

## Rule 7: Identity Separation

The system separates authenticating users from domain entities:

### System Users (Core)
Used for authentication. Holds accounts that can login (email, password, RBAC permissions).
- Table: `users`

### Business Actors (Modules)
Represent real-world entities (e.g., `students`, `parents`, `teachers`, `customers`). They do NOT require user accounts.
- `students` table has no `user_id` column.
- `parents` and `teachers` tables contain a nullable `user_id` column.
- Only actors requiring login are mapped to `user_id`.

---

## Rule 8: Database Ownership

Each table belongs to exactly one module owner.

- **Core**: `users`, `roles`, `permissions`, `settings`, `audit_logs`, `notifications`.
- **Nachhilfe**: `students`, `parents`, `teachers`, `subjects`, `lessons`, `attendance`.
- **Beauty**: `customers`, `services`, `appointments`, `packages`.
- **Restaurant**: `menu_categories`, `menu_items`, `orders`, `order_items`, `reservations`, `tables`.
- **Billing**: `invoices`, `payments`, `transactions`.

Direct joins or queries crossing these modular table boundaries are strictly prohibited.

---

## Rule 9: Module Registration & Bootstrapping

Modules are registered and loaded via static configuration. We strictly forbid querying database tables during the Laravel bootstrap phase.

Correct:
- `config/modules.php`

Example:
```php
return [
    'Nachhilfe' => [
        'enabled' => true,
        'provider' => App\Modules\Nachhilfe\Providers\ModuleServiceProvider::class,
    ],
];
```

Dynamic runtime status checks (enabling/disabling features) are handled using a **cache-first strategy** inside middleware or actions, never during boot.

---

## Rule 10: Database Standards

- **Primary Keys**: Prefer time-sortable **UUID v7** or **ULID** keys for better PostgreSQL indexing and lower fragmentation.
- **Indices**: Required for foreign keys, status fields, search columns, and dates.
- **Soft Deletes**: Soft deletes (`deleted_at`) apply strictly where business history matters: `students`, `lessons`, `customers`, `appointments`, `orders`.
