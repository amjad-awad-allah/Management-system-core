# Combined Architecture Documentation V4.2
# Pragmatic Modular Monolith Platform

## Document Status

Version: 4.2  
Architecture Type: Pragmatic Modular Monolith  
Database Strategy: Single PostgreSQL Database  
Application Style: Modular Laravel Application  
Architecture Owner: Principal Software Engineering Team  

---

# 00 - Architectural Vision

## Purpose

This document defines the official engineering architecture rules for the application.

The system is designed as a **single Laravel modular monolith** containing:

- A shared Core system.
- Independent business modules.
- Strict module ownership boundaries.
- Config-based module discovery.
- Pragmatic Clean Architecture where complexity requires it.
- Laravel-native implementation where simplicity is preferred.

The system is NOT:

- A SaaS platform.
- A multi-tenant deployment system.
- A microservice architecture.
- A distributed system.

The goal is to create a highly maintainable enterprise application that can grow into many business domains without creating technical debt.

---

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

---

# 02 - Software Requirements Specification (SRS)

# System Scope

The application provides multiple business solutions inside one modular platform.

Modules:

```
Core
|
├── Nachhilfe
├── Beauty
├── Restaurant
└── Billing
```

---

# Core Requirements

## Authentication

Supports:

* Email/password login.
* OAuth providers.
* Future SSO integrations.

Authentication belongs only to Core.

---

## Authorization

RBAC system:

Tables:

```
roles
permissions
role_permissions
user_roles
```

Permissions are checked through policies.

---

## Audit System

Every important action must be recorded.

Example:

```
User changed lesson price.
User cancelled appointment.
User modified invoice.
```

Stored in:

```
audit_logs
```

---

## Notification System

Central notification service.

Supports:

```
Email
SMS
Database notifications
Push notifications
```

Modules communicate through contracts.

---

# Business Modules

## Nachhilfe Module

Responsible for:

```
Students
Parents
Teachers
Lessons
Attendance
Subjects
```

---

## Beauty Module

Responsible for:

```
Customers
Services
Appointments
Packages
Staff Scheduling
```

---

## Restaurant Module

Responsible for:

```
Menu
Tables
Reservations
Orders
Order Items
```

---

## Billing Module

Responsible for:

```
Invoices
Payments
Transactions
Financial Reports
```

Billing is independent.

Core does not know invoice rules.

---

# 03 - Software Architecture Document (SAD)

## High Level Architecture

```
                 Users

                   |
                   |

            Presentation Layer

                   |

            Application Layer

                   |

        -----------------------

        Core        Modules

        -----------------------

                   |

          Infrastructure Layer

                   |

             PostgreSQL

```

---

# Application Structure

```
app/

├── Core/
│   ├── Auth
│   ├── Authorization
│   ├── Users
│   ├── Notifications
│   ├── Settings
│   └── Audit

├── Modules/
│   ├── Nachhilfe
│   ├── Beauty
│   ├── Restaurant
│   └── Billing

└── Shared/
    ├── Contracts/       <-- Global stable integration contracts ONLY
    ├── DTOs/
    ├── Events/
    └── Helpers/
```

---

# Module Internal Structure

Example:

```
Modules/Nachhilfe/

├── Domain/
│   ├── Entities/
│   ├── ValueObjects/
│   ├── Events/
│   └── Contracts/       <-- Module-specific contracts live in the module

├── Application/
│   ├── Actions/
│   ├── DTOs/
│   └── Services/

├── Infrastructure/
│   ├── Models/
│   ├── Repositories/
│   └── ExternalServices/

├── Presentation/
│   ├── Controllers/
│   ├── Requests/
│   └── Resources/

└── Providers/
    └── ModuleServiceProvider.php
```

---

# Dependency Direction

Allowed:

```
Presentation
      ↓
Application
      ↓
Domain

Infrastructure implements Domain contracts
```

Forbidden:

```
Domain -> Database
Domain -> Controller
Module A -> Module B Database
Core -> Business Module
```

---

# Module Registration & Routing Flow

