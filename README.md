# Management System Core (Modular Monolith Blueprint)

This repository serves as the enterprise-grade **Core Boilerplate** and **Architectural Blueprint** for all future modules and SaaS applications (e.g., Beauty, Restaurant, Billing, Nachhilfe).

---

## 🚀 Architecture Highlights
This platform is built on a highly strict **Modular Monolith** architecture:
- **Core Namespace**: `App\Core` handles unified systems (Authentication, RBAC Authorization, Base Models, Global Audits).
- **Module Namespaces**: Each module lives independently in `App\Modules\{ModuleName}` (e.g., `App\Modules\Nachhilfe`).
- **Strict Boundaries**: Modules are completely forbidden from directly interacting with each other's Databases or Eloquent Models.
- **Domain-Driven Design (DDD)**: Features strict separation into `Domain` (Entities, Value Objects), `Infrastructure` (Eloquent Repositories), and `Application` (Actions).

---

## 🛡️ Key Enterprise Features
- **Transactional Outbox Pattern**: Secure inter-module communication using `outbox_events` and a `lockForUpdate()->skipLocked()` background daemon worker (`php artisan outbox:work`).
- **Blind Indexing Encryption**: Sensitive PII data (Phones, Emails) are encrypted at rest using Laravel's encrypter and searched using SHA-256 salted Blind Indices.
- **Universal IDs (ULID)**: All primary keys and foreign keys use ULID (`foreignUlid()`) for hyper-scalable, timestamp-sortable, and conflict-free IDs.
- **Idempotency Guarantees**: A strict `processed_events` compound unique index ensures events cannot be executed twice.
- **GDPR Private Storage**: Sensitive student documentation (e.g., contracts, Jobcenter approvals) is stored in a non-public storage directory (`storage/app/private/`) and streamed securely through authenticated routes with Role-Based Access Controls (RBAC).

---

## 🚦 Quality Gates (QA)
The architecture is enforced mathematically via static analysis tools:
1. **Deptrac**: Enforces architectural boundaries (`vendor/bin/deptrac`).
2. **PHPStan (Level 9)**: Maximum strictness for Data Types, ensuring no `mixed` arrays pass silently (`vendor/bin/phpstan analyse`).
3. **Pest**: Fast, beautiful testing for unit and feature layers (`vendor/bin/pest`).

---

## 🛠️ Requirements & Setup
- PHP 8.2+
- Laravel 11.x
- PostgreSQL / MySQL 8+ (For Outbox SKIP LOCKED feature)

To spin up the worker:
```bash
php artisan outbox:work --sleep=3
```

---

## 🖥️ دليل استخدام وإدارة لوحة التحكم (User Guide & Control Panel Manual)

تم تصميم لوحة التحكم الخاصة بنظام **Nachhilfe ERP** لتسهيل إدارة شؤون الطلاب، المدرسين، الحصص، الموافقات الحكومية (Jobcenter/BuT)، والتقارير الشهرية بشكل آمن ومتكامل. فيما يلي شرح مفصل لكل قسم وخيار متاح في لوحة التحكم وكيفية الاستفادة منه:

### 1. شاشة الملف الشخصي للطالب (Student Profile View)
تحتوي هذه الشاشة على الملف الموحد للطالب وتضم أربعة تبويبات أساسية لتنظيم العمل:

---

#### 📑 التبويب الأول: نظرة عامة (Overview)
هذا التبويب يعطيك ملخصاً سريعاً عن حالة الطالب الأكاديمية والتمويلية:
* **التفاصيل الأساسية (Details Card):**
  * يعرض نوع الفوترة (خاص، من الدولة/مكتب العمل).
  * حالة الطالب الحالية في النظام (نشط، متوقف).
  * تاريخ ميلاد الطالب (Geburtsdatum).
* **المواد المسجل بها (Enrolled Subjects):**
  * تعرض جميع المواد الدراسية التي يتابعها الطالب (مثل: الرياضيات، الألمانية).
* **المعلمون المسؤولون (My Teachers):**
  * قائمة بالمعلمين المخصصين لتدريس هذا الطالب مع إمكانية الضغط على اسم المعلم للانتقال لملفه.
