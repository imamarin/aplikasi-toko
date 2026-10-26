# Aplikasi Kasir / Point of Sale (POS) Web Modern

Aplikasi Kasir / Point of Sale (POS) berbasis web yang profesional, modular, aman, responsif, dan siap digunakan untuk kebutuhan bisnis ritel, grosir, minimarket, toko kelontong, dan UMKM.

Aplikasi ini dibangun menggunakan arsitektur bersih Laravel dengan pemisahan *Service Layer*, integritas database transaksi penuh (`DB::transaction`), audit trail mutasi stok, serta rekonsiliasi kas shift kasir.

---

## 1. Teknologi & Stack Utama

* **Backend:** Laravel 13.x (PHP 8.2+)
* **Database:** MySQL / MariaDB
* **Frontend:** Blade Template + Bootstrap 5.3 + Bootstrap Icons
* **Interaktivitas:** Vanilla JavaScript (tanpa framework JS berat, super cepat & ringan)
* **Visualisasi & Grafik:** Chart.js
* **PDF Engine:** Barryvdh DomPDF (`barryvdh/laravel-dompdf`)
* **Arsitektur:** Service-Repository Pattern, Form Request Validation, Middleware Role & Permission

---

## 2. Struktur Modul & Hak Akses (Role & Permission)

Aplikasi memiliki 5 peran pengguna (*roles*) dengan batasan akses bertingkat:

1. **Super Admin (`admin`)**: Memiliki akses penuh ke seluruh modul, kelola user staf, dan konfigurasi toko.
2. **Admin (`admin`)**: Mengelola operasional toko, produk, inventori, pembelian, dan laporan.
3. **Kasir (`kasir`)**: Akses terminal POS penjualan, cetak struk thermal, buka/tutup shift kasir, dan retur penjualan konsumen.
4. **Gudang (`gudang`)**: Akses persediaan barang, produk, stok opname fisik, data supplier, dan penerimaan pembelian (PO).
5. **Owner / Manager (`owner`)**: Akses pemantauan dashboard eksekutif, audit laporan penjualan, pembelian, dan laba rugi (*Profit & Loss*).

### Akun Bawaan Demo:
| Peran | Username | Password |
| :--- | :--- | :--- |
| **Super Admin** | `admin` | `password` |
| **Kasir Utama** | `kasir` | `password` |
| **Staf Gudang** | `gudang` | `password` |
| **Owner / Direktur** | `owner` | `password` |

---

## 3. Fitur Utama Aplikasi

### A. Terminal Kasir (POS Penjualan)
* Pencarian produk kilat dengan nama, SKU, atau **Scan Barcode realtime**.
* Keranjang interaktif dengan kalkulasi instan subtotal, diskon item, diskon nota, dan pajak PPN.
* Multi-metode pembayaran: **Tunai (Cash), QRIS, Transfer Bank, Kartu Debit, dan Kartu Kredit**.
* Tombol pecahan uang cepat (*Quick Cash*) dan kalkulasi otomatis uang kembalian.
* **Shortcut Keyboard**:
  * `F2`: Fokus ke kolom scan barcode / pencarian
  * `F4`: Buka modal pembayaran / checkout
  * `Enter`: Konfirmasi bayar
  * `ESC`: Tutup modal
* Format **Cetak Struk Thermal 58mm / 80mm** otomatis.
* Ekspor Invoice Penjualan ke format PDF.

### B. Manajemen Stok & Kartu Stok Terpusat
* Pencegahan stok negatif secara atomik dengan row lock (`lockForUpdate()`).
* **Kartu Stok:** Pencatatan otomatis histori mutasi barang (`initial`, `sale`, `purchase`, `sale_return`, `purchase_return`, `opname`).
* Filter stok menipis (*Low Stock Alert*) dan stok habis.
* **Stok Opname:** Input perhitungan stok fisik vs sistem dengan penyesuaian (*adjustment*) otomatis dan kalkulasi nilai finansial selisih.

### C. Pembelian Barang ke Supplier (PO)
* Input faktur pembelian barang masuk ke supplier.
* Penambahan stok otomatis dan pembaruan harga beli terakhir (HPP).
* Dukungan metode pembayaran tunai, transfer, maupun tempo/kredit (hutang supplier).

