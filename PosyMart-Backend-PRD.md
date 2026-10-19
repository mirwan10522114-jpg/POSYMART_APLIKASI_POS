# PRD Backend PosyMart

Versi Markdown 1.1 · 11 Oktober 2026

Sumber: **PosyMart-Backend-PRD.docx**, versi 1.0, 11 Oktober 2026. Audiens: pemilik produk, backend engineer, frontend engineer, QA, dan DevOps. Lingkup: POS satu toko, REST API v1, Go, PostgreSQL, Redis, serta otorisasi staf Admin/Manager/Cashier.

**Keputusan produk utama: pembeli dapat memesan dan membayar tanpa mendaftar.** Transaksi baru menggunakan **Walk-in Customer** dengan `customerId = null`. Sidebar **Customers** dipakai untuk profil pelanggan yang sengaja disimpan. Akun staf pada **Users**, profil **Customers**, dan keanggotaan member adalah tiga hal berbeda.

Versi ini mempertahankan 14 bagian utama dan tabel teknis dari Word, merapikan struktur Markdown dan blok kode, serta memperjelas kebijakan pelanggan opsional. Koreksi terarah mencakup contoh Bagel, payload customer null, alur checkout, dan penjelasan data yang bertentangan dengan design.md. Rincian perubahan dan hal teknis yang masih perlu diselaraskan tercantum pada bagian 15. DDL, pseudocode, versi dependensi, estimasi roadmap, dan target performa merupakan spesifikasi atau usulan dari dokumen sumber; bukan bukti bahwa backend sudah dibangun, migration sudah dijalankan, atau target sudah diuji.

Baca [Alur Bisnis PosyMart](./PosyMart-Alur-Bisnis.md) untuk penjelasan operasional yang lebih ringkas. Acuan UI tersedia pada [design.md](./design.md).

## 0 Aturan pelanggan yang menjadi acuan

| Kebutuhan | Keputusan |
| --- | --- |
| Pembelian kopi atau produk biasa | Tidak memerlukan pendaftaran, nama, nomor telepon, email, password, atau OTP pembeli |
| Pilihan awal Customer | Walk-in Customer dengan `customerId = null` pada draft, Sale, dan Order |
| Penyimpanan Walk-in | Tidak membuat profil Customer bersama atau profil otomatis per transaksi |
| Login POS | Dilakukan staf pada Users; pembeli tidak perlu login |
| Customers | Profil opsional untuk riwayat dan kebutuhan pelanggan yang ingin dicatat |
| Pelanggan tersimpan | Tidak otomatis member dan tidak otomatis mempunyai akun login |
| Member | Pendaftaran hanya untuk manfaat keanggotaan yang memerlukannya; program membership/loyalty merupakan pengembangan terpisah |
| Promo umum | Boleh dipakai Walk-in bila syarat produk, periode, dan minimum belanja terpenuhi |
| Promo pelanggan tertentu | Hanya manfaat promo tersebut yang memerlukan profil eligible; pembelian biasa tetap tersedia |
| Retur | Mengacu transaksi/struk asal, bukan kewajiban registrasi |
| Laporan | Transaksi Walk-in tetap masuk Sales dan Reports; tidak menambah jumlah profil Customers |

### 0.1 Urutan checkout standar

1. Staf login dan membuka Dashboard.
2. Draft baru dibuat sebagai Walk-in Customer.
3. Kasir memasukkan produk, jumlah, catatan, dan promo umum jika diperlukan.
4. Memilih/membuat profil pelanggan merupakan langkah opsional yang dapat dilewati.
5. Kasir meninjau total, memilih metode pembayaran, dan menekan Pay.
6. Backend memvalidasi transaksi; validasi profil hanya berjalan bila Customer ID diisi.
7. Pembayaran simulasi yang berhasil menyimpan satu Sale Completed, item dan snapshot uang, pergerakan stok bila aktif, serta audit.
8. Sales dan Reports diperbarui. Agregat Customer hanya diperbarui jika Customer ID tidak null. Draft dibersihkan setelah commit berhasil.

Kontrak, perilaku, dan contoh pada bagian 0 serta klarifikasi bagian 15 menjadi acuan bila contoh teknis sumber yang belum lengkap memiliki perbedaan. Aplikasi tidak boleh menambahkan validasi Customer wajib pada checkout atau Orders.

## Daftar isi