* **موافقات وباقات الساعات (Hour Approvals & Vouchers):**
  * هذا القسم يمثل **قلب نظام إدارة التمويل (BuT/Jobcenter)**.
  * يتيح للمشرف إضافة موافقة جديدة (باقة ساعات) لكل مادة دراسية على حدة عبر الضغط على **Add Hour Approval**.
  * **حالات الموافقات (Package Status Lifecycle):**
    1. `Pending Approval` (Antrag gestellt): تم تقديم الطلب للمكتب ولم تصل الموافقة الرسمية بعد. يتيح هذا الخيار للطالب بدء الدروس مؤقتاً بالاعتماد على رصيد تقديري دون تعطيل العملية التعليمية.
    2. `Active` (Bewilligt): موافقة رسمية نشطة يمكن خصم الساعات منها.
    3. `Rejected` (Abgelehnt): تم رفض الطلب من قبل مكتب العمل. يُمنع النظام تلقائياً من استخدام رصيد هذه الباقة.
    4. `Expired` (Abgelaufen): انتهت فترة صلاحية الباقة المحددة في التاريخ. يقوم النظام تلقائياً بتغيير حالتها يومياً عبر مجدول المهام (Scheduler).
    5. `Exhausted`: انتهى رصيد الساعات بالكامل.
  * **محرك الخصم الذكي (Deduction Priority Engine):**
    عند تسجيل الحضور، يقوم النظام بخصم الساعات تلقائياً بترتيب ذكي: يبدأ بالباقات النشطة (`Active`) أولاً، ثم المعلقة (`Pending Approval`)، مع إعطاء الأولوية دائماً للباقة التي يقترب تاريخ انتهائها (`Soonest Expiration`) لضمان عدم ضياع الساعات على الطالب.

---

#### ⏳ التبويب الثاني: سجل العمليات والأنشطة (Student Timeline)
يوفر هذا التبويب أداة رقابة كاملة (Audit Trail) لمعرفة كل ما جرى لحساب الطالب بصورة متسلسلة زمنياً:
* **فلاتر العرض (Category Filters):**
  * يمكنك تصفية الأحداث حسب: الحصص المحجوزة، حضور وغياب الطالب، الفواتير المالية، الموافقات وباقات الساعات، ملاحظات المدرسين، أو التغييرات النظامية.
* **تفاصيل التعديل الفني (System Changes & Metadata):**
  * عند قيام الإدارة بتعديل أي بيانات (مثل تغيير اسم الأب أو رصيد الباقة)، يسجل النظام الحدث.
  * بالضغط على **Show System Edit Details**، يتم عرض القيم القديمة (Old Value) والقيم الجديدة (New Value) للحقوق المعدلة والمسؤول عن التعديل لضمان الشفافية الكاملة وتفادي الأخطاء.

---

#### 💶 التبويب الثالث: الفواتير (Invoices)
يختص بالعمليات المالية الخاصة بالطالب (لأولياء الأمور الذين يدفعون بشكل خاص أو فواتير المساهمات الحكومية):
* **استعراض الفواتير:** يعرض الفواتير الصادرة للطالب، قيمتها الإجمالية، والشهر التابع لها.
* **تأكيد الدفع (Mark Paid):** خيار مخصص للإدارة لتأكيد استلام المبالغ يدوياً وتغيير حالة الفاتورة فوراً من `Draft/Unpaid` إلى `Paid`.

---

#### 📂 التبويب الرابع: المستندات والتقارير (Documents & Reports)
تم تصميمه ليكون متوافقاً بالكامل مع معايير حماية البيانات الأوروبية الحساسة (GDPR):
* **قسم إدارة المستندات الآمنة (Student Documents):**
  * يتيح رفع ملفات هامة مثل عقود التدريس (`contract`)، إشعارات مكتب العمل (`application` / `extension`)، الفواتير (`invoice`)، وكشوفات الحضور اليومية (`attendance_sheet`).
  * **صلاحيات الوصول الصارمة (Strict Security Rules):**
    * **الإدارة (Admin):** تمتلك كامل الصلاحيات لرفع، استعراض، تحميل، وحذف أي مستند.
    * **أولياء الأمور (Parents):** يمكنهم فقط استعراض وتحميل مستندات أبنائهم فقط ولا يمكنهم الوصول لملفات طلاب آخرين نهائياً.
    * **المعلمون (Teachers):** يقتصر وصولهم **فقط** على مستندات كشوف الحضور (`attendance_sheet`) للطلاب الذين يدرسونهم فعلياً، ويتم حظرهم تلقائياً من رؤية العقود والخطابات المالية لضمان الخصوصية.
* **قسم التصدير الذكي (Export Stundennachweis PDF):**
  * يتيح للإدارة وأولياء الأمور توليد كشف حضور رسمي باللغة الألمانية لتقديمه لمكتب العمل للحصول على المستحقات.
  * **طريقة العمل:**
    1. حدد الشهر المطلوب (Target Month).
    2. اختر المادة الدراسية (أو اتركها فارغة لتشمل جميع المواد).
    3. اضغط على **Export Stundennachweis**.
    4. سيقوم النظام تلقائياً بالتحقق من الدروس التي حضرها الطالب فعلياً (حالة الحضور `present` أو `late` فقط) وحساب الساعات بدقة متناهية بناءً على مدة الدرس الفعلي (`Lesson duration_minutes`) وتصدير ملف PDF منسق وجاهز للطباعة يحتوي على جداول الحصص اليومية، مربعات لتوقيع الطالب اليومي، وإقرار نهائي يوقع عليه ولي الأمر والمركز.