Module registration is configuration-driven via `config/modules.php`. At boot, the enabled module service providers register their routes, which pass through the `module.active` middleware. This middleware checks the dynamic module settings using a cache-first strategy.

```
config/modules.php
      │
      ▼
Module Service Provider
      │
      ▼
Route Registration
      │
      ▼
module.active Middleware
      │
      ▼
Cached module_settings check
```

---

# 04 - Architectural Decision Records (ADR)

This section records the official architectural decisions.

Every decision includes:

- Decision
- Reason
- Benefit
- Tradeoff

---

# ADR-01: Modular Monolith Architecture

## Decision
The system will be implemented as a Modular Monolith:
- One Laravel application.
- One PostgreSQL database.
- Multiple isolated business modules.

---

## Reason
The current business requirements do not require distributed microservices. A modular monolith provides strong boundaries, faster development, easier deployment, and lower operational complexity.

---

## Benefit
- Simple infrastructure.
- Shared authentication and authorization.
- Easier debugging and lower maintenance cost.

---

## Tradeoff
Modules share the same runtime. A future extraction into microservices is possible but not a current goal.

---

# ADR-02: Single Database Strategy

## Decision
All modules use one PostgreSQL database:
- Core owns system tables (`users`, `roles`, `permissions`, `settings`, `audit_logs`, `notifications`).
- Business modules own their respective tables.

---

## Reason
The application requires fast internal communication, simple transactions, and consistent data.

---

## Benefit
- No distributed transactions.
- Simple backup strategy and easier reporting.

---

## Tradeoff
Database boundaries are logical, not physical. Strict ownership rules are mandatory.

---

# ADR-03: Configuration Based Module Discovery & Cache-First Activation

## Decision
Modules are registered through `config/modules.php`, never database queries during bootstrap. Dynamic runtime status checks use a **cache-first strategy** in the `module.active` middleware.

---

## Reason
Database queries during boot break config caching and create instability during CLI/artisan actions. Checking active status via cached settings guarantees performance.

---

## Benefit
- Full config/route caching support.
- Safe local migrations and CLI tool executions.

---

## Tradeoff
Adding a module requires configuration deployments.

---

# ADR-04: Pragmatic Clean Architecture

## Decision
Apply Clean Architecture selectively. Complex aggregates (Lesson booking, Appointments, Orders, Payments, Invoices) use full layered separation (Domain/Application/Infrastructure/Presentation), while simple CRUD (Subjects, Categories, Settings, Countries) remains Laravel-native.

---

## Benefit
Saves development time on simple tables while preserving structure for complex domains.

---

# ADR-05: Identity Separation

## Decision
System Users are separated from business entities.
- `users` table holds authentication records.
- Business actors (e.g. `students`) contain domain details only and do not require user accounts. Nullable `user_id` links are added optionally for actors requiring login (e.g., `parents`, `teachers`).

---

# ADR-06: Billing Module Ownership

## Decision
Billing is an independent module (`app/Modules/Billing`) that owns `invoices`, `payments`, and `transactions` tables. Core has no billing logic.

---

# ADR-07: Database Identifier Strategy

## Decision
Use time-sortable **UUID v7** or **ULID** as primary keys.

---

## Reason
Random UUID v4 keys cause PostgreSQL index fragmentation. Time-ordered keys improve write and indexing performance.

---

## Benefit
- Better indexing.
- Easier debugging.
- Distributed-safe IDs.

---

## Tradeoff
Slightly larger storage compared with integers.

---

# ADR-08: Selective Outbox Scope

## Decision
Outbox is not used for every event.
- **Critical Cross-Module Events**: Transactional Outbox (e.g., `InvoicePaid`, `OrderConfirmed`, `LessonCompleted`).
- **Simple Internal CRUD Events**: Laravel Native Events (e.g., `SubjectCreated`, `CategoryUpdated`).

---

# ADR-09: Contract Placement & Late Binding

