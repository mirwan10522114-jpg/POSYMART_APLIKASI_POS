# PRD Aplikasi Pemesanan QR Meja

Produk: **PosyMart Table Order**  
Versi: 1.0 · 11 Oktober 2026  
Dokumen pasangan: [Alur Bisnis](./ALUR-BISNIS.md)

## 1 Ringkasan dan keputusan produk

Pelanggan memindai QR di meja, memilih menu di ponselnya, membayar melalui penyedia pembayaran, lalu menunggu pesanan diproses. **Pembayaran harus terverifikasi otomatis oleh server sebelum pesanan masuk sebagai pesanan baru yang dapat diproses Admin/Kasir.**

Aplikasi mempunyai dua permukaan: website pelanggan tanpa akun dan dashboard staf yang memerlukan login. Keduanya merupakan bagian aplikasi QR meja ini. Aplikasi kasir pada paket pertama adalah produk terpisah; versi awal tidak otomatis berbagi transaksi atau stok dengannya.

| Aspek | Keputusan |
| --- | --- |
| Cara memesan | Pelanggan memindai QR meja melalui kamera ponsel dan membuka website |
| Identitas meja | Ditentukan server dari token QR, lalu ditampilkan untuk dikonfirmasi pelanggan |
| Akun pelanggan | Tidak diwajibkan; gunakan sesi tamu dan akses privat per pesanan |
| Nama pemesan | Opsional, nama panggilan saja; nomor meja dan nomor pesanan selalu tersedia |
| Pembayaran | Otomatis melalui penyedia pembayaran, sesuai pilihan pengguna |
| Waktu proses admin | Setelah pembayaran terverifikasi, bukan setelah klik tombol atau upload bukti |
| QR awal | Minimal 50 identitas QR untuk Meja 01–50; jumlah dapat ditambah |
| Notifikasi | Daftar notifikasi persisten + pembaruan live dashboard staf + indikator suara opsional |
| Mata uang dan zona waktu | Asumsi awal IDR dan Asia/Jakarta |
| Lingkup | Satu toko per instalasi, pelanggan mobile web, staf desktop/tablet |

“Barcode meja” pada kebutuhan pengguna diwujudkan sebagai **QR Code berisi tautan menu**. QR meja berbeda dari QRIS pembayaran: QR meja menetapkan konteks meja; QR pembayaran dibuat untuk tagihan dan nominal tertentu.

## 2 Tujuan dan batas lingkup

### Tujuan

1. Pelanggan dapat memesan dari mejanya tanpa mengantre di kasir dan tanpa registrasi POS.
2. Pesanan yang masuk antrean kerja staf sudah mempunyai pembayaran sah yang terhubung ke Order.
3. Admin langsung mengetahui meja, item, catatan, jumlah, dan waktu pembayaran.
4. Minimal 50 QR meja dapat dibuat, dikelola, dicetak, diuji, dan dinonaktifkan secara terpisah.
5. Pembayaran terlambat, request ganda, notifikasi terputus, atau refund tidak menyebabkan dana/pesanan hilang.

### Cakupan versi awal

Website menu mobile, keranjang, konfirmasi meja, nama opsional, checkout provider, status pembayaran, tracking pesanan sendiri, dashboard admin/kasir, antrean layanan, pembayaran/refund, katalog, stok unit jual, promo umum, laporan, Users, Settings, dan pengelolaan QR meja.

Metode pembayaran awal yang ditargetkan adalah QRIS dinamis melalui provider. Metode tambahan dapat diaktifkan setelah alurnya diuji. Provider belum dipilih dalam percakapan; desain memakai adapter pembayaran agar kontrak bisnis tidak bergantung pada satu merek. Contoh pemetaan Midtrans pada bagian integrasi adalah referensi teknis, bukan klaim akun merchant sudah terhubung.

### Di luar versi awal

Membership/loyalty, pelanggan wajib login, pembayaran sesudah makanan disiapkan, konfirmasi lunas manual oleh admin, split bill, patungan satu tagihan, penggabungan pembayaran beberapa ponsel, pemesanan delivery, multi-cabang, resep bahan baku, dan sinkronisasi otomatis dengan aplikasi kasir terpisah.

Pelanggan masih dapat membuka aplikasi bank/e-wallet miliknya untuk membayar. Autentikasi di aplikasi pembayaran tersebut berbeda dari kewajiban membuat akun pada POS.

## 3 Aktor dan izin

| Aktor | Hak utama |
| --- | --- |
| Pelanggan tamu | Melihat menu, membuat keranjang sendiri, membayar, dan melihat status/struk pesanannya sendiri |
| Kasir | Melihat pesanan Paid dan notifikasi, menerima, menyiapkan, menandai Ready/Served, mencari pesanan, meminta refund |
| Admin | Seluruh hak Kasir, mengelola QR/meja, katalog, promo, staf, Settings, laporan, pengecualian pembayaran, dan refund |
| Penyedia pembayaran | Membuat sesi pembayaran, menyediakan status transaksi, dan mengirim pemberitahuan server |
| Worker backend | Rekonsiliasi status, pengiriman event notifikasi, expiry reservation, dan pemrosesan tugas yang dapat diulang |

Pelanggan tidak dapat membaca dashboard staf, mengubah harga, menetapkan nomor meja sembarang, menandai lunas, atau melihat pesanan tamu lain pada meja yang sama. Admin tidak mempunyai tombol `Force Paid` yang melewati verifikasi provider.

## 4 Halaman pelanggan

| Halaman | Isi dan aksi wajib |
| --- | --- |
| QR Landing / Konfirmasi Meja | Nama kafe, Meja 17, status toko, tombol Lihat Menu; konfirmasi konteks meja |
| Menu | Foto, nama, harga, kategori, pencarian, label habis, detail produk |
| Product Detail | Deskripsi, opsi yang tersedia, jumlah, catatan, harga, Tambah ke Keranjang |
| Cart | Item/opsi, quantity, edit/hapus, catatan, ringkasan nominal, meja yang jelas |
| Checkout | Nama opsional, konfirmasi meja, breakdown harga, promo umum, metode, Pesan & Bayar |
| Payment | Hosted checkout/deep link atau instruksi provider, nominal, expiry resmi, tindakan kembali/periksa status |
| Payment Status | Menunggu, sedang diverifikasi, berhasil, gagal, kedaluwarsa, atau perlu pemeriksaan |
| Order Tracking | Nomor pesanan, meja, status Paid/New/Preparing/Ready/Served, rincian item, bantuan staf |
| Receipt | Snapshot pembayaran dan nominal; refund status bila ada |
| Error/Unavailable | QR invalid/nonaktif, toko tutup, item habis, jaringan gagal, sesi pesanan tidak tersedia |

