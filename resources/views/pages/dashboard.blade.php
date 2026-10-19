@extends('layouts.app')

@section('title', 'Dasbor')

@section('content')
<div x-data="dashboard" x-cloak class="pb-6" @keydown.window.escape="payOpen && closePay()">

    <x-page-header title="Selamat datang kembali, {{ auth()->user()?->name ?? 'Admin' }}! 👋" subtitle="Berikut ringkasan performa penjualan dan kasir Anda hari ini.">
        <a href="/orders" class="btn btn-secondary">
            <x-icon name="clipboard-list" class="size-4.5 text-primary"/> Antrean Pesanan Meja
        </a>
        <a href="/sales" class="btn btn-primary">
            <x-icon name="plus" class="size-4.5"/> Transaksi Kasir Baru
        </a>
    </x-page-header>

    {{-- ================= STATISTIK REAL-TIME ================= --}}
    <div class="grid grid-cols-2 xl:grid-cols-[repeat(3,minmax(0,1fr))_minmax(0,1.12fr)] gap-[22px] max-[480px]:gap-3">
        <div class="card p-5 sm:p-6 min-h-[120px] flex flex-col min-w-0">
            <div class="flex items-center gap-3.5 min-w-0">
                <span class="grid size-[52px] shrink-0 place-items-center rounded-full bg-primary-soft text-focus max-[480px]:size-11">
                    <x-icon name="dollar" class="size-[26px] max-[480px]:size-6"/>
                </span>
                <div class="min-w-0">
                    <p class="text-[14px]/[20px] text-ink-secondary truncate">Total Penjualan</p>
                    <p class="font-semibold text-ink tabular-nums mt-0.5 text-[24px]/[30px] tracking-tight" x-text="$rp(todaySales)"></p>
                </div>
            </div>
            <p class="mt-auto pt-3 text-[12px]/[18px] text-ink-muted">Penjualan hari ini</p>
        </div>

        <div class="card p-5 sm:p-6 min-h-[120px] flex flex-col min-w-0">
            <div class="flex items-center gap-3.5 min-w-0">
                <span class="grid size-[52px] shrink-0 place-items-center rounded-full bg-primary-soft text-focus max-[480px]:size-11">
                    <x-icon name="clipboard-list" class="size-[26px] max-[480px]:size-6"/>
                </span>
                <div class="min-w-0">
                    <p class="text-[14px]/[20px] text-ink-secondary truncate">Pesanan</p>
                    <p class="font-semibold text-ink tabular-nums mt-0.5 text-[28px]/[34px] tracking-tight" x-text="todayOrdersCount"></p>
                </div>
            </div>
            <p class="mt-auto pt-3 text-[12px]/[18px] text-ink-muted">Pesanan hari ini</p>
        </div>

        <div class="card p-5 sm:p-6 min-h-[120px] flex flex-col min-w-0">
            <div class="flex items-center gap-3.5 min-w-0">
                <span class="grid size-[52px] shrink-0 place-items-center rounded-full bg-primary-soft text-focus max-[480px]:size-11">
                    <x-icon name="users" class="size-[26px] max-[480px]:size-6"/>
                </span>
                <div class="min-w-0">
                    <p class="text-[14px]/[20px] text-ink-secondary truncate">Pelanggan</p>
                    <p class="font-semibold text-ink tabular-nums mt-0.5 text-[28px]/[34px] tracking-tight" x-text="totalCustomersCount"></p>
                </div>
            </div>
            <p class="mt-auto pt-3 text-[12px]/[18px] text-ink-muted">Pelanggan terdaftar</p>
        </div>

        <div class="card p-5 sm:p-6 min-h-[120px] flex flex-col min-w-0">
            <div class="flex items-center gap-3.5 min-w-0">
                <span class="grid size-[52px] shrink-0 place-items-center rounded-full bg-primary-soft text-focus max-[480px]:size-11">
                    <x-icon name="cart" class="size-[26px] max-[480px]:size-6"/>
                </span>
                <div class="min-w-0">
                    <p class="text-[14px]/[20px] text-ink-secondary truncate">Rata-rata Nilai Pesanan</p>
                    <p class="font-semibold text-ink tabular-nums mt-0.5 text-[24px]/[30px] tracking-tight" x-text="$rp(todayAov)"></p>
                </div>
            </div>
            <p class="mt-auto pt-3 text-[12px]/[18px] text-ink-muted">Rata-rata hari ini</p>
        </div>
    </div>

    {{-- ================= WORKSPACE ================= --}}
    <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,277fr)_minmax(0,324fr)] gap-6 mt-7 items-start">

        {{-- ---------- PANEL PENJUALAN SAAT INI ---------- --}}
        <section class="card p-5" aria-labelledby="current-sale-title">
            <div class="flex items-center justify-between">
                <h2 id="current-sale-title" class="text-[18px]/[24px] font-semibold">Penjualan Saat Ini</h2>
                <div class="relative">
                    <button type="button" class="btn btn-secondary btn-icon btn-sm" @click="kebabOpen = !kebabOpen"
                            :aria-expanded="kebabOpen" aria-label="Aksi lainnya untuk penjualan ini">
                        <x-icon name="more" class="size-4"/>
                    </button>
                    <div x-show="kebabOpen" x-cloak @click.outside="kebabOpen = false" @keydown.escape="kebabOpen = false"
                         x-transition:enter="pop-enter" x-transition:leave="pop-leave"
                         class="absolute right-0 top-[calc(100%+8px)] w-52 rounded-xl bg-white border border-line shadow-[0_12px_40px_rgba(30,41,59,0.14)] p-1.5 z-30" role="menu">
                        <button type="button" class="w-full text-left px-3 py-2 rounded-lg text-[13px] hover:bg-surface-muted" role="menuitem"
                                @click="kebabOpen = false; discountOpen = true">Terapkan Diskon</button>
                        <button type="button" class="w-full text-left px-3 py-2 rounded-lg text-[13px] hover:bg-surface-muted" role="menuitem"
                                @click="kebabOpen = false; noteOpen = true">Tambah Catatan</button>
                        <button type="button" class="w-full text-left px-3 py-2 rounded-lg text-[13px] hover:bg-surface-muted" role="menuitem"
                                @click="kebabOpen = false; clearCart()">Kosongkan Keranjang</button>
                    </div>
                </div>
            </div>

            <div class="h-px bg-divider mt-4"></div>

            {{-- Input Nama Pemesan (Detail Pesanan untuk Pemanggilan Saat Diantar) --}}
            <div class="mt-3.5 mb-2.5">
                <div class="flex items-center justify-between mb-1.5">
                    <label for="pos-customer-input" class="text-[12.5px] font-semibold text-ink flex items-center gap-1.5">
                        <x-icon name="user" class="size-3.5 text-primary"/>
                        <span>Nama Pemesan</span>
                    </label>
                    <span class="text-[11px] text-ink-muted">Detail panggil pesanan</span>
                </div>

                <div class="relative">
                    <div class="relative flex items-center">
                        <input id="pos-customer-input" type="text"
                               class="input pr-10 text-[13px] h-10 w-full"
                               placeholder="Nama pemesan (misal: Budi, Bu Ani)..."
                               x-model="customerName"
                               autocomplete="off">

                        <div class="absolute right-1.5 flex items-center">
                            <button type="button" x-show="customerName" x-cloak
                                    @click="clearCustomer()"
                                    class="size-7 grid place-items-center rounded-md text-ink-muted hover:text-ink hover:bg-surface-muted"
                                    title="Hapus nama">
                                <x-icon name="x" class="size-3.5"/>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pilihan Tipe Pesanan: Dine In (Makan di Tempat) vs Takeaway (Bawa Pulang) --}}
            <div class="mb-3.5 p-2.5 bg-surface-muted/70 rounded-xl border border-line">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[12px] font-semibold text-ink">Tipe Pesanan</span>
                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full"
                          :class="orderType === 'dine_in' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                          x-text="orderType === 'dine_in' ? 'Diantar ke Meja' : 'Ambil di Kasir (Bungkus)'"></span>
                </div>

                <div class="grid grid-cols-2 gap-1.5 p-1 bg-white rounded-lg border border-line">
                    <button type="button"
                            @click="orderType = 'dine_in'"
                            class="flex items-center justify-center gap-2 py-2 px-2.5 rounded-md text-xs font-bold transition"
                            :class="orderType === 'dine_in'
                                ? 'bg-emerald-700 text-white shadow-sm ring-1 ring-emerald-800'
                                : 'text-ink-secondary hover:text-ink hover:bg-slate-50'">
                        <x-icon name="utensils" class="size-3.5 shrink-0"/>
                        <span>Dine In</span>
                    </button>
                    <button type="button"
                            @click="orderType = 'takeaway'"
                            class="flex items-center justify-center gap-2 py-2 px-2.5 rounded-md text-xs font-bold transition"
                            :class="orderType === 'takeaway'
                                ? 'bg-amber-600 text-white shadow-sm ring-1 ring-amber-700'
                                : 'text-ink-secondary hover:text-ink hover:bg-slate-50'">
                        <x-icon name="shopping-bag" class="size-3.5 shrink-0"/>
                        <span>Takeaway</span>
                    </button>
                </div>

                {{-- Input Nomor Meja (Tampil saat Dine In) --}}
                <div x-show="orderType === 'dine_in'" x-transition class="mt-2.5 pt-2 border-t border-slate-200/70">
                    <div class="flex items-center justify-between mb-1">
                        <label for="pos-table-input" class="text-[11.5px] font-semibold text-ink flex items-center gap-1">
                            <span>Nomor Meja</span>
                            <span class="text-[10px] text-ink-muted font-normal">(opsional)</span>
                        </label>
                        <span class="text-[10.5px] text-emerald-700 font-medium">Pasti diantarkan ke meja</span>
                    </div>
                    <input id="pos-table-input" type="text"
                           class="input text-[12.5px] h-9 w-full bg-white"
                           placeholder="Nomor meja (misal: 12, Meja 5)..."
                           x-model="tableNumber"
                           autocomplete="off">
                </div>

                <p class="text-[10.5px]/[15px] text-ink-muted mt-2">
                    <span x-show="orderType === 'dine_in'">Pesanan langsung di kasir untuk makan di tempat, hidangan pasti diantarkan ke meja pelanggan.</span>
                    <span x-show="orderType === 'takeaway'">Pesanan dibungkus untuk dibawa pulang dan diambil langsung di kasir saat selesai.</span>
                </p>
            </div>



            {{-- daftar item --}}
            <div class="mt-1 max-h-[420px] overflow-y-auto nice-scroll -mx-1 px-1">
                <template x-if="cartLines.length === 0">
                    <div class="py-14 text-center">
                        <span class="mx-auto mb-3 grid size-12 place-items-center rounded-full bg-surface-muted text-ink-muted">
                            <x-icon name="cart" class="size-6"/>
                        </span>
                        <p class="text-[15px]/[22px] font-medium text-ink">Keranjang Anda kosong</p>
                        <p class="text-[13px]/[19px] text-ink-secondary mt-1">Tambahkan produk untuk memulai penjualan.</p>
                    </div>
                </template>
                <template x-for="line in cartLines" :key="line.productId">
                    <div class="grid grid-cols-[56px_minmax(0,1fr)_auto] max-[480px]:grid-cols-[44px_minmax(0,1fr)_auto] gap-x-3.5 py-3.5 border-b border-divider last:border-0 items-center">
                        <div class="flex items-center gap-3.5">
                            <img :src="`/images/products/${line.productId}.jpg`" :alt="`Foto ${line.product?.name ?? ''}`"
                                 class="thumb size-14 max-[480px]:size-11" loading="lazy" onerror="PM.imgFallback(this)">
                        </div>
                        <div class="min-w-0">
                            <p class="text-[13px]/[19px] font-medium text-ink truncate" x-text="line.product?.name"></p>
                            <p class="text-[12.5px]/[17px] text-ink-secondary tabular-nums" x-text="$rp(line.unitPrice)"></p>
                            <div class="flex items-center gap-3 mt-1.5 max-[480px]:mt-0.5 flex-wrap">
                                <div class="inline-flex items-center rounded-lg border border-line bg-white h-9">
                                    <button type="button" class="grid w-9 h-9 place-items-center rounded-l-lg text-ink-secondary hover:bg-surface-muted disabled:opacity-40 disabled:cursor-not-allowed"
                                            :disabled="line.quantity <= 1" @click="dec(line.productId)"
                                            :aria-label="`Kurangi jumlah ${line.product?.name}`">
                                        <x-icon name="minus" class="size-3.5"/>
                                    </button>
                                    <span class="w-8 text-center text-[14px] font-medium tabular-nums" x-text="line.quantity"
                                          :aria-label="`Jumlah ${line.product?.name}`"></span>
                                    <button type="button" class="grid w-9 h-9 place-items-center rounded-r-lg text-ink-secondary hover:bg-surface-muted"
                                            @click="inc(line.productId)"
                                            :aria-label="`Tambah jumlah ${line.product?.name}`">
                                        <x-icon name="plus" class="size-3.5"/>
                                    </button>
                                </div>
                                <p class="text-[13.5px]/[19px] font-semibold text-ink tabular-nums" x-text="$rp(line.lineTotal)"></p>
                            </div>
                        </div>
                        <button type="button" class="grid size-11 place-items-center self-start mt-1 rounded-lg text-ink-muted hover:text-danger hover:bg-danger-soft"
                                @click="remove(line.productId)" :aria-label="`Hapus ${line.product?.name}`">
                            <x-icon name="trash" class="size-[17px]"/>
                        </button>
                    </div>
                </template>
            </div>

            {{-- tambah catatan --}}
            <div class="mt-2.5">
                <template x-if="!noteOpen && !$store.posy.draftSale.note">
                    <button type="button" class="btn btn-muted btn-xs" @click="noteOpen = true">+ Tambah Catatan</button>
                </template>
                <template x-if="noteOpen || $store.posy.draftSale.note">
                    <div>
                        <template x-if="!noteOpen">
                            <button type="button" class="w-full text-left rounded-lg bg-surface-muted px-3.5 py-2.5 text-[12.5px]/[18px] text-ink-secondary hover:bg-[#ebebef] flex items-center justify-between gap-2"
                                    @click="noteOpen = true">
                                <span class="truncate" x-text="$store.posy.draftSale.note"></span>
                                <x-icon name="pencil" class="size-3.5 shrink-0"/>
                            </button>
                        </template>
                        <template x-if="noteOpen">
                            <div>
                                <label class="sr-only" for="sale-note">Catatan penjualan</label>
                                <textarea id="sale-note" class="textarea !min-h-[72px] text-[13px]" placeholder="Catatan untuk pesanan ini…" maxlength="500"
                                          x-model="noteDraft"></textarea>
                                <div class="flex items-center justify-end gap-2 mt-2">
                                    <button type="button" class="btn btn-secondary btn-xs" @click="noteOpen = false; noteDraft = $store.posy.draftSale.note">Batal</button>
                                    <button type="button" class="btn btn-primary btn-xs" @click="saveNote()">Simpan Catatan</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            {{-- ringkasan --}}
            <div class="mt-4 pt-4 border-t border-divider space-y-1.5">
                <div class="flex items-center justify-between text-[13px]/[19px]">
                    <span class="text-ink-secondary">Subtotal</span>
                    <span class="text-ink tabular-nums" x-text="$rp(totals.subtotal)"></span>
                </div>
                <div class="flex items-center justify-between text-[13px]/[19px]" x-show="$store.posy.draftSale.discount">
                    <span class="text-ink-secondary">Diskon (<span x-text="$store.posy.draftSale.discount?.code"></span>)</span>
                    <span class="text-positive tabular-nums" x-text="'-' + $rp(totals.discount)"></span>
                </div>
                <div class="flex items-center justify-between text-[13px]/[19px]">
                    <span class="text-ink-secondary" x-text="settings.taxEnabled ? `${settings.taxName} (${settings.taxRatePercent}%)` : `${settings.taxName} (nonaktif)`"></span>
                    <span class="text-ink tabular-nums" x-text="$rp(totals.tax)"></span>
                </div>
            </div>
            <div class="mt-2.5 pt-3.5 border-t border-divider flex items-end justify-between">
                <span class="text-[15px]/[22px] font-semibold">Total</span>
                <span class="text-[28px]/[34px] font-semibold tabular-nums" x-text="$rp(totals.total)"></span>
            </div>

            {{-- footer aksi --}}
            <div class="grid grid-cols-[minmax(0,1fr)_minmax(0,2fr)] gap-3.5 mt-5 max-[380px]:grid-cols-1">
                <button type="button" class="btn btn-muted h-[58px] max-[380px]:h-12" @click="clearCart()"
                        :disabled="!cartLines.length">Kosongkan Keranjang</button>
                <button type="button" class="btn btn-primary h-[58px] max-[380px]:h-12 text-[15px] tabular-nums" @click="openPay()"
                        :disabled="!cartLines.length || payState === 'processing'"
                        x-text="`Bayar ${$rp(totals.total)}`"></button>
            </div>
            <p x-show="stockProblem" x-cloak class="mt-2.5 text-[12px]/[18px] text-danger" x-text="stockProblem"></p>
            <p class="mt-3 text-[11px]/[16px] text-ink-muted text-center">Pembayaran merupakan simulasi demo — tidak ada dana yang benar-benar ditransfer.</p>
        </section>

        {{-- ---------- KATALOG PRODUK ---------- --}}
        <section class="card p-4" aria-labelledby="catalog-title">
            <h2 id="catalog-title" class="sr-only">Katalog Produk</h2>
            <div class="flex items-center gap-2.5">
                <div class="flex items-center gap-2.5 overflow-x-auto no-scrollbar flex-1 min-w-0 py-0.5" role="group" aria-label="Filter kategori">
                    <template x-for="cat in categories" :key="cat">
                        <button type="button" class="chip" :aria-pressed="activeCategory === cat" @click="activeCategory = cat"
                                x-text="cat"></button>
                    </template>
                </div>
                <div class="h-6 w-px bg-divider shrink-0"></div>
                <span class="grid size-10 place-items-center rounded-lg bg-primary-soft text-primary shrink-0" aria-label="Tampilan kisi aktif">
                    <x-icon name="grid" class="size-5"/>
                </span>
            </div>

            {{-- pencarian mobile (header menyembunyikan di layar kecil) --}}
            <div class="md:hidden mt-3 relative">
                <x-icon name="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-ink-muted pointer-events-none"/>
                <input type="search" class="input pl-10" placeholder="Cari produk…" aria-label="Cari produk" x-model="search">
            </div>

            <div class="mt-4">
                <template x-if="filteredProducts.length === 0">
                    <div class="py-16 text-center">
                        <span class="mx-auto mb-3 grid size-12 place-items-center rounded-full bg-surface-muted text-ink-muted">
                            <x-icon name="search" class="size-6"/>
                        </span>
                        <p class="text-[15px]/[22px] font-medium">Produk tidak ditemukan</p>
                        <p class="text-[13px]/[19px] text-ink-secondary mt-1">Coba kata kunci atau kategori lain.</p>
                        <button type="button" class="btn btn-secondary btn-sm mt-4" @click="search = ''; activeCategory = 'Semua'">Hapus pencarian</button>
                    </div>
                </template>
                <div class="grid grid-cols-2 min-[480px]:grid-cols-3 min-[768px]:grid-cols-4 gap-4 max-[480px]:gap-2.5">
                    <template x-for="p in filteredProducts" :key="p.id">
                        <article class="group rounded-[10px] border border-line bg-white overflow-hidden hover:shadow-card transition-shadow">
                            <div class="aspect-square bg-surface-muted relative overflow-hidden">
                                <img :src="`/images/products/${p.id}.jpg`" :alt="`Foto ${p.name}`" loading="lazy"
                                     class="size-full object-cover" onerror="PM.imgFallback(this)">
                                <span x-show="p.status === 'OutOfStock' || (Boolean(p.trackStock && p.track_stock !== false && p.track_stock !== 0) && settings.trackInventory && p.stock <= 0)" x-cloak
                                      class="absolute top-2 left-2 badge badge-danger">Habis</span>
                                <span x-show="p.status !== 'OutOfStock' && (Boolean(p.trackStock && p.track_stock !== false && p.track_stock !== 0) && settings.trackInventory && p.stock > 0 && p.stock <= p.lowStockThreshold)" x-cloak
                                      class="absolute top-2 left-2 badge badge-amber">Stok Menipis</span>
                            </div>
                            <div class="p-2.5">
                                <h3 class="text-[13px]/[18px] font-medium text-ink min-h-[36px] line-clamp-2" x-text="p.name"></h3>
                                <div class="flex items-center justify-between mt-1.5">
                                    <span class="text-[13px]/[18px] font-medium text-ink tabular-nums" x-text="$rp(p.price)"></span>
                                    <button type="button"
                                            class="grid size-8 place-items-center rounded-[8px] bg-primary text-white hover:bg-primary-hover active:bg-primary-pressed transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                                            @click="add(p.id)"
                                            :disabled="p.status === 'OutOfStock' || (Boolean(p.trackStock && p.track_stock !== false && p.track_stock !== 0) && settings.trackInventory && p.stock <= 0)"
                                            :aria-label="`Tambah ${p.name} ke penjualan`">
                                        <x-icon name="plus" class="size-4"/>
                                    </button>
                                </div>
                            </div>
                        </article>
                    </template>
                </div>
            </div>
        </section>
    </div>

    {{-- ================= DIALOG PEMBAYARAN ================= --}}
    <div x-show="payOpen" x-cloak class="fixed inset-0 z-[85] grid place-items-center p-4" role="dialog" aria-modal="true" aria-label="Simulasi pembayaran">
        <div class="absolute inset-0 bg-[#1c2b2b]/45" @click="closePay()" x-transition:enter="fade-enter" x-transition:leave="fade-leave"></div>
        <div x-ref="payPanel" @keydown.tab="PM.trap($event, $refs.payPanel)" @keydown.escape="closePay()"
             x-transition:enter="pop-enter" x-transition:leave="pop-leave"
             class="relative w-full max-w-[440px] rounded-2xl bg-white shadow-[0_24px_80px_rgba(30,41,59,0.22)] overflow-hidden">
            <template x-if="payState !== 'success'">
                <div>
                    <div class="px-6 pt-6 pb-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <h2 class="text-[18px]/[24px] font-semibold">Simulasi Pembayaran</h2>
                                <p class="text-[13px]/[19px] text-ink-secondary mt-0.5">Pembayaran demo — tidak ada dana yang ditransfer.</p>
                            </div>
                            <button type="button" class="btn btn-secondary btn-icon btn-sm" @click="closePay()" aria-label="Tutup dialog pembayaran">
                                <x-icon name="x" class="size-4"/>
                            </button>
                        </div>
                        <div class="mt-4 rounded-xl bg-surface-muted px-5 py-4 flex items-end justify-between">
                            <span class="text-[14px]/[20px] text-ink-secondary">Total tagihan</span>
                            <span class="text-[28px]/[34px] font-semibold tabular-nums" x-text="$rp(totals.total)"></span>
                        </div>
                        <div class="mt-2.5 flex items-center justify-between text-[12.5px] px-3.5 py-2 rounded-lg bg-surface-muted/60 border border-line/60">
                            <span class="text-ink-secondary flex items-center gap-1.5">
                                <x-icon name="user" class="size-3.5 text-primary"/>
                                Pemesan:
                            </span>
                            <span class="font-bold text-ink truncate max-w-[200px]" x-text="customerDisplayName"></span>
                        </div>
                    </div>
                    <div class="px-6 pb-6 space-y-4">
                        <fieldset>
                            <legend class="field-label">Metode pembayaran</legend>
                            <div class="grid grid-cols-3 gap-2.5">
                                <template x-for="m in paymentMethods" :key="m">
                                    <button type="button" class="rounded-xl border px-3 py-3.5 flex flex-col items-center gap-1.5 transition-colors"
                                            :class="payMethod === m ? 'border-primary bg-primary-soft/60 text-focus' : 'border-line hover:border-[#dfe3e8]'"
                                            @click="payMethod = m" :aria-pressed="payMethod === m">
                                        <x-icon name="banknote" class="size-5" x-show="m === 'Cash'"/>
                                        <x-icon name="credit-card" class="size-5" x-show="m === 'Card'"/>
                                        <x-icon name="wallet" class="size-5" x-show="m === 'Digital Wallet'"/>
                                        <span class="text-[12.5px] font-medium" x-text="m === 'Cash' ? 'Tunai' : m === 'Card' ? 'Kartu' : 'Dompet Digital'"></span>
                                    </button>
                                </template>
                            </div>
                            <p class="field-help" x-show="payMethod === 'Card' || payMethod === 'Digital Wallet'">Metode ini hanya simulasi — tidak ada nomor kartu yang diminta.</p>
                        </fieldset>

                        <div x-show="payMethod === 'Cash'">
                            <label class="field-label" for="cash-received">Uang diterima</label>
                            <input id="cash-received" type="text" inputmode="numeric" class="input tabular-nums" placeholder="0"
                                   x-model="payCashReceived" :aria-invalid="cashShort">
                            <p class="field-error" x-show="cashShort" x-cloak>Uang diterima kurang dari total.</p>
                            <p class="field-error" x-show="!settings.allowCashChange && !Number.isNaN(cashNumeric) && cashNumeric !== totals.total" x-cloak>
                                Kembalian dinonaktifkan — nominal harus tepat sama dengan total.
                            </p>
                            <p class="field-help" x-show="changeAmount !== null && changeAmount >= 0" x-cloak>
                                Kembalian: <span class="font-medium text-ink tabular-nums" x-text="$rp(changeAmount)"></span>
                            </p>
                        </div>

                        <p class="text-[12.5px]/[18px] text-danger flex items-start gap-2" x-show="payState === 'error'" x-cloak>
                            <x-icon name="alert-circle" class="size-4 shrink-0 mt-0.5"/>
                            <span x-text="payError || 'Pembayaran simulasi gagal. Keranjang tetap tersedia, silakan coba lagi.'"></span>
                        </p>

                        <div class="grid grid-cols-2 gap-3">
                            <button type="button" class="btn btn-secondary" @click="closePay()" :disabled="payState === 'processing'">Batal</button>
                            <button type="button" class="btn btn-primary" @click="confirmPay()" :disabled="payDisabled"
                                    x-text="payState === 'processing' ? 'Memproses…' : payState === 'error' ? 'Coba Lagi' : 'Konfirmasi Pembayaran'"></button>
                        </div>
                    </div>
                </div>
            </template>
            <template x-if="payState === 'success'">
                <div class="px-6 py-8 text-center">
                    <span class="mx-auto grid size-16 place-items-center rounded-full bg-[#e7f4f0] text-[#2f7d68]">
                        <x-icon name="check-circle" class="size-8"/>
                    </span>
                    <h2 class="text-[19px]/[26px] font-semibold mt-4">Pembayaran Berhasil</h2>
                    <p class="text-[13.5px]/[20px] text-ink-secondary mt-1.5">Transaksi demo <span class="font-medium text-ink tabular-nums" x-text="payResult?.saleId"></span> telah tersimpan di riwayat Penjualan.</p>
                    <div class="grid grid-cols-2 gap-3 mt-6">
                        <button type="button" class="btn btn-secondary" @click="printReceipt(payResult?.saleId)">Unduh Struk</button>
                        <a :href="`/sales?sale=${payResult?.saleId}`" class="btn btn-primary">Lihat Transaksi</a>
                    </div>
                    <button type="button" class="mt-3 text-[13px] font-medium text-primary hover:text-primary-hover" @click="finishPay()">Transaksi Baru</button>
                </div>
            </template>
        </div>
    </div>

    {{-- ================= DIALOG DISKON ================= --}}
    <div x-show="discountOpen" x-cloak class="fixed inset-0 z-[85] grid place-items-center p-4" role="dialog" aria-modal="true" aria-label="Terapkan diskon">
        <div class="absolute inset-0 bg-[#1c2b2b]/45" @click="discountOpen = false" x-transition:enter="fade-enter" x-transition:leave="fade-leave"></div>
        <div x-ref="discountPanel" @keydown.tab="PM.trap($event, $refs.discountPanel)" @keydown.escape="discountOpen = false"
             x-transition:enter="pop-enter" x-transition:leave="pop-leave"
             class="relative w-full max-w-[480px] rounded-2xl bg-white shadow-[0_24px_80px_rgba(30,41,59,0.22)] overflow-hidden max-h-[86vh] flex flex-col">
            <div class="px-5 pt-5 pb-4">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-[18px]/[24px] font-semibold">Terapkan Diskon</h2>
                        <p class="text-[13px]/[19px] text-ink-secondary mt-0.5">Satu diskon per penjualan. Diskon kedua akan menggantikan yang aktif.</p>
                    </div>
                    <button type="button" class="btn btn-secondary btn-icon btn-sm" @click="discountOpen = false" aria-label="Tutup dialog diskon">
                        <x-icon name="x" class="size-4"/>
                    </button>
                </div>
                <div class="relative mt-3.5">
                    <x-icon name="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-ink-muted pointer-events-none"/>
                    <input type="search" class="input pl-10" placeholder="Cari nama atau kode diskon…" aria-label="Cari diskon" x-model="discountQuery">
                </div>
            </div>
            <div class="flex-1 overflow-y-auto nice-scroll px-5 pb-2">
                <template x-if="$store.posy.draftSale.discount">
                    <div class="mb-3 rounded-xl border border-primary/50 bg-primary-soft/50 px-4 py-3 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-[13.5px]/[18px] font-medium" x-text="`${$store.posy.draftSale.discount.name} (${$store.posy.draftSale.discount.code})`"></p>
                            <p class="text-[12px]/[16px] text-ink-secondary">Sedang aktif pada penjualan ini.</p>
                        </div>
                        <button type="button" class="btn btn-danger-soft btn-xs" @click="removeDiscount()">Hapus</button>
                    </div>
                </template>
                <template x-for="d in eligibleDiscounts" :key="d.id">
                    <div class="rounded-xl border border-line px-4 py-3 mb-2.5 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="text-[13.5px]/[18px] font-medium truncate" x-text="d.name"></p>
                                <span class="font-mono text-[11.5px] rounded-md bg-surface-muted px-1.5 py-0.5 text-ink-secondary" x-text="d.code"></span>
                            </div>
                            <p class="text-[12px]/[17px] text-ink-secondary mt-0.5">
                                <span x-text="d.type === 'percentage' ? `${d.value}%` : $rp(d.value)"></span>
                                ·
                                <span x-text="d.appliesTo === 'all' ? 'Semua produk' : d.appliesTo === 'categories' ? d.categories.join(', ') : 'Produk terpilih'"></span>
                            </p>
                            <p class="text-[11.5px]/[16px] text-ink-muted mt-0.5" x-show="discountReason(d)" x-text="discountReason(d)"></p>
                        </div>
                        <button type="button" class="btn btn-primary btn-xs shrink-0" :disabled="!!discountReason(d)" @click="tryApplyDiscount(d)">Terapkan</button>
                    </div>
                </template>
            </div>
            <div class="px-5 py-4 border-t border-divider">
                <button type="button" class="btn btn-secondary w-full" @click="discountOpen = false">Tutup</button>
            </div>
        </div>
    </div>

    {{-- ================= DIALOG GANTI PELANGGAN ================= --}}
    <div x-show="changeCustomerOpen" x-cloak class="fixed inset-0 z-[85] grid place-items-center p-4" role="dialog" aria-modal="true" aria-label="Ganti pelanggan">
        <div class="absolute inset-0 bg-[#1c2b2b]/45" @click="changeCustomerOpen = false" x-transition:enter="fade-enter" x-transition:leave="fade-leave"></div>
        <div class="relative w-full max-w-[420px] rounded-2xl bg-white shadow-[0_24px_80px_rgba(30,41,59,0.22)] p-6" x-transition:enter="pop-enter" x-transition:leave="pop-leave">
            <h2 class="text-[18px]/[24px] font-semibold">Ganti pelanggan penjualan ini?</h2>
            <p class="text-[13.5px]/[20px] text-ink-secondary mt-2">
                Pelanggan saat ini: <span class="font-medium text-ink" x-text="$store.posy.customer($store.posy.draftSale.customerId)?.name"></span>.
                Pelanggan baru: <span class="font-medium text-ink" x-text="changeCustomerFor?.name"></span>.
                Item dan catatan tidak berubah.
            </p>
            <div class="grid grid-cols-2 gap-3 mt-5">
                <button type="button" class="btn btn-secondary" @click="changeCustomerOpen = false">Pertahankan</button>
                <button type="button" class="btn btn-primary" @click="$store.posy.setCartCustomer(changeCustomerFor.id); changeCustomerOpen = false">Ganti Pelanggan</button>
            </div>
        </div>
    </div>
</div>
@endsection