## Decision
Module-specific contracts belong to their owning module (e.g. `app/Modules/Billing/Contracts/BillingServiceContract.php`). Shared contracts under `app/Shared/Contracts/` contain only stable integration contracts, preventing circular dependencies.

---

# 05 - Entity Relationship Diagram (ERD)

The application uses one PostgreSQL database.

All tables are grouped by ownership.

---

# Core Database Tables

## Users
```
users
id UUID PK "UUID v7 / ULID"
email UNIQUE "Indexed"
password_hash
name
created_at
updated_at
```

---

## Roles
```
roles
id UUID PK
name
guard_name
```

---

## Permissions
```
permissions
id UUID PK
name
guard_name
```

---

## User Roles
```
user_roles
user_id FK "References users(id)"
role_id FK "References roles(id)"
```

---

## Role Permissions
```
role_permissions
role_id FK "References roles(id)"
permission_id FK "References permissions(id)"
```

---

## Audit Logs
```
audit_logs
id UUID PK
user_id FK "References users(id) [Indexed]"
action "Indexed"
old_values JSON
new_values JSON
ip_address
created_at "Indexed"
```

---

## Settings
```
settings
id UUID PK
key UNIQUE "Indexed"
value
type
```

---

## Notifications
```
notifications
id UUID PK
user_id FK "References users(id) [Indexed]"
type "Indexed"
data JSON
read_at "Indexed"
```

---

# Nachhilfe Module Tables

## Students
```
students
id UUID PK
first_name
last_name
birth_date "Encrypted"
school
grade
deleted_at "Soft Delete [Indexed]"
created_at
updated_at
```
No `user_id` required.

---

## Parents
```
parents
id UUID PK
user_id UUID NULL "References users(id) [Indexed]"
name
phone "Encrypted, Blind Indexed for Exact Search"
email "Encrypted, Blind Indexed for Exact Search"
```

---

## Teachers
```
teachers
id UUID PK
user_id UUID NULL "References users(id) [Indexed]"
name
qualification "Not Encrypted"
hourly_rate
```

---

## Subjects
```
subjects
id UUID PK
name "Not Encrypted [Indexed]"
description
is_active "Indexed"
```

---

## Lessons
```
lessons
id UUID PK
student_id FK "References students(id) [Indexed]"
teacher_id FK "References teachers(id) [Indexed]"
subject_id FK "References subjects(id) [Indexed]"
scheduled_at "Indexed"
duration_minutes
status "Indexed"
price
deleted_at "Soft Delete [Indexed]"
```
Indexes:
- `(student_id)`
- `(teacher_id)`
- `(status, scheduled_at)`

---

## Attendance
```
attendance
id UUID PK
lesson_id FK "References lessons(id) [Indexed]"
status "Indexed"
notes
```

---

# Beauty Module Tables

## Customers
```
customers
id UUID PK
user_id UUID NULL "References users(id) [Indexed]"
name
phone "Encrypted, Blind Indexed for Exact Search"
email "Encrypted, Blind Indexed for Exact Search"
deleted_at "Soft Delete [Indexed]"
```

---

## Services
```
services
id UUID PK
name "Not Encrypted [Indexed]"
duration
price
```

---

## Packages
```
packages
id UUID PK
name "Not Encrypted [Indexed]"
price
```

---

## Appointments
```
appointments
id UUID PK
customer_id FK "References customers(id) [Indexed]"
service_id FK "References services(id) [Indexed]"
scheduled_at "Indexed"
status "Indexed"
price
deleted_at "Soft Delete [Indexed]"
```
Indexes:
- `(status, scheduled_at)`
- `(customer_id)`

---

# Restaurant Module Tables

## Menu Categories
```
menu_categories
id UUID PK
name "Not Encrypted [Indexed]"
sort_order
```

---

## Menu Items
```
menu_items
id UUID PK
category_id FK "References menu_categories(id) [Indexed]"
name "Not Encrypted [Indexed]"
price
available "Indexed"
```

---

## Tables
```
tables
id UUID PK
number UNIQUE "Indexed"
capacity
```

---

