# Aplikasi Sarpras

Aplikasi pengelolaan sarana-prasarana sekolah berbasis Laravel 13.

Repository ini sedang dibangun. Fondasi awal mencakup:

- barang masuk dan stok pusat Sarpras;
- distribusi barang ke unit / program keahlian;
- inventaris berbasis quantity;
- peminjaman siswa melalui QR unit + PIN;
- approval dan serah-terima oleh petugas unit;
- pengembalian dengan bukti foto peminjam dan verifikasi petugas;
- ledger pergerakan stok dan audit trail;
- lookup siswa dari JUARA melalui koneksi read-only;
- object storage S3-compatible untuk foto dan dokumen.

Lihat `docs/` untuk kebutuhan LXC dan rancangan domain.
