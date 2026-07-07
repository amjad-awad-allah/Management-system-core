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