### D. Retur Penjualan & Pembelian
* Retur Penjualan Konsumen: verifikasi kuantitas transaksi nota, pengembalian stok ke inventori, dan pemotongan refund kasir.
* Retur Pembelian Supplier: pengembalian barang cacat/rusak ke supplier dan pemotongan stok otomatis.

### E. Manajemen Kas & Shift Kasir
* Buka shift kasir dengan input modal awal laci.
* Pencatatan otomatis kas masuk (penjualan tunai) dan kas keluar (refund retur).
* Fitur pencatatan kas kecil operasional (*Petty Cash*).
* **Closing Shift:** Rekonsiliasi saldo sistem yang seharusnya (*expected cash*) vs saldo uang fisik nyata di tangan (*actual cash*), serta audit selisih kas.

### F. Laporan Komprehensif & Analitik
* **Dashboard Eksekutif:** Grafik tren penjualan 7 hari terakhir (Chart.js), kartu KPI omset, laba, dan produk terlaris.
* **Laporan Penjualan:** Filter periode harian, mingguan, bulanan, tahunan, kustom, filter kasir, dan **Ekspor PDF**.
* **Laporan Pembelian:** Rekap belanja supplier dan monitoring hutang jatuh tempo.
* **Laporan Laba Rugi (P&L):** Pendapatan kotor, potongan diskon, HPP (COGS), laba kotor, beban operasional, dan laba bersih.
* **Laporan Valuasi Stok:** Perhitungan total aset modal stok tersimpan dan potensi omset.

### G. Pengaturan Sistem & Toko
* Nama toko, slogan, alamat, nomor telepon/WhatsApp, email.
* Persentase default pajak PPN.
* Format awalan nomor nota invoice (`INV`) dan PO (`PO`).
* Catatan footer struk kasir thermal.

---

## 4. Panduan Instalasi & Menjalankan Aplikasi

Ikuti langkah-langkah berikut untuk menjalankan aplikasi di komputer lokal (XAMPP / Laragon / Mac / Linux):

### 1. Masuk ke Direktori Project
```bash
cd "aplikasi-toko"
```

### 2. Install Dependensi PHP (Composer)
```bash
composer install
```

### 3. Konfigurasi Environment (`.env`)
Salin file konfigurasi contoh:
```bash
cp .env.example .env
```
Pastikan pengaturan database di file `.env` mengarah ke MySQL Anda:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kasir_db
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Generate App Key
```bash
php artisan key:generate
```

### 5. Jalankan Migration & Seeder
Perintah ini akan membuat 22 tabel relasional dan mengisi data master awal (roles, users, kategori, satuan, supplier, pelanggan, dan produk sample):
```bash
php artisan migrate:fresh --seed
```

### 6. Hubungkan Storage Link
```bash
php artisan storage:link
```

### 7. Jalankan Server Lokal
```bash
php artisan serve
```
Buka browser dan akses: **`http://127.0.0.1:8000`**

---

## 5. Menjalankan Pengujian Otomatis (Automated Testing)

Aplikasi telah dilengkapi unit & feature test komprehensif yang mencakup pengujian login, akses dashboard, transaksi kasir POS, audit stok atomik, shift closing, dan retur:
```bash
php artisan test
```

---

## 6. Prosedur Backup & Deployment Production

### Cara Backup Database MySQL:
Gunakan perintah `mysqldump`:
```bash
mysqldump -u root -p kasir_db > backup_kasir_$(date +%Y%m%d_%H%M%S).sql
```

### Deployment ke Server Production (Nginx / Apache):
1. Set `.env` ke `APP_ENV=production` dan `APP_DEBUG=false`.
2. Generate key dan optimize konfigurasi:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
3. Arahkan *Document Root* web server (Nginx/Apache) ke folder `public/`.
4. Berikan izin tulis (*permissions*) pada folder `storage/` dan `bootstrap/cache/`:
   ```bash
   chmod -R 775 storage bootstrap/cache
   ```
