# 10 - Development Workflow & Quality Gates

This document defines the pipelines, testing strategies, and tools for code integration, release quality verification, and database migrations.

---

## 1. Quality Gates (Automated CI/CD Validation)

Every code check-in must pass automated verification checks before merging.

```
[Code Push] ──> [Laravel Pint] ──> [Pest Tests] ──> [PHPStan Level 9] ──> [Deptrac Check] ──> [Merge Approval]
```

### 1.1 Quality Gate Tools Configurations

- **Laravel Pint**: Enforces strict PSR-12 styling configurations.
  - Command: `composer run lint`
- **Pest PHP**: Enforces strict unit testing. Pest suites target 100% coverage on Domain aggregates. Requires zero database connections or framework boot steps.
  - Command: `composer test`
- **PHPStan (Level 9)**: Enforces strict type safety, nullability, and non-empty checks.
  - Command: `composer run analyze`
- **Deptrac**: Enforces layer boundaries.
  - **Allowed**: Modules can import Core; Core can import Shared; Modules can import Shared.
  - **Forbidden**: Core cannot import Modules (`Core -> Modules`). Modules cannot import other Modules (`Nachhilfe -> Beauty`, `Restaurant -> Nachhilfe`).
  - Command: `composer run deptrac`
- **Additive Migrations Checks**: Enforces zero column drops or schema renames on active migrations.

---

## 2. Git & Release Management Rules

### 2.1 Branch Naming Conventions
- `feature/` -> Add new functionality (e.g. `feature/tutoring-attendance`)
- `bugfix/` -> Resolve bugs (e.g. `bugfix/outbox-retry-limit`)
- `refactor/` -> Structural changes with no functional modifications

### 2.2 Pull Request Guidelines
1. **Single Focus**: Each PR must address a single task or issue.
2. **Squash and Merge**: Feature branches must be squashed into a single clean commit when merging into main.
3. **Review Requirements**: Two approvals from senior backend engineers are required before merge.

---

## 3. Database Schema Lifecycle & Release Management

### 3.1 Versioning Strategy
We adhere to Semantic Versioning 2.0.0 (Major.Minor.Patch):
- **Major**: Major architectural modifications.
- **Minor**: New business module additions.
- **Patch**: Security fixes and backward-compatible patches.

### 3.2 Additive Migration Policy
- Migrations must only perform **additive** database adjustments. Deleting columns or renaming tables directly is strictly forbidden on active database systems.
- **Deprecation Cycle**: To drop a column:
  1. Release code that stops reading the column.
  2. In the next release cycle, execute the migration dropping the column safely.
- **Rollback Verification**: Every migration must define a functional `down()` rollback method.
