# LXC Requirements — Aplikasi Sarpras

Target awal: satu LXC aplikasi Laravel. Database, Redis, JUARA, dan S3-compatible storage boleh berada di host/service terpisah.

## LXC

- OS: Debian 13 atau Ubuntu 24.04 LTS
- LXC: unprivileged
- CPU: 2 vCPU minimum, 4 vCPU nyaman
- RAM: 4 GB minimum
- Disk: 30 GB minimum
- Network: IP statis / DHCP reservation
- Timezone: Asia/Jakarta

## Runtime

- PHP **8.4** (required by the QR library used by this project)
- Extensions:
  - bcmath
  - ctype
  - curl
  - fileinfo
  - intl
  - mbstring
  - openssl
  - pdo
  - pdo_mysql
  - redis
  - tokenizer
  - xml
  - zip
- Composer 2.x
- Nginx
- Git
- Node.js is not required for the current server-rendered MVP.

QR is generated locally as SVG by `endroid/qr-code`; the public unit token is never sent to a third-party QR service.

## Main database

MySQL 8 or compatible MariaDB.

Create a dedicated Sarpras user. Do not run the application with root database credentials.

## Redis

Used for cache, session, queue, rate limiting and temporary public-form state.

## S3-compatible storage

Production should use a private MinIO/S3-compatible bucket.

Stored objects include:

- item photos;
- invoices;
- signed distribution handover documents;
- borrower return evidence photos.

Do not make the bucket public.

## JUARA read-only

Run `docs/JUARA_READONLY.sql` on the JUARA database.

Create a dedicated `sarpras_reader` credential that receives SELECT only on `sarpras_student_directory`.

Do not grant INSERT, UPDATE, DELETE or broad `SELECT ON juara.*`.

## First boot

```bash
git clone https://github.com/rfmoscowsind/Aplikasi-Sarpras.git
cd Aplikasi-Sarpras
cp .env.example .env
nano .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan optimize:clear
```

Before seeding, fill:

- `SARPRAS_ADMIN_EMAIL`
- `SARPRAS_ADMIN_PASSWORD`

Also configure DB, JUARA, Redis and S3 values.

## Nginx

Document root must point to:

```text
/path/to/Aplikasi-Sarpras/public
```

Do not expose the project root.

## Workers

For production run at least:

```bash
php artisan queue:work --sleep=1 --tries=3
php artisan schedule:work
```

Use systemd or another service supervisor.

## Health check

Laravel health endpoint:

```text
GET /up
```