- [0 Aturan pelanggan yang menjadi acuan](#0-aturan-pelanggan-yang-menjadi-acuan)
- [1. Ringkasan Eksekutif](#1-ringkasan-eksekutif)
- [2. Konteks dan Tujuan](#2-konteks-dan-tujuan)
- [3. Arsitektur Sistem](#3-arsitektur-sistem)
- [4. Kontrak Data Domain](#4-kontrak-data-domain)
- [5. Skema Database (ERD dan DDL PostgreSQL)](#5-skema-database-erd-dan-ddl-postgresql)
- [6. Algoritma Alokasi Uang](#6-algoritma-alokasi-uang)
- [7. Alur Bisnis Inti](#7-alur-bisnis-inti)
- [8. Spesifikasi API REST](#8-spesifikasi-api-rest)
- [9. Autentikasi dan Otorisasi](#9-autentikasi-dan-otorisasi)
- [10. Pola Konsistensi dan Idempotency](#10-pola-konsistensi-dan-idempotency)
- [11. Non-Functional Requirements](#11-non-functional-requirements)
- [12. Roadmap Implementasi](#12-roadmap-implementasi)
- [13. Risiko dan Mitigasi](#13-risiko-dan-mitigasi)
- [14. Lampiran](#14-lampiran)
- [15 Klarifikasi dan kesiapan implementasi](#15-klarifikasi-dan-kesiapan-implementasi)

## 1. Ringkasan Eksekutif

Dokumen ini mendefinisikan Product Requirements Document (PRD) untuk backend PosyMart, sebuah sistem Point of Sale (POS) single-store yang sebelumnya hanya terspesifikasi pada sisi frontend di design.md. Pelanggan boleh membeli tanpa pendaftaran dengan customerId = null; akun yang login ke POS adalah akun staf. Tujuan utama PRD ini adalah menggantikan lapisan simulasi state lokal pada frontend dengan API persisten yang konsisten, dapat diaudit, dan menjaga invariant finansial yang sudah didefinisikan pada bagian 26.4 design.md, khususnya alokasi uang menggunakan metode largest remainder pada level unit item.

Lingkup backend mencakup sepuluh modul fungsional: Autentikasi & Otorisasi, Products, Customers, Sales, Orders, Returns, Discounts, Reports, Users, Settings, serta Inventory Movement sebagai cross-cutting concern. Stack teknologi yang dipilih adalah Go (Echo v4 atau Fiber v3) sebagai HTTP framework, PostgreSQL 15+ sebagai database persistensi tunggal, Redis untuk cache stok dan idempotency key, serta OpenAPI 3.1 sebagai kontrak API tunggal yang di-generate secara type-safe via oapi-codegen. Autentikasi menggunakan JWT access token berumur pendek dengan refresh token yang di-rotate, dan otorisasi mengikuti pola Role-Based Access Control (RBAC) dengan tiga role: Admin, Manager, dan Cashier.

Prinsip desain utama yang dianut PRD ini adalah snapshot transaksi immutable: setiap transaksi Sale menyimpan hasil perhitungan (subtotal, discount, tax, total, alokasi per item, alokasi per unit) pada saat checkout, sehingga perubahan pada Product, Settings, atau Discount setelah transaksi tidak mengubah receipt historis. Seluruh nominal disimpan sebagai integer cents (BIGINT) untuk menghindari floating point error, dan tarif pajak disimpan sebagai basis points (integer) dengan presisi 1/100 persen. Idempotency dijamin melalui kombinasi Idempotency-Key header untuk mutasi kritis dan Inventory Movement ID unik untuk setiap perubahan stok, sehingga retry yang aman dapat dilakukan tanpa efek samping ganda.

Audiens utama dokumen ini adalah tim backend engineer yang akan mengimplementasikan layanan, serta QA dan DevOps yang memverifikasi kelayakan produksi. Dokumen ini sengaja padat kode SQL, pseudocode, dan tabel referensi agar dapat berfungsi sebagai acuan implementasi langsung tanpa dokumen turunan. Estimasi durasi implementasi end-to-end adalah 14 minggu dengan enam milestone, dengan acceptance criteria mengacu pada bagian 27 design.md untuk konsistensi ledger demo.

## 2. Konteks dan Tujuan

### 2.1 Latar Belakang

PosyMart adalah aplikasi POS untuk satu toko kafe yang menjual produk kategori Coffee, Food, Drinks, dan Desserts. Spesifikasi frontend yang ada pada design.md (dokumen acuan tampilan) telah mendefinisikan sepuluh halaman operasional, kontrak data domain (bagian 26.3), urutan perhitungan uang (bagian 26.4), alur bisnis utama (bagian 26.5), dan acceptance criteria aplikasi lengkap (bagian 27). Spesifikasi frontend secara eksplisit menyatakan bahwa integrasi backend bukan bagian scope-nya dan seluruh state masih menggunakan simulasi lokal. PRD ini melengkapi gap tersebut dengan mendefinisikan backend persistent yang menjaga konsistensi data lintas halaman, lintas reload, dan lintas sesi pengguna.

Tantangan utama yang harus dipecahkan backend adalah menjaga invariant finansial yang sudah dispesifikasi frontend, terutama alokasi discount dan tax per unit item menggunakan metode largest remainder dengan tie-breaker ID item ascending dan unit index descending. Kesalahan satu sen pada refund partial dapat menyebabkan total refund melebihi total sale asli, yang merupakan defect kritis pada sistem POS. Selain itu, transisi state Sale, Order, dan Return harus idempotent terhadap retry jaringan dan reload halaman, sehingga pelanggan tidak dikenakan pembayaran dua kali dan stok tidak dikurangi dua kali.

### 2.2 Tujuan

Tujuan backend PosyMart adalah: (1) menyediakan REST API yang konsisten dengan kontrak data design.md sehingga frontend dapat mengganti seluruh state lokal dengan panggilan API tanpa mengubah behavior yang dispesifikasi; (2) menjamin invariant finansial melalui perhitungan disiplin pada level database transaction dengan isolation yang tepat; (3) menyediakan audit log immutable untuk setiap mutasi state kritis (sale pay, void, return approve, refund, settings change) sehingga setiap perubahan dapat ditelusuri; (4) menyediakan API untuk agregasi Reports yang efisien tanpa menghitung ulang pajak dari total agregat mingguan; (5) menyediakan mekanisme idempotency yang memungkinkan retry aman dari sisi klien.

### 2.3 Lingkup In-Scope

- REST API versi /api/v1 untuk sepuluh modul fungsional dan Autentikasi.

- Persistensi PostgreSQL dengan skema normalized untuk seluruh entitas domain, termasuk snapshot SaleItem dan unit allocation.

- Autentikasi JWT dengan refresh token rotation dan RBAC tiga role (Admin, Manager, Cashier).

- Algoritma alokasi uang largest remainder pada level item dan unit, dengan invariant yang dapat diverifikasi.

- Inventory movement tracking dengan idempotency via movement_id unik.

- Audit log append-only untuk setiap mutasi state kritis.

- Agregasi Reports harian dan mingguan berbasis ledger tersimpan, bukan recompute dari produk/settings terkini.

- Export CSV dengan sanitasi cell anti-formula-injection.

- Health check, structured logging JSON, Prometheus metrics, dan OpenTelemetry trace.

### 2.4 Lingkup Out-of-Scope

- Multi-tenant atau multi-store: scope single-store, satu database.

- Integrasi payment gateway sungguhan (Stripe, Midtrans, dll.). Pembayaran disimulasikan via endpoint /pay yang langsung menandai Sale sebagai Completed setelah validasi internal.

- Hardware printer thermal untuk receipt fisik. Receipt hanya disajikan via API response dan render frontend.

- Mobile native app (iOS/Android). Backend mendukung web client saja.

- Real-time push notification via WebSocket atau SSE. Frontend melakukan polling atau manual refresh.

- Loyalty program, gift card, dan store credit di luar scope versi awal PosyMart. Keanggotaan dapat dikembangkan sebagai fitur opsional; pendaftaran hanya diperlukan untuk memperoleh manfaat yang memang mensyaratkannya. Customer tersimpan tidak otomatis menjadi member, dan pembelian biasa tetap dapat dilakukan sebagai Walk-in.

### 2.5 Glossary

Tabel 2-1 Glossary Istilah

| Istilah | Definisi |
| --- | --- |
| Sale | Transaksi penjualan yang sudah dibayar (Completed) atau dibatalkan (Voided). Tidak ada status Draft pada Sale tersimpan — draft hanya ada di memori/Redis sebelum pay. |
| SaleItem | Snapshot item dalam Sale: productId, name, quantity, unitPriceCents, allocatedDiscountCents, allocatedTaxCents, dan array unitAllocations. |
| SaleItemUnitAllocation | Alokasi nominal per unit item: {unitIndex, netCents, taxCents}. Memungkinkan refund partial dengan presisi sen. |
| Order | Pesanan yang akan diproses (Draft, Pending, Preparing, Ready, Completed, Cancelled). Order Completed memiliki satu convertedSaleId. |
| Return | Permintaan pengembalian barang: Draft, Pending, Completed, Rejected. Menunjuk original Sale dan SaleItem tertentu. |
| Void | Pembatalan Sale Completed oleh Admin/Manager. Bukan refund dana; hanya membatalkan ledger dan mengembalikan stok. |
| Restock | Pengembalian stok saat Return Completed, hanya untuk unit dengan Restock=true dan Condition=Unopened. |
| Snapshot | Salinan nilai (nama produk, harga, nama kasir, tarif pajak) yang disimpan pada transaksi saat dibuat. Tidak berubah meski master data berubah. |
| MoneyCents | Tipe nominal integer BIGINT dalam satuan sen. Semua perhitungan finansial menggunakan tipe ini, bukan float/decimal. |
| Movement ID | UUID v7 unik untuk setiap perubahan stok. Menjamin idempotency: request dengan movement_id yang sama tidak mengubah stok dua kali. |
| Largest Remainder | Algoritma pembagian proporsional (Hamilton method) yang menjamin total alokasi sama persis dengan nominal target, tanpa kehilangan satu sen. |
| Idempotency-Key | Header HTTP yang dikirim klien pada mutasi kritis. Backend menyimpan key+response selama 24 jam; replay mengembalikan response asli. |
| Outbox Pattern | Pola menulis event ke tabel outbox dalam transaksi yang sama dengan mutasi data, kemudian dipublikasikan asinkron oleh worker. Menjamin consistency data+event. |

## 3. Arsitektur Sistem

### 3.1 Diagram Arsitektur Tingkat Tinggi

Arsitektur PosyMart backend mengikuti pola monolit modular: satu binary Go yang melayani seluruh endpoint, dengan pemisahan logis antara HTTP layer, service layer, dan repository layer. Pemisahan ini cukup untuk kebutuhan single-store dengan throughput 50 RPS concurrent, sekaligus memberikan jalur migrasi ke microservice di masa depan jika throughput bertumbuh. Berikut adalah diagram arsitektur dalam bentuk teks:

**Catatan arsitektur:** diagram teks berikut dipertahankan dari Word. Jalur request aplikasi yang dimaksud adalah Web Client → Nginx/LB → Go Service → PostgreSQL/Redis. Browser dan Nginx tidak mengakses tabel bisnis langsung. Audit log berada di PostgreSQL.

```text
┌──────────────┐      HTTPS      ┌─────────────────┐      SQL      ┌──────────────┐
│  Web Client  │  ──────────────> │  Nginx / LB     │  ──────────> │  PostgreSQL  │
│  (Browser)   │                  │  (TLS, rate     │              │  15+ (RDS)   │
└──────────────┘                  │   limit, gzip)  │              └──────────────┘
       ^                          └────────┬────────┘                     ^
       │                                   │                              │
       │                                   v                              │
       │                          ┌─────────────────┐    LISTEN/NOTIFY    │
       │                          │  Go Service     │  <─────────────────┘
       │                          │  (Echo/Fiber)   │
       │                          │  - handlers     │
       │                          │  - middleware   │
       │                          │  - services     │
       │                          │  - repositories │
       │                          └────────┬────────┘
       │                                   │
       │                                   v
       │                          ┌─────────────────┐
       │                          │  Redis 7        │
       │                          │  - stock cache  │
       │                          │  - idempotency  │
       │                          │  - rate limit   │
       │                          └─────────────────┘
       │                                   │
       │                                   v
       │                          ┌─────────────────┐
       │                          │  Audit Log Store│
       │                          │  (append-only)  │
       │                          └─────────────────┘
       │
       └──── OpenAPI 3.1 spec served at /openapi.json + /docs (Swagger UI)
```

Komponen utama: Web Client (browser PosyMart), Nginx sebagai reverse proxy dengan TLS termination dan rate limiting, Go service sebagai application server, PostgreSQL sebagai database persistensi tunggal, Redis untuk cache stok dan idempotency key TTL, dan Audit Log Store yang berupa tabel terpisah dalam Postgres dengan policy append-only. OpenAPI spec disajikan di endpoint /openapi.json dan Swagger UI di /docs untuk konsumsi tim engineering dan integrasi pihak ketiga di masa depan.

### 3.2 Komponen Internal Go Service

Struktur internal Go service mengikuti pola layered architecture dengan dependency direction satu arah dari handler ke repository. Setiap layer memiliki tanggung jawab yang jelas dan tidak ada pemanggilan balik dari bawah ke atas kecuali via interface yang di-inject.

Tabel 3-1 Layer dan Tanggung Jawab

| Layer | Paket | Tanggung Jawab |
| --- | --- | --- |
| HTTP Handler | internal/handler | Parse request, validasi via oapi-codegen generated types, panggil service, format response sesuai envelope {data, error, meta}. Tidak ada logika bisnis. |
| Middleware | internal/middleware | JWT auth, RBAC permission check, request ID injection, panic recover, structured logging, rate limit via Redis, idempotency key lookup. |
| Service / Use Case | internal/service | Logika bisnis: alokasi uang, state machine Order/Return, validasi invariant, koordinasi repository dalam satu db transaction. |
| Repository | internal/repository | Akses data via pgx. Query di-generate dari sqlc. Tidak ada logika bisnis. Setiap method satu concern. |
| Domain Model | internal/domain | Tipe Go murni: Sale, SaleItem, Order, Return, dll. Tidak ada dependency ke DB atau HTTP. |
| Outbox Worker | internal/worker | Background goroutine yang membaca tabel outbox dan mempublikasikan event (audit log write, future notification). |

### 3.3 Tech Stack

Tabel 3-2 Tech Stack dan Versi

| Komponen | Teknologi | Versi | Alasan |
| --- | --- | --- | --- |
| Bahasa | Go | 1.22+ | Performa tinggi, konkurensi native via goroutine, static typing, binary tunggal deploy mudah. |
| HTTP Framework | Echo v4 atau Fiber v3 | latest stable | Echo lebih matang dan ekspresif; Fiber lebih cepat via fasthttp. Pilih salah satu konsisten. |
| DB Driver | pgx/v5 | v5.5+ | Pure Go driver PostgreSQL terbaik; mendukung prepared statement, LISTEN/NOTIFY, batch, COPY. |
| Query Generator | sqlc | v1.25+ | Generate Go type-safe dari SQL. Tidak ada ORM overhead, query tetap di SQL murni untuk auditability. |
| Migration | golang-migrate | v6+ | CLI terpisah, support versioned migration up/down, file SQL murni untuk review. |
| Config | viper | v1.18+ | Support env, YAML, dan hot reload. Konfigurasi per-environment. |
| Logger | zap | v1.27+ | Structured JSON logger dengan level dan field. Performance tinggi. |
| JWT | golang-jwt/v5 | v5.2+ | Implementasi JWT standar HS256/RS256, support claim custom. |
| Validation | oapi-codegen + kin-openapi | v1.16+ | Generate type dan validator dari OpenAPI spec. Spec sebagai single source of truth. |
| Redis Client | go-redis/v9 | v9.5+ | Client Redis idiomatic Go, support pipeline dan pub/sub. |
| OpenTelemetry | otelgo | v1.24+ | Tracing terdistribusi standar CNCF. |
| Test Framework | testify + testcontainers | latest | Assertion ergonomis + integration test dengan Postgres container asli. |

### 3.4 Deployment Topology

Deployment target awal adalah single-region dengan arsitektur active-passive. Go service berjalan dalam container Docker, di-deploy ke instance compute (EC2/GCE/Cloud Run) dengan minimal 2 replika di belakang load balancer untuk availability. PostgreSQL menggunakan managed service (RDS/CloudSQL) dengan automated backup dan PITR (Point-in-Time Recovery) via WAL archiving setiap 5 menit. Redis menggunakan managed service (ElastiCache/MemoryStore) dengan failover otomatis. Audit log tetap dalam Postgres untuk konsistensi transaksional, dengan opsi replikasi ke object storage (S3) untuk retention jangka panjang.

Strategi scaling: Go service stateless sehingga horizontal scale mudah dengan menambah replika. PostgreSQL vertikal scale hingga 32 vCPU / 256 GB RAM, di atasnya perlu pertimbangkan read replica untuk query Reports. Redis single instance cukup untuk single-store; jika diperlukan, dapat di-cluster. Tidak ada CDN karena API tidak menyajikan aset statis (frontend di-deploy terpisah).

### 3.5 Prinsip Pemisahan Layer

Aturan ketat pemisahan layer: (1) Handler tidak boleh memanggil repository langsung, hanya via service. (2) Service tidak boleh tahu tentang HTTP (tidak import net/http atau Echo context). Service menerima dan mengembalikan domain model murni. (3) Repository tidak boleh mengandung logika bisnis; setiap method satu concern (e.g. GetSaleByID, InsertSale, UpdateSaleStatus). (4) Domain model tidak boleh punya tag JSON atau dependency ke DB driver. (5) Transaction boundary di service: service yang memulai dan commit transaction, repository menerima pgx.Tx sebagai parameter. Aturan ini ditegakkan via CI lint dengan rule import boundary.

## 4. Kontrak Data Domain

Kontrak data domain yang didefinisikan di sini merupakan turunan langsung dari bagian 26.3 design.md, dengan penyesuaian untuk representasi persisten di PostgreSQL. Setiap entitas yang dispesifikasi frontend tetap dipertahankan bentuknya di API response, sehingga frontend tidak perlu menulis adapter besar. Backend hanya menambahkan field internal (id, timestamps, audit) yang tidak diekspos ke frontend kecuali melalui endpoint read khusus.

### 4.1 Daftar Entitas

Tabel 4-1 Entitas Domain

| Entitas | Jumlah field inti | Tipe Persistensi | Catatan |
| --- | --- | --- | --- |
| Product | 11 | tabel products | Soft delete via archived_at, bukan hard delete. Status Active/Inactive mengontrol visibility di katalog POS. |
| Category | 3 | tabel categories | Lookup untuk Product.category. Seed: Coffee, Food, Drinks, Desserts. |
| Customer | 8 | tabel customers | Soft delete via archived_at. customerId pada Sale dan Order bersifat nullable. Walk-in tidak dibuat sebagai record Customer. Profil tersimpan bersifat opsional dan bukan otomatis member atau akun login. |
| Sale | 16 | tabel sales | Snapshot customerName, cashierName, taxRateBps, taxName. Status Completed/Voided. |
| SaleItem | 9 | tabel sale_items | Snapshot name, unitPriceCents. allocatedDiscountCents, allocatedTaxCents per item. |
| SaleItemUnitAllocation | 4 | tabel sale_item_unit_allocations | Alokasi netCents dan taxCents per unit index. Memungkinkan refund partial presisi sen. |
| Order | 10 | tabel orders | State machine: Draft→Pending→Preparing→Ready→Completed/Cancelled. convertedSaleId nullable. |
| OrderItem | 6 | tabel order_items | Snapshot productId, name, unitPriceCents pada saat order dibuat. |
| Return | 11 | tabel returns | Status Draft→Pending→Completed/Rejected. processorId = user yang approve. |
| ReturnItem | 8 | tabel return_items | Menunjuk sale_item_id. selectedUnits array JSONB. restock boolean, condition enum. |
| Discount | 12 | tabel discounts | Tipe: Percent, FixedAmount, CategoryPercent. Scope: All, Category, Product. baselineUsageCount terpisah dari usage actual. |
| DiscountUsage | 5 | tabel discount_usages | Link satu Sale ke satu Discount. Mencegah duplikasi redemption per sale. |
| DemoUser | 8 | tabel users | Stable ID: user-john-doe, dll. Role Admin/Manager/Cashier. isActive flag. |
| Settings | 30+ | tabel settings (singleton) | Store info, receipt, tax, inventory toggle. version untuk optimistic locking. |
| InventoryMovement | 8 | tabel inventory_movements | movement_id UUID v7 idempotent. sale_id/return_id reference. delta_qty int. |
| AuditLog | 8 | tabel audit_logs | Append-only. userId, action, target_type, target_id, before JSONB, after JSONB, trace_id. |
| IdempotencyKey | 5 | tabel idempotency_keys | key string unique, request_hash, response JSONB, expires_at. TTL 24 jam. |
| RefreshToken | 6 | tabel refresh_tokens | Token hash bcrypt, user_id, device_info, expires_at, revoked_at. |

### 4.2 Enum dan Tipe Status

Enum digunakan secara konsisten di seluruh layer: database (CHECK constraint atau enum type), service (const Go), dan API (string literal di OpenAPI schema). Berikut adalah daftar enum lengkap:

Tabel 4-2 Daftar Enum

| Enum | Nilai | Penggunaan |
| --- | --- | --- |
| SaleStatus | Completed \| Voided | Kolom sales.status. Tidak ada Draft — draft hanya di Redis/memori. |
| OrderStatus | Draft \| Pending \| Preparing \| Ready \| Completed \| Cancelled | Kolom orders.status. Transisi via state machine. |
| ReturnStatus | Draft \| Pending \| Completed \| Rejected | Kolom returns.status. Hanya Completed yang mengurangi net report. |
| DemoRole | Admin \| Manager \| Cashier | Kolom users.role. Digunakan oleh RBAC middleware. |
| PaymentMethod | Cash \| Card \| Digital Wallet | Kolom sales.payment_method. |
| DiscountType | Percent \| FixedAmount \| CategoryPercent | Kolom discounts.type. Menentukan cara hitung discount. |
| DiscountScope | All \| Category \| Product | Kolom discounts.scope. Menentukan item yang eligible. |
| ProductStatus | Active \| Inactive | Kolom products.status. Inactive tidak muncul di katalog POS. |
| RestockCondition | Unopened \| Opened \| Damaged | Kolom return_items.condition. Hanya Unopened yang eligible restock. |
| InventoryMovementType | SaleDecrement \| VoidRestore \| ReturnRestock \| ManualAdjust | Kolom inventory_movements.type. Menentukan arah dan kebijakan delta. |

### 4.3 Konvensi MoneyCents dan Basis Points

Seluruh nominal finansial disimpan sebagai BIGINT dalam satuan sen (integer cents), bukan NUMERIC/DECIMAL maupun float. Alasannya: (1) integer arithmetic di CPU lebih cepat dan deterministik; (2) tidak ada representasi floating point 0.1 yang tidak eksak; (3) PostgreSQL BIGINT mendukung nilai hingga 2^63-1, cukup untuk POS single-store selama ratusan tahun; (4) JSON serialization integer tidak kehilangan presisi seperti float. Tarif pajak disimpan sebagai basis points (bps) integer, di mana 100 bps = 1%, sehingga tarif 8% disimpan sebagai 800, dan tarif 8.25% sebagai 825. Konversi: taxCents = round(taxableSubtotalCents × taxRateBps / 10000).

### 4.4 Pola Snapshot Transaksi

Pola snapshot adalah prinsip paling penting dalam desain backend PosyMart. Saat Sale dibuat (POST /sales/draft/pay berhasil), backend menyalin nilai-nilai berikut ke tabel sales dan sale_items, terlepas dari apakah nilai master data akan berubah di masa depan: (1) customerNameSnapshot — nama customer saat checkout; (2) cashierNameSnapshot — nama kasir saat checkout; (3) taxRateBpsSnapshot, taxNameSnapshot, taxEnabledSnapshot — konfigurasi pajak saat checkout; (4) SaleItem.name, SaleItem.unitPriceCents — nama dan harga produk saat checkout; (5) SaleItem.allocatedDiscountCents, SaleItem.allocatedTaxCents — hasil alokasi yang sudah dihitung; (6) SaleItem.unitAllocations — array alokasi per unit. Setelah Sale tersimpan, tidak ada path API yang mengizinkan update field-field snapshot ini. Edit Product (nama, harga), edit Customer (nama), atau ubah Settings (tarif pajak) hanya berlaku untuk Sale yang dibuat setelah perubahan tersebut.

## 5. Skema Database (ERD dan DDL PostgreSQL)

Skema database PosyMart menggunakan PostgreSQL 15+ dengan 18 tabel utama. Bagian ini menyajikan ERD tekstual, konvensi penamaan, DDL lengkap untuk semua tabel, strategi index, dan rencana migration. Semua DDL ditulis dengan asumsi encoding UTF-8 dan timezone database UTC; konversi timezone ke Asia/Jakarta dilakukan di layer aplikasi.

### 5.1 ERD Tekstual

Diagram relasi entitas dalam bentuk teks. Notasi: 1—1 (one-to-one), 1—* (one-to-many), *—1 (many-to-one), *—* (many-to-many via junction).

**Catatan relasi:** `sales.customer_id` dan `orders.customer_id` nullable. Tidak ada entitas atau akun Walk-in khusus. Diagram sumber masih perlu dirapikan agar optionality dan kardinalitas Order–Sale sesuai constraint implementasi.

```text
                          ┌─────────────┐
                          │  categories │
                          └──────┬──────┘
                                 │ *—1
                          ┌──────┴──────┐
                          │   products  │
                          └──────┬──────┘
                                 │ 1—*
              ┌──────────────────┴────────────────────┐
              │ 1—*                                    │ 1—*
        ┌─────┴──────┐                          ┌──────┴────────┐
        │ order_items│                          │  sale_items   │
        └─────┬──────┘                          └──────┬────────┘
              │ *—1                                    │ 1—*
        ┌─────┴──────┐    convertedSaleId       ┌──────┴──────────────────────┐
        │   orders   │ ────────────*—1────────> │         sales              │
        └─────┬──────┘                          └──────┬──────────────────────┘
              │ *—1                                    │ *—1
              │                                  ┌─────┴──────┐    *—1
        ┌─────┴──────┐                            │  customers │ ──── (nullable, Walk-in = NULL)
        │   users    │ ←── cashierId, processorId │            │
        │ (DemoUser) │                            └────────────┘
        └────────────┘
              │ 1—*
        ┌─────┴─────────────┐
        │ inventory_movements│ ←── sale_id, return_id, product_id
        └───────────────────┘

        sales ──1—*── returns ──1—*── return_items ──*—1── sale_items
                                                              │
                                                              └──1—*── sale_item_unit_allocations

        discounts ──1—*── discount_usages ──*—1── sales

        settings (singleton, satu row)
        audit_logs (append-only)
        idempotency_keys (TTL 24 jam)
        refresh_tokens (one-to-many dengan users)
```

### 5.2 Konvensi Penamaan dan Tipe

Tabel 5-1 Konvensi Penamaan

| Aspek | Konvensi | Contoh |
| --- | --- | --- |
| Nama tabel | snake_case, jamak (plural) | sale_items, inventory_movements |
| Nama kolom | snake_case | created_at, customer_name_snapshot |
| Primary key | id UUID v7 (default gen_random_uuid()) | id UUID PRIMARY KEY DEFAULT gen_random_uuid() |
| Foreign key | {entity_singular}_id | sale_id, product_id, customer_id |
| Timestamp | created_at, updated_at TIMESTAMPTZ NOT NULL DEFAULT now() | universal di semua tabel |
| Soft delete | archived_at TIMESTAMPTZ NULL | products, customers, discounts, users |
| Status enum | VARCHAR(20) CHECK (...) — bukan PG enum type | status VARCHAR(20) CHECK (status IN ('Completed','Voided')) |
| Money | BIGINT NOT NULL CHECK (>= 0) | subtotal_cents, tax_cents, total_cents |
| Tarif pajak | INTEGER NOT NULL CHECK (>= 0 AND <= 10000) | tax_rate_bps_snapshot (bps, 10000=100%) |
| Snapshot text | TEXT NOT NULL | customer_name_snapshot, cashier_name_snapshot |
| JSONB array | JSONB NOT NULL DEFAULT '[]'::jsonb | unit_allocations, selected_units |

Alasan menggunakan VARCHAR + CHECK alih-alih PostgreSQL ENUM type: VARCHAR lebih mudah di-migrate (menambah nilai baru tidak memerlukan ALTER TYPE), lebih kompatibel dengan sqlc code generation, dan tidak menyebabkan error saat query nilai yang belum terdaftar. Trade-off: tidak ada type safety sekuat ENUM, tetapi invariant dijaga oleh layer aplikasi dan integration test.

### 5.3 DDL Lengkap PostgreSQL

Berikut adalah rancangan DDL dari PRD Word untuk dasar migration 0001_init.sql. Blok SQL dipertahankan sebagai referensi teknis dan belum diuji sebagai migration yang dapat langsung dijalankan. Periksa urutan foreign key, constraint, cakupan field, dan catatan bagian 15 sebelum implementasi.

#### 5.3.1 Tabel users

```sql
CREATE TABLE users (
  id              TEXT PRIMARY KEY,        -- 'user-john-doe' dst, stable ID
  email           TEXT NOT NULL UNIQUE,
  password_hash   TEXT NOT NULL,           -- bcrypt cost 12
  full_name       TEXT NOT NULL,
  role            VARCHAR(20) NOT NULL CHECK (role IN ('Admin','Manager','Cashier')),
  is_active       BOOLEAN NOT NULL DEFAULT TRUE,
  last_login_at   TIMESTAMPTZ,
  created_at      TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at      TIMESTAMPTZ NOT NULL DEFAULT now(),
  archived_at     TIMESTAMPTZ
);
CREATE INDEX idx_users_role_active ON users(role) WHERE is_active = TRUE;
```

#### 5.3.2 Tabel categories

```sql
CREATE TABLE categories (
  id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  name        TEXT NOT NULL UNIQUE,
  sort_order  INTEGER NOT NULL DEFAULT 0,
  created_at  TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at  TIMESTAMPTZ NOT NULL DEFAULT now()
);
```

#### 5.3.3 Tabel products

```sql
CREATE TABLE products (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  sku             TEXT UNIQUE,
  name            TEXT NOT NULL,
  description     TEXT,
  category_id     UUID REFERENCES categories(id) ON DELETE RESTRICT,
  price_cents     BIGINT NOT NULL CHECK (price_cents >= 0),
  cost_cents      BIGINT CHECK (cost_cents >= 0),
  tax_rate_bps    INTEGER NOT NULL DEFAULT 800 CHECK (tax_rate_bps >= 0 AND tax_rate_bps <= 10000),
  status          VARCHAR(20) NOT NULL DEFAULT 'Active'
                  CHECK (status IN ('Active','Inactive')),
  stock_qty       INTEGER NOT NULL DEFAULT 0,
  track_stock     BOOLEAN NOT NULL DEFAULT TRUE,
  image_url       TEXT,
  created_at      TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at      TIMESTAMPTZ NOT NULL DEFAULT now(),
  archived_at     TIMESTAMPTZ
);
CREATE INDEX idx_products_status_name ON products(status, name) WHERE archived_at IS NULL;
CREATE INDEX idx_products_category ON products(category_id) WHERE archived_at IS NULL;
```

#### 5.3.4 Tabel customers

**Catatan DDL Customer:** `sales.customer_id` dan `orders.customer_id` sudah nullable pada rancangan. Constraint email parsial dalam blok berikut harus diselaraskan menjadi sintaks index/constraint PostgreSQL yang valid dan diuji; lihat bagian 15. Tidak ada alasan menambah foreign key wajib untuk checkout.

```sql
CREATE TABLE customers (
  id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  name          TEXT NOT NULL,
  email         TEXT,
  phone         TEXT,
  address       TEXT,
  note          TEXT,
  total_spent_cents BIGINT NOT NULL DEFAULT 0,  -- di-maintain oleh trigger
  total_orders      INTEGER NOT NULL DEFAULT 0,
  created_at    TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at    TIMESTAMPTZ NOT NULL DEFAULT now(),
  archived_at   TIMESTAMPTZ,
  CONSTRAINT customers_email_unique UNIQUE (email) WHERE email IS NOT NULL
);
CREATE INDEX idx_customers_name ON customers(name) WHERE archived_at IS NULL;
CREATE INDEX idx_customers_phone ON customers(phone) WHERE archived_at IS NULL;
```

#### 5.3.5 Tabel sales dan sale_items

```sql
CREATE TABLE sales (
  id                       UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  tx_code                  TEXT NOT NULL UNIQUE,   -- 'TX-1001' dst
  created_at               TIMESTAMPTZ NOT NULL DEFAULT now(),
  customer_id              UUID REFERENCES customers(id) ON DELETE RESTRICT,
  customer_name_snapshot   TEXT NOT NULL,          -- 'Walk-in Customer' jika NULL
  cashier_id               TEXT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
  cashier_name_snapshot    TEXT NOT NULL,
  status                   VARCHAR(20) NOT NULL DEFAULT 'Completed'
                           CHECK (status IN ('Completed','Voided')),
  payment_method           VARCHAR(20) NOT NULL
                           CHECK (payment_method IN ('Cash','Card','Digital Wallet')),
  subtotal_cents           BIGINT NOT NULL CHECK (subtotal_cents >= 0),
  discount_cents           BIGINT NOT NULL DEFAULT 0 CHECK (discount_cents >= 0),
  discount_id              UUID REFERENCES discounts(id) ON DELETE RESTRICT,
  taxable_subtotal_cents   BIGINT NOT NULL CHECK (taxable_subtotal_cents >= 0),
  tax_cents                BIGINT NOT NULL DEFAULT 0 CHECK (tax_cents >= 0),
  tax_rate_bps_snapshot    INTEGER NOT NULL CHECK (tax_rate_bps_snapshot >= 0 AND tax_rate_bps_snapshot <= 10000),
  tax_name_snapshot        TEXT NOT NULL,
  tax_enabled_snapshot     BOOLEAN NOT NULL,
  total_cents              BIGINT NOT NULL CHECK (total_cents >= 0),
  source_order_id          UUID,
  voided_at                TIMESTAMPTZ,
  voided_by                TEXT REFERENCES users(id),
  void_reason              TEXT,
  updated_at               TIMESTAMPTZ NOT NULL DEFAULT now(),
  CHECK (subtotal_cents - discount_cents = taxable_subtotal_cents),
  CHECK (taxable_subtotal_cents + tax_cents = total_cents)
);
CREATE INDEX idx_sales_created_status ON sales(created_at DESC, status);
CREATE INDEX idx_sales_customer ON sales(customer_id) WHERE customer_id IS NOT NULL;
CREATE INDEX idx_sales_cashier ON sales(cashier_id);
CREATE INDEX idx_sales_status_completed ON sales(created_at DESC) WHERE status = 'Completed';

CREATE TABLE sale_items (
  id                          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  sale_id                     UUID NOT NULL REFERENCES sales(id) ON DELETE CASCADE,
  product_id                  UUID REFERENCES products(id) ON DELETE RESTRICT,
  name                        TEXT NOT NULL,                -- snapshot
  quantity                    INTEGER NOT NULL CHECK (quantity > 0),
  unit_price_cents            BIGINT NOT NULL CHECK (unit_price_cents >= 0),
  gross_subtotal_cents        BIGINT NOT NULL CHECK (gross_subtotal_cents = unit_price_cents * quantity),
  allocated_discount_cents    BIGINT NOT NULL DEFAULT 0 CHECK (allocated_discount_cents >= 0),
  net_subtotal_cents          BIGINT NOT NULL CHECK (net_subtotal_cents >= 0),
  allocated_tax_cents         BIGINT NOT NULL DEFAULT 0 CHECK (allocated_tax_cents >= 0),
  total_cents                 BIGINT NOT NULL CHECK (total_cents >= 0),
  sort_order                  INTEGER NOT NULL DEFAULT 0,
  CHECK (gross_subtotal_cents - allocated_discount_cents = net_subtotal_cents),
  CHECK (net_subtotal_cents + allocated_tax_cents = total_cents)
);
CREATE INDEX idx_sale_items_sale ON sale_items(sale_id, sort_order);
CREATE INDEX idx_sale_items_product ON sale_items(product_id);
```

#### 5.3.6 Tabel sale_item_unit_allocations

```sql
CREATE TABLE sale_item_unit_allocations (
  id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  sale_item_id  UUID NOT NULL REFERENCES sale_items(id) ON DELETE CASCADE,
  unit_index    INTEGER NOT NULL CHECK (unit_index >= 0),
  net_cents     BIGINT NOT NULL CHECK (net_cents >= 0),
  tax_cents     BIGINT NOT NULL CHECK (tax_cents >= 0),
  total_cents   BIGINT NOT NULL CHECK (total_cents = net_cents + tax_cents),
  is_refunded   BOOLEAN NOT NULL DEFAULT FALSE,
  refunded_at   TIMESTAMPTZ,
  UNIQUE (sale_item_id, unit_index)
);
CREATE INDEX idx_unit_alloc_sale_item ON sale_item_unit_allocations(sale_item_id, unit_index);
CREATE INDEX idx_unit_alloc_refundable ON sale_item_unit_allocations(sale_item_id)
  WHERE is_refunded = FALSE;
```

#### 5.3.7 Tabel orders dan order_items

```sql
CREATE TABLE orders (
  id                UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  order_code        TEXT NOT NULL UNIQUE,    -- 'ORD-2001' dst
  status            VARCHAR(20) NOT NULL DEFAULT 'Draft'
                    CHECK (status IN ('Draft','Pending','Preparing','Ready','Completed','Cancelled')),
  customer_id       UUID REFERENCES customers(id) ON DELETE RESTRICT,
  customer_name_snapshot TEXT NOT NULL,
  note              TEXT,
  converted_sale_id UUID REFERENCES sales(id) ON DELETE RESTRICT,
  created_by        TEXT NOT NULL REFERENCES users(id),
  created_at        TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at        TIMESTAMPTZ NOT NULL DEFAULT now(),
  completed_at      TIMESTAMPTZ,
  cancelled_at      TIMESTAMPTZ,
  CHECK ((status = 'Completed') = (converted_sale_id IS NOT NULL)),
  CHECK ((status = 'Cancelled') = (cancelled_at IS NOT NULL))
);
CREATE INDEX idx_orders_status_created ON orders(status, created_at DESC);
CREATE INDEX idx_orders_customer ON orders(customer_id) WHERE customer_id IS NOT NULL;

CREATE TABLE order_items (
  id                UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  order_id          UUID NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
  product_id        UUID REFERENCES products(id) ON DELETE RESTRICT,
  name              TEXT NOT NULL,
  quantity          INTEGER NOT NULL CHECK (quantity > 0),
  unit_price_cents  BIGINT NOT NULL CHECK (unit_price_cents >= 0),
  note              TEXT,
  sort_order        INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX idx_order_items_order ON order_items(order_id, sort_order);
```

#### 5.3.8 Tabel returns dan return_items

```sql
CREATE TABLE returns (
  id                UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  return_code       TEXT NOT NULL UNIQUE,
  sale_id           UUID NOT NULL REFERENCES sales(id) ON DELETE RESTRICT,
  status            VARCHAR(20) NOT NULL DEFAULT 'Draft'
                    CHECK (status IN ('Draft','Pending','Completed','Rejected')),
  processor_id      TEXT REFERENCES users(id),
  reason            TEXT,
  subtotal_refund_cents BIGINT NOT NULL DEFAULT 0 CHECK (subtotal_refund_cents >= 0),
  tax_refund_cents      BIGINT NOT NULL DEFAULT 0 CHECK (tax_refund_cents >= 0),
  total_refund_cents    BIGINT NOT NULL DEFAULT 0 CHECK (total_refund_cents >= 0),
  refund_method    VARCHAR(20) CHECK (refund_method IN ('Cash','Card','Digital Wallet','Store Credit')),
  submitted_at     TIMESTAMPTZ,
  completed_at     TIMESTAMPTZ,
  rejected_at      TIMESTAMPTZ,
  created_at       TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at       TIMESTAMPTZ NOT NULL DEFAULT now(),
  CHECK (subtotal_refund_cents + tax_refund_cents = total_refund_cents)
);
CREATE INDEX idx_returns_sale ON returns(sale_id);
CREATE INDEX idx_returns_status_created ON returns(status, created_at DESC);

CREATE TABLE return_items (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  return_id       UUID NOT NULL REFERENCES returns(id) ON DELETE CASCADE,
  sale_item_id    UUID NOT NULL REFERENCES sale_items(id) ON DELETE RESTRICT,
  product_id      UUID REFERENCES products(id) ON DELETE RESTRICT,
  name            TEXT NOT NULL,
  quantity        INTEGER NOT NULL CHECK (quantity > 0),
  selected_units  JSONB NOT NULL DEFAULT '[]'::jsonb, -- [{unitIndex, netCents, taxCents}]
  restock         BOOLEAN NOT NULL DEFAULT FALSE,
  condition       VARCHAR(20) NOT NULL DEFAULT 'Unopened'
                  CHECK (condition IN ('Unopened','Opened','Damaged')),
  refund_subtotal_cents BIGINT NOT NULL CHECK (refund_subtotal_cents >= 0),
  refund_tax_cents      BIGINT NOT NULL CHECK (refund_tax_cents >= 0),
  refund_total_cents    BIGINT NOT NULL CHECK (refund_total_cents >= 0),
  CHECK (refund_subtotal_cents + refund_tax_cents = refund_total_cents)
);
CREATE INDEX idx_return_items_return ON return_items(return_id);
CREATE INDEX idx_return_items_sale_item ON return_items(sale_item_id);
```

#### 5.3.9 Tabel discounts dan discount_usages

```sql
CREATE TABLE discounts (
  id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  code                TEXT NOT NULL UNIQUE,
  name                TEXT NOT NULL,
  description         TEXT,
  type                VARCHAR(20) NOT NULL
                      CHECK (type IN ('Percent','FixedAmount','CategoryPercent')),
  scope               VARCHAR(20) NOT NULL
                      CHECK (scope IN ('All','Category','Product')),
  value_bps           INTEGER CHECK (value_bps >= 0 AND value_bps <= 10000),  -- untuk Percent
  value_cents         BIGINT CHECK (value_cents >= 0),                        -- untuk FixedAmount
  target_category_id  UUID REFERENCES categories(id) ON DELETE RESTRICT,
  target_product_id   UUID REFERENCES products(id) ON DELETE RESTRICT,
  min_subtotal_cents  BIGINT NOT NULL DEFAULT 0,
  max_discount_cents  BIGINT,    -- cap, NULL = tanpa cap
  baseline_usage_count INTEGER NOT NULL DEFAULT 0,   -- metadata historis
  usage_count         INTEGER NOT NULL DEFAULT 0,    -- increment dari transaksi baru
  start_at            TIMESTAMPTZ NOT NULL,
  end_at              TIMESTAMPTZ,
  is_active           BOOLEAN NOT NULL DEFAULT TRUE,
  created_at          TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at          TIMESTAMPTZ NOT NULL DEFAULT now(),
  archived_at         TIMESTAMPTZ,
  CHECK ((type = 'Percent') = (value_bps IS NOT NULL)),
  CHECK ((type = 'FixedAmount') = (value_cents IS NOT NULL)),
  CHECK ((scope = 'Category') = (target_category_id IS NOT NULL)),
  CHECK ((scope = 'Product') = (target_product_id IS NOT NULL))
);
CREATE INDEX idx_discounts_active_dates ON discounts(is_active, start_at, end_at)
  WHERE archived_at IS NULL;

CREATE TABLE discount_usages (
  id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  discount_id   UUID NOT NULL REFERENCES discounts(id) ON DELETE RESTRICT,
  sale_id       UUID NOT NULL REFERENCES sales(id) ON DELETE RESTRICT,
  applied_cents BIGINT NOT NULL CHECK (applied_cents >= 0),
  created_at    TIMESTAMPTZ NOT NULL DEFAULT now(),
  UNIQUE (sale_id)   -- satu discount per sale
);
CREATE INDEX idx_discount_usages_discount ON discount_usages(discount_id);
```

#### 5.3.10 Tabel inventory_movements

```sql
CREATE TABLE inventory_movements (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  movement_id     UUID NOT NULL UNIQUE,    -- idempotency key dari service
  product_id      UUID NOT NULL REFERENCES products(id) ON DELETE RESTRICT,
  type            VARCHAR(30) NOT NULL
                  CHECK (type IN ('SaleDecrement','VoidRestore','ReturnRestock','ManualAdjust')),
  delta_qty       INTEGER NOT NULL,        -- negatif = decrement, positif = increment
  sale_id         UUID REFERENCES sales(id) ON DELETE RESTRICT,
  return_id       UUID REFERENCES returns(id) ON DELETE RESTRICT,
  reason          TEXT,
  created_by      TEXT NOT NULL REFERENCES users(id),
  created_at      TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX idx_inv_mov_product ON inventory_movements(product_id, created_at DESC);
CREATE INDEX idx_inv_mov_sale ON inventory_movements(sale_id) WHERE sale_id IS NOT NULL;
CREATE INDEX idx_inv_mov_return ON inventory_movements(return_id) WHERE return_id IS NOT NULL;
```

#### 5.3.11 Tabel settings (singleton), audit_logs, idempotency_keys, refresh_tokens

```sql
CREATE TABLE settings (
  id              INTEGER PRIMARY KEY DEFAULT 1 CHECK (id = 1),
  -- Store info
  store_name      TEXT NOT NULL DEFAULT 'PosyMart',
  store_address   TEXT,
  store_phone     TEXT,
  store_email     TEXT,
  -- Receipt
  receipt_footer  TEXT,
  receipt_show_cashier BOOLEAN NOT NULL DEFAULT TRUE,
  receipt_show_customer BOOLEAN NOT NULL DEFAULT TRUE,
  -- Tax
  tax_enabled     BOOLEAN NOT NULL DEFAULT TRUE,
  tax_rate_bps    INTEGER NOT NULL DEFAULT 800,
  tax_name        TEXT NOT NULL DEFAULT 'Tax',
  -- Inventory
  track_inventory BOOLEAN NOT NULL DEFAULT TRUE,
  low_stock_threshold INTEGER NOT NULL DEFAULT 5,
  -- Currency & locale
  currency_code   TEXT NOT NULL DEFAULT 'USD',
  currency_symbol TEXT NOT NULL DEFAULT '$',
  timezone        TEXT NOT NULL DEFAULT 'Asia/Jakarta',
  -- Theme (untuk konsistensi frontend)
  theme_mode      VARCHAR(20) NOT NULL DEFAULT 'Light',
  accent_color    TEXT NOT NULL DEFAULT 'teal',
  -- Versioning untuk optimistic locking
  version         INTEGER NOT NULL DEFAULT 1,
  updated_by      TEXT REFERENCES users(id),
  updated_at      TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE audit_logs (
  id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id       TEXT REFERENCES users(id) ON DELETE SET NULL,
  action        VARCHAR(50) NOT NULL,   -- 'sale.pay', 'sale.void', 'return.approve', dst.
  target_type   VARCHAR(30) NOT NULL,   -- 'sale', 'return', 'product', 'settings', dst.
  target_id     TEXT,
  before_data   JSONB,
  after_data    JSONB,
  trace_id      TEXT,
  ip_address    INET,
  created_at    TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX idx_audit_user_created ON audit_logs(user_id, created_at DESC);
CREATE INDEX idx_audit_target ON audit_logs(target_type, target_id, created_at DESC);

CREATE TABLE idempotency_keys (
  key           TEXT PRIMARY KEY,
  user_id       TEXT REFERENCES users(id) ON DELETE CASCADE,
  request_hash  TEXT NOT NULL,
  response_status INTEGER NOT NULL,
  response_body JSONB,
  expires_at    TIMESTAMPTZ NOT NULL,
  created_at    TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX idx_idem_expires ON idempotency_keys(expires_at);

CREATE TABLE refresh_tokens (
  id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id       TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  token_hash    TEXT NOT NULL UNIQUE,    -- bcrypt hash
  device_info   TEXT,
  expires_at    TIMESTAMPTZ NOT NULL,
  revoked_at    TIMESTAMPTZ,
  created_at    TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX idx_refresh_tokens_user ON refresh_tokens(user_id) WHERE revoked_at IS NULL;
```

### 5.4 Strategi Index

Strategi index dirancang berdasarkan pola query yang paling sering muncul di sepuluh modul. Berikut adalah justifikasi index utama:

- idx_sales_created_status: index komposit untuk Sales list page dengan filter date + status. Descending created_at karena UI menampilkan terbaru di atas.

- idx_sales_status_completed: partial index hanya untuk status=Completed. Mempercepat query Reports yang sering memfilter Completed saja. Ukuran index kecil karena exclude Voided.

- idx_unit_alloc_refundable: partial index WHERE is_refunded=FALSE. Mempercepat validasi refundable units saat create return.

- idx_audit_target: index komposit untuk query audit by target (e.g. "tampilkan semua audit log untuk sale TX-1001").

- GIN index pada sale_items tidak digunakan karena unit_allocations di-normalize ke tabel terpisah. JSONB GIN hanya digunakan pada audit_logs.before_data dan after_data untuk query key-specific.

- Covering index untuk Reports daily summary: CREATE INDEX idx_sales_daily ON sales((created_at::date), status) INCLUDE (subtotal_cents, tax_cents, total_cents) WHERE status='Completed'; — memungkinkan query agregasi harian tanpa touching heap.

### 5.5 Rencana Migration

Migration menggunakan golang-migrate dengan file SQL murni di direktori db/migrations/. Setiap migration memiliki versi 4-digit dan deskripsi singkat. Berikut adalah urutan migration awal:

Tabel 5-2 Daftar Migration Awal

| Versi | File | Isi |
| --- | --- | --- |
| 0001 | 0001_init.up.sql / .down.sql | Skema awal: seluruh 18 tabel, index, constraint, CHECK invariant. |
| 0002 | 0002_triggers.up.sql / .down.sql | Trigger updated_at otomatis, trigger maintain customers.total_spent_cents dan total_orders. |
| 0003 | 0003_seed_users.up.sql / .down.sql | Seed 6 DemoUser: user-john-doe, user-avery-morgan, user-jordan-lee, user-taylor-reed, user-casey-brooks, user-riley-parker. Password default 'posymart-demo' (bcrypt hash). |
| 0004 | 0004_seed_master.up.sql / .down.sql | Seed categories (Coffee, Food, Drinks), products (8 item demo: Cappuccino, Latte, Chocolate Cake, Sandwich, dst), settings singleton row. |
| 0005 | 0005_seed_ledger.up.sql / .down.sql | Seed 44 transaksi historis Completed Oct 04–09 + 6 arsip + 6 record Today + 5 return requests + 5 orders, sesuai design.md 26.2. |
| 0006 | 0006_seed_baseline_usage.up.sql / .down.sql | Set discounts.baseline_usage_count = 140 untuk discount COFFEE1. Tidak menambah record transaksi. |

Prinsip migration: (1) Setiap migration harus reversible — .down.sql wajib ada dan teruji. (2) Expand-contract pattern untuk perubahan skema: tambah kolom dulu, deploy code yang menulis BOTH old+new, lalu migrate data, lalu hapus kolom lama. (3) Tidak ada migration yang menghapus data tanpa review manual. (4) CI menjalankan migrate up + down + up pada database kosong sebagai smoke test. (5) Migration besar (>1 juta row affected) dipecah menjadi batch dengan SELECT ... LIMIT dan UPDATE ... WHERE id BETWEEN, untuk menghindari lock table lama.

## 6. Algoritma Alokasi Uang

Algoritma alokasi uang menjaga agar total refund tidak melebihi total Sale asli. Bagian ini memuat urutan perhitungan, metode largest remainder, alokasi per unit, invariant, serta contoh pseudocode dari PRD Word. Pseudocode merupakan bahan implementasi yang masih perlu diuji; khusus alokasi integer dan contoh Bagel, gunakan aturan serta ralat yang dijelaskan di bawah.

### 6.1 Urutan Perhitungan Sale

Perhitungan total Sale mengikuti urutan enam langkah berikut, sesuai design.md 26.4. Setiap langkah bersifat deterministic dan tidak boleh diubah urutannya:

```text
// 1. Subtotal bruto item
grossSubtotal = sum(unitPriceCents * quantity for each item)

// 2. Discount (satu per sale, non-stacking)
eligibleSubtotal = sum(item.grossSubtotal for item where isEligible(discount, item))
rawDiscount      = computeDiscountAmount(discount, eligibleSubtotal)
discountCents    = min(rawDiscount, eligibleSubtotal)   // tidak boleh negatif atau melebihi eligible

// 3. Taxable subtotal
taxableSubtotal = grossSubtotal - discountCents

// 4. Tax (dibulatkan SEKALI per transaksi, bukan per item)
taxCents = roundTaxOnce(taxableSubtotal, taxRateBps)

// 5. Total
totalCents = taxableSubtotal + taxCents

// 6. Reports menjumlahkan nominal TERSIMPAN, tidak recompute tax dari agregat mingguan
```

Fungsi computeDiscountAmount bergantung pada tipe discount: (a) Percent: discountCents = round(eligibleSubtotal × valueBps / 10000); (b) FixedAmount: discountCents = min(valueCents, eligibleSubtotal); (c) CategoryPercent: sama dengan Percent tetapi eligibleSubtotal hanya dari item pada kategori target. roundTaxOnce menggunakan pembulatan HALF_UP (round half away from zero) pada level sen, sesuai konvensi POS.

### 6.2 Alokasi Discount per Item (Largest Remainder)

Discount total dialokasikan ke setiap item eligible secara proporsional terhadap subtotal bruto. Jumlah alokasi wajib tepat sama dengan discount total. Implementasi harus memakai aritmetika integer: hitung quotient dan remainder dari totalCents × weight / totalWeight, distribusikan sisa sen menurut remainder descending dan stable item ID ascending. Gunakan perantara yang cukup besar atau pemeriksaan overflow. Bila totalWeight = 0 dan totalCents = 0, hasil seluruhnya nol; bila totalWeight = 0 tetapi totalCents > 0, tolak sebagai input tidak valid. Pseudocode Go berikut dipertahankan dari Word untuk penelusuran, tetapi masih menggunakan float64, tie-break index, dan cabang zero-weight yang perlu diganti sebelum implementasi.

```go
// AllocateByLargestRemainder membagi totalCents ke items secara proporsional
// terhadap weight[i]. Invariant: sum(result) == totalCents.
//
// Tie-breaker untuk sisa sen: weight fractional tertinggi lebih dulu,
// tie weight sama → index item ascending.
func AllocateByLargestRemainder(weights []int64, totalCents int64) []int64 {
    n := len(weights)
    if n == 0 || totalCents == 0 {
        out := make([]int64, n)
        return out
    }
    totalWeight := int64(0)
    for _, w := range weights {
        totalWeight += w
    }
    if totalWeight == 0 {
        // semua weight 0 → distribusi merata
        each := totalCents / int64(n)
        out := make([]int64, n)
        for i := range out { out[i] = each }
        // sisa sen ke item terakhir (tie-break index descending)
        remainder := totalCents - each*int64(n)
        out[n-1] += remainder
        return out
    }

    // Tahap 1: floor allocation
    allocations := make([]int64, n)
    remainders := make([]float64, n)
    for i, w := range weights {
        exact := float64(totalCents) * float64(w) / float64(totalWeight)
        floor := int64(math.Floor(exact))
        allocations[i] = floor
        remainders[i] = exact - float64(floor)
    }

    // Tahap 2: distribusikan sisa sen ke remainder tertinggi
    allocated := int64(0)
    for _, a := range allocations { allocated += a }
    remainder := totalCents - allocated

    // Urutkan index berdasarkan remainder descending, tie-breaker index ascending
    idx := make([]int, n)
    for i := range idx { idx[i] = i }
    sort.SliceStable(idx, func(a, b int) bool {
        if remainders[idx[a]] != remainders[idx[b]] {
            return remainders[idx[a]] > remainders[idx[b]]
        }
        return idx[a] < idx[b] // tie-break: index ascending
    })

    for i := int64(0); i < remainder; i++ {
        allocations[idx[i]]++
    }
    return allocations
}
```

Penggunaan untuk alokasi discount: weights[i] = item.grossSubtotalCents (hanya item eligible), totalCents = discountCents. Item tidak eligible mendapat allocatedDiscountCents = 0. Invariant yang harus dipenuhi: sum(item.allocatedDiscountCents) == sale.discountCents, dan setiap item.allocatedDiscountCents <= item.grossSubtotalCents.

### 6.3 Alokasi Tax per Item

Setelah discount dialokasikan, tax total harus dialokasikan ke setiap item secara proporsional terhadap net value item (gross − discount). Algoritma sama dengan 6.2 tetapi weight yang digunakan adalah netSubtotalCents. Alasan: item dengan discount lebih besar memiliki net lebih kecil, sehingga tax yang dialokasikan juga lebih kecil. Ini konsisten dengan contoh COFFEE1 pada design.md 26.4: Cappuccino bruto 350 sen, discount 100 sen, net 250 sen, tax 20 sen (8% dari 250).

```text
// Alur alokasi tax:
// 1. Hitung netSubtotal per item
for i, item := range items {
    item.netSubtotalCents = item.grossSubtotalCents - item.allocatedDiscountCents
}

// 2. Alokasikan tax total berdasarkan net subtotal
weights := make([]int64, len(items))
for i, item := range items {
    weights[i] = item.netSubtotalCents
}
taxAllocations := AllocateByLargestRemainder(weights, sale.taxCents)
for i, t := range taxAllocations {
    items[i].allocatedTaxCents = t
}
```

### 6.4 Alokasi per Unit (UnitAllocations)

Setiap SaleItem dengan quantity N harus memiliki array unitAllocations berisi N entri, masing-masing menyimpan alokasi netCents dan taxCents untuk unit ke-i. Tujuannya: ketika hanya sebagian unit direfund (partial return), backend dapat menjumlahkan alokasi unit yang dipilih tanpa recompute dari harga produk. Aturan alokasi per unit sesuai design.md 26.4: tie-breaker item ID ascending, tie-breaker unit index descending (sisa sen masuk unit terakhir lebih dulu).

```go
// AllocateUnits membagi itemTotalCents ke n unit.
// Tie-break: unit index DESCENDING untuk sisa sen (unit terakhir dapat sisa).
func AllocateUnits(n int, itemTotalCents int64) []int64 {
    each := itemTotalCents / int64(n)
    remainder := itemTotalCents - each*int64(n)

    out := make([]int64, n)
    for i := range out { out[i] = each }

    // Distribusikan remainder ke unit TERAKHIR lebih dulu (index descending)
    for i := int64(0); i < remainder; i++ {
        out[n-1-int(i)]++
    }
    return out
}

// BuildUnitAllocations membangun array alokasi per unit untuk sebuah SaleItem.
func BuildUnitAllocations(item SaleItem) []UnitAllocation {
    n := item.Quantity
    nets := AllocateUnits(n, item.netSubtotalCents)
    taxes := AllocateUnits(n, item.allocatedTaxCents)

    out := make([]UnitAllocation, n)
    for i := 0; i < n; i++ {
        out[i] = UnitAllocation{
            UnitIndex: i,
            NetCents:  nets[i],
            TaxCents:  taxes[i],
            TotalCents: nets[i] + taxes[i],
        }
    }
    return out
}
```

### 6.5 Contoh Perhitungan TX-1001 (Bagel)

Contoh TX-1001 sesuai design.md: Cappuccino 350 × 1, Bagel 280 × 2, Caesar Salad 650 × 1, dan Orange Juice 290 × 1. Subtotal = 1850 sen, discount = 0, dan pajak transaksi 8% = 148 sen. Pajak dialokasikan ke item berdasarkan net: Cappuccino 28, Bagel 45, Caesar Salad 52, Orange Juice 23 sen. Hasil floor awal [28, 44, 52, 23] berjumlah 147; satu sen tersisa diberikan kepada Bagel yang memiliki remainder terbesar 0.8. Tidak diperlukan perubahan harga, tarif khusus, atau pemaksaan snapshot agar hasil ini benar.

Bagel berharga 280 sen per unit dengan quantity 2: net item 560 sen, alokasi pajak item 45 sen, total item 605 sen. Dengan tie-break unit index descending, unit 0 menyimpan net 280 + tax 22 = total 302 sen; unit 1 menyimpan net 280 + tax 23 = total 303 sen. Refund terpisah menjadi $3.02 dan $3.03, total $6.05. Refund seluruh TX-1001 tetap net $18.50 + tax $1.48 = $19.98. Harga satuan 350 atau 302.5 sen dalam contoh Word diganti karena tidak sesuai fixture.

### 6.6 Invariant Alokasi (Wajib Diverifikasi)

Berikut adalah invariant yang harus dipenuhi setiap Sale tersimpan. Setiap invariant diverifikasi via CHECK constraint di database dan integration test:

Tabel 6-1 Invariant Alokasi Uang

| # | Invariant | Verifikasi |
| --- | --- | --- |
| I1 | sum(item.grossSubtotalCents) == sale.subtotalCents | CHECK constraint pada sale (subquery) + integration test. |
| I2 | sum(item.allocatedDiscountCents) == sale.discountCents | Integration test post-check. |
| I3 | item.grossSubtotalCents - item.allocatedDiscountCents == item.netSubtotalCents | CHECK constraint pada sale_items. |
| I4 | sum(item.allocatedTaxCents) == sale.taxCents | Integration test post-check. |
| I5 | item.netSubtotalCents + item.allocatedTaxCents == item.totalCents | CHECK constraint pada sale_items. |
| I6 | sum(item.totalCents) == sale.totalCents | Integration test post-check. |
| I7 | sum(unit.netCents) == item.netSubtotalCents | Integration test post-check. |
| I8 | sum(unit.taxCents) == item.allocatedTaxCents | Integration test post-check. |
| I9 | sum(unit.totalCents) == item.totalCents | CHECK constraint pada sale_item_unit_allocations + integration test. |
| I10 | sale.subtotalCents - sale.discountCents == sale.taxableSubtotalCents | CHECK constraint pada sales. |
| I11 | sale.taxableSubtotalCents + sale.taxCents == sale.totalCents | CHECK constraint pada sales. |
| I12 | Untuk refund: sum(return_item.refund_total_cents) <= sale.total_cents | Integration test pre-check + trigger guard. |
| I13 | Untuk setiap SaleItem: sum(unit_refunded.total) <= item.totalCents | Integration test + CHECK via trigger. |

### 6.7 Algoritma Refund

Saat Return Completed, backend menjumlahkan alokasi unit yang dipilih pada setiap ReturnItem. Penting: backend TIDAK menghitung ulang pajak dari harga produk atau tarif pajak terkini. Refund menggunakan snapshot alokasi yang tersimpan pada saat Sale dibuat. Pseudocode:

**Catatan refund Walk-in:** perhitungan dan penyelesaian Return tetap berjalan bila Customer ID null. Hanya pembaruan agregat Customer yang dilewati. Nilai refund wajib berasal dari snapshot server, bukan nominal yang dipercaya langsung dari request klien.

```text
// Pada POST /returns/{id}/process-refund:
// 1. Mulai transaction
tx := db.Begin()
defer tx.Rollback()

// 2. Lock return row
ret, err := repo.LockReturn(tx, returnID)
if ret.Status != "Pending" {
    return ErrInvalidState("return must be Pending")
}

// 3. Untuk setiap ReturnItem, validasi dan kalkulasi refund
for _, ri := range ret.Items {
    saleItem, err := repo.GetSaleItem(tx, ri.SaleItemID, ForUpdate)
    // Validasi: unit yang dipilih belum direfund
    for _, sel := range ri.SelectedUnits {
        unit := findUnit(saleItem.UnitAllocations, sel.UnitIndex)
        if unit.IsRefunded {
            return ErrUnitAlreadyRefunded(sel.UnitIndex)
        }
        // Tandai unit sebagai refunded
        repo.MarkUnitRefunded(tx, unit.ID, now)
    }
    // refund_total sudah disimpan saat return dibuat, tidak recompute
}

// 4. Update return status ke Completed
repo.UpdateReturnStatus(tx, returnID, "Completed", now)

// 5. Restock untuk unit dengan Restock=true & Condition=Unopened
if settings.TrackInventory {
    for _, ri := range ret.Items {
        if ri.Restock && ri.Condition == "Unopened" {
            movementID := uuid.New()  // idempotency key
            repo.InsertInventoryMovement(tx, InventoryMovement{
                MovementID: movementID,
                ProductID:  ri.ProductID,
                Type:       "ReturnRestock",
                DeltaQty:   ri.Quantity,
                ReturnID:   returnID,
                Reason:     fmt.Sprintf("Return %s restock", ret.ReturnCode),
            })
            repo.AdjustProductStock(tx, ri.ProductID, +ri.Quantity)
        }
    }
}

// 6. Audit log
repo.InsertAuditLog(tx, AuditLog{
    UserID:    currentUser.ID,
    Action:    "return.complete",
    TargetType: "return",
    TargetID:  returnID,
    AfterData: ret,
})

// 7. Update customers.total_spent_cents (kurangi)
if sale.CustomerID != nil {
    repo.AdjustCustomerTotalSpent(tx, *sale.CustomerID, -ret.TotalRefundCents)
}

tx.Commit()
```

Idempotency: jika klien retry POST /returns/{id}/process-refund dengan Idempotency-Key yang sama, backend mengembalikan response asli dari request pertama tanpa menjalankan ulang efek samping. Mekanisme ini dijamin oleh tabel idempotency_keys yang mengecek key sebelum eksekusi.

## 7. Alur Bisnis Inti

Bagian ini mendefinisikan tujuh alur bisnis utama PosyMart, diterjemahkan dari bagian 26.5 design.md ke kontrak backend yang konkret. Setiap alur mencantumkan endpoint yang dipanggil, transisi state, validasi yang dijalankan, efek samping pada tabel terkait, dan kondisi error yang harus ditangani klien. Pseudocode singkat disertakan untuk alur yang melibatkan banyak langkah.

### 7.1 Alur A — Melakukan Sale Baru

Kasir yang sudah login membuat draft sale, menambahkan item, memilih customer hanya bila diperlukan, lalu menyelesaikan pembayaran. Draft baru default customerId = null dan ditampilkan sebagai Walk-in Customer. Tidak ada form registrasi, email, nomor telepon, password, atau OTP pembeli yang wajib diisi. Memilih promo umum juga tidak mewajibkan Customer ID. Draft disimpan di Redis (key draft:{userId}, TTL 1 jam sesuai rancangan awal). Saat pay berhasil, Sale tersimpan di PostgreSQL; draft baru dibersihkan setelah commit berhasil. Redis TTL merupakan batas pemulihan draft, bukan jaminan draft tersimpan tanpa batas.

#### 7.1.1 Endpoint yang Dilibatkan

- POST /api/v1/sales/draft — buat draft baru (atau ambil draft existing).

- POST /api/v1/sales/draft/items — tambah item ke draft. Body: {productId, quantity, note?}.

- PATCH /api/v1/sales/draft/items/{itemId} — update qty atau note.

- DELETE /api/v1/sales/draft/items/{itemId} — hapus item dari draft.

- POST /api/v1/sales/draft/customer — memilih atau melepas profil pelanggan. Body {"customerId":"<customer UUID>"} untuk memilih, atau {"customerId":null} untuk Walk-in. Tidak perlu memanggil endpoint ini pada draft baru yang sudah default Walk-in. JSON {null} bukan payload valid.

- POST /api/v1/sales/draft/discount — apply discount. Body: {discountCode}.

- DELETE /api/v1/sales/draft/discount — hapus discount dari draft.

- POST /api/v1/sales/draft/pay — eksekusi pembayaran simulasi versi awal. Body mengikuti kontrak detail: {"payment_method":"Cash"}, atau metode aktif lain. Idempotency-Key dikirim pada header. Customer dibaca dari draft dan boleh null. Response: SaleRecord lengkap.

#### 7.1.2 Validasi saat Pay

- Draft tidak kosong (minimal 1 item dengan quantity > 0).

- Jika Settings.trackInventory = true: setiap item.quantity <= product.stock_qty dan product.status = Active. Jika gagal, return 409 dengan detail {productId, availableQty}.

- Jika discount dipilih: discount.is_active = true, start_at <= now <= end_at (jika end_at tidak null), dan usage_count < max_usage (jika ada).

- Jika customerId null, lewati validasi profil dan simpan customer_name_snapshot = Walk-in Customer; jangan membuat record Customer. Jika customerId diisi, pastikan profil ditemukan dan tidak diarsipkan. Jika profil tidak valid, tawarkan pilih profil lain atau Continue as Walk-in, lalu evaluasi ulang promo dan total sebelum checkout.

- Cashier (current user) is_active = true dan role = Cashier/Manager/Admin.

#### 7.1.3 Efek Samping saat Pay Berhasil

Semua efek samping dijalankan dalam satu transaction PostgreSQL dengan isolation READ COMMITTED, menggunakan SELECT FOR UPDATE pada products untuk cegah race condition stok:

```text
// Pseudocode Pay Sale:
tx := db.Begin()
// 1. Lock products
for _, item := range draft.Items {
    p := repo.GetProductForUpdate(tx, item.ProductID)
    if settings.TrackInventory && p.StockQty < item.Quantity {
        return ErrInsufficientStock(p.ID, p.StockQty)
    }
}
// 2. Generate tx_code
txCode := generateTxCode()  // format TX-{year}{seq}, e.g. TX-1144
// 3. Insert sale (status=Completed, snapshot customerName, cashierName, taxRateBps)
sale := buildSaleFromDraft(draft, txCode, currentUser, settings)
repo.InsertSale(tx, sale)
// 4. Insert sale_items + unit_allocations
for _, item := range sale.Items {
    repo.InsertSaleItem(tx, item)
    for _, u := range item.UnitAllocations {
        repo.InsertUnitAllocation(tx, item.ID, u)
    }
}
// 5. Decrement stock + insert inventory_movements
if settings.TrackInventory {
    for _, item := range sale.Items {
        movementID := uuid.New()
        repo.InsertInventoryMovement(tx, {
            MovementID: movementID, ProductID: item.ProductID,
            Type: "SaleDecrement", DeltaQty: -item.Quantity, SaleID: sale.ID,
        })
        repo.DecrementProductStock(tx, item.ProductID, item.Quantity)
    }
}
// 6. Insert discount_usage jika ada
if sale.DiscountID != nil {
    repo.InsertDiscountUsage(tx, sale.DiscountID, sale.ID, sale.DiscountCents)
    repo.IncrementDiscountUsageCount(tx, sale.DiscountID)
}
// 7. Update customer total_spent_cents dan total_orders
if sale.CustomerID != nil {
    repo.AdjustCustomerTotals(tx, sale.CustomerID, +sale.TotalCents, +1)
}
// 8. Audit log
repo.InsertAuditLog(tx, {Action: "sale.pay", TargetType: "sale", TargetID: sale.ID, ...})
// 9. Commit lebih dahulu; bila gagal, draft tidak dihapus
if err := tx.Commit(); err != nil { return nil, err }
// 10. Bersihkan versi draft yang dibayar setelah commit berhasil.
// Cleanup gagal boleh diulang; Sale yang sudah commit tidak dibuat ulang.
// Guard draft/operation yang persisten dibahas pada bagian 15.
redis.Del(ctx, "draft:"+userID)
return sale, nil
```

#### 7.1.4 Kondisi Error

Tabel 7-1 Error pada Pay Sale

| HTTP | Error Code | Penyebab | Aksi Klien |
| --- | --- | --- | --- |
| 409 | POS-010 | Stok tidak mencukupi untuk satu atau lebih item. | Tampilkan item dan sisa qty; minta user edit qty atau hapus item. |
| 409 | POS-011 | Produk sudah Inactive atau di-archived. | Hapus item dari draft; tampilkan pesan. |
| 409 | POS-040 | Discount sudah expired atau tidak aktif. | Hapus discount dari draft; tampilkan pesan. |
| 409 | POS-041 | Discount usage sudah mencapai max. | Hapus discount dari draft; tampilkan pesan. |
| 422 | POS-001 | Draft kosong (tidak ada item). | Tampilkan pesan 'tambah minimal 1 item'. |
| 409 | POS-050 | Idempotency-Key sudah digunakan dengan payload berbeda. | Gunakan key baru atau batalkan operasi. |
| 500 | POS-500 | Internal error (DB, Redis, dll). | Retry dengan Idempotency-Key yang sama; jika persisten, hubungi ops. |

### 7.2 Alur B — Mengelola Produk

Product management adalah CRUD standar dengan beberapa twist: (1) Soft delete via archived_at, bukan hard delete, untuk menjaga referensi historis sale_items. (2) Adjust stock adalah endpoint terpisah dari PATCH product, karena menulis ke inventory_movements dengan alasan. (3) Perubahan harga hanya berlaku untuk sale baru; sale historis tetap menggunakan snapshot.

#### 7.2.1 Endpoint Product

Tabel 7-2 Endpoint Product

| Method | Path | Role | Deskripsi |
| --- | --- | --- | --- |
| GET | /products?status=&category_id=&q=&page=&limit= | All | List dengan filter, search, pagination cursor. |
| POST | /products | Admin, Manager | Buat produk baru. Body: {sku?, name, description?, categoryId, priceCents, costCents?, taxRateBps?, status?, stockQty?, trackStock?, imageUrl?}. |
| GET | /products/{id} | All | Detail produk termasuk current stock dan last 5 movements. |
| PATCH | /products/{id} | Admin, Manager | Update field non-stok. Body parsial. |
| POST | /products/{id}/adjust-stock | Admin, Manager | Body: {deltaQty, reason}. Insert inventory_movements type=ManualAdjust. |
| POST | /products/{id}/archive | Admin, Manager | Soft delete: set archived_at = now(). Tidak boleh jika ada draft sale yang mengandung produk ini. |
| POST | /products/{id}/restore | Admin, Manager | Unarchive: set archived_at = NULL. Tidak mengembalikan ke Active otomatis. |

### 7.3 Alur C — Mengelola Pelanggan

Menu Customers mengelola profil pembeli yang ingin disimpan, bukan syarat memesan atau membayar. Pembelian biasa menggunakan customerId = null pada Sale maupun Order, tanpa membuat record Walk-in bersama. Profil dapat dipilih atau dibuat secara opsional; pembuatan profil tidak membuat akun Users dan tidak otomatis membuat member. Saat menyimpan profil melalui UI, nama serta minimal satu kontak telepon/email mengikuti validasi design.md; persyaratan ini hanya berlaku ketika pengguna sengaja membuat profil. Agregat total spent dan total orders hanya diperbarui jika Sale mempunyai customerId. Gunakan tepat satu mekanisme pembaruan agregat agar trigger dan service tidak menghitung dua kali. Registrasi keanggotaan adalah perluasan terpisah, dan menolak registrasi tetap memungkinkan checkout Walk-in.

#### 7.3.1 Endpoint Customer

Tabel 7-3 Endpoint Customer

| Method | Path | Role | Deskripsi |
| --- | --- | --- | --- |
| GET | /customers?q=&page=&limit= | All | Search by name/email/phone. |
| POST | /customers | All | Membuat profil opsional. Body: {name, email?, phone?, address?, note?}; nama wajib dan minimal email atau phone untuk profil tersimpan sesuai design.md. Endpoint ini tidak dipanggil otomatis oleh Pay. |
| GET | /customers/{id} | All | Detail + total spent + total orders (dari kolom denormalized). |
| GET | /customers/{id}/activity?page=&limit= | All | List sales + returns untuk customer ini. |
| PATCH | /customers/{id} | All | Update field. Email unique. |
| POST | /customers/{id}/archive | Admin, Manager | Soft delete. Sale historis tetap terbaca via snapshot customerName. |

### 7.4 Alur D — Menyelesaikan Order

Order adalah pesanan yang akan diproses, biasanya untuk dine-in atau takeaway dengan nomor antrean. customerId opsional dan default null; label pesanan, nomor meja, atau nama panggilan tidak otomatis membuat Customer. State machine versi awal: Draft → Pending → Preparing → Ready → Completed atau Cancelled. Pembayaran dilakukan saat penyelesaian; Order Completed menyimpan satu convertedSaleId. Klik Complete Order kedua mengembalikan Sale existing. Untuk kafe yang membayar sebelum persiapan tetapi ingin tetap melacak antrean barista, pisahkan payment status dan fulfillment status sebagai pengembangan model; state machine tunggal ini belum mendukung alur tersebut secara penuh.

#### 7.4.1 Transisi State Order

**Batas alur:** Order versi awal dibayar saat penyelesaian. Antrean persiapan sesudah pembayaran membutuhkan pemisahan status pembayaran/persiapan seperti dijelaskan pada panduan alur bisnis.

```text
State machine Order:
  Draft    -> Pending    (action: submit, by: Cashier+)
  Pending  -> Preparing  (action: start-prepare, by: Cashier+)
  Preparing -> Ready     (action: mark-ready, by: Cashier+)
  Ready    -> Completed  (action: complete, by: Cashier+)
              └──> trigger: buat Sale baru dari OrderItems (convertedSaleId)
  Draft    -> Cancelled  (action: cancel, by: Cashier+)
  Pending  -> Cancelled  (action: cancel, by: Cashier+)
  Preparing -> Cancelled (action: cancel, by: Manager+) -- setelah mulai prep, perlu Manager
  Ready    -> Cancelled  (action: cancel, by: Manager+) -- tidak biasa, perlu approval

Aturan:
  - convertedSaleId hanya diisi saat transisi ke Completed
  - Jika tombol Complete ditekan lagi, return Sale existing (HTTP 200, bukan 201)
  - Cancelled tidak menghasilkan Sale
  - Order tidak masuk Sales/Reports sampai Completed
```

#### 7.4.2 Endpoint Order

Tabel 7-4 Endpoint Order

| Method | Path | Role | Deskripsi |
| --- | --- | --- | --- |
| GET | /orders?status=&customer_id=&date_from=&date_to= | All | List order dengan filter. |
| POST | /orders | All | Body: {customerId?, items[], note?}. Customer ID boleh omitted/null; default Walk-in. Status awal Draft. Field fulfillment/meja/label pesanan perlu diselaraskan dengan design.md. |
| GET | /orders/{id} | All | Detail order + items + converted sale jika ada. |
| PATCH | /orders/{id}/items | All | Update items hanya jika status=Draft. |
| POST | /orders/{id}/transition | All / Manager | Body: {action: 'submit'\|'start-prepare'\|'mark-ready'\|'complete'\|'cancel'}. Validasi state machine. |

### 7.5 Alur E — Pengembalian (Return)

Return adalah permintaan pengembalian dari Sale Completed yang belum Voided. Alur: Draft → Pending → Completed setelah proses refund berhasil, atau Rejected. Pencarian memakai Sale ID/nomor transaksi/struk; pembeli Walk-in tidak perlu mendaftar. Unit yang sudah direfund tidak boleh dipilih kembali. Return mengikuti customerId Sale sumber; pengurangan customer.total_spent_cents hanya dilakukan bila ID tersebut tidak null. Refund summary Sales dan Reports diperbarui untuk semua Return Completed, termasuk Walk-in.

#### 7.5.1 Endpoint Return

Tabel 7-5 Endpoint Return

| Method | Path | Role | Deskripsi |
| --- | --- | --- | --- |
| GET | /returns?status=&date_from=&date_to= | All | List return dengan filter. |
| POST | /returns | All | Body: {saleId}. Buat return baru dengan status Draft. |
| GET | /returns/{id} | All | Detail return + items + sale sumber. |
| POST | /returns/{id}/items | All | Body: {saleItemId, selectedUnits[], restock, condition}. Hanya saat status=Draft. |
| POST | /returns/{id}/submit | All | Draft → Pending. Validasi semua item dan unit. |
| POST | /returns/{id}/approve | Manager, Admin | Pending → tunggu process-refund. |
| POST | /returns/{id}/reject | Manager, Admin | Body: {reason}. Pending → Rejected. |
| POST | /returns/{id}/process-refund | Manager, Admin | Body: {refundMethod, idempotencyKey}. Eksekusi restock + update customer totals. |

### 7.6 Alur F — Inventory, Void, dan Restock

Inventory management adalah cross-cutting concern yang dipengaruhi oleh Sale pay (decrement), Void (restore), dan Return complete (restock conditional). Setiap perubahan stok dicatat di inventory_movements dengan movement_id UUID v7 idempotent. Track inventory toggle di Settings mengontrol apakah stok dibatasi pada checkout dan apakah auto-decrement berlaku.

#### 7.6.1 Aturan Inventory

- Track inventory ON: checkout memvalidasi qty ≤ stock_qty. Sale Completed mengurangi stok. Void mengembalikan stok. Return Completed dengan Restock=true & Condition=Unopened mengembalikan stok.

- Track inventory OFF: checkout tidak memvalidasi stok. Sale Completed tidak mengurangi stok. Void tidak mengembalikan. Return Completed tidak mengembalikan. Stok hanya berubah via ManualAdjust.

- Inventory movement ID idempotent: request dengan movement_id yang sama return cached result, tidak insert duplikat.

- Seed stok awal 280 adalah jumlah stok seluruh 12 produk; stok Cappuccino adalah 42. Angka awal sudah mencerminkan semua transaksi seed. Jangan replay movement seed saat reload.

#### 7.6.2 Alur Void Sale

**Klarifikasi stok saat Void:** pemulihan harus mengikuti movement pengurangan stok Sale asal yang benar-benar ada dan belum dibalik, bukan hanya nilai toggle tracking saat Void. Pseudocode sumber di bawah belum menggambarkan guard tersebut secara lengkap.

```text
// POST /api/v1/sales/{id}/void
// Body: {reason, idempotencyKey}
// Role: Admin, Manager
// Validasi:
//   - Sale.status == "Completed"
//   - Tidak ada Return dengan status "Pending" atau "Completed" untuk sale ini
// Efek samping dalam transaction:
//   1. Update sales: status="Voided", voided_at=now(), voided_by=userID, void_reason
//   2. Jika trackInventory: insert inventory_movements type=VoidRestore, delta=+qty per item
//      Update products.stock_qty += qty
//   3. Jika sale punya discount_usage: hapus record DAN decrement discount.usage_count
//      (void membatalkan redemption)
//   4. Jika customer_id != null: customer.total_spent_cents -= sale.total_cents
//                              customer.total_orders -= 1
//   5. Insert audit_log {action: "sale.void", target_type: "sale", target_id, before, after}
// Response: Sale updated dengan status Voided
```

### 7.7 Alur G — Discount, Akun Demo, dan Settings

#### 7.7.1 Discount

Discount aktif dapat dipilih dari draft sale sesuai produk, tanggal, minimum belanja, dan batas penggunaan. Cakupan All customers mencakup Walk-in. Selected customers membutuhkan profil yang eligible, tetapi pembeli tetap dapat melanjutkan tanpa promo itu. Nama promo MEMBER5 tidak membuktikan keanggotaan sudah diterapkan; pada design.md itu promo Selected customers yang Disabled. Mengganti/melepas customer mengevaluasi ulang eligibility dan menampilkan total baru. Satu discount per sale; discountCents dibatasi eligibleSubtotal. Periode aktif mengikuti design.md: start inklusif dan end eksklusif.

#### 7.7.2 Users dan Permissions

Users adalah akun staf, bukan akun pelanggan. Backend menerapkan autentikasi dan RBAC nyata bagi staf; pelanggan Walk-in tidak membutuhkan akun. Users management: CRUD dengan validasi (a) tidak boleh menonaktifkan current user, (b) tidak boleh menonaktifkan Admin terakhir yang aktif, (c) tidak boleh mengubah role Admin terakhir ke role lain. Validasi ini dijalankan di service layer dan di-guard oleh integration test. Pengiriman undangan email tetap di luar integrasi versi awal. Perilaku simulasi role pada frontend tidak menggantikan pemeriksaan izin di backend.

#### 7.7.3 Settings

Settings adalah singleton row dengan version untuk optimistic locking. PUT /settings menggantikan seluruh field (atomic). Perubahan Settings hanya berlaku untuk Sale/Record berikutnya; transaksi historis tetap menggunakan snapshot. Endpoint mengembalikan 409 Conflict jika version di request tidak match dengan version di DB.

## 8. Spesifikasi API REST

Bagian ini mendefinisikan kontrak REST API PosyMart versi /api/v1. Spec lengkap di-maintain sebagai file OpenAPI 3.1 YAML (openapi.yaml) yang di-generate ke Go types via oapi-codegen. Bagian ini menyajikan ringkasan kontrak umum, struktur error envelope, dan daftar endpoint per modul. Untuk detail request/response schema lengkap, lihat file openapi.yaml di repository.

### 8.1 Kontrak Umum

Tabel 8-1 Kontrak Umum API

| Aspek | Spesifikasi |
| --- | --- |
| Base URL | /api/v1 (production: https://api.posymart.example.com/api/v1) |
| Content-Type | application/json; charset=utf-8 untuk request dan response body |
| Authentication | Bearer JWT di header Authorization: Bearer <access_token> |
| Pagination | Cursor-based untuk list besar (sales, orders, returns, audit_logs). Offset-based untuk master data (products, customers). Response meta: {nextCursor, hasMore, total}. |
| Filter | Query string: ?status=Completed&date_from=2026-10-04&date_to=2026-10-10&customer_id=&q= |
| Sort | ?sort=-created_at (prefix - untuk descending). Multi-sort: ?sort=-created_at,name |
| Idempotency | Header Idempotency-Key wajib untuk POST /sales/draft/pay, POST /sales/{id}/void, POST /returns/{id}/process-refund. Format: UUID v4 atau string opaque. |
| Rate Limit | 100 req/min per token. Header: X-RateLimit-Limit, X-RateLimit-Remaining, X-RateLimit-Reset. 429 jika exceeded. |
| ETag | Response GET detail menyertakan ETag. Klien dapat kirim If-None-Match; server return 304 jika tidak berubah. |
| Versioning | /api/v1 di URL. Breaking changes menaikkan versi ke /api/v2 dengan periode overlap minimum 6 bulan. |
| CORS | Allow origin: environment variable CORS_ALLOWED_ORIGINS. Allow methods: GET, POST, PATCH, DELETE, OPTIONS. Allow headers: Authorization, Content-Type, Idempotency-Key, X-Request-Id. |
| Localization | Header Accept-Language: id atau en. Default id. Pesan error diterjemahkan. |

### 8.2 Error Envelope

Semua error mengikuti format RFC 7807 yang disederhanakan. Body response error selalu memiliki struktur berikut:

```json
{
  "error": {
    "code": "POS-010",
    "message": "Stok tidak mencukupi untuk satu atau lebih item",
    "fields": [
      { "field": "items[2].quantity", "issue": "exceeds_available", "available": 3 }
    ],
    "trace_id": "abc-123-def-456"
  },
  "meta": {
    "request_id": "req-...",
    "timestamp": "2026-10-10T14:00:00+07:00"
  }
}
```

HTTP status code mengikuti konvensi REST: 200 OK (GET, PATCH sukses), 201 Created (POST sukses), 204 No Content (DELETE sukses), 400 Bad Request (validasi input), 401 Unauthorized (token invalid/expired), 403 Forbidden (RBAC denied), 404 Not Found, 409 Conflict (state conflict, race), 422 Unprocessable Entity (semantic error, e.g. draft kosong), 429 Too Many Requests, 500 Internal Server Error, 503 Service Unavailable.

### 8.3 Endpoint Autentikasi

Tabel 8-2 Endpoint Auth

| Method | Path | Deskripsi | Request Body | Response 200/201 |
| --- | --- | --- | --- | --- |
| POST | /auth/login | Login dengan email + password. | {email, password} | {access_token, expires_in, refresh_token (httpOnly cookie), user} |
| POST | /auth/refresh | Refresh access token. Body kosong, baca refresh_token cookie. | (cookie) | {access_token, expires_in} |
| POST | /auth/logout | Revoke refresh_token current session. | (cookie) | 204 No Content |
| GET | /auth/me | Profil user current. | - | {id, email, full_name, role, is_active, last_login_at} |

Seluruh endpoint Auth ditujukan kepada staf POS. Tidak ada kewajiban login atau register pembeli untuk checkout Walk-in. Strategy JWT: access token berumur 15 menit, signed dengan HS256 secret di config. Refresh token berumur 7 hari, disimpan sebagai bcrypt hash di tabel refresh_tokens, di-rotate setiap digunakan (revoke old, issue new). Revocation terjadi saat logout atau saat detect reuse (jika refresh token yang sudah di-revoke digunakan kembali, semua token user tersebut di-revoke sebagai mitigasi kompromi).

### 8.4 Ringkasan Endpoint per Modul

Detail per endpoint (request schema, response schema, error codes) tersedia di openapi.yaml. Tabel berikut adalah ringkasan endpoint per modul untuk navigasi cepat:

Tabel 8-3 Ringkasan Endpoint per Modul

| Modul | Endpoint Count | Path Prefix | RBAC Minimum |
| --- | --- | --- | --- |
| Auth | 4 | /auth | Public (login), Token (me/logout/refresh) |
| Products | 7 | /products | GET: All; POST/PATCH: Admin,Manager |
| Customers | 6 | /customers | All |
| Sales (list & detail) | 2 | /sales | All |
| Sales (draft) | 7 | /sales/draft | Cashier+ |
| Sales (pay/void) | 2 | /sales/{id}/pay, /sales/{id}/void | Cashier+ (pay), Manager+ (void) |
| Orders | 5 | /orders | All |
| Returns | 8 | /returns | Submit: Cashier+; Approve/Reject/Process: Manager+ |
| Discounts | 5 | /discounts | GET: All; POST/PATCH: Manager+ |
| Reports | 5 | /reports | Manager+ (kecuali Admin-only export) |
| Users | 5 | /users | Admin (kecuali GET: All) |
| Settings | 2 | /settings | GET: All; PUT: Admin |
| Inventory | 1 | /inventory/products/{id}/movements | All |
| Total | 59 | - | - |

### 8.5 Contoh Endpoint Detail — POST /sales/draft/pay

Endpoint ini adalah contoh paling kompleks. Berikut adalah kontrak lengkap:

Tabel 8-4 Kontrak POST /sales/draft/pay

| Aspek | Spesifikasi |
| --- | --- |
| Method | POST |
| Path | /api/v1/sales/draft/pay |
| Header wajib | Authorization: Bearer <token>, Idempotency-Key: <uuid> |
| Body | {payment_method: 'Cash'\|'Card'\|'Digital Wallet'} |
| Role minimum | Cashier |
| Validasi | Draft tidak kosong; produk tersedia; jika track_inventory aktif, qty ≤ stock; promo eligible; validasi profil hanya jika customerId tidak null. |
| Efek samping | Insert sale, sale_items, unit_allocations; decrement stock bila aktif; insert inventory_movements dan discount_usages; update customer totals hanya bila customerId tidak null; audit log; bersihkan draft setelah commit berhasil. |
| Response 201 | {id, tx_code, created_at, status, payment_method, customer, cashier, items[], subtotal_cents, discount_cents, taxable_subtotal_cents, tax_cents, total_cents, source_order_id?} |
| Response 409 | POS-010 insufficient stock; POS-040 discount expired; POS-041 discount max usage |
| Response 422 | POS-001 draft empty; POS-002 invalid payment method |
| Response 401 | AUTH-001 token invalid; AUTH-002 token expired |
| Response 403 | AUTH-003 role not allowed |
| Idempotent | Ya, via Idempotency-Key header. Response replay 24 jam. |

### 8.6 OpenAPI Spec sebagai Single Source of Truth

File openapi.yaml adalah satu-satunya sumber kebenaran untuk kontrak API. Setiap perubahan endpoint harus diawali dengan edit openapi.yaml, lalu generate ulang Go types via oapi-codegen. CI pipeline menjalankan lint berikut: (1) oapi-codegen diff antara generated code dan committed code — jika berbeda, CI fail. (2) spectral linting OpenAPI spec — harus lulus tanpa warning. (3) Contract testing: jalankan service dan PRISM mock, bandingkan response — harus match. (4) Generate dokumentasi Swagger UI dari spec dan deploy ke /docs. Frontend dapat generate TypeScript client dari spec yang sama, sehingga kontrak benar-benar terjamin konsistensi.

## 9. Autentikasi dan Otorisasi

### 9.1 Strategi JWT

PosyMart menggunakan JWT (JSON Web Token) access token berumur pendek (15 menit) dengan refresh token rotation. Access token di-signed menggunakan HS256 dengan secret di config (min 32 byte, rotate setiap 90 hari). Token berisi claim: sub (user ID), role, exp, iat, jti (unique ID untuk tracking revocation). Access token disimpan di client-side memory atau sessionStorage (bukan localStorage untuk mitigasi XSS). Refresh token di-set sebagai httpOnly cookie dengan SameSite=Strict, Secure=true, Path=/auth/refresh.

### 9.2 Refresh Token Rotation

Setiap kali /auth/refresh dipanggil, refresh token lama di-revoke (revoked_at = now) dan refresh token baru di-issue. Ini disebut refresh token rotation dan merekomendasikan oleh OAuth 2.0 BCP. Jika refresh token yang sudah di-revoke digunakan kembali (detected via token_hash lookup), backend menganggap token tersebut compromised dan me-revoke SEMUA refresh token user tersebut sebagai mitigasi. Event ini juga di-log ke audit_logs dengan action=auth.refresh_reuse untuk investigasi.

### 9.3 Password Policy

Password disimpan sebagai bcrypt hash dengan cost factor 12 (sekitar 250ms per hash di hardware modern). Validasi password: minimum 8 karakter, maksimum 128 karakter, harus mengandung kombinasi huruf dan angka (rule ini dapat di-config via Settings). Reset password tidak diimplementasikan di scope awal karena demo; implementasi production memerlukan email service dan token reset terpisah.

### 9.4 RBAC Matrix

Role-Based Access Control dengan tiga role: Admin, Manager, Cashier. Permission di-check di middleware berdasarkan (a) role user dari JWT, (b) action dari route definition. Berikut adalah matriks lengkap permission per role per modul:

Tabel 9-1 RBAC Permission Matrix

| Modul / Aksi | Admin | Manager | Cashier |
| --- | --- | --- | --- |
| Products: read | ✓ | ✓ | ✓ |
| Products: create/update/archive | ✓ | ✓ | — |
| Products: adjust stock | ✓ | ✓ | — |
| Customers: read/create/update | ✓ | ✓ | ✓ |
| Customers: archive | ✓ | ✓ | — |
| Sales: list/detail | ✓ | ✓ | ✓ |
| Sales: draft (CRUD + pay) | ✓ | ✓ | ✓ |
| Sales: void | ✓ | ✓ | — |
| Orders: all actions | ✓ | ✓ | ✓ |
| Orders: cancel setelah Preparing | ✓ | ✓ | — |
| Returns: create/submit | ✓ | ✓ | ✓ |
| Returns: approve/reject/process-refund | ✓ | ✓ | — |
| Discounts: read | ✓ | ✓ | ✓ |
| Discounts: create/update/archive | ✓ | ✓ | — |
| Reports: read | ✓ | ✓ | — |
| Reports: export | ✓ | ✓ | — |
| Users: read | ✓ | — | — |
| Users: create/update/deactivate | ✓ | — | — |
| Settings: read | ✓ | ✓ | ✓ |
| Settings: update | ✓ | — | — |
| Inventory: view movements | ✓ | ✓ | ✓ |
| Audit log: read | ✓ | — | — |

### 9.5 Implementasi Middleware

Middleware RBAC diimplementasikan sebagai fungsi Go yang menerima list of required roles dan mengembalikan Echo/Fiber middleware. Implementasi mengikuti pola berikut:

```go
// RBAC middleware — pseudo Go
func RequireRole(roles ...string) echo.MiddlewareFunc {
    return func(next echo.HandlerFunc) echo.HandlerFunc {
        return func(c echo.Context) error {
            user := c.Get("user").(*domain.User)  // di-set oleh JWT middleware
            for _, r := range roles {
                if user.Role == r {
                    return next(c)
                }
            }
            return c.JSON(403, errorEnvelope{
                Code:    "AUTH-003",
                Message: "role not allowed for this action",
                TraceID: c.Get("trace_id").(string),
            })
        }
    }
}

// Penggunaan di route:
api.POST("/sales/:id/void", handler.VoidSale,
    middleware.JWT(secret),
    RequireRole("Admin", "Manager"),
)
```

### 9.6 Constraint Validasi User

Selain RBAC, terdapat constraint validasi user yang dijalankan di service layer:

- Tidak boleh menonaktifkan current user (is_active=false pada user sendiri).

- Tidak boleh menonaktifkan Admin terakhir yang aktif. Backend query: SELECT COUNT(*) FROM users WHERE role='Admin' AND is_active=true. Jika count == 1 dan itu user yang akan dinonaktifkan, return 409.

- Tidak boleh mengubah role Admin terakhir ke role lain. Validasi sama dengan di atas.

- Tidak boleh hard delete user; hanya soft delete via archived_at. User yang sudah archived tetap terbaca di audit log dan snapshot sale historical.

### 9.7 Audit Log

Setiap mutasi state kritis menulis ke audit_logs dalam transaction yang sama dengan mutasi data, untuk menjamin konsistensi. Audit log adalah append-only: tidak ada path API yang dapat update atau delete record audit_logs. Pembacaan audit log hanya untuk Admin via endpoint GET /audit-logs?target_type=&target_id=&user_id=&date_from=&date_to=. Field audit log: id, user_id, action, target_type, target_id, before_data (JSONB), after_data (JSONB), trace_id, ip_address, created_at.

Tabel 9-2 Action yang Di-audit

| Action | Target Type | Trigger | Before/After |
| --- | --- | --- | --- |
| user.login | user | POST /auth/login sukses | —/user_id, ip |
| user.logout | user | POST /auth/logout | —/user_id |
| sale.pay | sale | POST /sales/draft/pay | —/sale full snapshot |
| sale.void | sale | POST /sales/{id}/void | sale before/sale after (Voided) |
| order.transition | order | POST /orders/{id}/transition | order before/after |
| return.submit | return | POST /returns/{id}/submit | —/return |
| return.approve | return | POST /returns/{id}/approve | return before/after |
| return.complete | return | POST /returns/{id}/process-refund | return before/after + sale updated |
| return.reject | return | POST /returns/{id}/reject | return before/after |
| product.create | product | POST /products | —/product |
| product.update | product | PATCH /products/{id} | product before/after |
| product.adjust_stock | product | POST /products/{id}/adjust-stock | stock before/after + movement |
| product.archive | product | POST /products/{id}/archive | —/archived_at |
| customer.create | customer | POST /customers | —/customer |
| customer.update | customer | PATCH /customers/{id} | customer before/after |
| customer.archive | customer | POST /customers/{id}/archive | —/archived_at |
| discount.create | discount | POST /discounts | —/discount |
| discount.update | discount | PATCH /discounts/{id} | discount before/after |
| discount.archive | discount | POST /discounts/{id}/archive | —/archived_at |
| user.create | user | POST /users | —/user (tanpa password_hash) |
| user.update | user | PATCH /users/{id} | user before/after (tanpa password_hash) |
| user.deactivate | user | POST /users/{id}/deactivate | user before/after |
| settings.update | settings | PUT /settings | settings before/after |
| auth.refresh_reuse | user | Detected refresh token reuse | —/user_id, token hint |

## 10. Pola Konsistensi dan Idempotency

Konsistensi data adalah prinsip paling penting pada sistem POS. Kesalahan konsistensi (stok berkurang dua kali, Sale dibayar dua kali, refund melebihi total) dapat menyebabkan kerugian finansial dan kehilangan kepercayaan pengguna. Bagian ini mendefinisikan pola yang digunakan untuk menjamin konsistensi dan idempotency pada backend PosyMart.

### 10.1 Database Transaction Boundary

Setiap mutasi state kritis berjalan dalam satu database transaction dengan isolation READ COMMITTED (default PostgreSQL). Untuk operasi yang melibatkan race potential (e.g. stock decrement pada saat sale pay), digunakan SELECT ... FOR UPDATE pada row yang akan diubah, sehingga concurrent transaction lain yang mencoba lock row yang sama akan menunggu hingga transaction pertama commit/rollback. Deadlock dihindari dengan urutan lock yang konsisten (selalu lock products berurutan berdasarkan product_id ascending).

```text
// Pola transaction di service layer:
func (s *SaleService) Pay(ctx context.Context, draftID string, req PayRequest) (*Sale, error) {
    tx, err := s.db.BeginTx(ctx, pgx.TxOptions{IsoLevel: pgx.ReadCommitted})
    if err != nil { return nil, err }
    defer tx.Rollback()  // no-op jika Commit sukses

    // Lock products dalam urutan konsisten
    productIDs := draft.ProductIDs()
    sort.Slice(productIDs, func(i, j int) bool { return productIDs[i] < productIDs[j] })
    for _, pid := range productIDs {
        p, err := s.repo.GetProductForUpdate(ctx, tx, pid)
        if err != nil { return nil, err }
        if s.settings.TrackInventory && p.StockQty < draft.QtyFor(pid) {
            return nil, ErrInsufficientStock{pid, p.StockQty}
        }
    }

    // ... insert sale, sale_items, unit_allocations, inventory_movements ...

    if err := tx.Commit(); err != nil { return nil, err }
    return sale, nil
}
```

### 10.2 Inventory Movement ID untuk Idempotency

Setiap perubahan stok (sale pay, void, return complete, manual adjust) menulis ke inventory_movements dengan movement_id UUID v7 yang di-generate oleh service. Tabel inventory_movements memiliki UNIQUE constraint pada movement_id. Jika request retry dengan Idempotency-Key yang sama, service akan meng-generate movement_id yang sama (berdasarkan hash Idempotency-Key + sale_id), sehingga INSERT akan konflik dan backend dapat mendeteksi retry. Pada konflik, backend membaca record existing dan mengembalikan response asli.

### 10.3 Idempotency-Key Header

Untuk mutasi kritis (POST /sales/draft/pay, POST /sales/{id}/void, POST /returns/{id}/process-refund, POST /orders/{id}/transition), klien wajib mengirim header Idempotency-Key. Backend menyimpan key + request_hash + response di tabel idempotency_keys dengan TTL 24 jam. Jika key yang sama digunakan lagi: (a) Jika request_hash sama → replay response asli (status, body, header). (b) Jika request_hash berbeda → return 409 Conflict dengan error code POS-050. Berikut adalah pseudocode middleware:

**Catatan implementasi:** pseudocode middleware di bawah masih berupa ilustrasi dari Word, bukan implementasi idempotency yang lengkap. Validasi header wajib, scope pengguna/operasi, request concurrent, penyimpanan hasil atomik, serta pengaitan response recorder harus dipastikan sebelum dipakai.

```go
// Idempotency middleware:
func Idempotency(repo Repository, redis *redis.Client) echo.MiddlewareFunc {
    return func(next echo.HandlerFunc) echo.HandlerFunc {
        return func(c echo.Context) error {
            key := c.Request().Header.Get("Idempotency-Key")
            if key == "" { return next(c) }  // tidak wajib untuk semua endpoint

            // Cek di cache dan DB
            cached, err := repo.GetIdempotencyKey(c.Request().Context(), key)
            if err == nil {
                reqHash := hashRequest(c.Request())
                if cached.RequestHash != reqHash {
                    return c.JSON(409, errorEnvelope{Code: "POS-050", ...})
                }
                // Replay response
                return c.JSON(cached.ResponseStatus, cached.ResponseBody)
            }

            // Capture response
            rec := httptest.NewRecorder()
            err = next(c)
            // Simpan ke DB
            repo.SaveIdempotencyKey(c.Request().Context(), key, hashRequest, rec.Code, rec.Body.Bytes())
            return err
        }
    }
}
```

### 10.4 Outbox Pattern untuk Konsistensi Data + Event

Untuk menjamin bahwa audit log selalu ditulis bersamaan dengan mutasi data (tidak ada skenario di mana data berubah tetapi audit log hilang karena crash sebelum publikasi event), digunakan pola outbox. Tabel outbox ditambahkan ke transaction yang sama dengan mutasi data, berisi event yang akan dipublikasikan. Background worker membaca outbox dan: (a) menulis ke audit_logs (jika belum), (b) publish ke Redis pub/sub untuk real-time notification, (c) mark outbox row sebagai published. Pola ini menjamin at-least-once delivery event, dengan idempotency consumer menangani kemungkinan duplikasi.

```sql
-- Tabel outbox
CREATE TABLE outbox (
  id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  aggregate_type VARCHAR(30) NOT NULL,  -- 'sale', 'return', 'order', dst.
  aggregate_id  UUID NOT NULL,
  event_type    VARCHAR(50) NOT NULL,    -- 'sale.paid', 'return.completed', dst.
  payload       JSONB NOT NULL,
  published_at  TIMESTAMPTZ,
  created_at    TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX idx_outbox_unpublished ON outbox(created_at) WHERE published_at IS NULL;
```

Worker menjalankan query SELECT ... FOR UPDATE SKIP LOCKED LIMIT 100 untuk concurrency-safe processing. Jika worker crash sebelum mark published, row akan di-pick ulang oleh worker lain setelah timeout transaction. Idempotency consumer: audit_logs dapat menulis event_type + aggregate_id sebagai unique key, sehingga publish dua kali tidak menulis audit log dua kali.

### 10.5 Optimistic Locking untuk Settings

Settings menggunakan optimistic locking via kolom version. PUT /settings mengirim version di body; backend UPDATE settings SET ... WHERE id=1 AND version=$1 RETURNING version+1. Jika affected rows = 0, return 409 Conflict dengan error code POS-060 (settings version mismatch). Klien harus GET /settings ulang untuk dapat version terbaru dan merge perubahan. Pola ini mencegah lost update ketika dua user concurrent edit settings.

### 10.6 Stock Decrement Race Condition

Skenario race: User A dan User B concurrent checkout dengan item yang sama, stok tersisa 1. Tanpa locking, kedua transaction bisa membaca stock=1 dan mengurangi, menghasilkan stock=-1 (negatif). Solusi: SELECT FOR UPDATE pada products row di awal transaction. Transaction kedua akan menunggu hingga transaction pertama commit/rollback. Jika commit, transaction kedua membaca stock baru (0) dan gagal dengan 409. Jika rollback, transaction kedua membaca stock lama (1) dan dapat lanjut.

### 10.7 Serialization Failure Retry

Pada isolation level yang lebih tinggi (SERIALIZABLE), PostgreSQL dapat mendeteksi konflik dan mengembalikan error SQLSTATE 40001 (serialization_failure). Backend menangkap error ini dan retry transaction hingga 3 kali dengan backoff 10ms/30ms/90ms. Jika setelah 3 retry masih gagal, return 409 Conflict. Untuk READ COMMITTED (default PosyMart), serialization failure jarang terjadi, tetapi mekanisme retry tetap diimplementasikan sebagai safety net.

## 11. Non-Functional Requirements

Non-functional requirements (NFR) mendefinisikan atribut kualitas yang harus dipenuhi backend PosyMart: performance, availability, security, observability, scalability, data retention, dan backup. Setiap NFR memiliki target terukur dan strategi pencapaian. NFR ini berlaku untuk deployment single-store dengan throughput harian rata-rata 200 transaksi/hari (peak 50 RPS concurrent).

### 11.1 Performance

Tabel 11-1 Target Performance

| Operasi | p50 | p95 | p99 | Strategi |
| --- | --- | --- | --- | --- |
| GET /sales (list, page 20) | 30ms | 100ms | 200ms | Index composite, cursor pagination, Redis cache count. |
| GET /sales/{id} (detail) | 20ms | 60ms | 120ms | Index PK, join sale_items + unit_allocations dengan 1 query. |
| POST /sales/draft/pay | 150ms | 500ms | 1000ms | Transaction optimized, INSERT batch, lock products hanya jika track_inventory. |
| GET /reports/summary | 100ms | 500ms | 1500ms | Covering index, materialized view untuk daily summary (refresh every 5 min). |
| GET /products (list, filter) | 20ms | 80ms | 150ms | Index partial pada active, Redis cache category list. |
| POST /auth/login | 100ms | 250ms | 400ms | bcrypt cost 12 dominant; tidak dapat di-cache. |
| POST /returns/{id}/process-refund | 200ms | 600ms | 1200ms | Transaction multiple tables, lock return + sale_items. |

### 11.2 Availability

Target availability 99.5% per bulan (max 3.6 jam downtime). Strategi: (a) Stateless Go service dengan minimal 2 replika di belakang LB, health check otomatis. (b) PostgreSQL managed service dengan automated failover. (c) Redis managed dengan replica. (d) Maintenance window terjadwal (e.g. Minggu 02:00-04:00 WIB) dengan mode read-only jika diperlukan schema migration. (e) Circuit breaker untuk dependency (Redis, DB) — jika Redis down, fallback ke in-memory cache dengan TTL pendek.

### 11.3 Security

- TLS 1.3 wajib; TLS 1.2 hanya untuk compatibility legacy, akan di-deprecate.

- OWASP Top 10 mitigation: input validation via oapi-codegen, parameterized query (pgx), output encoding, CSRF token untuk cookie-based auth (refresh token).

- SQL injection: 100% query via pgx parameterized. sqlc generate Go code dari SQL murni, tidak ada string concatenation.

- XSS: backend tidak menyajikan HTML; response JSON. Frontend bertanggung jawab encoding.

- Rate limit: 100 req/min per token. Burst 20 req. 429 dengan Retry-After header.

- Audit log immutable: tabel audit_logs tanpa UPDATE/DELETE permission untuk role aplikasi. Hanya INSERT dan SELECT.

- Secret management: secret di environment variable atau Vault. Tidak ada secret di code/config file.

- Dependency scanning: dependabot/govulncheck mingguan. CVE critical di-patch dalam 7 hari.

- Penetration test: setiap 6 bulan oleh tim eksternal atau internal red team.

### 11.4 Observability

Tiga pilar observability: logs, metrics, traces. Semua dikirim ke platform central (Loki + Prometheus + Tempo, atau SaaS equivalent seperti Datadog/New Relic).

#### 11.4.1 Structured Logging

```jsonc
// Format log JSON (zap):
{
  "level": "info",
  "ts": "2026-10-10T14:00:00.123Z",
  "logger": "sale.service",
  "msg": "sale paid",
  "trace_id": "abc-123",
  "request_id": "req-456",
  "user_id": "user-john-doe",
  "sale_id": "uuid-...",
  "tx_code": "TX-1144",
  "total_cents": 1798,
  "duration_ms": 142
}

// Level: DEBUG (verbose, dev only), INFO (default), WARN (recoverable), ERROR (logged + alert), FATAL (process exit)
// Sampling: DEBUG 1%, INFO 100%, WARN/ERROR 100%
// Rotation: 30 hari hot, 90 hari cold storage
```

#### 11.4.2 Prometheus Metrics

- http_requests_total{method, path, status} — counter

- http_request_duration_seconds{method, path} — histogram dengan buckets [5,10,25,50,100,250,500,1000,2500,5000]ms

- http_inflight_requests — gauge

- db_pool_active / db_pool_idle / db_pool_max — gauge

- redis_commands_total{command, status} — counter

- inventory_movements_total{type} — counter

- sales_paid_total{payment_method} — counter

- sale_pay_duration_seconds — histogram

- idempotency_key_replay_total — counter

#### 11.4.3 Distributed Tracing

OpenTelemetry tracing dengan trace propagasi via W3C Trace Context header (traceparent, tracestate). Setiap request incoming membuat span root. Setiap call ke DB, Redis, dan external service membuat child span. Trace ID juga di-inject ke log entry dan audit log untuk korelasi. Sampling: 100% untuk error, 10% untuk sukses (dapat di-config).

#### 11.4.4 Health Endpoints

- GET /healthz — liveness probe. Return 200 OK jika process running. Tidak cek dependency.

- GET /readyz — readiness probe. Cek DB ping, Redis ping. Return 200 jika semua dependency healthy, 503 jika ada yang down.

- GET /metrics — Prometheus scrape endpoint. Expose semua metrics di 11.4.2.

### 11.5 Scalability

Stateless Go service dapat di-scale horizontal dengan menambah replika. Bottleneck utama adalah PostgreSQL. Strategi scaling: (a) Vertikal: naikkan instance type hingga 32 vCPU / 256 GB RAM. (b) Read replica: arahkan query Reports (read-heavy, dapat stale) ke replica. (c) Connection pooling: pgx pool default 25, dapat di-tune hingga 100 per instance. (d) Query optimization: EXPLAIN ANALYZE setiap query Reports, index tuning berdasarkan slow query log. (e) Caching: Redis cache untuk lookup yang jarang berubah (settings, products list, categories). Cache TTL 5 menit, invalidate on update.

### 11.6 Data Retention

Tabel 11-2 Kebijakan Retention Data

| Tipe Data | Retention | Storage | Penghapusan |
| --- | --- | --- | --- |
| sales + sale_items + unit_allocations | Forever (immutable) | PostgreSQL hot 1 tahun, archive ke S3 setelahnya | Tidak pernah dihapus |
| returns + return_items | Forever (immutable) | Sama dengan sales | Tidak pernah |
| audit_logs | Forever (immutable, WORM) | PostgreSQL hot 90 hari, S3 + Glacier setelahnya | Tidak pernah |
| discount_usages | Forever (immutable) | PostgreSQL | Tidak pernah |
| inventory_movements | Forever (immutable) | PostgreSQL hot 1 tahun, S3 setelahnya | Tidak pernah |
| orders + order_items | Forever (immutable) | PostgreSQL | Tidak pernah |
| refresh_tokens | 30 hari setelah expires_at | PostgreSQL | Cron job harian DELETE WHERE expires_at < now() - interval '30 days' |
| idempotency_keys | 24 jam setelah expires_at | PostgreSQL | Cron job per jam DELETE WHERE expires_at < now() |
| outbox | 30 hari setelah published_at | PostgreSQL | Cron job harian DELETE WHERE published_at IS NOT NULL AND published_at < now() - interval '30 days' |
| products (archived) | 365 hari setelah archived_at, kemudian hard delete jika tidak ada referensi | PostgreSQL | Cron job mingguan; cek referensi sebelum hapus |
| customers (archived) | 365 hari setelah archived_at, kemudian hard delete jika tidak ada referensi | PostgreSQL | Sama dengan products |
| users (archived) | Forever, untuk audit trail | PostgreSQL | Tidak pernah hard delete |

### 11.7 Backup dan Disaster Recovery

- PostgreSQL PITR: WAL archiving setiap 5 menit ke S3. Recovery ke point-in-time mana pun dalam 30 hari. RPO 5 menit.

- Nightly logical backup: pg_dump full database setiap 02:00 WIB. Disimpan di S3 dengan 30 hari retention.

- Restore test: setiap bulan, restore backup ke staging environment dan jalankan smoke test. Verifikasi data integrity.

- RTO (Recovery Time Objective): 1 jam untuk single-region failover. 4 jam untuk cross-region disaster recovery (jika diimplementasikan di masa depan).

- Runbook: dokumentasi langkah demi langkah untuk skenario common (DB failover, Redis failover, restore from backup, partial data corruption). Disimpan di wiki internal dan di-rehearsal setiap kuartal.

## 12. Roadmap Implementasi

Roadmap implementasi backend PosyMart dibagi menjadi 6 milestone dengan total durasi 14 minggu. Setiap milestone menghasilkan deliverable yang dapat di-deploy dan di-test secara independen. Acceptance criteria setiap milestone mengacu pada bagian 27 design.md untuk konsistensi ledger demo. Tim yang direkomendasikan: 1 Tech Lead (part-time), 2 Backend Engineer (full-time), 1 QA Engineer (full-time mulai M3), 1 DevOps (part-time).

### 12.1 Milestone Overview

Tabel 12-1 Roadmap Milestone

| Milestone | Durasi | Deliverable Utama | Acceptance Criteria |
| --- | --- | --- | --- |
| M1 Fondasi | 2 minggu | Project scaffold (Go + Echo/Fiber + pgx + sqlc), config via viper, structured logging zap, DB migration 0001-0004, JWT auth + RBAC middleware, audit log infrastructure, health endpoints, OpenAPI spec skeleton. | Login/logout/refresh works; /healthz dan /readyz return 200; migration up/down clean; RBAC middleware blocks unauthorized; audit log records user.login. |
| M2 Master Data | 2 minggu | Products, Categories, Customers, Users, Settings CRUD. Adjust stock endpoint. Soft delete pattern. Customer denormalized totals via trigger. | CRUD products/customers/users/settings works; adjust stock writes inventory_movements; archive soft-deletes; restore works; customer.total_spent_cents maintains via trigger. |
| M3 Sales & Payment | 3 minggu | Draft sale Redis, add/update/delete items, apply discount, pay endpoint with full transaction. Algoritma alokasi uang largest remainder. Void sale. Idempotency-Key middleware. | Pay creates sale with correct allocations (verified by integration test I1-I13); void restores stock; idempotency key replays response; race condition test passed. |
| M4 Orders & Returns | 3 minggu | Order state machine. Return workflow Draft→Pending→Completed/Rejected. Refund calculation per unit allocation. Customer total_spend adjustment on refund. | Order transitions valid per state machine; complete order creates one sale (no duplicate on retry); return partial refund correct per unit; rejected return doesn't affect totals. |
| M5 Reports & Inventory | 2 minggu | Reports summary, payment breakdown, daily summary, top products, export CSV. Inventory movement list endpoint. Materialized view for daily summary. CSV sanitasi anti-formula-injection. | Reports Oct 04-10 returns 49 Completed, gross paid $1,315.22, subtotal $1,217.80, tax $97.42 (match design.md 27.2); CSV export safe; inventory movement list shows full history per product. |
| M6 Hardening | 2 minggu | Observability stack (Prometheus metrics, OpenTelemetry trace), rate limit, integration test 80%+ coverage, load test 50 RPS, backup drill, runbook, security review. | p95 latency meet target; load test pass; backup restore verified; security review no critical findings; runbook tested via game day. |

### 12.2 Dependency Antar Milestone

M1 adalah fondasi wajib sebelum M2–M6. Product dan Settings dari M2 dibutuhkan untuk M3. Sale tidak membutuhkan record Customer: checkout Walk-in harus dapat diuji saat tabel Customers kosong. Modul profil pelanggan dapat diintegrasikan setelah alur dasar itu berjalan. M3 wajib sebelum M4 untuk sumber Return dan M5 untuk agregasi Reports. M6 dapat dimulai paralel dengan M5, tetapi load test memerlukan cakupan layanan yang telah siap.

### 12.3 Deliverable per Milestone

Setiap milestone menghasilkan deliverable berikut: (1) Runnable service dengan endpoint yang dijanjikan; (2) OpenAPI spec diperbarui dan generated Go types committed; (3) Migration scripts (up + down) untuk skema baru; (4) Integration test suite dengan coverage ≥ 80% untuk modul yang dikerjakan; (5) Smoke test script untuk QA manual; (6) Changelog entry yang mendokumentasikan breaking changes dan new features. CI pipeline harus lulus sebelum merge ke main branch.

## 13. Risiko dan Mitigasi

Bagian ini mengidentifikasi risiko utama dalam implementasi backend PosyMart dan strategi mitigasinya. Risiko dinilai berdasarkan probability (Low/Medium/High) dan impact (Low/Medium/High). Risiko dengan kombinasi probability × impact tertinggi mendapat prioritas mitigasi tertinggi.

Tabel 13-1 Register Risiko

| ID | Risiko | Prob. | Impact | Mitigasi |
| --- | --- | --- | --- | --- |
| R1 | Bug alokasi uang largest remainder menyebabkan total refund ≠ total sale. Defect kritis pada POS. | Medium | High | Unit test 100% coverage untuk AllocateByLargestRemainder. Property-based test dengan quickcheck: invariant sum(result)==total untuk random input. Integration test verifikasi I1-I13 setiap pay sale. |
| R2 | Race condition stock decrement menyebabkan stock negatif atau double-decrement. | Medium | High | SELECT FOR UPDATE pada products di awal transaction. Urutan lock konsisten (product_id ascending) untuk hindari deadlock. Integration test concurrent pay dengan item sama. |
| R3 | Snapshot drift: product/customer diubah tapi receipt historis ikut berubah. | Low | High | Snapshot kolom redundant di sales/sale_items. Integration test: edit product setelah sale, verify receipt tidak berubah. CI lint: tidak ada JOIN ke products pada query get sale by ID. |
| R4 | Migration rusak di produksi, menyebabkan downtime atau data loss. | Low | High | Expand-contract pattern. Rollback script wajib dan teruji. Migration di-test di staging dengan copy data produksi. CI menjalankan migrate up→down→up sebagai smoke test. |
| R5 | OpenAPI spec out-of-sync dengan kode, menyebabkan frontend rusak. | Medium | Medium | Spec sebagai single source of truth. oapi-codegen generate types dari spec. CI lint: generated code must match committed. Spectral lint spec. Contract testing via PRISM. |
| R6 | Audit log kehilangan event saat crash sebelum insert. | Low | High | Audit log insert dalam transaction yang sama dengan mutasi data. Outbox pattern untuk publikasi event asinkron. Worker idempotent. |
| R7 | Performance Reports lambat saat data bertumbuh (>100k transaksi). | Medium | Medium | Covering index untuk daily summary. Materialized view refresh every 5 min. Read replica untuk query Reports. EXPLAIN ANALYZE setiap query Reports di CI. |
| R8 | Idempotency-Key collision antara client berbeda. | Low | Medium | Key di-scope per user (user_id + key unique). Conflict return 409, bukan replay. Dokumentasi klien: gunakan UUID v4. |
| R9 | JWT secret compromised. | Low | High | Secret di Vault, rotate 90 hari. Support revocation via jti blacklist (jika diimplementasikan). Monitoring anomali usage. |
| R10 | Dependency library memiliki CVE critical. | Medium | Medium | dependabot/govulncheck mingguan. Patch dalam 7 hari untuk critical. Lock file committed untuk reproducibility. |
| R11 | Redis down menyebabkan idempotency dan stock cache tidak berfungsi. | Low | Medium | Circuit breaker: fallback ke in-memory cache dengan TTL pendek. Idempotency fallback ke DB lookup. Alert triggered saat Redis unhealthy. |
| R12 | Timezone bug: transaksi dibuat di WIB tapi disimpan di UTC, menyebabkan filter 'Today' salah. | Medium | Medium | DB timezone UTC. Konversi WIB→UTC di service layer. Integration test: sale dibuat 23:30 WIB, filter Today harus include. Test dengan DST transition (meskipun WIB tidak pakai DST). |

## 14. Lampiran

### 14.1 Lampiran A — Katalog Error Code

Tabel A-1 Katalog Error Code

| Code | HTTP | Pesan | Penyebab |
| --- | --- | --- | --- |
| AUTH-001 | 401 | Token tidak valid | JWT signature invalid, format salah, atau claim tidak lengkap. |
| AUTH-002 | 401 | Token sudah kedaluwarsa | exp claim < now. Klien harus refresh. |
| AUTH-003 | 403 | Role tidak diizinkan untuk aksi ini | RBAC check gagal. Lihat tabel 9-1. |
| AUTH-004 | 401 | Email atau password salah | Login gagal. Tidak diungkapkan mana yang salah. |
| AUTH-005 | 401 | Refresh token tidak valid | Cookie tidak ada, hash tidak match, atau sudah di-revoke. |
| AUTH-006 | 401 | Refresh token sudah kedaluwarsa | expires_at < now. Klien harus login ulang. |
| AUTH-007 | 401 | Akun dinonaktifkan | user.is_active = false. Hubungi admin. |
| POS-001 | 422 | Draft sale kosong | Tidak ada item pada draft saat pay. Tambah minimal 1 item. |
| POS-002 | 422 | Metode pembayaran tidak valid | payment_method bukan Cash/Card/Digital Wallet. |
| POS-010 | 409 | Stok tidak mencukupi | item.quantity > product.stock_qty saat track_inventory=true. Field: productId, availableQty. |
| POS-011 | 409 | Produk tidak tersedia | product.status=Inactive atau archived. Hapus item dari draft. |
| POS-020 | 409 | Sale sudah Voided | Tidak dapat void sale yang sudah Voided. |
| POS-021 | 409 | Sale memiliki return Pending/Completed | Tidak dapat void sale dengan return aktif. Reject return dulu. |
| POS-030 | 409 | Unit sudah direfund | selectedUnits mengandung unitIndex yang sudah is_refunded=true. |
| POS-031 | 409 | Total refund melebihi total sale | Cumulative refund > sale.total_cents. Invariant dilanggar. |
| POS-040 | 409 | Discount sudah kedaluwarsa atau tidak aktif | discount.end_at < now atau is_active=false atau start_at > now. |
| POS-041 | 409 | Discount usage mencapai maksimum | usage_count >= max_usage (jika ada). |
| POS-042 | 409 | Discount tidak eligible untuk item di cart | scope Category/Product tidak match. Atau min_subtotal_cents tidak terpenuhi. |
| POS-050 | 409 | Idempotency-Key sudah digunakan dengan payload berbeda | request_hash mismatch. Gunakan key baru. |
| POS-060 | 409 | Settings version mismatch | Optimistic locking gagal. GET ulang dan merge. |
| POS-070 | 409 | State transition tidak valid | Order/Return transition tidak diizinkan dari status current. Lihat state machine. |
| POS-080 | 409 | Tidak dapat menonaktifkan Admin terakhir | Validasi constraint user. Lihat 9.6. |
| POS-081 | 409 | Tidak dapat menonaktifkan current user | Validasi constraint user. |
| POS-082 | 409 | Email sudah digunakan | Unique constraint customers.email atau users.email. |
| POS-090 | 404 | Resource tidak ditemukan | ID tidak ada di DB atau sudah di-archived (untuk soft delete). |
| POS-100 | 422 | Validasi input gagal | Field-level error di response.fields[]. |
| POS-500 | 500 | Internal server error | Unhandled error. Lihat log dengan trace_id. |
| POS-503 | 503 | Service unavailable | Dependency (DB/Redis) down. Coba lagi nanti. |

### 14.2 Lampiran B — Reference Enum

Tabel B-1 Enum Reference

| Enum | Nilai | Default | Catatan |
| --- | --- | --- | --- |
| SaleStatus | Completed, Voided | Completed | Draft tidak ada di DB — hanya di Redis |
| OrderStatus | Draft, Pending, Preparing, Ready, Completed, Cancelled | Draft | Transisi via state machine |
| ReturnStatus | Draft, Pending, Completed, Rejected | Draft | Rejected tidak mengubah nominal |
| DemoRole | Admin, Manager, Cashier | Cashier | RBAC matrix tabel 9-1 |
| PaymentMethod | Cash, Card, Digital Wallet | (wajib input) | Tidak ada default |
| DiscountType | Percent, FixedAmount, CategoryPercent | (wajib input) | Percent pakai value_bps, FixedAmount pakai value_cents |
| DiscountScope | All, Category, Product | All | Category pakai target_category_id, Product pakai target_product_id |
| ProductStatus | Active, Inactive | Active | Inactive tidak muncul di katalog POS |
| RestockCondition | Unopened, Opened, Damaged | Unopened | Hanya Unopened yang eligible restock |
| InventoryMovementType | SaleDecrement, VoidRestore, ReturnRestock, ManualAdjust | (auto) | DeltaQty negatif untuk Decrement, positif untuk lainnya |

### 14.3 Lampiran C — Sample Seed Data Transaksi Historis

Berikut adalah resep seed data 44 transaksi historis Completed pada Oct 04–09 2026, sesuai design.md 26.2. Seed ini digunakan untuk verifikasi Reports dan filter Sales. Timestamp mulai 09:00 WIB (02:00 UTC), bertambah 10 menit per record. Alokasi ID per tanggal: urutan Cash → Card → Digital Wallet. Item template disesuaikan agar subtotal match dengan tabel design.md.

Tabel C-1 Seed Historis Oct 04-09 (44 transaksi)

| Tanggal | ID Range | Cash | Card | Digital Wallet | Count |
| --- | --- | --- | --- | --- | --- |
| Oct 04 | TX-1100–TX-1105 | 2 × $30.00 | 2 × $45.00 | 2 × $15.00 | 6 |
| Oct 05 | TX-1106–TX-1113 | 2 × $25.00 | 4 × $30.00 | 2 × $15.00 | 8 |
| Oct 06 | TX-1114–TX-1122 | 3 × $25.00 | 5 × $25.00 | 1 × $25.00 | 9 |
| Oct 07 | TX-1123–TX-1129 | 2 × $25.00 | 4 × $30.00 | 1 × $35.00 | 7 |
| Oct 08 | TX-1130–TX-1139 | 3 × $25.00 | 6 × $25.00 | 1 × $25.00 | 10 |
| Oct 09 | TX-1140–TX-1143 | 2 × $15.00 | 1 × $50.00 | 1 × $20.00 | 4 |

Tabel C-2 Template Item per Net Subtotal

| Net Subtotal | Item Snapshot | Tax (8%) | Paid Total |
| --- | --- | --- | --- |
| $15.00 | Cappuccino ×3 + Chocolate Cake ×1 | $1.20 | $16.20 |
| $20.00 | Sandwich ×2 + Chocolate Cake ×2 | $1.60 | $21.60 |
| $25.00 | Cappuccino ×2 + Chocolate Cake ×4 | $2.00 | $27.00 |
| $30.00 | Cappuccino ×6 + Chocolate Cake ×2 | $2.40 | $32.40 |
| $35.00 | Cappuccino ×10 | $2.80 | $37.80 |
| $45.00 | Chocolate Cake ×10 | $3.60 | $48.60 |
| $50.00 | Cappuccino ×4 + Chocolate Cake ×8 | $4.00 | $54.00 |

Selain 44 historis, tambah 6 arsip (TX-0988 untuk ORD-2005, TX-0990/0992/0993/0994/0995 untuk 5 return) dan 6 record Today (Oct 10) — total 56 record dengan 55 Completed dan 1 Voided sebelum mutasi. Seed counter stok adalah posisi stok akhir setelah transaksi seed; jangan mengurangi stok lagi ketika memuat histori.

### 14.4 Lampiran D — Test Matrix Alokasi Uang

Test matrix berikut memastikan invariant alokasi uang terpenuhi pada kasus uji dari design.md. Setiap test case harus lulus pada integration test suite.

Tabel D-1 Test Matrix Alokasi

| Test ID | Skenario | Input | Expected | Invariant |
| --- | --- | --- | --- | --- |
| T1 | TX-1001 Bagel 2 unit, tax 45 sen | unit_price=280, qty=2, item_net=560, allocated_item_tax=45 | unitAllocations=[{0,280,22,302},{1,280,23,303}] | I7,I8,I9: sum=605 match item.total |
| T2 | COFFEE1 Cappuccino fixed discount $1.00 | unit_price=350, qty=1, discount=100, tax_rate=800 | net=250, tax=20, total=270 | I3,I5: net+tax=total |
| T3 | Cart $18.50 dengan discount 10% | subtotal=1850, discount=185, tax_rate=800 | taxable=1665, tax=133, total=1798 | I10,I11 |
| T4 | Refund seluruh Sale TX-1001 | All units refunded | Total refund = sale.total_cents | I12: refund ≤ total |
| T5 | Partial refund unit pertama TX-1001 | Unit 0 selected | Refund = 302 | I13: ≤ item.total |
| T6 | Largest remainder 3 item, total 10 | item IDs ascending, weights=[1,1,1], total=10 | allocations=[4,3,3]; untuk unit index descending, hasil [3,3,4] | sum=10 |
| T7 | Largest remainder 3 item, total 100 | weights=[1,2,3], total=100 | allocations=[17,33,50] | sum=100 |
| T8 | Discount melebihi eligible | eligible=100, discount=150 | discountCents=100 (capped) | Tidak negatif |
| T9 | Sale dengan 0 item | items=[], pay | 422 POS-001 | Tidak ada Sale dibuat |
| T10 | Concurrent pay item sama, stock=1 | 2 request concurrent | 1 sukses, 1 return 409 POS-010 | Stock tidak negatif |

### 14.5 Lampiran E — Referensi

- design.md bagian 26 — Kontrak data dan alur lintas halaman (sumber utama PRD ini).

- design.md bagian 27 — Acceptance criteria aplikasi lengkap (acuan testing).

- RFC 7807 — Problem Details for HTTP APIs (format error envelope).

- OpenAPI Specification 3.1 — https://spec.openapis.org/oas/v3.1.0

- OWASP Top 10 — https://owasp.org/Top10/

- OWASP API Security Top 10 — https://owasp.org/API-Security/

- OAuth 2.0 Security Best Current Practice — RFC 9700 (refresh token rotation).

- PostgreSQL Documentation v15 — https://www.postgresql.org/docs/15/

- Go database/sql tutorial — https://go.dev/doc/database/

- pgx documentation — https://pkg.go.dev/github.com/jackc/pgx/v5

- sqlc documentation — https://docs.sqlc.dev/

- OpenTelemetry Go SDK — https://opentelemetry.io/docs/languages/go/

- Prometheus best practices — https://prometheus.io/docs/practices/

- RFC 4122 — UUID (digunakan untuk movement_id dan idempotency_key).

- Largest Remainder Method (Hamilton method) — https://en.wikipedia.org/wiki/Largest_remainder_method

## 15 Klarifikasi dan kesiapan implementasi

### 15.1 Perubahan pada versi Markdown

| Area | Penegasan atau koreksi |
| --- | --- |
| Ringkasan dan checkout | Walk-in menjadi default; Customer ID boleh null; login hanya untuk staf |
| Customers | Profil opsional, tidak otomatis akun atau member; validasi kontak hanya ketika sengaja membuat profil |
| Orders | Customer tetap opsional; model awal membayar saat penyelesaian; antrean setelah pembayaran memerlukan model lanjutan |
| Return | Transaksi Walk-in dapat diretur menurut Sale sumber tanpa pendaftaran |
| Promo | All customers mencakup Walk-in; Selected customers memerlukan profil eligible; MEMBER5 bukan sistem membership |
| API customer | Contoh `{null}` diganti dengan JSON valid `{"customerId":null}` |
| API pembayaran | Contoh memakai `payment_method` dari kontrak detail dan header Idempotency-Key; identitas customer dibaca dari draft |
| Konsistensi draft | Contoh penghapusan draft dipindahkan sesudah commit berhasil |
| Bagel TX-1001 | Harga 280 sen × 2; tax item 45; unit totals 302 dan 303 sen; total Sale 1998 sen |
| Test matrix | Input Bagel, jenis COFFEE1 fixed $1.00, serta tie-break item ascending dibetulkan |
| Kategori dan stok | Empat kategori termasuk Desserts; total stok 280 untuk 12 produk, Cappuccino 42 |
| Keandalan contoh kode | DDL dan pseudocode ditandai sebagai rancangan yang masih harus diverifikasi |

### 15.2 Kontrak customer opsional

- **Create draft/Order:** `customerId` boleh omitted atau null; keduanya berarti Walk-in. Jangan menggunakan UUID kosong atau string `walk-in` sebagai foreign key.
- **Set customer:** `{"customerId":"<UUID>"}` memilih profil; `{"customerId":null}` melepas profil. Pada endpoint perubahan customer ini, field harus eksplisit agar tidak ambigu.
- **Pay:** customer mengikuti draft tersimpan. Null valid; ID non-null harus merujuk profil yang masih dapat dipakai.
- **Snapshot:** null menghasilkan label `Walk-in Customer` yang disimpan server; pelanggan terpilih menggunakan snapshot nama profil saat transaksi dibuat.
- **Pembuatan profil:** hanya dari aksi Add Customer yang disengaja; tidak otomatis dari catatan pesanan, label antrean, struk, atau checkout.
- **Promo:** melepas/mengganti customer memvalidasi ulang promo; tampilkan perubahan total sebelum pembayaran. Promo umum tidak mewajibkan identitas.
- **Agregat:** Walk-in masuk ledger Sales/Reports tetapi tidak masuk agregat satu Customer bersama. Total pelanggan tersimpan tidak boleh disamakan dengan total transaksi Walk-in atau jumlah orang unik.
- **Riwayat anonim:** transaksi Walk-in tidak digabung otomatis berdasarkan nama panggilan atau kontak. Menghubungkan sale historis ke profil di kemudian hari belum termasuk workflow awal; membutuhkan kebijakan dan audit terpisah tanpa menulis ulang snapshot struk.
- **API staf:** label Role `All` pada tabel endpoint berarti semua role staf yang terautentikasi, bukan akses publik bagi pembeli.
- **Membership:** nullable Customer ID tidak perlu diubah menjadi wajib ketika fitur member kelak ditambahkan. Tambahkan model membership terpisah beserta eligibility manfaat; customer tersimpan saja tidak membuktikan status member aktif.

### 15.3 Acceptance tambahan

1. Tabel Customers kosong tidak menghalangi checkout, pembuatan Order, ataupun Return dari transaksi Walk-in.
2. Sale tanpa customer tersimpan dengan Customer ID null dan label Walk-in Customer; tidak ada insert Customer dari alur Pay.
3. Telepon, email, password, OTP, dan kartu member tidak diminta untuk pembelian biasa.
4. Menolak atau membatalkan pendaftaran pelanggan/member tidak menghapus keranjang dan tidak memblokir checkout biasa.
5. Promo umum dapat digunakan Walk-in; promo pelanggan tertentu menolak hanya manfaat yang tidak eligible dan menyediakan jalan melanjutkan pembelian.
6. Sale Walk-in memperbarui Sales, stok bila aktif, pajak, dan Reports dengan aturan yang sama seperti Sale dengan profil.
7. Perubahan profil tidak mengubah snapshot struk lama; identitas return mengikuti transaksi sumber.
8. Request ulang dengan idempotency yang sama tidak membuat sale, refund, movement, atau agregat pelanggan ganda.
9. Customers, Users, dan membership tidak memakai satu konsep identitas yang memaksa pembeli login.
10. QA menjalankan skenario C01–C20 pada [Alur Bisnis PosyMart](./PosyMart-Alur-Bisnis.md).

### 15.4 Hal teknis sumber yang perlu diselaraskan sebelum implementasi

Konversi ini bukan audit implementasi backend. Daftar berikut mencatat ketidaksesuaian yang terlihat pada dokumen agar contoh sumber tidak dianggap sebagai kode produksi yang sudah siap.

| Area | Hal yang perlu diselesaikan |
| --- | --- |
| DDL dan urutan migration | `sales` merujuk `discounts` sebelum definisi tabel tersebut; constraint email memakai bentuk partial UNIQUE yang perlu dijadikan index/constraint valid. Susun urutan atau tambahkan foreign key setelah tabel tersedia, lalu uji seluruh migration. |
| ID dan fixture | Narasi menyebut UUID v7 tetapi contoh default menggunakan `gen_random_uuid()`; design.md memakai ID fixture seperti p-cappuccino/CUS-001. Tetapkan UUID internal dan kode referensi terpisah serta mapping seed secara eksplisit. |
| Keutuhan kontrak | DDL belum memuat seluruh field UI: status pelanggan, fulfillment/meja/label Order, batas dan eligibility pelanggan promo, serta seluruh tab Settings. Lengkapi skema dan OpenAPI sebelum mengklaim semua menu tercakup. |
| Seed dan default | Tabel migration sumber menyebut tiga kategori, delapan produk, lima order, baseline 140 pada COFFEE1, serta threshold stok 5. Acuan design.md adalah empat kategori, 12 produk, tujuh order, baseline total 140 tersebar per promo, dan threshold awal 8. Perbaiki seed migration; jangan mengubah fixture UI untuk mengikuti ringkasan yang keliru. |
| Algoritma uang | Pseudocode sumber masih memakai float64 serta index sebagai tie-break. Ganti dengan integer quotient/remainder dan ID item ascending; unit index descending. Uji overflow, bobot nol, dan invariant penuh. |
| CHECK dan agregat | Invariant antarbaris tidak cukup dijamin CHECK satu baris. Tetapkan service transaction/constraint trigger yang tepat. Pembaruan total Customer harus menggunakan satu pemilik logika; jangan menjalankan trigger dan service increment sekaligus. |
| Draft dan idempotency | Redis cleanup terpisah dari commit DB. Simpan penanda operasi/draft yang dibayar secara persisten, scope idempotency pada staf/operasi, tangani request concurrent, dan cegah versi draft lama dibayar ulang setelah cleanup gagal atau key baru dibuat. |
| Void dan histori | Pembalikan stok mengacu movement Sale awal. Sumber memerintahkan delete discount_usage tetapi tabel retention menyebut immutable; tentukan model pembatalan redemption yang tetap menyimpan audit. Return Completed tidak boleh direject untuk membuka akses Void. |
| API dan izin | Ringkasan endpoint pay berbeda dengan detail, CORS belum menyebut PUT untuk Settings, dan izin read Users/export Reports berbeda antara ringkasan dan matriks. Gunakan endpoint detail serta matriks role sebagai dasar penyelarasan OpenAPI, lalu verifikasi frontend. |
| Settings dan pajak | UPDATE version pada contoh optimistic locking belum menuliskan kenaikan version di SET. Ada tax rate per produk dan per Settings; acuan fixture memakai tarif transaksi dari Settings. Tentukan aturan tunggal sebelum menghitung total. |
| Audit dan outbox | Sumber menyebut audit langsung di transaksi dan audit ditulis worker. Pilih audit transaksi sebagai catatan utama, jelaskan tujuan event outbox, dan gunakan ID event yang tidak meniadakan mutasi berulang pada entitas yang sama. |
| Refund scope | DDL mengizinkan Store Credit sedangkan bagian out-of-scope mengecualikannya. UI/API versi awal hanya menawarkan metode yang memang didukung; status return tetap Draft/Pending/Completed/Rejected tanpa Approved tersimpan tambahan. |
| Autentikasi staf | Rincian cookie refresh/logout, cara lookup hash token, panjang password/hash, dan penyimpanan access token perlu ditinjau serta diuji sebelum produksi. Hal ini tidak menciptakan kebutuhan autentikasi pembeli. |
| Operasional | Versi library, target latency, availability, retensi, backup, dan estimasi 14 minggu berasal dari PRD Word. Tetapkan dan verifikasi pada lingkungan implementasi; dokumen ini tidak menyatakan target tersebut telah tercapai. |

### 15.5 Batas versi awal dan pengembangan berikutnya

**Versi awal:** staf login, checkout Walk-in, profil Customer opsional, katalog, stok, Sale/Order/Return, promo umum dan pelanggan tertentu, laporan, konfigurasi, serta pembayaran simulasi sesuai lingkup PRD.

**Pengembangan berikutnya:** program membership/loyalty, portal atau akun pembeli, pemrosesan dana nyata, pengiriman email/struk eksternal, serta antrean persiapan setelah bayar dengan status fulfillment terpisah. Tidak satu pun menjadi prasyarat agar orang bisa membeli kopi.
