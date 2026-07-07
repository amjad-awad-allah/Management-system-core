# Management System Core (Modular Monolith Blueprint)

This repository serves as the enterprise-grade **Core Boilerplate** and **Architectural Blueprint** for all future modules and SaaS applications (e.g., Beauty, Restaurant, Billing, Nachhilfe).

## 🚀 Architecture Highlights
This platform is built on a highly strict **Modular Monolith** architecture:
- **Core Namespace**: `App\Core` handles unified systems (Authentication, RBAC Authorization, Base Models, Global Audits).
- **Module Namespaces**: Each module lives independently in `App\Modules\{ModuleName}` (e.g., `App\Modules\Nachhilfe`).
- **Strict Boundaries**: Modules are completely forbidden from directly interacting with each other's Databases or Eloquent Models.
- **Domain-Driven Design (DDD)**: Features strict separation into `Domain` (Entities, Value Objects), `Infrastructure` (Eloquent Repositories), and `Application` (Actions).

## 🛡️ Key Enterprise Features
- **Transactional Outbox Pattern**: Secure inter-module communication using `outbox_events` and a `lockForUpdate()->skipLocked()` background daemon worker (`php artisan outbox:work`).
- **Blind Indexing Encryption**: Sensitive PII data (Phones, Emails) are encrypted at rest using Laravel's encrypter and searched using SHA-256 salted Blind Indices.
- **Universal IDs (ULID)**: All primary keys and foreign keys use ULID (`foreignUlid()`) for hyper-scalable, timestamp-sortable, and conflict-free IDs.
- **Idempotency Guarantees**: A strict `processed_events` compound unique index ensures events cannot be executed twice.

## 🚦 Quality Gates (QA)
The architecture is enforced mathematically via static analysis tools:
1. **Deptrac**: Enforces architectural boundaries (`vendor/bin/deptrac`).
2. **PHPStan (Level 9)**: Maximum strictness for Data Types, ensuring no `mixed` arrays pass silently (`vendor/bin/phpstan analyse`).
3. **Pest**: Fast, beautiful testing for unit and feature layers (`vendor/bin/pest`).

## 🛠️ Requirements & Setup
- PHP 8.2+
- Laravel 11.x
- PostgreSQL / MySQL 8+ (For Outbox SKIP LOCKED feature)

To spin up the worker:
```bash
php artisan outbox:work --sleep=3
```
