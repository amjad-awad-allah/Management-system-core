# 03 - Software Architecture Document (SAD)

## High Level Architecture

```
                 Users

                   |
                   |

            Presentation Layer

                   |

            Application Layer

                   |

        -----------------------

        Core        Modules

        -----------------------

                   |

          Infrastructure Layer

                   |

             PostgreSQL

```

---

# Application Structure

```
app/

├── Core/
│   ├── Auth
│   ├── Authorization
│   ├── Users
│   ├── Notifications
│   ├── Settings
│   └── Audit

├── Modules/
│   ├── Nachhilfe
│   ├── Beauty
│   ├── Restaurant
│   └── Billing

└── Shared/
    ├── Contracts/       <-- Global stable integration contracts ONLY
    ├── DTOs/
    ├── Events/
    └── Helpers/
```

---

# Module Internal Structure

Example:

```
Modules/Nachhilfe/

├── Domain/
│   ├── Entities/
│   ├── ValueObjects/
│   ├── Events/
│   └── Contracts/       <-- Module-specific contracts live in the module

├── Application/
│   ├── Actions/
│   ├── DTOs/
│   └── Services/

├── Infrastructure/
│   ├── Models/
│   ├── Repositories/
│   └── ExternalServices/

├── Presentation/
│   ├── Controllers/
│   ├── Requests/
│   └── Resources/

└── Providers/
    └── ModuleServiceProvider.php
```

---

# Dependency Direction

Allowed:

```
Presentation
      ↓
Application
      ↓
Domain

Infrastructure implements Domain contracts
```

Forbidden:

```
Domain -> Database
Domain -> Controller
Module A -> Module B Database
Core -> Business Module
```

---

# Module Registration & Routing Flow

Module registration is configuration-driven via `config/modules.php`. At boot, the enabled module service providers register their routes, which pass through the `module.active` middleware. This middleware checks the dynamic module settings using a cache-first strategy.

```
config/modules.php
      │
      ▼
Module Service Provider
      │
      ▼
Route Registration
      │
      ▼
module.active Middleware
      │
      ▼
Cached module_settings check
```