## Reservations
```
reservations
id UUID PK
table_id FK "References tables(id) [Indexed]"
customer_name "Encrypted, Blind Indexed for Exact Search"
customer_phone "Encrypted, Blind Indexed for Exact Search"
reservation_at "Indexed"
status "Indexed"
```

---

## Orders
```
orders
id UUID PK
table_id FK "References tables(id) [Indexed]"
status "Indexed"
total
deleted_at "Soft Delete [Indexed]"
```

---

## Order Items
```
order_items
id UUID PK
order_id FK "References orders(id) [Indexed]"
menu_item_id FK "References menu_items(id) [Indexed]"
quantity
price
```

---

# Billing Module Tables

## Invoices
```
invoices
id UUID PK
reference_type "Indexed"
reference_id "Indexed"
amount
status "Indexed"
created_at "Indexed"
```

---

## Payments
```
payments
id UUID PK
invoice_id FK "References invoices(id) [Indexed]"
amount
method "Indexed"
status "Indexed"
deleted_at "Soft Delete [Indexed]"
```

---

## Transactions
```
transactions
id UUID PK
payment_id FK "References payments(id) [Indexed]"
external_reference UNIQUE "Indexed"
status "Indexed"
```

---

# Database Ownership Rule

Forbidden:
- Restaurant queries `students` table directly.
- Beauty queries `lessons` table directly.
- Core queries `orders` table directly.

Allowed communication flow:
```
Module Event -> Shared Contract -> Target Module Action
```

---

# 06 - Primary Use Cases

## Use Case 1: User Authentication

Actor:
- System User

Flow:
```
Login Request
      │
      ▼
Auth Service
      │
      ▼
Validate Credentials
      │
      ▼
Generate Session Token
      │
      ▼
Return User Profile
```

---

## Use Case 2: Create Lesson

Actor:
- Teacher/Admin

Flow:
```
HTTP Request
      │
      ▼
LessonController
      │
      ▼
CreateLessonAction
      │
      ▼
Lesson Repository
      │
      ▼
Save Lesson
      │
      ▼
Dispatch Domain Event
```

---

## Use Case 3: Create Appointment

Actor:
- Beauty Staff

Flow:
```
Request
      │
      ▼
Appointment Action
      │
      ▼
Validate Availability
      │
      ▼
Create Appointment
      │
      ▼
Send Notification Event
```

---

## Use Case 4: Restaurant Order

Flow:
```
Create Order
      │
      ▼
Add Items
      │
      ▼
Calculate Total
      │
      ▼
Save Transaction
      │
      ▼
Notify Kitchen
```

---

# 07 - API Design & Internal Communication

This section defines API standards and communication rules between system components.

---

# 1. REST API Standards

All APIs must follow:

```
/api/v1/{resource}
```

Example:

```
GET    /api/v1/students
POST   /api/v1/lessons
PUT    /api/v1/appointments/{id}
DELETE /api/v1/orders/{id}
```

---

## Standard Success Response
```json
{
    "success": true,
    "data": {},
    "meta": {
        "version": "1.0"
    }
}
```

---

## Standard Error Response
```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "Invalid request",
        "details": {}
    }
}
```

---

# 2. Module Communication Rules

Modules must never communicate through:
- **Forbidden**: Direct database queries, importing another module Model, accessing another module Repository directly.

Allowed communication:
- **Option 1: Shared Contracts**: Interface contracts placed in `Shared/Contracts` namespace (e.g. `PaymentGatewayContract`, `NotificationContract`). Module-specific contracts (like `BillingServiceContract`) belong strictly inside their owning modules.
- **Option 2: Domain Events**: Dispatched via bus (e.g. `LessonScheduled`, `AppointmentCreated`, `OrderCompleted`).

---

# 3. Domain Events

Events describe completed actions and must use past tense:
- **Correct**: `StudentRegistered`, `LessonScheduled`, `InvoicePaid`.
- **Wrong**: `RegisterStudent`, `ScheduleLesson`, `PayInvoice`.

---

# 4. Event Handling Strategy

