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