UI pelanggan memakai bahasa Indonesia. Checkout tidak meminta nomor telepon/email untuk membuat profil. Pilih kanal provider yang mendukung alur tamu sesuai kebutuhan; persyaratan autentikasi aplikasi pembayaran tidak diubah menjadi akun pelanggan POS.

## 5 Halaman dan sidebar staf

| Menu | Isi dan fitur |
| --- | --- |
| Dashboard | Pesanan New, Preparing, Ready, pembayaran terverifikasi, pengecualian yang perlu ditangani |
| Orders | Board/list pesanan Paid, filter meja/tanggal/status, detail, Accept, Start Preparing, Mark Ready, Mark Served |
| Payments | Order/provider reference, nominal, status, waktu, rekonsiliasi, detail event; Pending terlihat di sini untuk pemantauan, bukan antrean produksi |
| Tables & QR | Minimal 50 record, pencarian meja/QR ID, filter status, preview, generate batch, print/download, disable, rotate token |
| Products | Menu, kategori, opsi, harga, foto, ketersediaan, stok, bulk active/inactive |
| Discounts | Promo umum, periode, cakupan produk/kategori, batas penggunaan |
| Refunds | Request, persetujuan, status provider/pengembalian, retry dan alasan penolakan |
| Reports | Pembayaran, refund, produk terjual, meja, metode, waktu layanan, ekspor |
| Notifications | Paid order baru, pesanan belum ditangani, payment review, refund gagal, koneksi; read/unread |
| Users | Admin/Kasir, status, izin dan audit |
| Settings | Toko, jam buka, pembayaran, pajak/biaya, QR branding, suara, waktu eskalasi, receipt, maintenance |

Menu Customers tidak termasuk versi awal. Identitas meja dan nama opsional sudah cukup untuk pelayanan. Pengelolaan koneksi provider menampilkan status/mode dan konfigurasi aman; secret tidak ditampilkan pada browser pelanggan atau QR.

## 6 Pengelolaan minimal 50 QR meja

### 6.1 Identitas dan lifecycle

- Seed minimum mencakup **50 record berbeda**: `T001` sampai `T050`, label `Meja 01` sampai `Meja 50`, dan `QR-T001` sampai `QR-T050`.
- Satu QR aktif mengarah ke satu meja pada satu toko. Setiap meja mempunyai token publik acak yang unik; QR tidak berisi kredensial staf atau token akses Order.
- Pola URL: `https://<domain-pemesanan>/m/<public-qr-token>`. Nomor meja dibaca dari database server, bukan dipercaya dari parameter bebas.
- Status QR: `Draft`, `Active`, `Inactive`, `Revoked`. Draft belum terbit; Active menerima pesanan baru; Inactive dapat diaktifkan lagi; Revoked sudah diganti dan tidak dipakai ulang.
- Menambah meja ke-51 dan seterusnya harus didukung; 50 merupakan minimum kapasitas/seed, bukan batas maksimum.
- Untuk toko dengan titik yang belum dipasang, record dapat tetap Draft/Inactive. Daftar 50 tidak memaksa toko memiliki 50 meja fisik yang sedang digunakan.
- Rotasi membuat token baru, mencabut token lama, dan meminta cetak ulang. Jangan mengalihkan token lama ke meja lain secara diam-diam.
- Menonaktifkan QR menghentikan pembukaan checkout baru. Tracking Order yang sudah dimiliki pelanggan dan pembayaran yang sedang berjalan tetap direkonsiliasi; tidak boleh hilang.

QR meja adalah identitas titik layanan yang dapat dipindai publik, bukan bukti bahwa perangkat benar-benar berada di lokasi. Nomor meja terlihat jelas agar pelanggan dan staf dapat mengoreksi salah meja sebelum transaksi selesai.

### 6.2 Daftar dan aksi

Kolom minimum: QR ID, kode meja, nama meja, area opsional, status meja, status QR, versi token, updated time, scan count agregat bila diaktifkan, last printed, dan Actions.

Aksi Admin: Add Table, Edit Label, Generate QR, Preview, Download PNG/SVG, Print Card, Bulk Generate, Bulk Download, Bulk Print, Disable, Enable, Rotate QR. Semua mutasi mempunyai validasi, hasil nyata, dan audit. Kasir boleh melihat nomor meja; perubahan token hanya Admin.

Status “meja sedang terisi” tidak disimpulkan hanya dari scan atau adanya pembayaran. Bila occupancy manual ditambahkan, pisahkan dari status QR dan status Order.

### 6.3 Kartu cetak dan pengujian

