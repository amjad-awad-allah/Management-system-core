# Nachhilfe ERP & Enterprise Management System Core (Modular Monolith Blueprint)

[![PHP Version](https://img.shields.io/badge/PHP-8.3%2B-777BB4?style=flat-square&logo=php)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-11-FF2D20?style=flat-square&logo=laravel)](https://laravel.com)
[![Vue Version](https://img.shields.io/badge/Vue.js-3.5-4FC08D?style=flat-square&logo=vuedotjs)](https://vuejs.org)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.6-3178C6?style=flat-square&logo=typescript)](https://www.typescriptlang.org)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-v4-06B6D4?style=flat-square&logo=tailwindcss)](https://tailwindcss.com)
[![PHPStan Level 9](https://img.shields.io/badge/PHPStan-Level%209-brightgreen?style=flat-square)](https://phpstan.org)
[![Quality Gate](https://img.shields.io/badge/Deptrac-Enforced-blue?style=flat-square)](https://github.com/qossmic/deptrac)

This repository serves as the enterprise-grade **Core Boilerplate** and **Architectural Blueprint** for all SaaS modules and enterprise management applications (Nachhilfe, Beauty, Restaurant, Billing, HR). Built with a strict **Modular Monolith** architecture, Domain-Driven Design (DDD) principles, and modern real-time capabilities.

---

## 🚀 Key Architectural Highlights

### 🏰 1. Strict Modular Monolith Architecture
- **Core Namespace (`App\Core`)**: Encapsulates cross-cutting systems used by all modules:
  - Authentication (Sanctum Tokens, Mobile Login Codes, Session Revocation).
  - Role-Based Access Control (RBAC with Spatie Permissions).
  - Universal Notification Center (`NotificationService` with multi-channel routing).
  - Global Audit Logging Engine (tracking state diffs with old vs new values).
  - Core System Settings engine.
- **Module Namespaces (`App\Modules\{ModuleName}`)**: Each business domain (e.g., `App\Modules\Nachhilfe`, `App\Modules\Billing`) is completely decoupled into its own directory.
- **Strict Boundary Enforcement**: Modules are strictly forbidden from directly importing or accessing each other's Database models or internal repositories. All inter-module communication is asynchronous via domain events.
- **Domain-Driven Design (DDD)**: Each module is divided into clean architectural layers:
  - `Domain`: Entities, Value Objects, and Domain Events.
  - `Infrastructure`: Eloquent Models, Database Migrations, Repositories, and System Adapters.
  - `Application`: Use-case Actions, Handlers, and Listeners.
  - `Presentation`: API Controllers, Resources, and Request Validators.

---

## 🛡️ Enterprise Engineering Patterns

- **⚡ Transactional Outbox Pattern**: Secure, reliable inter-module event publishing using `outbox_events` and a background worker (`php artisan outbox:work`) with `lockForUpdate()->skipLocked()` for zero lock contention and guaranteed message delivery.
- **🔐 Blind Indexing & PII Encryption**: Sensitive Personally Identifiable Information (PII) like phone numbers and email addresses are encrypted at rest using AES-256 and queried using salted SHA-256 Blind Indices (`phone_blind_index`).
- **🆔 Universal Lexicographically Sortable Identifiers (ULID)**: All database tables use 26-character ULID primary keys (`foreignUlid()`) providing timestamp-sortable, hyper-scalable, and conflict-free IDs across microservices/modules.
- **🔄 Idempotency & Duplicate Protection**: A compound unique index on `processed_events` prevents double-execution of domain events and outbox notifications.
- **📁 GDPR Private Storage & Secure Streaming**: Sensitive documentation (student contracts, BuT Jobcenter approvals) is stored in a non-public directory (`storage/app/private/`) and streamed strictly through authenticated RBAC routes.

---

## 💬 WhatsApp-Style Real-Time Chat & Instant Messaging

The platform features an enterprise-grade instant messaging system designed to mimic **WhatsApp Web**:

- **🚀 Direct Instant Access**: Automatically selects and loads the most recent active conversation on page load, eliminating empty selection screens.
- **👥 Group Creation Modal (`CreateGroupModal.vue`)**:
  - Multi-source user search across System Admins, Teachers, and Students without duplicates.
  - Interactive top selection bar displaying selected members as removable chips with `x` buttons.
  - Group name validation alert & auto-focus if the group name field is left blank.
- **💬 Direct Messaging Modal (`CreateDirectModal.vue`)**: Quick 1-on-1 contact search and instant conversation initiation.
- **⚡ Dual-Layer Real-Time Engine**:
  - **Primary Layer**: Push notifications via **Laravel Reverb WebSockets** (`broadcast(new ChatMessageSent(...))`).
  - **Secondary Layer**: Smart background polling timer (every 3s) with message deduplication (`msg.id`), guaranteeing **100% SignalR / Firebase style instant sync** in all environments (even when WebSockets are disabled or offline).
- **🔔 Smart Notification Navigation**: Clicking any chat notification marks it as read, selects the target channel, and navigates directly to `/messaging`.

---

## 🚦 Quality Gates & Verification (QA)

Architecture and code health are verified automatically:

1. **Deptrac Architectural Check**:
   ```bash
   vendor/bin/deptrac
   ```
2. **PHPStan (Level 9 - Maximum Strictness)**:
   ```bash
   vendor/bin/phpstan analyse
   ```
3. **Pest PHP Unit & Feature Testing**:
   ```bash
   vendor/bin/pest
   ```
4. **Vue 3 TypeScript Compilation Check**:
   ```bash
   cd frontend && npx vue-tsc -b
   ```

---

## 🛠️ Setup & Running Instructions

### Prerequisites
- **PHP**: 8.2 or 8.3+ with `pdo`, `mbstring`, `openssl`, `bcmath` extensions.
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
4. Run migrations and database seeding (populates mock test data):
   ```bash
   php artisan migrate:fresh --seed
   ```
5. Start the Laravel backend server:
   ```bash
   php artisan serve --port=8001
   ```
6. Start the Outbox background worker (crucial for inter-module event processing):
   ```bash
   php artisan outbox:work --sleep=3
   ```
7. *(Optional)* Start Laravel Reverb WebSocket server for instant broadcasting:
   ```bash
   php artisan reverb:start
   ```

### 2. Frontend Setup
1. Navigate to the `frontend` folder:
   ```bash
   cd frontend
   ```
2. Install npm packages:
   ```bash
   npm install
   ```
3. Start the Vite development server:
   ```bash
   npm run dev
   ```
   Open your browser at `http://localhost:5173`.

---

## 🖥️ Comprehensive SaaS ERP Portal User Manual

The **Nachhilfe ERP** control panel is designed to manage the entire educational lifecycle and comply with official funding authority requirements in Germany (such as Jobcenter and BuT). Below is the comprehensive guide to all sections:

---

### 📊 1. Main Dashboard
The central landing screen upon login, featuring:
* **Key Performance Indicators (Stat Cards):**
  * **Active Students:** Total enrolled students currently taking lessons.
  * **Active Teachers:** Number of qualified instructors available.
  * **Monthly Revenue:** Financial performance calculated from private subscriptions.
  * **Scheduled Lessons:** Total upcoming bookings.
* **Upcoming Lessons Timeline:** Displays scheduled sessions with subject, teacher, student, room, and start time details.

---

### 👥 2. Students Management
Dedicated module for managing student profiles and funding documentation:
* **Add Student Slideover:** Creates a student account linked to their parent user account with school, grade, and billing type details.
* **Unified Student Profile View:**
  1. **Overview:** Contact information, enrolled subjects, and assigned teachers.
  2. **BuT Vouchers Management (Hour Approvals):**
     - Manage funding vouchers issued by Jobcenter/BuT with status tracking (`Active`, `Pending Approval`, `Rejected`, `Expired`, `Exhausted`).
     - **Automated Deduction Engine:** Automatically deducts lesson hours from active vouchers prioritized by nearest expiration date.
  3. **Timeline & Audit Details:** Timeline log capturing all student activities. Click **Show System Edit Details** to view exact old vs new value diffs with operator attribution.
  4. **GDPR Private Documents:**
     - Upload and stream contracts and approvals via secure non-public storage (`storage/app/private/`).
     - **Stundennachweis PDF Export:** Generates an official German attendance report formatted for Jobcenter submission, aggregating attended lessons (`present` / `late`) with signature sections.

---

### 👨‍🏫 3. Teachers Management
* **Teachers Grid:** List of qualified instructors, contact info, and qualifications.
* **Teacher Profile View:**
  - Base hourly rate (`hourly_rate`) for automated payroll generation.
  - Qualified subjects and weekly availability schedules (`Availability Schedules`).

---

### 📅 4. Lessons & Calendar
* **Interactive Calendar Grid:** Weekly and daily visual schedule color-coded by lesson status.
* **Book Lesson Slideover:** Select instructor, subject, classroom (physical or online room), and enroll individual or group students.
* **Attendance Logging:** Mark attendance (`present`, `late`, `absent_excused`, `absent_unexcused`) with automated cancellation policy enforcement and hour deduction.

---

### 💬 5. Instant Messaging & WhatsApp-Style Chat
* **Direct & Group Conversations:** Rich messaging supporting text, images, and document attachments.
* **Create Group Modal:** Multi-source user search (Admins, Teachers, Students), selected user chips, and empty group name validation alert.
* **Dual-Layer Real-Time Engine:** Combines Reverb WebSockets with smart background polling to ensure zero lost messages across all environments.

---

### 💰 6. Billing & Payrolls
* **Invoices View:** Automated monthly invoice generation for private-paying students, status management, and printing.
* **Teachers Payroll View:**
  - **Automated Payroll Calculation:** Calculates completed monthly lesson hours multiplied by the teacher's hourly rate.
  - **Manual Overrides:** Manually specify total amounts and initial status to generate custom items or adjustments without requiring lesson records.
  - **Deletion & Soft-Delete:** Red deletion button allowing instant record removal and recalculation.
  - **Advanced Search & Filtering:** Filter by name, specific day, or custom date range (From - To).

---

### ⚙️ 7. General Settings & RBAC
* **Subjects & Rooms View:** Classroom capacity management, physical vs online setup, and subject code management.
* **Standard Packages:** Preset subscription packages and pricing.
* **Users, Roles & RBAC:** Role-based permission controls (`Super Admin`, `Center Manager`, `Teacher`, `Parent/Student`).

---

## 📜 License

This project is proprietary software developed as a Core Management Boilerplate. All rights reserved.
