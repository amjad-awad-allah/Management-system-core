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

# ADR-08: Selective Outbox Scope

## Decision
Outbox is not used for every event.
- **Critical Cross-Module Events**: Transactional Outbox (e.g., `InvoicePaid`, `OrderConfirmed`, `LessonCompleted`).
- **Simple Internal CRUD Events**: Laravel Native Events (e.g., `SubjectCreated`, `CategoryUpdated`).

---

# ADR-09: Contract Placement & Late Binding

## Decision
Module-specific contracts belong to their owning module (e.g. `app/Modules/Billing/Contracts/BillingServiceContract.php`). Shared contracts under `app/Shared/Contracts/` contain only stable integration contracts, preventing circular dependencies.