Because this is a Modular Monolith, events are stored and dispatched inside the same database transaction when transactional reliability is required.

Example (Create Lesson):
```
BEGIN TRANSACTION
  Create Lesson
  Create LessonScheduled Event
COMMIT
Dispatch Listener
END
```

---

# 5. Outbox Pattern & Scope

The project uses a lightweight transactional outbox only for important asynchronous communication. It is **NOT** an enterprise microservice message broker.

### Selective Outbox Scope
Outbox is NOT used for every event:
- **Use Outbox (Critical Cross-Module Events)**: `InvoicePaid`, `OrderConfirmed`, `LessonCompleted`.
- **No Outbox (Simple Internal CRUD Events)**: Laravel Native Events (e.g. `SubjectCreated`, `CategoryUpdated`).

### Database Table: `outbox_events`
- `id` (UUID PK)
- `event_type`
- `event_version`
- `payload` (JSON)
- `status` (`pending`, `processing`, `completed`, `failed`)
- `attempts`
- `available_at`
- `processed_at`
- `created_at`

### Outbox Processing Flow
```
Worker -> Claim Events -> Mark Processing -> Execute Listener
                                                 │
                  ┌──────────────────────────────┴──────────────────────────────┐
                  ▼                                                             ▼
        Success: Mark completed                                       Failure: Retry/Mark failed
```

---

# 6. Idempotency

Every consumer must prevent duplicate execution using the idempotency tracking table:
- Table: `processed_events`
- Structure: `id` (UUID PK), `event_id`, `consumer_name`, `processed_at`.
- Unique Index: `UNIQUE(event_id, consumer_name)`.

Before execution, listeners check if a record with the `event_id` and `consumer_name` exists. If it does, they skip execution.

---

# 08 - Module Customization & Extensibility

This section defines how new business modules are added and registered.

---

# 1. Adding a New Module

Example: adding a `Gym` module:
- Create folder `app/Modules/Gym/` with:
```
Gym/
├── Domain/
├── Application/
├── Infrastructure/
├── Presentation/
└── Providers/
```

---

# 2. Module Provider

Each module contains its Service Provider (e.g. `GymServiceProvider.php`) which is responsible for registering:
* Routes.
* Commands.
* Container bindings.
* Events listeners.
* Migration paths.

---

# 3. Module Registration

All modules are registered in `config/modules.php`. Service Providers are loaded from this config file. Querying the database during Laravel framework boot is strictly forbidden.

Example (`config/modules.php`):
```php
return [
    'Nachhilfe' => [
        'enabled'  => true,
        'provider' => App\Modules\Nachhilfe\Providers\ModuleServiceProvider::class,
    ],
    'Beauty' => [
        'enabled'  => true,
        'provider' => App\Modules\Beauty\Providers\ModuleServiceProvider::class,
    ],
];
```

---

# 4. Runtime Module Settings & Cache-First checks

Dynamic activation/deactivation of features at runtime is handled through a database settings table (`module_settings` containing fields `module_name` and `enabled`). 

To avoid database bottlenecks:
- The system checks module settings using a **cache-first strategy** inside routing middleware or actions.
- The settings database table is **never** queried during Laravel Service Provider bootstrap.
- Changing a setting at runtime clears the local cache key for that module setting.

---

# 09 - Infrastructure Architecture

# 1. Application Environment

The system runs:
- Laravel Application
- PostgreSQL Database
- Redis (Cache, Sessions, & Queues)
- Queue Workers

---

# 2. Logging

Central logging system.
- Records all errors, security authorization failures, and critical business exceptions.
- Formats logs into structured JSON output.

---

# 3. Monitoring

Required metrics:
- **Application Metrics**: Request duration, Database queries latency, Queue sizes & processing durations, Exception occurrences.

---

# 4. Error Tracking

- Sentry integration for capturing runtime errors, categorized by module namespaces for routing to Backend Leads.

---

# 5. GDPR Requirements

