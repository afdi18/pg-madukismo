# Dashboard Monitoring PG Madukismo
## Struktur Project Laravel 12 + Vue 3 + TailAdmin Pro

```
pg-madukismo/
├── app/
│   ├── Enums/
│   │   └── Permission.php             # Daftar permission ABAC
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   └── AuthenticatedSessionController.php
│   │   │   ├── Api/
│   │   │   │   ├── DashboardController.php     (SQL Server)
│   │   │   │   ├── PetaKebunController.php     (PostgreSQL)
│   │   │   │   ├── LabQaController.php         (PostgreSQL)
│   │   │   │   └── UserManagementController.php(PostgreSQL)
│   │   ├── Middleware/
│   │   │   └── AbacMiddleware.php
│   │   └── Requests/
│   ├── Models/
│   │   ├── pgsql/                     # Models PostgreSQL
│   │   │   ├── User.php
│   │   │   ├── Role.php
│   │   │   ├── Permission.php
│   │   │   ├── UserAttribute.php
│   │   │   ├── AbacPolicy.php
│   │   │   ├── KebunPeta.php
│   │   │   └── LabQa.php
│   │   └── sqlsrv/                    # Models SQL Server
│   │       ├── RendemenHarian.php
│   │       ├── RingkasanMusim.php
│   │       └── KpiOperasional.php
│   └── Services/
│       ├── AbacService.php            # Logic ABAC
│       └── DualDatabaseService.php
├── config/
│   └── database.php                   # Dual DB config
├── database/
│   ├── migrations/
│   │   ├── pgsql/                     # Migration PostgreSQL
│   │   └── sqlsrv/                    # Migration SQL Server
│   └── seeders/
│       ├── AbacSeeder.php
│       └── UserSeeder.php
├── resources/
│   ├── js/                            # Vue Frontend
│   │   ├── components/
│   │   │   ├── layout/                # TailAdmin Layout
│   │   │   ├── common/                # Shared components
│   │   │   ├── dashboard/             # Dashboard widgets
│   │   │   ├── peta/                  # Leaflet map
│   │   │   ├── lab/                   # Lab QA
│   │   │   └── admin/                 # User management
│   │   ├── composables/
│   │   │   ├── useAuth.ts
│   │   │   ├── useTheme.ts
│   │   │   ├── useFullscreen.ts
│   │   │   ├── usePermissions.ts
│   │   │   └── useAbac.ts
│   │   ├── router/
│   │   │   └── index.ts               # Vue Router + ABAC guard
│   │   ├── stores/
│   │   │   ├── auth.ts                # Pinia auth store
│   │   │   └── abac.ts                # Pinia ABAC store
│   │   ├── views/
│   │   │   ├── Auth/
│   │   │   │   └── Login.vue          # Login username+pass
│   │   │   ├── Dashboard/
│   │   │   │   ├── Index.vue          # Dashboard utama
│   │   │   │   ├── InformasiTebu.vue
│   │   │   │   └── MonitoringPabrik.vue
│   │   │   ├── PetaKebun/
│   │   │   │   └── Index.vue          # Leaflet GIS
│   │   │   ├── LabQa/
│   │   │   │   └── Index.vue
│   │   │   └── Admin/
│   │   │       ├── Users/Index.vue    # Manajemen user
│   │   │       └── Users/Create.vue   # Tambah user (admin only)
│   │   └── types/
│   │       └── index.d.ts
│   └── views/
│       └── app.blade.php              # SPA entry point
└── routes/
    ├── api.php                        # API routes + ABAC
    └── web.php
```
