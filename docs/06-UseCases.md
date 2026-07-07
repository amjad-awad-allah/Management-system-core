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
