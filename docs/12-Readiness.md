# V4.2 Implementation Readiness Verification

The documentation audit is accepted.

Before starting implementation, perform one final architectural validation pass. Do not modify the architecture unless a conflict is found.

Verify and enforce the following implementation rules:

## 1. Project Foundation

The Laravel application must start as a single application:

```
app/
├── Core/
├── Modules/
│   ├── Nachhilfe/
│   ├── Beauty/
│   ├── Restaurant/
│   └── Billing/
└── Shared/
```

Forbidden:

```
app/Products
app/Tenants
app/Platform
app/Customer
```

---

## 2. Module Independence

Every module must own:
- Routes
- Migrations
- Models
- Business logic
- Tests

Example:

```
Modules/Nachhilfe/

Infrastructure/
Persistence/
Models/
Migrations/

Application/
Domain/
Presentation/
```

No module may import another module's internal classes.

---

## 3. Core Responsibility

Core contains only platform capabilities:

Allowed:
- Authentication
- Authorization
- Roles
- Permissions
- Settings
- Audit logging
- Notifications
- Shared infrastructure

Forbidden:
- Lessons
- Orders
- Appointments
- Customers
- Invoices

---

## 4. Database Rules

One PostgreSQL database only.

Rules:
- UUID v7 or ULID primary keys.
- Foreign keys belong to the owning module.
- Module tables cannot reference another module's tables directly.
- Communication happens through contracts/events.

---

## 5. Architecture Style

Apply pragmatic rules:

Simple CRUD:
```
Model
Action
Controller
Test
```

Complex aggregates:
```
Domain
Application
Infrastructure
Presentation
```

Do not create unnecessary repositories or entities for simple CRUD tables.

---

## 6. First Implementation Target

Do not implement all modules.

Start only with:
```
Core
+
Nachhilfe
```

Implementation order:
1. Laravel base setup
2. Module loader
3. Core authentication
4. RBAC
5. Settings
6. Audit logs
7. Nachhilfe module
8. Tests

Beauty, Restaurant and Billing remain architectural placeholders until Nachhilfe is stable.

---

## 7. Before Writing Business Code

Generate only:
- Folder structure
- Namespace configuration
- Service Providers
- config/modules.php
- Base contracts
- Testing setup
- Code quality configuration

Do not generate business entities yet.

After approval, continue module implementation.
