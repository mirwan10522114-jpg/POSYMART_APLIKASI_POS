# PosyMart — Aplikasi Point of Sale (Laravel 13)

PosyMart adalah aplikasi kasir (POS) yang dibangun dengan **Laravel 13, Blade, Tailwind CSS 4, Alpine.js, dan Chart.js**. Seluruh antarmuka berbahasa Indonesia dengan mata uang Rupiah dan zona waktu Asia/Jakarta.

## Fitur

- **10 halaman lengkap**: Dasbor, Penjualan, Produk, Pelanggan, Pesanan, Laporan, Retur, Diskon, Pengguna, Pengaturan
- **Dasbor POS**: katalog produk dengan foto nyata, filter kategori, pencarian, keranjang Penjualan Saat Ini, catatan, diskon per penjualan, dan simulasi pembayaran (tunai + kembalian, kartu, dompet digital)
- **Ledger demo konsisten**: 56 transaksi seed (6 hari ini + 6 arsip + 44 historis deterministik) yang menjadi dasar statistik Penjualan dan Laporan
- **Alur pesanan**: Draf → Menunggu → Disiapkan → Siap → Selesaikan & Bayar (membuat tepat satu transaksi penjualan)
- **Retur 3 langkah**: cari transaksi → pilih item dengan alokasi refund per unit (largest remainder) → tinjau → setujui/tolak
- **Diskon**: 7 promo seed, status efektif otomatis (Aktif/Terjadwal/Draf/Kedaluwarsa/Nonaktif/Habis), validasi kode unik
- **Laporan Chart.js**: tren penjualan (garis), rincian pembayaran (donat), pesanan selesai (batang), ringkasan harian + ekspor CSV + cetak
- **Pengguna & izin demo**: 3 peran (Admin/Manager/Cashier) dengan pratinjau izin, proteksi akun current & Admin terakhir
- **Pengaturan 7 tab**: Umum, Toko, Pembayaran, Pajak, Struk (dengan pratinjau langsung), Notifikasi, Tampilan + reset data demo
- **Responsif**: desktop (1440px) → tablet → ponsel (hingga 320px) tanpa overflow horizontal
- **Aksesibilitas**: semantic HTML, aria, focus trap pada drawer/dialog, live region, target sentuh ≥44px

## Data Demo

Seluruh data dikelola di sisi klien (Alpine.js + LocalStorage, key `posymart-demo-v1`) — tanpa database transaksi nyata, autentikasi server, atau payment gateway. Jam demo dibekukan pada **10 Okt 2026, 14.00 WIB**. Pajak 8% hanyalah konfigurasi contoh, bukan ketentuan pajak resmi.

Reset seluruh data demo: **Pengaturan → Tampilan → Reset semua data demo**.

## Menjalankan Aplikasi

```bash
# 1) Dependensi PHP
composer install

# 2) Dependensi frontend + build aset
npm install
npm run build

# 3) Konfigurasi
cp .env.example .env
php artisan key:generate

# 4) Jalankan server dev
php artisan serve
# buka http://localhost:8000 — otomatis dialihkan ke /dashboard
```

Untuk pengembangan frontend dengan hot-reload: `npm run dev` (Vite) bersamaan dengan `php artisan serve`.

## Struktur Utama

```
resources/
├── css/app.css              # Design tokens Tailwind 4 (@theme) + komponen
├── js/
│   ├── app.js               # Entry Alpine + shell + helper PM (focus trap, fallback gambar)
│   ├── data/seed.js         # Seed deterministik seluruh modul (Rupiah integer)
│   ├── store/store.js       # Store global + persistensi LocalStorage + semua aksi
│   ├── utils/               # money (alokasi pajak/diskon), date (id-ID), files (CSV/struk)
│   └── pages/               # Komponen Alpine per halaman (10 file)
└── views/
    ├── layouts/app.blade.php    # Shell: sidebar teal, header, drawer, toast, live region
    ├── layouts/partials/header  # Search ⌘K, notifikasi (badge 3), keranjang (badge 2)
    ├── components/              # icon (Lucide inline), page-header, stat-card, pagination
    └── pages/                   # 10 halaman + 404
```

## Catatan Konversi Lokalisasi

Fixture desain memakai USD; implementasi ini mengonversi **1 sen USD ≈ Rp100** (Cappuccino $3.50 → Rp35.000). Semua perhitungan memakai integer Rupiah dengan pembulatan half-up pada level Rupiah, sehingga identitas akuntansi tetap konsisten (contoh: Penjualan Neto + Pajak Neto = Neto Terkumpul).
