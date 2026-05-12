# Deploy Production ke CyberPanel (PG Madukismo)

Panduan ini khusus untuk project ini (`Laravel 12`, `PHP 8.2`, frontend `Vite + Vue`, database `PostgreSQL + SQL Server`).

## 1) Prasyarat Server

- VPS Linux (disarankan Ubuntu 22.04+)
- CyberPanel + OpenLiteSpeed sudah terpasang
- Domain sudah mengarah ke IP server (`A record`)
- Akses SSH ke server

### Kebutuhan Runtime

- PHP `8.2`
- Composer `2.x`
- Node.js `20+` (hanya jika build frontend di server)
- PostgreSQL client/server (sesuai arsitektur)
- Driver SQL Server untuk Linux:
  - `msodbcsql18`
  - `unixodbc-dev`
  - `php8.2-sqlsrv`
  - `php8.2-pdo-sqlsrv`

### PHP Extensions minimum

`mbstring`, `xml`, `curl`, `zip`, `bcmath`, `intl`, `openssl`, `fileinfo`, `pdo`, `pdo_pgsql`, `sqlsrv`, `pdo_sqlsrv`.

---

## 2) Buat Website di CyberPanel

1. Masuk CyberPanel → **Websites** → **Create Website**.
2. Isi domain (misal `dashboard.domainanda.com`) dan pilih PHP `8.2`.
3. Catat path docroot website, biasanya:
   - `/home/<nama-user>/public_html/<domain>`
4. Pastikan **Rewrite** aktif untuk OpenLiteSpeed.

> Untuk Laravel, aplikasi harus serve dari folder `public`.

---

## 3) Upload Source Code

Metode paling aman: clone dari git ke folder website.

```bash
cd /home/<nama-user>/public_html/<domain>
git clone <repo-url> .
```

Jika upload ZIP, extract sampai struktur root project berisi file seperti `artisan`, `composer.json`, `app/`, `public/`.

---

## 4) Set Document Root ke `public`

Di CyberPanel, pastikan document root website menunjuk ke folder:

`/home/<nama-user>/public_html/<domain>/public`

Jika UI tidak memungkinkan langsung, gunakan konfigurasi vHost OpenLiteSpeed agar web root mengarah ke `public`.

---

## 5) Install Dependency Backend

Jalankan dari root project:

> Disarankan **jangan** menjalankan Composer sebagai `root`. Pakai user pemilik website/domain di CyberPanel. Jika terlanjur login sebagai `root`, pindah dulu ke user website dengan `su - <nama-user>` lalu jalankan Composer dari sana.

```bash
cd /home/<nama-user>/public_html/<domain>
composer install --no-dev --optimize-autoloader
```

Jika memory mepet:

```bash
COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader
```

Jika muncul pesan:

```text
Do not run Composer as root/super user
Continue as root/super user [yes]?
```

itu **warning**, bukan error. Aman untuk dibatalkan lalu dijalankan ulang sebagai user non-root. Menjawab `yes` tetap bisa lanjut, tetapi tidak direkomendasikan untuk deploy rutin.

---

## 6) Siapkan `.env` Production

Buat file `.env` dari template:

```bash
cp .env.example .env
```

Ubah minimal konfigurasi berikut:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://dashboard.domainanda.com
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id
APP_FALLBACK_LOCALE=id

LOG_CHANNEL=stack
LOG_LEVEL=warning

DB_CONNECTION=pgsql
DB_PGSQL_HOST=<host-pgsql>
DB_PGSQL_PORT=5432
DB_PGSQL_DATABASE=<db-pgsql>
DB_PGSQL_USERNAME=<user-pgsql>
DB_PGSQL_PASSWORD=<password-pgsql>

