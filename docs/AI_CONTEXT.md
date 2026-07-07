# 📄 AI_CONTEXT.md — النسخة النهائية الكاملة

```markdown
# AI CONTEXT & ARCHITECTURAL GUARDRAILS

## 🏗️ Project Overview

- **Type**: Modular Monolith Business Platform
- **Architecture**: Pragmatic Modular Monolith
- **Application Model**: Single Laravel Application
- **Database Model**: Single PostgreSQL Database
- **Structure**:
  - `/app/Core` → Shared system capabilities
  - `/app/Modules` → Independent business modules
  - `/app/Shared` → Cross-cutting reusable components

The system is designed as one professional application containing multiple isolated business domains:

Core Modules:
- Authentication
- Authorization (RBAC)
- Users
- Roles
- Permissions
- Settings
- Audit Logs
- Notifications

Business Modules:
- Nachhilfe (Tutoring)
- Beauty (Salon)
- Restaurant
- Billing

---

# ⚖️ Core Architectural Laws (NON-NEGOTIABLE)

## 1. Modular Isolation

Every business module is an independent bounded context.

Rules:

- A module MUST own its own:
  - Models
  - Database tables
  - Business logic
  - Services
  - Controllers
  - Tests

Forbidden:

- Direct access to another module's database tables.
- Joining another module's tables.
- Importing another module's Models.
- Sharing business logic between modules.

Communication between modules happens only through:

1. Shared Contracts
2. Domain Events
3. Application Services exposed through interfaces

---

## 2. Core Must Stay Business-Agnostic

`App\Core` must never know business modules.

Forbidden:

```php
if ($module === 'restaurant')
if ($module === 'beauty')
if ($module === 'nachhilfe')
```

Core cannot contain:

* Product names
* Business rules
* Module-specific conditions

Core provides only technical capabilities:

* Authentication
* Authorization
* Events
* Notifications
* Logging
* Settings
* Infrastructure services

---

## 2.1 Billing Module Isolation

Billing is an INDEPENDENT business module, NOT part of Core.

Core NEVER knows about:
- Invoices
- Payments
- Transactions
- Financial reports

Billing owns:
- invoices table
- payments table
- transactions table

Other modules (Nachhilfe, Beauty, Restaurant) communicate with Billing through:
- Shared/Contracts/BillingContract.php
- Domain Events (InvoicePaid, PaymentFailed)

---

## 3. Module Discovery and Registration

Modules are loaded using configuration.

NEVER load modules by querying the database during application boot.

Forbidden:

```php
DB::table('modules')->get()
```

inside:

* Service Providers
* Bootstrap process
* Application boot sequence

Allowed:

```php
config/modules.php
```

Example:

```php
return [
    'Nachhilfe' => [
        'enabled' => true,
        'provider' => App\Modules\Nachhilfe\Providers\NachhilfeServiceProvider::class
    ],

    'Beauty' => [
        'enabled' => true,
        'provider' => App\Modules\Beauty\Providers\BeautyServiceProvider::class
    ],
];
```

Database configuration may only control runtime activation:

Example:

* Feature availability
* Subscription state
* Module settings

It must NOT control Laravel boot loading.

---

## 3.1 Runtime Activation Strategy

Runtime module activation MUST use Cache-First strategy:

```
HTTP Request
    ↓
ModuleRegistry::isEnabled('Nachhilfe')
    ↓
Check Cache (Redis/File)
    ↓
Cache hit → return true/false
Cache miss → Query DB → Store in Cache → return
```

NEVER query database directly during HTTP request for module status.

Implementation example:

```php
class ModuleRegistry
{
    public function isEnabled(string $module): bool
    {
        $cacheKey = "module_enabled_{$module}";
        
        return Cache::remember($cacheKey, 3600, function () use ($module) {
            return DB::table('module_settings')
                ->where('module', $module)
                ->where('enabled', true)
                ->exists();
        });
    }
}
```

---

## 4. Pragmatic Clean Architecture

We do NOT apply extreme Enterprise DDD everywhere.

Architecture must balance:

* Maintainability
* Development speed
* Professional quality

## Complex Business Domains

Use full separation:

Examples:

* Lesson booking
* Appointment scheduling
* Restaurant orders
* Payments

Structure:

```
Domain/
Application/
Infrastructure/
Presentation/
```

## Simple CRUD Domains

Use Laravel practical approach:

Examples:

* Subjects
* Categories
* Settings
* Static configurations

Direct Eloquent usage inside Actions is acceptable.

---

## 5. Directory Structure

```
app/

├── Core/
│
│   ├── Auth/
│   ├── Authorization/
│   ├── Notifications/
│   ├── Settings/
│   ├── Audit/
│   ├── Events/
│   └── Contracts/


├── Modules/
│
│   ├── Nachhilfe/
│   │   ├── module.json
│   │   ├── Providers/
│   │   ├── Domain/
│   │   ├── Application/
│   │   ├── Infrastructure/
│   │   └── Presentation/
│
│   ├── Beauty/
│   │   ├── module.json
│   │   ├── Providers/
│   │   ├── Domain/
│   │   ├── Application/
│   │   ├── Infrastructure/
│   │   └── Presentation/
│
│   ├── Restaurant/
│   │   ├── module.json
│   │   ├── Providers/
│   │   ├── Domain/
│   │   ├── Application/
│   │   ├── Infrastructure/
│   │   └── Presentation/
│
│   └── Billing/
│       ├── module.json
│       ├── Providers/
│       ├── Domain/
│       ├── Application/
│       ├── Infrastructure/
│       └── Presentation/


