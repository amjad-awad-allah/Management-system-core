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
