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
