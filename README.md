# SIMONKEU — Sistem Monitoring Keuangan Pusdiklat

Aplikasi monitoring rencana & realisasi anggaran diklat, dibangun dari template
**Laporan Keuangan** (Excel) yang sudah dipakai. Dibuat dengan **PHP 8.2+**,
framework **CodeIgniter 4**, dan database **MySQL/MariaDB**.

## Fitur

- **Dashboard** interaktif: serapan anggaran, tren bulanan, akumulasi, porsi per
  jenis aktivitas, matriks jenis × bulan, komponen biaya, top kegiatan, per
  akun/cost center — semua bisa difilter tahun/bulan/jenis tanpa memuat ulang halaman.
- **Anggaran tahunan**: tetapkan pagu anggaran per tahun (total keseluruhan,
  dan/atau terpisah per jenis aktivitas). Pagu berkurang otomatis mengikuti
  realisasi transaksi — tidak perlu dihitung manual, dan tampil sebagai panel
  progres di dashboard serta halaman "Anggaran tahunan" tersendiri.
- **Status pembayaran** transaksi (Belum / Diproses / Lunas), dengan diagram
  proporsi status di dashboard sehingga langsung terlihat berapa banyak
  transaksi yang masih perlu diselesaikan.
- **CRUD transaksi** lengkap (tambah/lihat/ubah/hapus) dengan total biaya
  otomatis (mengikuti rumus `=SUM` pada template), validasi, dan pencarian/filter/urut/paginasi.
- **Import dari Excel**: membaca ulang template asli (header dikenali dari nama
  kolom, bukan posisi), merapikan nilai yang tidak konsisten, menormalkan data
  master baru secara otomatis, dan menampilkan pratinjau + peringatan sebelum disimpan.
- **Laporan & ekspor**: 7 jenis laporan (rekap bulanan, per jenis, matriks,
  per akun/CC, komponen biaya, rincian ala sheet PIVOT, daftar transaksi),
  masing-masing bisa difilter termasuk **Inhouse/Public**, dan diunduh sebagai
  **Excel** (dengan rumus hidup), **PDF**, atau **CSV**.
- **Validasi data**: aturan otomatis mendeteksi baris yang perlu diperiksa
  (realisasi melebihi anggaran, tanggal terbalik, dll).
- **Master data** (jenis aktivitas, Inhouse/Public, No. akun, Cost center),
  **manajemen pengguna** (admin/operator/viewer), dan **log aktivitas**.

## Kebutuhan sistem

- PHP ≥ 8.2 dengan ekstensi: `mysqli`, `mbstring`, `intl`, `curl`, `zip`, `gd`
- MySQL 5.7+/8 atau MariaDB 10.4+
- Composer

## Instalasi

```bash
composer install
cp .env.example .env    # lalu sesuaikan (lihat bawah)
php spark migrate
php spark db:seed DatabaseSeeder   # data referensi + akun awal + (opsional) data contoh dari Excel
php spark serve
```

Buka `http://localhost:8080`.

### Sudah pernah instal sebelumnya? (memperbarui ke versi ini)

Ganti seluruh isi folder `app/` dan `public/assets/` dengan yang ada di paket
ini (atau cukup timpa file-file yang berubah), lalu jalankan sekali:

```bash
php spark migrate
```

Perintah ini akan menambahkan tabel `anggaran_tahunan` dan merapikan status
pembayaran lama ke 3 kategori baru (Belum/Diproses/Lunas), tanpa mengubah
atau menghapus data transaksi yang sudah ada. Tidak perlu `db:seed` ulang.

### Konfigurasi `.env`

```ini
CI_ENVIRONMENT = production          # ganti 'development' saat masih menguji

app.baseURL = 'https://domain-anda.example/'

database.default.hostname = 127.0.0.1
database.default.database = simonkeu
database.default.username = root
database.default.password = 
database.default.DBDriver = MySQLi
database.default.port = 3306

Simonkeu.orgName  = 'PT Semen Padang'
Simonkeu.unitName = 'Pusdiklat'
```

### Akun awal (dari `DatabaseSeeder`)

| Nama pengguna | Kata sandi     | Peran      |
|---------------|----------------|------------|
| `admin`       | `admin123`     | Administrator |
| `operator`    | `operator123`  | Operator keuangan |
| `viewer`      | `viewer123`    | Peninjau (hanya baca) |

**Ganti kata sandi ini segera** setelah masuk pertama kali (menu Pengguna, atau
halaman "Ubah kata sandi" di pojok kanan atas).

### Mengimpor data dari file Excel Anda sendiri

Dua cara:
1. **Lewat aplikasi** (disarankan): masuk sebagai admin/operator → menu
   **Import Excel** → unggah file → periksa pratinjau → simpan.
2. **Saat instalasi awal**: ganti file di
   `app/Database/Seeds/data/Template_Laporan_Keuangan_Per_2_Agus_26.xlsx` dengan
   file Anda (nama file boleh beda, sesuaikan di `TransaksiSeeder.php`), lalu
   jalankan `php spark db:seed DatabaseSeeder`.

Sistem mengenali kolom dari **nama header**, bukan posisi kolom, dan mencari
baris header "AKTIVITAS" di sheet mana pun — jadi cukup toleran terhadap
template yang sedikit berbeda urutan kolomnya, selama nama kolomnya serupa.

## Struktur singkat

```
app/Controllers/   Alur permintaan (Dashboard, Transaksi, Laporan, Import, Master, Users, Audit, Auth)
app/Models/         Akses data (TransaksiModel, MasterModel + 4 turunannya, UserModel, AuditLogModel)
app/Libraries/       Logika inti: ExcelImporter, ReportBuilder, ExportXlsx, ExportPdf, Statistik, DataChecks
app/Views/           Tampilan (layout + per modul)
app/Database/Migrations/  Skema database
app/Database/Seeds/       Data referensi awal + (opsional) impor contoh dari Excel
public/assets/            CSS/JS/font/ikon — semua dibundel lokal, tanpa CDN
```

## Keamanan

- Kata sandi di-hash (`password_hash`/bcrypt), sesi diregenerasi saat masuk.
- Proteksi CSRF aktif untuk semua form.
- Percobaan masuk dibatasi (8×/menit per IP).
- Hak akses berbasis peran (admin/operator/viewer) di tingkat rute.
- Transaksi dihapus secara *soft delete* (tetap ada di database, tercatat di
  log aktivitas) — data tidak pernah hilang permanen dari aplikasi biasa.

## Menyesuaikan lebih lanjut

- Nama organisasi/unit, daftar status pembayaran, dan daftar kolom biaya diatur
  di `app/Config/Simonkeu.php`.
- Warna & tampilan (tema "beton & buku besar") ada di `public/assets/css/app.css`.
- Jenis laporan baru bisa ditambahkan di `app/Libraries/ReportBuilder.php`
  (method `ds...`) tanpa mengubah exporter Excel/PDF.
