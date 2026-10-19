# PosyMart

PosyMart adalah aplikasi **point of sale (POS) dan pemesanan meja untuk kafe**. Staf dapat mengelola penjualan, produk, stok, pelanggan, pesanan, retur, dan laporan dalam satu dasbor. Pelanggan dapat memindai barcode QR di meja, login atau mendaftar, lalu memesan langsung dari ponsel tanpa perlu mengantre di kasir.

Aplikasi ini dibangun dengan Laravel 13, Blade, Alpine.js, Tailwind CSS 4, dan Chart.js. Antarmuka menggunakan bahasa Indonesia, mata uang Rupiah, dan zona waktu Asia/Jakarta.

## Fitur utama

- **Kasir dan penjualan:** katalog produk, keranjang, diskon, pajak, pembayaran, serta riwayat transaksi.
- **Pemesanan pelanggan:** barcode QR per meja membuka portal seluler, lalu pelanggan login atau mendaftar sebelum memilih menu, checkout, dan memantau status pesanan.
- **Operasional staf:** antrean pesanan, pengelolaan 50 meja dan QR demo, notifikasi, pemantauan pembayaran, dan rekonsiliasi.
- **Inventaris dan pelanggan:** pengelolaan produk, penyesuaian stok, riwayat pergerakan stok, serta data pelanggan.
- **Retur dan refund:** pengajuan, persetujuan, dan pencatatan pengembalian dana.
- **Laporan:** ringkasan penjualan, tren pendapatan, metode pembayaran, produk terlaris, grafik interaktif, dan ekspor CSV.
- **Akses staf:** login, peran Admin/Manager/Cashier, dan API versi 1 dengan autentikasi Laravel Sanctum.

## Teknologi

| Bagian | Teknologi |
| --- | --- |
| Backend | PHP 8.3+, Laravel 13, Laravel Sanctum |
| Frontend | Blade, JavaScript, Alpine.js, Tailwind CSS 4, Chart.js |
| Database | SQLite secara default melalui `.env.example` |
| Build | Vite, npm |
| QR | `chillerlan/php-qrcode` dan `qrcode` |

## Menjalankan secara lokal

Pastikan PHP 8.3+, Composer, dan Node.js/npm tersedia. Jalankan perintah berikut dari direktori proyek:

```bash
composer install
npm ci
```

Salin `.env.example` menjadi `.env`, lalu siapkan aplikasi dan database SQLite:

```bash
php -r "file_exists('.env') || copy('.env.example', '.env');"
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate --seed
npm run build
php artisan serve
```

Buka `http://127.0.0.1:8000/login` untuk staf. Portal pelanggan tersedia di `http://127.0.0.1:8000/order`. Setelah database di-seed, contoh halaman QR meja adalah `http://127.0.0.1:8000/m/qr-meja-01`.

Untuk mengembangkan antarmuka dengan hot reload, jalankan `npm run dev` di terminal lain saat server Laravel aktif. Jika port 8000 sudah dipakai, pilih port lain dengan `php artisan serve --port=8001` dan sesuaikan URL.

### Akun demo

Seeder membuat akun berikut dengan kata sandi `posymart-demo`:

| Peran | Email |
| --- | --- |
| Admin | `john.doe@example.com` |
| Manager | `avery.morgan@example.com` |
| Cashier | `jordan.lee@example.com` |

Akun dan kata sandi tersebut hanya untuk lingkungan demo. Kode saat ini juga memuat jalur login dengan kata sandi demo, sehingga aplikasi perlu ditinjau dan diperkuat sebelum digunakan di produksi.

## Alur singkat

1. Staf login, membuka dasbor, lalu mengelola produk dan transaksi di halaman kasir.
2. Pelanggan memindai barcode QR yang terpasang di meja. Tautan QR membuka halaman pemesanan untuk meja tersebut; portal umum juga tersedia di `/order`.
3. Pelanggan login atau mendaftar, memilih menu, lalu mengirim pesanan dari ponsel tanpa mengantre di kasir.
4. Staf menerima dan memproses pesanan, serta memantau pembayaran dan notifikasi dari dasbor.
5. Admin atau Manager dapat meninjau laporan, retur, dan refund sesuai hak aksesnya.

## API dan struktur proyek

Endpoint JSON berada di `/api/v1`. Rute mencakup autentikasi, produk, pelanggan, penjualan, pesanan, retur, diskon, laporan, pengaturan, inventaris, pemesanan meja, dan webhook pembayaran. Lihat [`routes/api.php`](routes/api.php) untuk daftar endpoint dan [`PosyMart-Backend-PRD.md`](PosyMart-Backend-PRD.md) untuk spesifikasi backend.

```text
app/Http/Controllers/Api/v1/  Controller REST API
app/Models/                   Model Eloquent
app/Services/                 Perhitungan uang, audit, dan alur pembayaran
database/migrations/          Skema database
database/seeders/             Data demo staf, produk, transaksi, dan meja
resources/views/              Halaman Blade staf dan pelanggan
resources/js/                 Komponen Alpine.js dan state antarmuka
routes/web.php                Rute halaman web
routes/api.php                Rute API v1
tests/                        Pengujian aplikasi
```

## Pengujian dan batasan demo

Jalankan pengujian dengan `php artisan test`. Sebagian state antarmuka masih menggunakan `localStorage` dan disinkronkan dengan API pada modul tertentu. Alur QRIS di `PaymentProviderService` menggunakan provider **simulasi** (`qris_simulated`); proyek ini belum terhubung ke gateway pembayaran produksi.

Jangan commit `.env` atau file database lokal. Keduanya sudah dikecualikan melalui `.gitignore`.