The system must support:
* Data export.
* Data deletion (Right to be Forgotten).
* **Selective Encryption**: Sensitive PII columns (phone, email, birth date) are encrypted (using AES-256-GCM cast keys on Eloquent Models). General columns (subject names, service names, menu items) are left in plain text to optimize database performance.
* **Exact Search**: Blind index columns (e.g. hashed email or phone value) are introduced only where exact searching on encrypted data is required (email lookups, phone lookups).

---

# 10 - Development Workflow & Quality Gates

This document defines the pipelines, testing strategies, and tools for code integration, release quality verification, and database migrations.

---

## 1. Quality Gates (Automated CI/CD Validation)

Every code check-in must pass automated verification checks before merging.

```
[Code Push] ──> [Laravel Pint] ──> [Pest Tests] ──> [PHPStan Level 9] ──> [Deptrac Check] ──> [Merge Approval]
```

### 1.1 Quality Gate Tools Configurations

- **Laravel Pint**: Enforces strict PSR-12 styling configurations.
  - Command: `composer run lint`
- **Pest PHP**: Enforces strict unit testing. Pest suites target 100% coverage on Domain aggregates. Requires zero database connections or framework boot steps.
  - Command: `composer test`
- **PHPStan (Level 9)**: Enforces strict type safety, nullability, and non-empty checks.
  - Command: `composer run analyze`
- **Deptrac**: Enforces layer boundaries.
  - **Allowed**: Modules can import Core; Core can import Shared; Modules can import Shared.
  - **Forbidden**: Core cannot import Modules (`Core -> Modules`). Modules cannot import other Modules (`Nachhilfe -> Beauty`, `Restaurant -> Nachhilfe`).
  - Command: `composer run deptrac`
- **Additive Migrations Checks**: Enforces zero column drops or schema renames on active migrations.

---

## 2. Git & Release Management Rules

### 2.1 Branch Naming Conventions
- `feature/` -> Add new functionality (e.g. `feature/tutoring-attendance`)
- `bugfix/` -> Resolve bugs (e.g. `bugfix/outbox-retry-limit`)
- `refactor/` -> Structural changes with no functional modifications

### 2.2 Pull Request Guidelines
1. **Single Focus**: Each PR must address a single task or issue.
2. **Squash and Merge**: Feature branches must be squashed into a single clean commit when merging into main.
3. **Review Requirements**: Two approvals from senior backend engineers are required before merge.

---

## 3. Database Schema Lifecycle & Release Management

### 3.1 Versioning Strategy
We adhere to Semantic Versioning 2.0.0 (Major.Minor.Patch):
- **Major**: Major architectural modifications.
- **Minor**: New business module additions.
- **Patch**: Security fixes and backward-compatible patches.

### 3.2 Additive Migration Policy
- Migrations must only perform **additive** database adjustments. Deleting columns or renaming tables directly is strictly forbidden on active database systems.
- **Deprecation Cycle**: To drop a column:
  1. Release code that stops reading the column.
  2. In the next release cycle, execute the migration dropping the column safely.
- **Rollback Verification**: Every migration must define a functional `down()` rollback method.

---

# 11 - Product Module Reference: Nachhilfe (Tutoring)

This document provides a concrete, implementation-oriented design blueprint for the **Nachhilfe (Tutoring)** business module. It serves as the developer reference implementation for both complex aggregates and simple CRUD operations in the Pragmatic Modular Monolith.

---

## 1. Directory Structure

The module resides under `app/Modules/Nachhilfe/` and adheres to the Pragmatic Clean Architecture rules:

