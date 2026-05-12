# Dashboard Monitoring PG Madukismo
## Struktur Project (Laravel + Vue 3 + Vite)

```text
pg-madukismo/
 app/
    Enums/
       Permission.php
    Http/
       Controllers/
          Auth/
             AuthenticatedSessionController.php
          Api/
              DashboardController.php
              PenerimaanController.php
              EposController.php
              PosNppController.php
              LabQaController.php
              AclManagementController.php
              UserManagementController.php
       Middleware/
           AbacMiddleware.php
    Models/
       Pgsql/
       Sqlsrv/
    Services/
        AbacService.php
 config/
    database.php
 database/
    migrations/
       pgsql/
       sqlsrv/
       2026_04_29_000001_add_lab_qa_pos_npp_permission_admin_only.php
    seeders/
 docs/
    STRUKTUR_PROJECT.md
 resources/
    js/
        components/
           layout/
               AdminLayout.vue
        composables/
        router/
           index.ts
        stores/
        views/
            Auth/
            Dashboard/
            Penerimaan/
               ManajemenSPA/
                   Index.vue
                   PengaturanEPOS.vue
            LabQa/
               Index.vue
               PosNpp.vue
            Admin/
                Users/
                Acl/
                    Index.vue
 routes/
    api.php
    web.php
 public/
     build/
```

## Catatan Modul

- `Lab QA > Pos NPP`
  - Frontend: `resources/js/views/LabQa/PosNpp.vue`
  - API: `GET /api/lab-qa/pos-npp/config`, `GET /api/lab-qa/pos-npp/list`, `POST /api/lab-qa/pos-npp/submit`
  - Permission: `lab_qa.pos_npp`

- `Penerimaan > Pengaturan EPOS`
  - Frontend: `resources/js/views/Penerimaan/ManajemenSPA/PengaturanEPOS.vue`
  - API pengunci SPTA: `GET /api/epos/mstpos`, `POST /api/epos/mstpos/kunci`
  - Sumber data: `TBL_MSTPOS`

- `Manajemen ACL`
  - Frontend: `resources/js/views/Admin/Acl/Index.vue`
  - API: `GET /api/acl/permissions`, `GET /api/acl/roles`, `POST/PUT/DELETE /api/acl/roles`
  - `Pos NPP` sudah dimasukkan ke daftar hak akses di grup `Analisa QA`.
