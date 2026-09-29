# Domain foundation

## Unit

Semua pemilik stok direpresentasikan sebagai unit.

Contoh:

- Sarpras Pusat
- TJKT
- TKR
- Tata Boga
- TU
- Perpustakaan

Dengan model ini, distribusi adalah perpindahan stok antar-unit. Sarpras Pusat adalah unit bertipe `central`.

## Barang masuk

Data minimum:

- tanggal barang masuk;
- nama barang;
- jumlah;
- spesifikasi opsional;
- supplier/vendor;
- invoice opsional;
- foto barang;
- pencatat.

Barang masuk pertama kali menambah stok unit Sarpras Pusat.

## Distribusi

Distribusi dapat memindahkan sebagian quantity.

Contoh: stok pusat 10, TJKT menerima 3. Sisa pusat 7.

Status:

1. draft
2. awaiting_signed_document
3. completed
4. cancelled

Membuat draft tidak langsung memindahkan stok. Setelah surat bertanda tangan di-upload dan petugas mengkonfirmasi, transaksi database melakukan row lock, validasi stok ulang, pengurangan sumber, penambahan tujuan, ledger, dan completion secara atomik.

## Peminjaman siswa

Entry point publik:

QR unit -> PIN unit -> form peminjaman.

Lookup siswa berasal dari JUARA read-only.

Form:

- nama siswa (autocomplete);
- NIS dan kelas hasil lookup;
- nomor HP wajib;
- satu atau lebih barang;
- quantity per barang;
- keperluan;
- perkiraan pengembalian.

Status utama:

`pending_approval -> approved -> borrowed -> return_pending -> completed`

Cabang:

- rejected
- overdue
- partially_returned
- lost / damaged pada item pengembalian

Stok baru berubah menjadi borrowed saat petugas benar-benar menyerahkan barang.

## Pengembalian

Peminjam mengirim permintaan pengembalian dan upload foto bukti.

Petugas unit memeriksa fisik lalu approve/reject.

Foto peminjam adalah submission evidence, bukan final verification.

Barang kondisi baik kembali ke available. Barang rusak masuk damaged dan tidak kembali ke available.

## Stock invariant

Semua quantity unsigned.

Untuk setiap `unit_stocks`:

`total_qty = available_qty + reserved_qty + borrowed_qty + damaged_qty + lost_qty`

Mutasi stok wajib:

- transaction;
- `SELECT ... FOR UPDATE`;
- validasi ulang di dalam transaction;
- idempotency untuk action final;
- stock movement / ledger;
- rollback penuh jika satu langkah gagal.

Tidak boleh mengandalkan validasi frontend.