```
app/Modules/Nachhilfe/
├── Domain/
│   ├── Entities/
│   │   ├── Student.php    <-- Plain PHP Domain Entity
│   │   ├── Teacher.php
│   │   └── Lesson.php
│   ├── ValueObjects/
│   │   └── LessonDuration.php
│   ├── Events/
│   │   └── LessonScheduled.php
│   ├── Contracts/
│   │   └── LessonRepository.php  <-- Module-specific contract belongs here
│   └── Exceptions/
│       └── TeacherUnavailableException.php
│
├── Application/
│   ├── Actions/
│   │   ├── BookLessonAction.php    <-- Complex Aggregate Action (DDD)
│   │   └── CreateSubjectAction.php <-- Simple CRUD Action (Direct Eloquent)
│   └── DTOs/
│       └── LessonBookingData.php
│
├── Infrastructure/
│   ├── Persistence/
│   │   ├── Models/
│   │   │   ├── LessonModel.php     <-- Eloquent Model
│   │   │   └── SubjectModel.php    <-- Eloquent Model
│   │   ├── Repositories/
│   │   │   └── EloquentLessonRepository.php
│   │   └── Migrations/
│   │       ├── 2026_07_07_200000_create_lessons_table.php
│   │       └── 2026_07_07_200001_create_subjects_table.php
│   └── Providers/
│       └── NachhilfeServiceProvider.php
│
└── Presentation/
    ├── Controllers/
    │   ├── LessonController.php
    │   └── SubjectController.php
    ├── Requests/
    │   └── BookLessonRequest.php
    └── Resources/
        └── LessonResource.php
```

---

## 2. Complex Business Aggregate (DDD + Repository + Outbox)

For complex domain logic (e.g. lesson scheduling checks), we enforce strict Clean Architecture layers:

### 2.1 The Domain Entity (Plain PHP)
```php
namespace App\Modules\Nachhilfe\Domain\Entities;

use App\Modules\Nachhilfe\Domain\ValueObjects\LessonDuration;
use DateTimeImmutable;
use InvalidArgumentException;

class Lesson
{
    private string $id;
    private string $studentId;
    private string $teacherId;
    private string $subjectId;
    private DateTimeImmutable $scheduledAt;
    private LessonDuration $duration;
    private string $status;
    private float $price;
    private ?string $notes;

    public function __construct(
        string $id,
        string $studentId,
        string $teacherId,
        string $subjectId,
        DateTimeImmutable $scheduledAt,
        LessonDuration $duration,
        string $status,
        float $price,
        ?string $notes = null
    ) {
        if ($price < 0) {
            throw new InvalidArgumentException("Lesson price cannot be negative.");
        }

        $this->id = $id;
        $this->studentId = $studentId;
        $this->teacherId = $teacherId;
        $this->subjectId = $subjectId;
        $this->scheduledAt = $scheduledAt;
        $this->duration = $duration;
        $this->status = $status;
        $this->price = $price;
        $this->notes = $notes;
    }

    public function getId(): string { return $this->id; }
    public function getStudentId(): string { return $this->studentId; }
    public function getTeacherId(): string { return $this->teacherId; }
    public function getSubjectId(): string { return $this->subjectId; }
    public function getScheduledAt(): DateTimeImmutable { return $this->scheduledAt; }
    public function getDuration(): LessonDuration { return $this->duration; }
    public function getStatus(): string { return $this->status; }
    public function getPrice(): float { return $this->price; }
    public function getNotes(): ?string { return $this->notes; }

    public function cancel(): void
    {
        if ($this->status === 'completed') {
            throw new InvalidArgumentException("Completed lessons cannot be cancelled.");
        }
        $this->status = 'cancelled';
    }
}
```

### 2.2 Domain Repository Contract (Module-Specific)
```php
namespace App\Modules\Nachhilfe\Domain\Contracts;

use App\Modules\Nachhilfe\Domain\Entities\Lesson;

interface LessonRepository
{
    public function find(string $id): ?Lesson;
    public function save(Lesson $lesson): void;
    public function isTeacherAvailable(string $teacherId, \DateTimeImmutable $start, int $durationMinutes): bool;
}
```

