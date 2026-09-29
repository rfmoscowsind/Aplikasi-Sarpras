# LXC Requirements — Aplikasi Sarpras

Target awal: satu LXC aplikasi untuk Laravel. Database, Redis, dan S3-compatible storage boleh berada di host/service terpisah.

## Minimum development

- OS: Debian 13 atau Ubuntu 24.04 LTS
- LXC: unprivileged
- CPU: 2 vCPU minimum, 4 vCPU nyaman
- RAM: 4 GB minimum
- Disk: 30 GB minimum
- Network: IP statis / DHCP reservation
- Timezone: Asia/Jakarta

## Runtime

- PHP 8.4 recommended (Laravel 13 membutuhkan PHP >= 8.3)
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
- Node.js LTS + npm
- Nginx
- Git

## Services

### Main database

MySQL 8 / MariaDB yang kompatibel.

Database user aplikasi **jangan root**. Beri hak hanya pada database Sarpras.

### Redis

Dipakai untuk:

- cache;
- session;
- queue;
- rate limiting / temporary public-form state.

### S3-compatible storage

Production disarankan memakai MinIO / S3-compatible storage.

Bucket Sarpras bersifat private. Foto barang, invoice, surat serah-terima bertanda tangan, dan bukti pengembalian tidak boleh diekspos sebagai public object.

### JUARA database

Sarpras memakai koneksi kedua bernama `juara`.

Buat credential terpisah seperti `sarpras_reader` yang hanya diberi hak SELECT pada view/dataset siswa yang diperlukan. Jangan beri write permission ke database JUARA.

Data minimum yang dibutuhkan:

- student_id;
- name;
- nis;
- class_name;
- class_code.

Rekomendasi: expose view khusus, misalnya `sarpras_student_directory`, bukan akses bebas ke seluruh tabel `users`.

## First boot

```bash
git clone https://github.com/rfmoscowsind/Aplikasi-Sarpras.git
cd Aplikasi-Sarpras
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
```

Setelah frontend ditambahkan:

```bash
npm install
npm run build
```

## Environment values to prepare

Isi minimal:

- `APP_URL`
- `DB_HOST`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `JUARA_DB_*`
- `REDIS_HOST`
- `AWS_ACCESS_KEY_ID`
- `AWS_SECRET_ACCESS_KEY`
- `AWS_BUCKET`
- `AWS_ENDPOINT`

Jangan commit file `.env`.