DB_SQLSRV_HOST=<host-sqlsrv>
DB_SQLSRV_PORT=1433
DB_SQLSRV_DATABASE=<db-sqlsrv>
DB_SQLSRV_USERNAME=<user-sqlsrv>
DB_SQLSRV_PASSWORD=<password-sqlsrv>
DB_SQLSRV_ENCRYPT=yes
DB_SQLSRV_TRUST_CERT=false

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Generate key aplikasi:

```bash
php artisan key:generate --force
```

---

## 7) Build Frontend (Vite)

### Opsi A — Build di server

```bash
npm ci
npm run build
```

### Opsi B — Build di lokal/CI (direkomendasikan)

- Jalankan `npm ci && npm run build` di lokal/CI.
- Upload folder hasil build: `public/build` ke server.

---

## 8) Migrasi & Cache Laravel

> Jalankan hanya setelah backup database production.

```bash
php artisan migrate --force
php artisan db:seed --force

php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Jika tidak ingin seeding di production, lewati `db:seed --force`.

---

## 9) Permission Folder

Pastikan web user bisa menulis ke `storage` dan `bootstrap/cache`:

```bash
cd /home/<nama-user>/public_html/<domain>
chown -R nobody:nobody storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

> Di beberapa server user web bukan `nobody` (bisa `www-data`/`lsadm`). Sesuaikan dengan konfigurasi server Anda.

---

## 10) SSL, Scheduler, Queue

### SSL

- CyberPanel → **SSL** → Issue Let’s Encrypt.
- Aktifkan redirect HTTP ke HTTPS.

### Cron Scheduler Laravel

Tambahkan cron (setiap menit):

```cron
* * * * * /usr/bin/php /home/<nama-user>/public_html/<domain>/artisan schedule:run >> /dev/null 2>&1
```

### Queue Worker (jika dipakai)

Gunakan `systemd` atau `supervisor` untuk menjalankan:

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=90
```

---

## 11) Instal Driver SQL Server di Ubuntu (Jika Belum Ada)

```bash
curl -sSL https://packages.microsoft.com/keys/microsoft.asc | sudo gpg --dearmor -o /usr/share/keyrings/microsoft-prod.gpg
curl -sSL https://packages.microsoft.com/config/ubuntu/22.04/prod.list | sudo tee /etc/apt/sources.list.d/mssql-release.list
sudo apt update
sudo ACCEPT_EULA=Y apt install -y msodbcsql18 unixodbc-dev
sudo apt install -y php8.2-sqlsrv php8.2-pdo-sqlsrv
sudo systemctl restart lsws
```

Verifikasi extension:

```bash
php -m | grep -E "sqlsrv|pdo_sqlsrv|pdo_pgsql"
```

---

## 12) Checklist Verifikasi Pasca Deploy

- [ ] `https://domain` bisa diakses tanpa 403/500
- [ ] Login berhasil
- [ ] Data PostgreSQL tampil normal
- [ ] Data SQL Server tampil normal
- [ ] Folder `storage/logs` terisi log tanpa permission error
- [ ] Cron `schedule:run` berjalan
- [ ] Queue (jika digunakan) memproses job

Cek cepat:

```bash
php artisan about
php artisan optimize:clear
php artisan config:cache
```

---

## 13) SOP Deploy Update (Rutin)

Dari root project:

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm ci && npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Lalu restart service terkait (OpenLiteSpeed/queue worker) jika diperlukan.

---

## 14) Rollback Sederhana

1. Simpan backup DB sebelum migrasi.
2. Simpan tag release git sebelum deploy.
3. Jika gagal:
   - checkout ke release sebelumnya
   - restore backup DB
   - `php artisan optimize:clear && php artisan config:cache`

---

## Catatan Khusus Project Ini

- Project memakai dua koneksi database (`pgsql` dan `sqlsrv`) sehingga validasi koneksi keduanya wajib sebelum go-live.
- Default koneksi utama ada di `config/database.php` (`DB_CONNECTION=pgsql`).
- Pastikan variabel SQL Server (`DB_SQLSRV_*`) terisi benar di `.env` production.
