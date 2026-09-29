# Aplikasi Sarpras

Aplikasi pengelolaan sarana-prasarana sekolah berbasis Laravel 13.

## Fitur yang sudah tersedia

### Multi-account dan unit scoped access

Role sistem:

- `admin` — administrasi sistem + akses seluruh Sarpras;
- `sarpras` — operasional Sarpras pusat;
- `unit` — akses dibatasi oleh membership unit/program keahlian.

Kaprodi/kepala unit dapat:

- melihat inventaris unitnya;
- mencatat barang existing unit;
- mengelola PIN peminjaman unit;
- memproses approval, serah-terima, dan pengembalian peminjaman unit.

### Barang masuk

Data:

- tanggal barang masuk;
- supplier/vendor;
- invoice opsional;
- satu atau lebih barang;
- jumlah;
- spesifikasi opsional;
- foto barang;
- pencatat.

Barang masuk menambah stok Sarpras Pusat dan menghasilkan stock ledger.

### Distribusi

Flow:

```text
buat distribusi
    ↓
available -> reserved
    ↓
cetak surat serah terima
    ↓
surat ditandatangani
    ↓
upload surat final
    ↓
konfirmasi serah terima
    ↓
source.reserved -> target.available
```

Distribusi dapat dipisah per jumlah. Distribusi yang dibatalkan mengembalikan alokasi ke stok tersedia.

Finalisasi memakai database transaction + row locking dan tidak mengizinkan stok negatif.

### Inventaris unit

Unit dapat mencatat barang yang sudah berada di unit tanpa harus menunggu admin pusat.

Record menyimpan:

- siapa yang mencatat;
- sumber inventaris;
- foto;
- quantity;
- kondisi bucket stok;
- histori pergerakan stok.

### Peminjaman siswa tanpa akun

Flow publik:

```text
scan QR unit
    ↓
masukkan PIN unit
    ↓
cari siswa dari JUARA
    ↓
pilih beberapa barang
    ↓
nomor HP wajib
    ↓
isi keperluan + perkiraan pengembalian
    ↓
submit
```

Petugas unit:

```text
pending approval
    ↓
approve -> stok reserved
    ↓
serahkan barang -> stok borrowed + waktu pinjam tercatat
```

QR menggunakan random public token. PIN unit tidak disimpan di QR dan dapat diganti oleh pengelola unit. Token QR juga dapat dirotasi.

### Pengembalian

Peminjam:

- mencari transaksi aktif;
- memilih barang dan jumlah yang dikembalikan;
- menyatakan kondisi;
- upload foto bukti;
- submit.

Petugas memeriksa fisik lalu ACC atau menolak/meminta pengajuan ulang.

Saat ACC:

- kondisi baik -> `available`;
- rusak -> `damaged`;
- hilang -> `lost`.

Partial return didukung.

### Dashboard dan audit

Dashboard menampilkan antara lain:

- jenis barang;
- total quantity;
- sedang dipinjam;
- menunggu persetujuan;
- menunggu verifikasi kembali;
- terlambat;
- rusak.

Tersedia juga:

- stock ledger;
- audit log;
- histori aktor;
- private file/photo viewer dengan authorization.

## Keamanan stok

Invariant utama:

```text
total_qty =
    available_qty
  + reserved_qty
  + borrowed_qty
  + damaged_qty
  + lost_qty
```

Mutasi stok harus melalui `StockService` dan menggunakan:

- DB transaction;
- `SELECT ... FOR UPDATE`;
- validasi ulang di dalam transaction;
- ledger;
- idempotent final action.

Lihat `AGENTS.md` sebelum memodifikasi domain stok.

## JUARA integration

JUARA digunakan sebagai **read-only student directory**.

Data yang dipakai:

- student ID;
- nama;
- NIS;
- kelas.

Setup view dan least-privilege DB user:

```text
docs/JUARA_READONLY.sql
```

Jangan memberikan Sarpras write permission ke database JUARA.

## Runtime

Recommended:

- Debian 13 / Ubuntu 24.04 LTS
- PHP 8.4
- MySQL 8 / MariaDB compatible
- Redis
- private S3-compatible storage (MinIO juga oke)
- Nginx
- Composer 2

Detail lengkap:

```text
docs/LXC_REQUIREMENTS.md
```

## First deployment

```bash
git clone https://github.com/rfmoscowsind/Aplikasi-Sarpras.git
cd Aplikasi-Sarpras

cp .env.example .env
nano .env

composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --seed
php artisan optimize
php artisan sarpras:doctor
```

Isi minimal sebelum `migrate --seed`:

- main DB credentials;
- JUARA read-only credentials;
- Redis;
- S3-compatible endpoint + bucket;
- `SARPRAS_ADMIN_EMAIL`;
- `SARPRAS_ADMIN_PASSWORD`.

## Development / verification

```bash
composer install
php artisan test
php artisan sarpras:doctor
```

GitHub Actions menjalankan:

- Composer validation;
- dependency installation;
- PHP syntax checks;
- feature/regression tests;
- MySQL 8.4 migration verification.

## Production workers

Jalankan dengan systemd/Supervisor:

```bash
php artisan queue:work --sleep=1 --tries=3
php artisan schedule:work
```

Health endpoint:

```text
GET /up
```

## Storage

File produksi disimpan private melalui Laravel Filesystem abstraction.

Contoh object:

```text
incoming/
  invoices/
  photos/

unit-inventory/
  {unit_id}/

distributions/
  signed/{distribution_id}/

returns/
  public/
```

Jangan expose bucket secara public.