└── Shared/

    ├── Contracts/
    ├── DTOs
    ├── Enums
    ├── Exceptions
    └── Helpers
```

---

## 5.1 Module Manifest

Every module MUST contain a `module.json` file:

```
app/Modules/Nachhilfe/
├── module.json
├── Providers/
├── Domain/
├── Application/
├── Infrastructure/
└── Presentation/
```

Example `module.json`:

```json
{
    "name": "Nachhilfe",
    "version": "1.0.0",
    "enabled": true,
    "dependencies": ["Billing"],
    "description": "Tutoring management system"
}
```

This enables:
- Self-documenting modules
- Automated dependency checking
- `php artisan module:list` command

---

## 6. Database Ownership Rules

Single database.

Every table has one owner.

Example:

Core owns:

```
users
roles
permissions
audit_logs
settings
notifications
```

Nachhilfe owns:

```
students
parents
teachers
subjects
lessons
attendance
```

Beauty owns:

```
customers
services
appointments
packages
```

Restaurant owns:

```
menu_categories
menu_items
orders
order_items
reservations
tables
```

Billing owns:

```
invoices
payments
transactions
```

Forbidden:

```sql
SELECT *
FROM students
JOIN appointments
```

from another module.

---

## 7. Identity Separation

System Users are NOT Business Entities.

Core:

```
users
```

contains accounts that can login.

Business entities:

```
students
customers
restaurant guests
```

do not require users.

Allowed:

```
parents.user_id nullable
teachers.user_id nullable
beauty_customers.user_id nullable
```

A customer can exist without creating an account.

---

## 8. Database Standards

Primary Keys:

Preferred:

* UUID v7
* ULID

Avoid random UUID v4 for large tables.

All important tables require:

* Foreign key indexes
* Status indexes
* Date indexes

Critical business tables use:

```
deleted_at
```

Soft delete applies to:

* Students
* Lessons
* Customers
* Appointments
* Orders

---

## 8.1 Encryption & Blind Index Strategy

### What to Encrypt (PII Only):

- ✅ Parent email addresses
- ✅ Phone numbers
- ✅ Physical addresses
- ❌ DO NOT encrypt: names, prices, categories, service names

### Blind Index for Searchable Fields:

When exact lookup is required on encrypted data:

```sql
parents table:
- email (encrypted with AES-256-GCM)
- email_hash (SHA-256 with fixed system salt) ← for WHERE email = ?
```

Implementation:

```php
// When saving:
$parent->email = encrypt($email);
$parent->email_hash = hash('sha256', $email . config('app.encryption_salt'));

// When searching:
$searchHash = hash('sha256', $inputEmail . config('app.encryption_salt'));
$parent = Parent::where('email_hash', $searchHash)->first();
```

---

## 9. Controller Rules

Controllers are thin.

Allowed:

* Validate request
* Call Action
* Return Resource

Forbidden:

* Business logic
* Database transactions
* Complex queries
* External API calls

Example:

```php
public function store(StoreLessonRequest $request)
{
    return $this->bookLessonAction->execute(
        $request->validated()
    );
}
```

---

## 10. Repository Rules

Repositories are selective.

Required for:

* Complex aggregates
* Complex business workflows

Examples:

YES:

```
LessonRepository
OrderRepository
AppointmentRepository
PaymentRepository
```

Not required:

```
SubjectRepository
CategoryRepository
SettingsRepository
```

Simple CRUD may use Eloquent directly.

---

## 11. Events

Domain events use past tense.

Correct:

```
LessonScheduled
OrderCreated
AppointmentCancelled
```

Wrong:

```
CreateLesson
ScheduleOrder
CancelAppointment
```

---

## 12. Outbox Policy

Outbox is used ONLY for CRITICAL events that require guaranteed delivery.

### Use Outbox (Critical Events):

- InvoicePaid
- OrderConfirmed
- LessonCompleted
- PaymentProcessed
- AppointmentConfirmed (external notification)

### Use Laravel Events (Simple Events):

- SubjectCreated
- CategoryUpdated
- MenuChanged
- StudentRegistered (internal only)

### Outbox Schema:

```
outbox_events:
- event_id (UUID)
- event_type
- event_version
- occurred_at
- aggregate_id
- correlation_id
- payload (JSON)
- status (pending/processing/completed/failed)
- attempts
- available_at
- processed_at

processed_events (for idempotency):
- event_id
- consumer_name
- processed_at
UNIQUE(event_id, consumer_name)
```

---

## 13. Quality Gates

Every change must pass:

## Static Analysis

* PHPStan Level 9
* Laravel Pint
* Deptrac

## Testing

* Pest
* Unit tests for business rules
* Feature tests for APIs

---

## 14. Engineering Role Instruction

When modifying this project:

Act as a senior software architecture team.

Think like:

* Principal Software Architect
* Backend Lead Engineer
* Database Architect
* Security Engineer
* QA Lead