### 2.3 Application Action (Uses Repository & Outbox)
```php
namespace App\Modules\Nachhilfe\Application\Actions;

use App\Modules\Nachhilfe\Application\DTOs\LessonBookingData;
use App\Modules\Nachhilfe\Domain\Entities\Lesson;
use App\Modules\Nachhilfe\Domain\ValueObjects\LessonDuration;
use App\Modules\Nachhilfe\Domain\Contracts\LessonRepository;
use App\Modules\Nachhilfe\Domain\Exceptions\TeacherUnavailableException;
use App\Modules\Nachhilfe\Domain\Events\LessonScheduled;
use App\Core\Events\Contracts\DomainEventBus; // Abstract core outbox publisher
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookLessonAction
{
    private LessonRepository $lessons;
    private DomainEventBus $eventBus;

    public function __construct(LessonRepository $lessons, DomainEventBus $eventBus)
    {
        $this->lessons = $lessons;
        $this->eventBus = $eventBus;
    }

    public function execute(LessonBookingData $dto): Lesson
    {
        return DB::transaction(function () use ($dto) {
            if (!$this->lessons->isTeacherAvailable($dto->teacherId, $dto->scheduledAt, $dto->durationMinutes)) {
                throw new TeacherUnavailableException("The teacher is unavailable.");
            }

            $lesson = new Lesson(
                Str::uuid()->toString(),
                $dto->studentId,
                $dto->teacherId,
                $dto->subjectId,
                $dto->scheduledAt,
                new LessonDuration($dto->durationMinutes),
                'scheduled',
                $dto->price,
                $dto->notes
            );

            $this->lessons->save($lesson);

            // Publish Outbox Event via abstract bus (Critical Business Event: Uses Outbox)
            $this->eventBus->publish(new LessonScheduled(
                Str::uuid()->toString(),
                $lesson->getId(),
                $lesson->getStudentId(),
                $lesson->getTeacherId()
            ));

            return $lesson;
        });
    }
}
```

---

## 3. Simple CRUD Entity (Bypassing Repositories & Outbox)

For simple CRUD domains (e.g. subjects mapping), we query and write directly to the Eloquent Model inside the Action to avoid unnecessary mapping boilerplate, and dispatch events using native Laravel events:

### 3.1 Simple Application Action (Direct Eloquent Queries & Native Events)
```php
namespace App\Modules\Nachhilfe\Application\Actions;

use App\Modules\Nachhilfe\Infrastructure\Persistence\Models\SubjectModel;
use App\Modules\Nachhilfe\Infrastructure\Persistence\Events\SubjectCreated; // Native Laravel Event
use Illuminate\Support\Str;

class CreateSubjectAction
{
    public function execute(array $data): SubjectModel
    {
        // CRUD entities bypass Domain Entities & Repositories, querying Eloquent directly
        $subject = SubjectModel::create([
            'id'          => Str::uuid()->toString(),
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active'   => $data['is_active'] ?? true,
        ]);

        // Simple CRUD event: Dispatches via native Laravel Events (No Outbox)
        event(new SubjectCreated($subject->id, $subject->name));

        return $subject;
    }
}
```

---

# 12 - V4.2 Implementation Readiness Verification

This section establishes the implementation rules for the platform launch:

## 1. Project Foundation Configuration
The codebase strictly deploys to `app/Core`, `app/Modules`, and `app/Shared`. Directories like `app/Products` or `app/Platform` are prohibited.

## 2. Module Independence Boundaries
Every module contains its own: routes, migrations, models, logic classes, and tests. Modules do not import another module's internal classes.

## 3. Core Responsibilities Isolation
Core maps system operations only (Auth, Authorization, Settings, Audit, Notifications). Business logic (Lessons, Orders, Appointments, Invoices) belongs strictly to Modules.

## 4. Single PostgreSQL Database Rules
- ULID/UUID v7 keys.
- Foreign keys scoped to owning module tables.
- No direct foreign keys between separate business modules tables.

## 5. Selective Architecture Rules
- CRUD models bypass repository wrappers (Action directly calls Eloquent Model).
- Complex aggregates require full DDD layered mapping.

## 6. Phase 1 Skeleton Setup Target
- Core bootstrap dynamic registry mapping.
- Core authenticating adapters.
- Settings & Audit log parameters.
- Nachhilfe module directory setup.
- Placeholders for Beauty, Restaurant, and Billing.
