# 08 - Module Customization & Extensibility

This section defines how new business modules are added and registered.

---

# 1. Adding a New Module

Example: adding a `Gym` module:
- Create folder `app/Modules/Gym/` with:
```
Gym/
├── Domain/
├── Application/
├── Infrastructure/
├── Presentation/
└── Providers/
```

---

# 2. Module Provider

Each module contains its Service Provider (e.g. `GymServiceProvider.php`) which is responsible for registering:
* Routes.
* Commands.
* Container bindings.
* Events listeners.
* Migration paths.

---

# 3. Module Registration

All modules are registered in `config/modules.php`. Service Providers are loaded from this config file. Querying the database during Laravel framework boot is strictly forbidden.

Example (`config/modules.php`):
```php
return [
    'Nachhilfe' => [
        'enabled'  => true,
        'provider' => App\Modules\Nachhilfe\Providers\ModuleServiceProvider::class,
    ],
    'Beauty' => [
        'enabled'  => true,
        'provider' => App\Modules\Beauty\Providers\ModuleServiceProvider::class,
    ],
];
```

---

# 4. Runtime Module Settings & Cache-First checks

Dynamic activation/deactivation of features at runtime is handled through a database settings table (`module_settings` containing fields `module_name` and `enabled`). 

To avoid database bottlenecks:
- The system checks module settings using a **cache-first strategy** inside routing middleware or actions.
- The settings database table is **never** queried during Laravel Service Provider bootstrap.
- Changing a setting at runtime clears the local cache key for that module setting.
