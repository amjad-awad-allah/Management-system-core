# Nachhilfe ERP & Enterprise Management System Core (Modular Monolith Blueprint)

[![PHP Version](https://img.shields.io/badge/PHP-8.3%2B-777BB4?style=flat-square&logo=php)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-12-FF2D20?style=flat-square&logo=laravel)](https://laravel.com)
[![Vue Version](https://img.shields.io/badge/Vue.js-3.5-4FC08D?style=flat-square&logo=vue.js)](https://vuejs.org)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.6-3178C6?style=flat-square&logo=typescript)](https://www.typescriptlang.org)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-v4-06B6D4?style=flat-square&logo=tailwindcss)](https://tailwindcss.com)
[![PHPStan Level 9](https://img.shields.io/badge/PHPStan-Level%209-brightgreen?style=flat-square)](https://phpstan.org)
[![Quality Gate](https://img.shields.io/badge/Deptrac-Enforced-blue?style=flat-square)](https://github.com/qossmic/deptrac)
[![WebSockets](https://img.shields.io/badge/WebSockets-Laravel%20Reverb-purple?style=flat-square)](https://laravel.com/docs/reverb)

This repository serves as the enterprise-grade **Core Boilerplate** and **Architectural Blueprint** for all SaaS modules and enterprise management applications (Nachhilfe, Education, Beauty, Restaurant, Billing, HR). Built with a strict **Modular Monolith** architecture, Domain-Driven Design (DDD) principles, real-time WebSocket communication, comprehensive compliance with German educational regulations (Jobcenter / BuT), and full bilingual support (German & English).

---

## 🚀 Key Architectural Highlights

### 🏰 1. Strict Modular Monolith Architecture
- **Core Namespace (`App\Core`)**: Encapsulates cross-cutting infrastructure used across all business modules:
  - Authentication (Sanctum Tokens, Mobile Alphanumeric Login Codes, Device Session Revocation).
  - Role-Based Access Control (RBAC with Spatie Permissions).
  - Center Branding & Institutional Settings (`CenterSettingsService` for logo uploads, center names, and federal state calibration).
  - Universal Notification Center (`NotificationService` with multi-channel push, in-app bell, and WebSocket routing).
  - Production-Ready System & Error Logging Engine (`SystemLogService`, `CorrelationContext`, and JSON Lines Monolog processors).
  - Global Audit Logging Engine (tracking state diffs with before vs after values and operator attribution).
- **Module Namespaces (`App\Modules\{ModuleName}`)**: Each business domain (e.g., `App\Modules\Nachhilfe`, `App\Modules\Billing`) is completely isolated in its own self-contained directory.
- **Strict Boundary Enforcement**: Modules are decoupled and communicate asynchronously via Domain Events and the Transactional Outbox pattern.
- **Domain-Driven Design (DDD)**: Each module is structured into clean architectural layers:
  - `Domain`: Entities, Value Objects, and Domain Events.
  - `Infrastructure`: Eloquent Models, Database Migrations, Repositories, and External System Adapters.
  - `Application`: Use-case Actions, Handlers, and Event Listeners.
  - `Presentation`: API Controllers, Resources, and Request Validators.

---

## 🛡️ Enterprise Engineering & Security Patterns

- **⚡ Transactional Outbox Pattern**: Reliable inter-module event publishing using `outbox_events` and a background worker (`php artisan outbox:work`) with `lockForUpdate()->skipLocked()` for zero lock contention and guaranteed message delivery.
- **🔍 End-to-End Correlation & Client-Aware Request IDs**: Every HTTP request, CLI command, and queued job is tracked using `CorrelationContext` and `AssignRequestIdMiddleware`. Incoming `X-Request-Id` headers are validated and echoed back in HTTP response headers.
- **🔒 Sensitive Data Sanitization**: All logged payloads, request headers, query parameters, and exception context pass through `SystemLogSanitizer` to automatically redact passwords, auth tokens, session cookies, and API keys with `[REDACTED]`.
- **📊 Production-Ready System Error & Exception Logging (`ndjson`)**: Monolog writes discrete JSON Lines (`JsonLinesFormatter` & `StructuredLogProcessor`), ensuring single-line structured storage for multiline stack traces with live 24-hour error statistics, search filtering, and TXT/JSON streaming exports.
- **🔐 Blind Indexing & PII Encryption**: Sensitive Personally Identifiable Information (PII) like birthdates, phone numbers, and parent contacts are encrypted at rest using AES-256 and queried using salted SHA-256 Blind Indices (`phone_blind_index`).
- **🆔 Universal Lexicographically Sortable Identifiers (ULID)**: All database tables use 26-character ULID primary keys (`foreignUlid()`) providing timestamp-sortable, hyper-scalable, and conflict-free IDs.
- **🔒 Cryptographic Payroll Sealing (`Snapshot Hash`)**: Approved teacher payrolls generate an immutable SHA-256 fingerprint (`snapshot_hash_version: 1`) locking wages, completed hours, and lesson breakdowns against retroactive tampering.
- **⚠️ Double-Booking & Conflict Prevention Engine**: Automated validation preventing room overcapacity and teacher schedule overlap across both individual and group sessions.
- **🏛️ Regional Holiday & Vacation Calibration**: Built-in `HolidayService` calibrates public holidays and school vacations based on the center's configured German Bundesland (e.g., `NW`, `BY`, `BE`, `BW`).
- **📁 GDPR-Compliant Private Storage & Streaming**: Student contracts, Jobcenter BuT approvals, and generated reports are stored in non-public storage (`storage/app/private/`) and streamed strictly through authenticated RBAC controllers.

---

## 💬 WhatsApp-Style Real-Time Messaging & Feedback System

- **👥 Advanced Group & Direct Conversation Modals**:
  - **Category "Select All / Deselect All"**: Independent one-click selection buttons for Management & Staff, Teachers, and Students.
  - **Filter-Aware Multi-Select**: When searching for names or subjects, "Select All" smartly selects matching filtered users.
  - **WhatsApp Style Chips**: Interactive top chips bar displaying selected members with instant removal.
- **📊 In-Chat Student Feedback Surveys**: Create and distribute interactive ratings and multiple-choice survey cards directly in channels with live response aggregation.
- **⚡ Dual-Layer Real-Time Engine**:
  - **Primary Layer**: Real-time push via **Laravel Reverb WebSockets** (`broadcast(new ChatMessageSent(...))`).
  - **Secondary Layer**: Smart background polling timer (every 3s) with message deduplication (`msg.id`), guaranteeing **100% instant sync** even in restricted proxy or offline environments.
- **📱 Responsive Mobile Master-Detail Layout**: Seamless transition between conversations list and active chat view with an integrated `← Back` button on mobile screens.

---

## 📄 Official German PDF Document Generators

The system generates official German regulatory PDF reports with dynamic institutional branding:
1. **Official Stundennachweis PDF**: German attendance certificate formatted for Jobcenter/BuT caseworker submission, aggregating attended lessons (`present` / `late`) with official signatures and voucher references.
2. **GDPR-Compliant Room Door Sheet PDF (*Raumbelegungsplan*)**: Classroom door timetable displaying daily bookings while redacting student surnames to comply with European and German privacy laws.
3. **Teacher Timetable PDF (*Lehrer-Stundenplan*)**: Comprehensive weekly timetable with room allocations, subjects, and student lists for instructor reference.

---

## 🚦 Quality Gates & Verification (QA)

Architecture, types, and business rules are verified automatically:

1. **PHPUnit Feature & Unit Testing**:
   ```bash
   php artisan test
   ```
2. **Deptrac Architectural Boundary Gate**:
   ```bash
   vendor/bin/deptrac
   ```
3. **PHPStan (Level 9 - Maximum Strictness)**:
   ```bash
   vendor/bin/phpstan analyse
   ```
4. **Frontend i18n Key Parity Check**:
   ```bash
   cd frontend && npx vitest run tests/i18n/locales.spec.ts
   ```
5. **Vue 3 TypeScript Compilation Check**:
   ```bash
   cd frontend && npm run build
   ```

---

## 🛠️ Setup & Running Instructions

### Prerequisites
- **PHP**: 8.2 or 8.3+ with `pdo`, `mbstring`, `openssl`, `bcmath`, `curl`.
- **Database**: MySQL 8.0+ / MariaDB on port `3306`.
- **Node.js**: 18+ & `npm`.
- **Composer**: 2+.

### 1. Backend Setup
1. Clone the repository:
   ```bash
   git clone https://github.com/amjad-awad-allah/Management-system-core.git
   cd Management-system-core
   ```
2. Install PHP dependencies:
   ```bash
   composer install
   ```
3. Configure your `.env` file:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
4. Run migrations and populate rich real-world test data:
   ```bash
   php artisan migrate:fresh --seed
   ```
5. Start the Laravel backend API (port 8001):
   ```bash
   php artisan serve --port=8001
   ```
6. Start the Outbox background worker:
   ```bash
   php artisan outbox:work --sleep=3
   ```
7. Start the Laravel Reverb WebSocket server (port 8081):
   ```bash
   php artisan reverb:start --port=8081
   ```

### 2. Frontend Setup
1. Navigate to the `frontend` folder:
   ```bash
   cd frontend
   npm install
   ```
2. Start the Vite development server (port 5173):
   ```bash
   npm run dev
   ```
   Open your browser at `http://localhost:5173`.

### 3. Default Demo Accounts
| Role | Email | Password | Quick Login Code |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `admin@admin.com` | `password` | — |
| **Teacher (Herr Dr. Müller)** | `teacher@teacher.com` | `password` | `TEACH123` |
| **Student / Parent (Max Mustermann)** | `parent@parent.com` | `password` | `STUD1234` |

---

## 📖 Interactive In-App User Playbook (`/guide`)

The system includes a dedicated, non-technical **User Guide & Operational Playbook** accessible from the dashboard sidebar covering all 13 ERP modules:

1. **Lesson Scheduling**: Step-by-step individual and group booking workflows.
2. **Conflict & Overlap Prevention**: Room capacity and teacher availability validation rules.
3. **Attendance & Automated Deduction**: Marking attendance and automated hour deduction from student vouchers.
4. **Automated Reminders**: 24h and 2h pre-session notification engine.
5. **Teacher Payrolls**: Calculating completed hours, rates, approval locking, and cryptographic sealing.
6. **Invoicing & Parent Billing**: Generating, sending, and tracking private tuition invoices.
7. **Student Packages & BuT Vouchers**: Managing Jobcenter vouchers and monitoring low-balance alerts (`< 2h`).
8. **Official PDF Exports**: Exporting Stundennachweis, Door Signs, and Teacher Timetables.
9. **Teacher & Room Configuration**: Hourly wage rates, subjects, and classroom capacities.
10. **Live Chat & Surveys**: Messaging, group channels, and student satisfaction feedback.
11. **Roles & Permissions**: Access control and operational privilege boundaries.
12. **System & Error Logs**: 24-hour error capture, stack trace accordion, log streaming exports, and safe clear routines.
13. **Mobile Quick Login Codes**: Instant alphanumeric code authorization for mobile devices.

---

## 📜 License

This project is proprietary software developed as an Enterprise Core Management Boilerplate. All rights reserved.
