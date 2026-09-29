# Production deployment

Dokumen ini melengkapi `LXC_REQUIREMENTS.md`.

## Layout

Rekomendasi:

```text
/var/www/sarpras
/etc/nginx/sites-available/sarpras
/etc/systemd/system/sarpras-queue.service
/etc/systemd/system/sarpras-scheduler.service
```

## Deploy aplikasi

```bash
cd /var/www
git clone https://github.com/rfmoscowsind/Aplikasi-Sarpras.git sarpras
cd sarpras

cp .env.example .env
nano .env

composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --seed --force
php artisan optimize
php artisan sarpras:doctor
```

Ownership:

```bash
chown -R www-data:www-data /var/www/sarpras
chmod -R ug+rwX storage bootstrap/cache
```

## Nginx

Copy template:

```bash
cp deploy/nginx-sarpras.conf /etc/nginx/sites-available/sarpras
ln -s /etc/nginx/sites-available/sarpras /etc/nginx/sites-enabled/sarpras
nginx -t
systemctl reload nginx
```

Edit `server_name` terlebih dahulu.

Untuk HTTPS, pasang sertifikat di reverse proxy atau konfigurasi TLS langsung di Nginx. Production `APP_URL` harus memakai URL HTTPS final.

## Queue dan scheduler

```bash
cp deploy/sarpras-queue.service /etc/systemd/system/
cp deploy/sarpras-scheduler.service /etc/systemd/system/
systemctl daemon-reload
systemctl enable --now sarpras-queue sarpras-scheduler
```

Verifikasi:

```bash
systemctl status sarpras-queue
systemctl status sarpras-scheduler
curl -fsS http://127.0.0.1/up
```

## Setelah git pull

```bash
cd /var/www/sarpras
git pull --ff-only
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
php artisan queue:restart
systemctl restart sarpras-scheduler
php artisan sarpras:doctor
```

## Permission boundary

- DB Sarpras: read/write hanya database Sarpras.
- DB JUARA: user `sarpras_reader`, SELECT-only pada view student directory.
- S3/MinIO: bucket private.
- Web root: hanya `/var/www/sarpras/public`, bukan root repository.
- Jangan jalankan PHP-FPM atau queue worker sebagai root.