Kartu memuat nama kafe, nomor meja besar, QR, instruksi “Scan untuk pesan dan bayar”, serta domain pendek yang dapat dikenali. QR hitam pada latar putih dengan kontras baik; branding diletakkan di luar area kode. Sisakan quiet zone minimal empat modul pada semua sisi sesuai [panduan DENSO WAVE](https://www.qrcode.com/en/howto/code.html).

Target desain awal kartu adalah 90 × 130 mm dengan area QR sekitar 40 mm; ukuran dapat disesuaikan setelah uji cetak nyata. Export mendukung satu kartu, batch PDF A4 untuk cetak, dan ZIP PNG/SVG. Berkas tidak boleh memuat token staf atau identitas pesanan.

QA memindai **seluruh 50 QR**, bukan sampel saja, dan mencocokkan QR-T001 → Meja 01 sampai QR-T050 → Meja 50. Uji ponsel Android/iOS, cahaya kafe, cetak aktual, QR nonaktif, QR lama setelah rotasi, dan domain HTTPS yang benar.

### 6.4 Daftar awal 50 QR

Tabel berikut adalah daftar identitas yang wajib dibuat oleh implementasi. `Generate saat setup` berarti token dan gambar QR belum menjadi tautan produksi dalam dokumen ini. Aktivasi dilakukan setelah domain, mapping, dan hasil scan diperiksa.

| No | Kode meja | Label | QR ID | Token dan aset | Status awal |
| --- | --- | --- | --- | --- | --- |
| 1 | T001 | Meja 01 | QR-T001 | Generate saat setup | Draft |
| 2 | T002 | Meja 02 | QR-T002 | Generate saat setup | Draft |
| 3 | T003 | Meja 03 | QR-T003 | Generate saat setup | Draft |
| 4 | T004 | Meja 04 | QR-T004 | Generate saat setup | Draft |
| 5 | T005 | Meja 05 | QR-T005 | Generate saat setup | Draft |
| 6 | T006 | Meja 06 | QR-T006 | Generate saat setup | Draft |
| 7 | T007 | Meja 07 | QR-T007 | Generate saat setup | Draft |
| 8 | T008 | Meja 08 | QR-T008 | Generate saat setup | Draft |
| 9 | T009 | Meja 09 | QR-T009 | Generate saat setup | Draft |
| 10 | T010 | Meja 10 | QR-T010 | Generate saat setup | Draft |
| 11 | T011 | Meja 11 | QR-T011 | Generate saat setup | Draft |
| 12 | T012 | Meja 12 | QR-T012 | Generate saat setup | Draft |
| 13 | T013 | Meja 13 | QR-T013 | Generate saat setup | Draft |
| 14 | T014 | Meja 14 | QR-T014 | Generate saat setup | Draft |
| 15 | T015 | Meja 15 | QR-T015 | Generate saat setup | Draft |
| 16 | T016 | Meja 16 | QR-T016 | Generate saat setup | Draft |
| 17 | T017 | Meja 17 | QR-T017 | Generate saat setup | Draft |
| 18 | T018 | Meja 18 | QR-T018 | Generate saat setup | Draft |
| 19 | T019 | Meja 19 | QR-T019 | Generate saat setup | Draft |
| 20 | T020 | Meja 20 | QR-T020 | Generate saat setup | Draft |
| 21 | T021 | Meja 21 | QR-T021 | Generate saat setup | Draft |
| 22 | T022 | Meja 22 | QR-T022 | Generate saat setup | Draft |
| 23 | T023 | Meja 23 | QR-T023 | Generate saat setup | Draft |
| 24 | T024 | Meja 24 | QR-T024 | Generate saat setup | Draft |
| 25 | T025 | Meja 25 | QR-T025 | Generate saat setup | Draft |
| 26 | T026 | Meja 26 | QR-T026 | Generate saat setup | Draft |
| 27 | T027 | Meja 27 | QR-T027 | Generate saat setup | Draft |
| 28 | T028 | Meja 28 | QR-T028 | Generate saat setup | Draft |
| 29 | T029 | Meja 29 | QR-T029 | Generate saat setup | Draft |
| 30 | T030 | Meja 30 | QR-T030 | Generate saat setup | Draft |
| 31 | T031 | Meja 31 | QR-T031 | Generate saat setup | Draft |
| 32 | T032 | Meja 32 | QR-T032 | Generate saat setup | Draft |
| 33 | T033 | Meja 33 | QR-T033 | Generate saat setup | Draft |
| 34 | T034 | Meja 34 | QR-T034 | Generate saat setup | Draft |
| 35 | T035 | Meja 35 | QR-T035 | Generate saat setup | Draft |
| 36 | T036 | Meja 36 | QR-T036 | Generate saat setup | Draft |
| 37 | T037 | Meja 37 | QR-T037 | Generate saat setup | Draft |
| 38 | T038 | Meja 38 | QR-T038 | Generate saat setup | Draft |
| 39 | T039 | Meja 39 | QR-T039 | Generate saat setup | Draft |
| 40 | T040 | Meja 40 | QR-T040 | Generate saat setup | Draft |
| 41 | T041 | Meja 41 | QR-T041 | Generate saat setup | Draft |
| 42 | T042 | Meja 42 | QR-T042 | Generate saat setup | Draft |
| 43 | T043 | Meja 43 | QR-T043 | Generate saat setup | Draft |
| 44 | T044 | Meja 44 | QR-T044 | Generate saat setup | Draft |
| 45 | T045 | Meja 45 | QR-T045 | Generate saat setup | Draft |
| 46 | T046 | Meja 46 | QR-T046 | Generate saat setup | Draft |
| 47 | T047 | Meja 47 | QR-T047 | Generate saat setup | Draft |
| 48 | T048 | Meja 48 | QR-T048 | Generate saat setup | Draft |
| 49 | T049 | Meja 49 | QR-T049 | Generate saat setup | Draft |
| 50 | T050 | Meja 50 | QR-T050 | Generate saat setup | Draft |

## 7 Alur pemesanan dan perhitungan harga

1. Scan QR menetapkan konteks toko/meja dan membuat sesi tamu terbatas.
2. Pelanggan menambahkan item ke keranjang. Keranjang belum menambah antrean admin dan belum mengurangi stok fisik.
3. Checkout menampilkan meja, nama opsional, item/opsi, catatan, diskon, pajak/biaya, dan total.
4. Server memvalidasi toko/meja aktif, jam operasional, ketersediaan, harga, promo, dan batas jumlah. Harga dari client bukan sumber kebenaran.
5. Jika harga atau ketersediaan berubah, pelanggan harus meninjau rincian baru sebelum sesi pembayaran dibuat.
6. Server membekukan snapshot Order dan membuat satu payment attempt aktif serta reservasi stok bila digunakan.
7. Provider membuat sesi/tagihan untuk order reference, currency, dan nominal yang tepat.
8. Pelanggan membayar. Server melakukan verifikasi dan pencatatan pembayaran; sesudah hasil sah, Order dapat masuk antrean New atau jalur ReviewRequired bila ada pengecualian.

Nominal awal menggunakan integer rupiah (`amount_minor`, currency IDR, skala 0); tarif persentase memakai basis points. Default fixture pajak/biaya 0 sampai dikonfigurasi. Snapshot nominal diambil saat pelanggan menyetujui checkout. Satu promo umum per Order; tanpa syarat member.

Keranjang yang sudah menghasilkan tagihan tidak diedit sambil memakai tagihan lama. Perubahan membuat checkout baru setelah attempt lama dibatalkan/terminal secara aman; status yang belum pasti harus direkonsiliasi terlebih dahulu. Memesan tambahan sesudah Paid menghasilkan Order dan pembayaran baru, bukan mengubah tagihan lama.

## 8 Pembayaran otomatis dan sumber kebenaran

### 8.1 Kontrak provider

Adapter backend minimum menyediakan `CreatePayment`, `GetPaymentStatus`, `VerifyNotification`, `CancelOrExpirePayment` bila didukung, `RequestRefund`, dan `GetRefundStatus`. Semua reference provider disimpan dan unik. Domain callback/return/webhook, mode sandbox/production, metode, dan merchant credential disiapkan sebelum go-live.

Keberhasilan halaman redirect, screenshot bukti, tombol “Saya sudah bayar”, atau callback JavaScript tidak dapat menetapkan Paid. Tindakan pelanggan untuk memeriksa pembayaran hanya meminta backend membaca status provider.

Sebagai contoh integrasi Midtrans, notifikasi diverifikasi melalui mekanisme autentikasi resmi dan/atau API status server; data dari frontend tidak menjadi bukti akhir. Dokumentasi juga membahas notifikasi berulang, urutan terbalik, dan rekonsiliasi status saat notifikasi terlambat. Kontrak adapter harus menangani kondisi tersebut. [Dokumentasi webhook Midtrans](https://docs.midtrans.com/docs/https-notification-webhooks).

### 8.2 Pemrosesan pembayaran sah

Backend mencocokkan identitas merchant, order reference, provider transaction ID, nominal, currency, serta status keberhasilan yang sesuai dengan metode. Event autentik tetapi nominal/order berbeda masuk jalur investigasi; jangan dimasukkan ke antrean kerja.

Dalam transaksi database yang terkunci secara konsisten: simpan event yang belum pernah diproses → perbarui payment → buat/tautkan tepat satu Sale → konversi reservasi menjadi pengurangan stok bila valid → tetapkan fulfillment New atau ReviewRequired → simpan audit dan event outbox. Worker mengirim event dashboard dari outbox yang sudah commit.

Pengiriman provider dan worker dapat berulang. Efek bisnis harus sekali: satu pembayaran yang diakui per receipt provider, satu Sale per Order, satu pengurangan stok, dan satu event logis `OrderPaid` yang layak diproses.

### 8.3 Status pembayaran

| Status aplikasi | Arti | Boleh masuk antrean produksi |
| --- | --- | --- |
| Unpaid | Draft/checkout belum memiliki pembayaran | Tidak |
| Pending | Tagihan aktif menunggu pembayaran | Tidak |
| Verifying | Status provider belum pasti atau perlu rekonsiliasi | Tidak |
| Paid | Penerimaan pembayaran diverifikasi server | Ya, jika fulfillment eligible dan tidak ReviewRequired |
| Failed | Provider menyatakan kegagalan final untuk attempt | Tidak |
| Expired | Provider mengonfirmasi tagihan kedaluwarsa | Tidak |
| Cancelled | Tagihan dibatalkan tanpa pembayaran diterima | Tidak |
| PartiallyRefunded | Sebagian pembayaran dikembalikan | Tidak membuat antrean baru; kelanjutan layanan ditentukan admin |
| Refunded | Seluruh nominal yang berlaku dikembalikan | Tidak membuat antrean baru |

Pemetaan contoh Midtrans: `pending` belum lunas; QRIS `settlement` berarti sukses; `expire`, `cancel`, `deny`, dan `failure` bukan keberhasilan. Bila metode kartu diaktifkan, tangani `capture` dan kondisi fraud sesuai kontrak metode. Jangan membuat pemetaan universal tanpa memperhatikan metode. [Siklus status Midtrans](https://docs.midtrans.com/docs/transaction-status-cycle).

Refund mempunyai record dan lifecycle sendiri; nominal penerimaan awal tidak ditulis ulang menjadi nol. Reversal/dispute setelah Paid dicatat sebagai peristiwa keuangan terpisah, bukan diabaikan karena transaksi pernah sukses.

### 8.4 Payment attempt dan retry

- Satu Order hanya mempunyai satu attempt aktif yang dapat ditagih pada satu waktu.
- CreatePayment timeout menghasilkan Verifying/Unknown pada attempt; query provider dengan reference yang sama sebelum membuat attempt pengganti.
- Setelah kegagalan/expiry/cancel dipastikan, pelanggan boleh membuat attempt baru dengan reference baru untuk snapshot yang tetap valid atau membuat checkout baru.
- Retry client memakai idempotency key yang sama untuk operasi yang sama. Backend juga mengunci Order agar key baru tidak menggandakan sesi/tagihan secara tidak sengaja.
- Jika dua attempt ternyata dibayar, semua penerimaan tetap dicatat. Order hanya menghasilkan satu Sale/antrean; kelebihan pembayaran masuk kasus refund/review, tidak dihilangkan dari ledger.
- Event Pending yang tiba sesudah Paid tidak menurunkan status. Perubahan seperti refund/dispute tetap diproses melalui transisi yang sah.

### 8.5 Pembayaran di satu ponsel

Pelanggan yang membuka QR pembayaran pada ponselnya harus memiliki jalur yang dapat digunakan: deep link aplikasi pembayaran jika didukung provider, unduh/simpan QR lalu gunakan fitur pembayaran yang tersedia, atau pilih metode alternatif yang sudah diuji. Instruksi mengikuti kemampuan kanal sebenarnya. Uji alur satu ponsel; jangan menganggap pelanggan selalu mempunyai kamera/perangkat kedua.

## 9 Stok reservasi dan pembayaran terlambat

Jika tracking aktif, checkout mereservasi unit secara atomik. `available = on_hand - reserved`; dua pelanggan tidak boleh mereservasi unit terakhir yang sama. Snapshot barang/nominal dikunci untuk periode tagihan.

Target awal durasi checkout 15 menit bila didukung metode provider. `expires_at` efektif yang dikembalikan provider menjadi sumber timer, dan TTL reservasi diselaraskan dengannya. Jangan memakai countdown UI untuk menyatakan dana belum diterima.

Sebelum melepaskan reservasi karena expiry/cancel, backend memeriksa status provider. Jika status belum pasti, masuk Verifying dan jalur retry/review operasional; jangan menandai pembayaran gagal secara sepihak. Retry diberi batas dan alert agar reservasi tidak tersembunyi tanpa penanganan.

Jika pembayaran terkonfirmasi setelah reservasi terlanjur dilepas atau toko tidak dapat memenuhi pesanan: catat dana sebagai Paid, fulfillment `ReviewRequired`, dan kirim notifikasi pengecualian kepada Admin. Admin memeriksa ketersediaan; bila masih bisa dipenuhi, lakukan alokasi stok baru secara atomik sebelum melepas ke antrean. Jika tidak, batalkan layanan dan proses refund. Jangan mengabaikan dana atau menyiapkan barang tanpa stok.

Saat Paid normal, kurangi on_hand dan reserved sekali dalam transaksi yang sama. Saat terminal belum bayar, lepas reserved saja. Refund minuman yang telah dibuat tidak otomatis restock; Admin menentukan berdasarkan kondisi fisik dan mencatat alasannya.

## 10 Antrean admin dan status persiapan

| Status layanan | Makna dan transisi |
| --- | --- |
| NotQueued | Draft/Pending/Verifying; tidak tampil sebagai pesanan baru siap dikerjakan |
| New | Paid sudah valid dan siap diterima staf |
| Accepted | Seorang staf menerima tanggung jawab; actor dan waktu dicatat |
| Preparing | Staf mulai membuat pesanan |
| Ready | Pesanan selesai disiapkan dan siap diantar |
| Served | Pesanan sudah diserahkan ke meja/pelanggan |
| ReviewRequired | Dana/pesanan memerlukan keputusan Admin sebelum dapat diproses, misalnya late payment tanpa reservasi |
| Cancelled | Layanan dibatalkan; status refund tetap dilacak secara terpisah |

Alur normal: `NotQueued → New → Accepted → Preparing → Ready → Served`. Transisi New hanya berasal dari hasil backend yang sah. Aksi staf tidak dapat memaksa order Pending menjadi New/Preparing. Admin dapat mengeluarkan ReviewRequired ke New hanya setelah masalah terselesaikan dan pembayaran/stock terbukti memenuhi syarat.

Setiap perubahan status memakai version dan actor. Dua staf yang menekan Accept bersamaan tidak membuat dua pekerjaan; staf kedua melihat status/pemilik terbaru. Mark Served tidak membuat Sale, pembayaran, atau pengurangan stok tambahan.

Order Paid yang ditolak Admin menjadi Cancelled dengan alasan dan kewajiban refund. Cancelled tidak otomatis berarti Refunded. Pelanggan melihat status layanan dan status pengembalian secara terpisah. Partial refund karena satu item tidak dapat dibuat mempertahankan snapshot transaksi awal dan mencatat item layanan yang dibatalkan; sisa item dapat dilanjutkan setelah peninjauan Admin.

## 11 Notifikasi dan pembaruan status

### Peristiwa pesanan baru

Setelah pembayaran sah dan Order eligible masuk New, backend menyimpan event logis `OrderPaid` bersama perubahan data. Notifikasi berisi nomor pesanan, **Meja 17**, nama opsional, daftar item/quantity, catatan penting, total, badge Paid, waktu masuk, dan tombol `View Order`/`Accept`.

Pesanan Pending tidak menghasilkan bunyi atau notifikasi “pesanan baru siap diproses”. Admin tetap dapat memantau payment pending di Payments. Kasus late payment/amount mismatch mengirim notifikasi pengecualian yang berbeda dan tidak menyuruh staf mulai membuat minuman.

### Pengiriman dan pemulihan

- Gunakan stream SSE atau WebSocket untuk dashboard staf; keputusan implementasi awal SSE dengan fallback polling 10 detik.
- Simpan notifikasi di database dan dukung cursor/replay setelah reconnect. Websocket/SSE bukan satu-satunya tempat menyimpan event.
- Worker outbox dapat retry. UI dan backend melakukan dedup berdasarkan event ID/Order agar tidak membuat kartu antrean duplikat.
- `Read` berarti sudah dilihat; `Accepted` berarti pesanan telah diambil untuk dikerjakan. Keduanya tidak saling menggantikan.
- Jika New belum diterima selama target 60 detik, tandai warna/waktu tunggu dan eskalasi ke Admin. Nilai dapat diatur toko; bukan timeout pembatalan otomatis.
- Dashboard mempunyai `Enable Sound` melalui tindakan pengguna. Jika suara diblokir browser, badge, daftar, dan banner visual tetap berfungsi.
- Tampilkan indikator koneksi terputus serta waktu sinkronisasi terakhir. Setelah tersambung, ambil seluruh New/Accepted/Preparing/Ready yang belum selesai.
- Target internal: notifikasi tersedia p95 ≤5 detik setelah commit Paid yang eligible. Waktu konfirmasi eksternal provider dihitung terpisah.
- Saat dashboard ditutup, pembayaran dan notifikasi tetap disimpan. Suara/push ketika browser tertutup bukan jaminan versi awal; staff station harus aktif saat toko menerima order.

Pelanggan melihat status dari server untuk pesanan miliknya. Refresh halaman atau kembali dari provider tidak membuat Order baru.

## 12 Sesi pelanggan dan keamanan akses

QR publik hanya memberikan konteks menu/meja. Sistem membuat sesi tamu dan kemampuan akses khusus untuk Order yang dibuatnya. Token akses order berbeda dari token QR meja, tidak dapat ditebak dari nomor pesanan, dan tidak dicetak pada kartu QR meja.

Dua ponsel di Meja 17 memiliki keranjang/pembayaran masing-masing. Mereka tidak otomatis dapat melihat pesanan satu sama lain. Shared cart dan split bill belum termasuk versi awal. Satu meja boleh memiliki beberapa Order aktif dengan nomor berbeda.

Target awal sesi keranjang adalah 2 jam sejak aktivitas terakhir; akses tracking Order sendiri setidaknya 24 jam setelah dibuat atau sampai proses pengecualian terselesaikan melalui kebijakan akses yang diuji. Masa akses tersebut tidak menghapus ledger. Jika sesi hilang, staf membantu menggunakan nomor transaksi dan pemeriksaan yang sesuai tanpa membuka akses publik ke semua pesanan meja.

Endpoint guest memverifikasi sesi, kepemilikan Order, table context, dan batas request/quantity. Input nama/catatan diperlakukan sebagai teks. Secret provider hanya berada di backend. Login Admin/Kasir, webhook provider, dan sesi tamu menggunakan mekanisme autentikasi yang berbeda sesuai tujuannya.

Mengganti parameter meja pada URL/request tidak boleh mengubah context Order yang sudah disetujui. Salah meja diperbaiki sebelum bayar dengan scan QR yang tepat; setelah Paid, staf menangani pemindahan titik pengantaran melalui aksi audit tanpa mengubah nominal atau snapshot asal.

## 13 Pembatalan dan refund

### Belum dibayar

Pelanggan dapat meninggalkan Cart. Jika tagihan sudah dibuat, cancel harus diperiksa ke provider; uang yang ternyata sudah dibayar masuk alur Paid/refund, bukan hilang karena layar pelanggan ditutup. Tagihan terminal tanpa pembayaran melepas reservasi dan tidak membuat Sale.

### Sudah dibayar

Pembatalan memerlukan keputusan Admin dan alasan. Sistem menyimpan kewajiban refund, mengirim permintaan refund jika kanal mendukung, dan mengikuti status aktual. Refund mempunyai status `Requested`, `Approved`, `Processing`, `Succeeded`, `Failed`, atau `Rejected`.

Admin tidak boleh menandai refund berhasil hanya karena request telah dikirim. Jika provider mengembalikan hasil belum pasti, lakukan rekonsiliasi menggunakan reference refund yang sama. Retry tidak boleh mengirim uang kembali dua kali.

Pemilihan provider/metode sebelum go-live harus menyertakan alur refund yang diuji. Bila kanal tidak menyediakan API refund, penyelesaian dilakukan melalui prosedur merchant yang disepakati dan dicatat dengan bukti hasil oleh Admin. Ini adalah penanganan refund, bukan izin untuk memverifikasi pembayaran masuk secara manual.

Nominal refund berdasarkan snapshot dan unit/item eligible. Total pengembalian per receipt pembayaran tidak melebihi penerimaan terkait; kelebihan pembayaran dari attempt ganda memiliki kasus/refund tersendiri. Order, provider receipt, audit, dan hubungan refund tetap tersimpan.

## 14 Model data dan invariant

| Entitas | Data penting dan aturan |
| --- | --- |
| StaffUser | Admin/Cashier, identitas staf, status, sesi |
| Table | ID internal, table_code unik dalam toko, label, status penerimaan order, area opsional |
| TableQR | QR ID, table_id, token hash/identifier, versi, status, created/rotated/printed time; satu token aktif per meja |
| GuestSession | Konteks toko/meja, expiry, scope akses; bukan akun Customer |
| Product/Variant/Category | Nama, harga, opsi, foto, status, on_hand/reserved bila tracking aktif |
| Cart | Sesi pemilik, item, version; harga final divalidasi server |
| Order/OrderItem | Kode, meja snapshot, nama opsional snapshot, item/opsi, nilai uang, currency, fulfillment status, version |
| PaymentAttempt | Order, provider order reference unik, attempt number, status, expires_at, nominal/currency, last checked |
| PaymentReceipt | Provider transaction ID unik, actual amount/currency, verified_at, penerimaan; semua receipt sah tetap tercatat |
| Sale | Satu sumber Order, snapshot nilai dan paid_at; bukan dibuat lagi saat Served |
| StockReservation | Order/item/variant, qty, effective expiry, state Held/Consumed/Released |
| InventoryMovement | Pengurangan/restock, source operation unik, qty, reason, actor |
| Refund/RefundItem | PaymentReceipt, item/unit, nominal, provider reference, status, reason |
| Notification/OutboxEvent | Event ID unik, Order, payload aman, delivery retry, read state |
| Settings/AuditLog/IdempotencyOperation | Konfigurasi versioned, riwayat perubahan, dedup request |

Invariant wajib:

1. Tidak ada antrean produksi untuk payment Pending/Verifying/Failed/Expired/Cancelled.
2. Fulfillment New hanya dibuat setelah verifikasi Paid dan validasi readiness Order.
3. Satu Order mempunyai maksimum satu Sale; beberapa attempt tidak berarti beberapa penjualan.
4. Satu provider transaction ID tidak dapat memenuhi dua Order.
5. Callback berulang tidak menambah stok movement, Sale, atau notifikasi logis baru.
6. `on_hand >= reserved >= 0` selama tracking; reservasi hanya dikonversi atau dilepas sekali.
7. Harga, currency, dan total berasal dari snapshot server; request tamu tidak menetapkan amount yang dipercaya.
8. Customer ID, member, nomor telepon, dan email tidak menjadi foreign key wajib untuk Order.
9. Refund tidak mengubah nominal receipt pembayaran awal atau menghapus Order.
10. Token QR tidak memberi akses ke data pesanan tamu lain atau API staf.

## 15 API minimum dan batas akses

Base path contoh `/api/v1`. Frontend pelanggan dan dashboard staf dapat berada pada host/subdomain terpisah, tetapi API tetap memverifikasi izin pada setiap request.

| Akses | Method/path | Tanggung jawab |
| --- | --- | --- |
| Publik terbatas | GET `/qr/{publicToken}` | Resolve konteks meja aktif, bukan histori pesanan |
| Tamu | POST `/guest-sessions` | Membuat sesi dari QR yang valid |
| Tamu | GET `/menu`; GET `/menu/products/{id}` | Menu yang boleh dijual untuk konteks tersebut |
| Tamu pemilik | POST `/carts`; PATCH `/carts/{id}` | Keranjang dan optimistic version |
| Tamu pemilik | POST `/checkouts` | Validasi harga, reservasi, Order snapshot, dan payment attempt |
| Tamu pemilik | GET `/orders/{id}` | Status/rincian hanya milik sesi |
| Tamu pemilik | POST `/orders/{id}/payment-status-check` | Meminta backend rekonsiliasi; rate limited; tidak mengirim flag Paid |
| Tamu pemilik | POST `/orders/{id}/payment-attempts` | Retry setelah attempt lama terminal/terekonsiliasi |
| Tamu pemilik | GET `/orders/{id}/receipt` | Struk pesanan sendiri |
| Provider terverifikasi | POST `/webhooks/payments/{provider}` | Menerima event, verifikasi, simpan secara idempotent |
| Staf | GET `/admin/orders`; GET `/admin/orders/{id}` | Daftar/detail sesuai akses |
| Staf | POST `/admin/orders/{id}/transitions` | Accept/prepare/ready/serve dengan state/role/version |
| Staf | GET `/admin/events`; GET `/admin/notifications` | Stream/replay dan daftar notifikasi |
| Staf | POST `/admin/notifications/{id}/read` | Read tanpa mengambil pekerjaan |
| Admin | GET/POST/PATCH `/admin/tables...` | Pengelolaan meja |
| Admin | POST `/admin/table-qrs/batch`; POST `/admin/table-qrs/exports` | Generate ≥50, print/download batch |
| Admin | POST `/admin/table-qrs/{id}/rotate`; POST `/admin/table-qrs/{id}/disable` | Siklus token dan audit |
| Admin | GET `/admin/payments`; POST `/admin/payments/{id}/reconcile` | Pemeriksaan provider, tanpa Force Paid |
| Staf/Admin | POST `/admin/orders/{id}/refund-requests`; POST `/admin/refunds/{id}/approve` | Permintaan oleh staf, persetujuan Admin |
| Admin | GET/POST/PATCH `/admin/products...`, `/admin/discounts...`, `/admin/users...` | Pengelolaan master dan staf |
| Admin | GET `/admin/reports`; GET `/admin/reports/export`; GET/PUT `/admin/settings` | Laporan dan konfigurasi |

Contoh request checkout; meja berasal dari guest session sehingga client tidak menetapkan ulang meja atau harga:

```json
{
  "cart_id": "cart-example",
  "cart_version": 4,
  "order_name": "Rani",
  "payment_method": "QRIS"
}
```

Response memuat `order_id`, `order_code`, `table_label`, total/currency, `payment_attempt_id`, `expires_at`, dan action pembayaran provider yang aman. Secret provider tidak pernah dikirim ke pelanggan.

Error minimum: QR_INVALID, TABLE_INACTIVE, STORE_CLOSED, CART_CHANGED, ITEM_UNAVAILABLE, INSUFFICIENT_STOCK, PAYMENT_IN_PROGRESS, PAYMENT_STATUS_UNKNOWN, IDEMPOTENCY_CONFLICT, ORDER_ACCESS_DENIED, INVALID_TRANSITION, dan REFUND_PENDING. UI mempertahankan input bila memungkinkan dan menjelaskan langkah pemulihan.

## 16 Laporan dan pemisahan dari aplikasi kasir

Laporan memakai receipt pembayaran yang terverifikasi dan refund yang benar-benar Succeeded. Pisahkan gross sales, discount, tax/fee, amount paid, refund, excess payment yang belum dialokasikan, dan net retained. Biaya provider/payout merchant bukan otomatis sama dengan total pesanan; tampilkan terpisah bila integrasi datanya tersedia.

Penjualan dicatat saat Paid, tidak saat Accepted/Served. Metrik operasional terpisah: New belum diterima, waktu tunggu, waktu persiapan, dan Served per meja. Scan QR tidak dihitung sebagai pembeli unik atau penjualan.

Aplikasi kasir dan aplikasi QR pada paket ini merupakan dua produk mandiri. Jika kelak dipasang di toko yang sama dan harus berbagi stok/laporan, rancang integrasi sumber data bersama secara eksplisit agar tidak ada pengurangan stok atau pengakuan penjualan ganda. Penggabungan tersebut tidak menjadi dependensi rilis kedua PRD ini.

## 17 UI dan nonfunctional requirements

- Brand memakai fondasi PosyMart teal/putih. Admin mengikuti desktop/tablet; pelanggan didesain dari lebar 360/390 px dan tetap layak pada 320 px.
- Nomor meja terlihat pada menu, keranjang, checkout, tracking, notifikasi, dan detail admin.
- Cart memiliki akses tetap yang tidak menutupi konten; total sebelum Pay jelas dan tidak ada biaya tersembunyi setelah pembayaran.
- Seluruh state loading/empty/error/disabled/success tersedia, termasuk pembayaran masih diverifikasi dan QR tidak aktif.
- Target sentuh ≥44 px, label aksesibel, fokus terlihat, dialog keyboard, status tidak bergantung pada warna/suara.
- Target p95 menu ≤2 detik pada uji jaringan 4G; API internal read ≤1 detik; notifikasi ≤5 detik setelah commit eligible. Provider latency dipisahkan dari target internal.
- Beban uji awal: 50 sesi meja aktif, 100 pelanggan membuka menu bersamaan, dan 20 checkout bersamaan dengan simulasi callback duplikat/out-of-order.
- Database menjadi sumber ledger dan notifikasi persisten; cache/stream gagal tidak boleh menghilangkan Paid.
- Log menyediakan trace Order, provider reference, event ID, dan hasil rekonsiliasi tanpa secret/token akses tamu.
- Backup/restore, queue retry, koneksi putus, dan sandbox/production separation diuji sebelum go-live.

## 18 Acceptance criteria

| ID | Skenario | Hasil wajib |
| --- | --- | --- |
| Q01 | Membuka daftar QR awal | Ada minimal 50 record unik T001–T050 dan QR-T001–QR-T050 |
| Q02 | Scan seluruh 50 kartu | Masing-masing membuka nomor meja yang benar, tanpa mapping silang |
| Q03 | Menambah meja ke-51 | Dapat dibuat dengan QR unik; 50 bukan batas sistem |
| Q04 | Bulk export/cetak | Seluruh 50 aset dapat diunduh/dicetak dan tetap terbaca |
| Q05 | Scan QR Inactive/Revoked | Tidak dapat memulai checkout baru; tampil petunjuk menghubungi staf |
| Q06 | Rotate QR | Token lama tidak menerima pesanan baru; order yang sudah ada tetap terlacak |
| Q07 | Pelanggan tanpa akun | Bisa scan, pilih menu, bayar, dan tracking tanpa registrasi POS |
| Q08 | Dua ponsel satu meja | Order/keranjang terpisah; tidak bisa membaca pesanan tamu lain |
| Q09 | Mengubah table_id/amount pada request | Server menolak/mengabaikan data tidak berwenang dan memakai konteks/snapshot server |
| Q10 | Pembayaran masih Pending | Tidak ada notifikasi pesanan siap diproses atau akses Preparing |
| Q11 | Redirect frontend mengaku sukses | Payment tetap menunggu hasil server/provider yang sah |
| Q12 | Webhook palsu/merchant/reference tidak cocok | Tidak menetapkan Paid atau membuat antrean |
| Q13 | Pembayaran valid | Tepat satu Sale, pengurangan stok, kartu New, dan notifikasi logis |
| Q14 | Callback sama dikirim berulang | Efek pembayaran dan antrean tidak berganda |
| Q15 | Pending tiba setelah Paid | Status tidak turun dan tidak membuat tagihan ulang |
| Q16 | Payment create timeout | Reconcile reference lama; tidak membuat tagihan kedua tanpa kepastian |
| Q17 | Webhook terlambat/hilang | Status dapat dipulihkan melalui rekonsiliasi server |
| Q18 | Tagihan expired tanpa pembayaran | Tidak ada Sale; reservasi dilepas setelah status dipastikan |
| Q19 | Paid terlambat sesudah reservasi dilepas | Dana tercatat, ReviewRequired, Admin memutuskan pemenuhan/refund |
| Q20 | Dua attempt ternyata dibayar | Satu Order/Sale; kelebihan dana tercatat untuk refund, bukan penjualan kedua |
| Q21 | Dua pembeli merebut stok terakhir | Hanya satu reservasi berhasil; available tidak negatif |
| Q22 | Harga berubah sebelum checkout | Pelanggan meninjau harga baru sebelum membayar |
| Q23 | Item diedit setelah payment session aktif | Tidak memakai tagihan lama untuk keranjang berbeda |
| Q24 | Admin melihat New | Nomor meja, item, catatan, nominal, Paid, dan waktu tersedia |
| Q25 | Dua staf Accept bersamaan | Satu klaim berhasil; staf lain mendapat status terbaru |
| Q26 | Read notification | Tidak otomatis mengubah Order menjadi Accepted |
| Q27 | Dashboard offline lalu tersambung | Semua pesanan aktif dipulihkan; tidak hilang walau bunyi tidak pernah diputar |
| Q28 | Browser memblokir suara | Badge dan daftar notifikasi tetap cukup untuk pelayanan |
| Q29 | Pelanggan refresh/kembali dari provider | Menampilkan Order sama, tidak membuat checkout baru |
| Q30 | Menekan Served | Tidak membuat Payment/Sale/stok movement kedua |
| Q31 | Admin menolak setelah Paid | Alasan tersimpan; refund tracking jelas sampai uang benar-benar kembali |
| Q32 | Refund request timeout | Query reference yang sama; tidak melakukan pengembalian ganda |
| Q33 | Refund gagal | Tetap terlihat sebagai kewajiban yang perlu tindakan, bukan Succeeded |
| Q34 | Refund minuman sudah dibuat | Tidak otomatis restock |
| Q35 | Pesan tambahan di meja sama | Order/payment baru; snapshot pesanan Paid sebelumnya tidak berubah |
| Q36 | Toko ditutup saat ada tagihan berjalan | Checkout baru ditolak; event pembayaran lama tetap diproses dan ditangani |
| Q37 | Bayar memakai satu ponsel | Jalur pembayaran benar-benar dapat dipakai tanpa perangkat kedua wajib |
| Q38 | Nominal/currency callback tidak cocok | Masuk pemeriksaan; tidak dikirim ke produksi; dana/event tetap dapat ditelusuri |
| Q39 | Laporan dibanding ledger | Gross, refunds, retained, provider references, dan pesanan cocok |
| Q40 | Uji pemisahan produk | Aplikasi berjalan sendiri tanpa login/data aplikasi kasir pertama |

## 19 Urutan implementasi dan gate rilis

1. Model Order/Payment/Fulfillment terpisah, staff auth, guest session, katalog, dan master meja.
2. Tables & QR, seed 50 identitas, generate/export, dan QA pemetaan/cetak.
3. Menu mobile, cart, konfirmasi meja, snapshot harga, dan reservasi.
4. Adapter payment sandbox, webhook terverifikasi, retry/reconcile, dan late-payment handling.
5. Antrean paid-only, outbox, stream/poll, notifikasi, dan tracking pelanggan.
6. Refund, Reports, audit, pemulihan koneksi, dan akses order per tamu.
7. Uji Q01–Q40, uji bayar/refund sesuai kanal, dan konfigurasi produksi.

Gate produksi: domain HTTPS aktif, akun merchant/metode aktif, callback/redirect terkonfigurasi, seluruh 50 QR yang akan dipasang lolos scan, receipt dan rekonsiliasi cocok, staf dilatih menangani ReviewRequired/refund, serta pemulihan event berhasil diuji. Pembayaran sandbox tetap ditandai uji dan tidak dianggap pembayaran produksi.

Definisi selesai: pelanggan dapat scan dari meja, memesan, membayar, dan mengikuti status sendiri; Admin/Kasir mendapat pesanan valid setelah pembayaran, memproses sampai Served, serta menangani pengecualian tanpa kehilangan dana/data. Dokumen ini menspesifikasikan implementasi dan daftar QR; belum merupakan aplikasi berjalan atau 50 QR produksi yang sudah diterbitkan.
