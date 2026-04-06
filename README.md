# Dashboard Monitoring PG Madukismo
## Panduan Instalasi & Setup

---

## Persyaratan Sistem

| Komponen    | Versi Minimum  |
|-------------|----------------|
| PHP         | 8.2+           |
| Composer    | 2.x            |
| Node.js     | 20+            |
| PostgreSQL  | 14+            |
| SQL Server  | 2019+ / Azure  |
| Extension   | pdo_pgsql, pdo_sqlsrv |

---

## LANGKAH 1 — Clone & Install Dependensi

```bash
# Clone project
git clone <repository-url> pg-madukismo
cd pg-madukismo

# Install PHP dependencies
composer install

# Install Node dependencies (termasuk TailAdmin Pro)
# Salin folder src/ dari tailadmin-vue-pro ke resources/js/
# kemudian jalankan:
npm install

# Salin file environment
cp .env.example .env

# Generate application key
php artisan key:generate
```

---

## LANGKAH 2 — Konfigurasi Database

Edit file `.env`:

```env
# PostgreSQL
DB_PGSQL_HOST=127.0.0.1
DB_PGSQL_PORT=5432
DB_PGSQL_DATABASE=madukismo_system
DB_PGSQL_USERNAME=postgres
DB_PGSQL_PASSWORD=your_password

# SQL Server
DB_SQLSRV_HOST=localhost\SQLEXPRESS   # atau IP server
DB_SQLSRV_PORT=1433
DB_SQLSRV_DATABASE=madukismo_tanaman
DB_SQLSRV_USERNAME=sa
DB_SQLSRV_PASSWORD=your_password
DB_SQLSRV_TRUST_CERT=true             # untuk dev; false untuk production
```

### Buat Database di PostgreSQL:
```sql
CREATE DATABASE madukismo_system
    WITH ENCODING='UTF8'
    LC_COLLATE='id_ID.UTF-8'
    LC_CTYPE='id_ID.UTF-8';
```

### Buat Database di SQL Server:
```sql
CREATE DATABASE madukismo_tanaman
    COLLATE Indonesian_CI_AS;
```

---

## LANGKAH 3 — Jalankan Migrasi

```bash
# Migrasi PostgreSQL (default)
php artisan migrate --path=database/migrations/pgsql

# Migrasi SQL Server
php artisan migrate --path=database/migrations/sqlsrv \
                    --database=sqlsrv

# Jalankan seeder (buat roles, permissions, user admin)
php artisan db:seed

# Output:
# ✅ ABAC Seeder berhasil:
#    - 32 permissions dibuat
#    - 5 roles dibuat
#    - User admin: username=admin, password=Admin@Madukismo2024
```

---

## LANGKAH 4 — Integrasi TailAdmin Pro

```bash
# Salin komponen TailAdmin Pro yang dibutuhkan:
# Dari: tailadmin-vue-pro-v2.2.0/src/
# Ke:   resources/js/

# Komponen yang disalin:
cp -r tailadmin-vue-pro-v2.2.0/src/assets/css/  resources/js/assets/css/
cp -r tailadmin-vue-pro-v2.2.0/src/icons/        resources/js/icons/

# Salin logo Madu Baru:
cp madubaru.png public/images/logo/madubaru.png
```

---

## LANGKAH 5 — Build Frontend

```bash
# Development (dengan hot reload)
npm run dev

# Production build
npm run build
```

---

## LANGKAH 6 — Jalankan Server

```bash
# Development
php artisan serve

# Akses aplikasi:
# http://localhost:8000

# Login default:
# Username : admin
# Password : Admin@Madukismo2024
```

---

## Struktur ABAC — Role Default

| Role          | Akses                                                    |
|---------------|----------------------------------------------------------|
| Administrator | Semua fitur + manajemen user                             |
| Manajer       | Baca semua data + export. Tidak bisa kelola user         |
| Supervisor    | Input + update data + approve Lab QA                     |
| Operator      | Input + update data tanaman, kebun, lab                  |
| Viewer        | Baca saja (read-only) semua modul                        |

### Atribut ABAC:
- **area_kebun** — membatasi akses kebun per area (A, B, A1, B2, dll)
- **shift** — membatasi akses input data per shift kerja
- **level_akses** — publik / internal / rahasia

---

## Perintah Artisan Berguna

```bash
# Lihat daftar route API
php artisan route:list --path=api

# Clear cache
php artisan optimize:clear

# Re-seed (development)
php artisan migrate:fresh --path=database/migrations/pgsql && php artisan db:seed

# Tinker untuk testing
php artisan tinker
>>> App\Models\Pgsql\User::with('roles')->first()
```

---

## Catatan Keamanan Production

1. Ubah password admin default segera setelah instalasi
2. Set `APP_ENV=production` dan `APP_DEBUG=false`
3. Set `DB_SQLSRV_TRUST_CERT=false` dan gunakan SSL
4. Konfigurasi CORS di `config/cors.php`
5. Aktifkan HTTPS dan set `SESSION_SECURE_COOKIE=true`

---

## Troubleshooting

**Error: "could not find driver" untuk SQL Server:**
```bash
# Ubuntu/Debian — install driver ODBC + PHP extension
curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add -
apt-get install msodbcsql18 php8.2-sqlsrv php8.2-pdo-sqlsrv
```

**Error CORS saat development:**
```bash
# Pastikan .env memiliki:
APP_URL=http://localhost:8000
# Dan frontend Vite berjalan di port yang sama atau dikonfigurasi di config/cors.php
```

**Leaflet tidak muncul:**
```bash
# Pastikan CSS Leaflet ter-load. Di app.blade.php sudah ada:
# <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
# Pastikan container peta memiliki height yang ditentukan (bukan 0px)
```
EOF
