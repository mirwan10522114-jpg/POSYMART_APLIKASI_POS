@extends('layouts.app')

@section('title', 'Produk')
@section('subtitle', 'Kelola katalog, harga, dan stok Anda.')

@section('content')
<div x-data="productsPage" x-cloak @keydown.window.escape="formOpen ? closeForm() : (detailId && closeDetail())">

    <x-page-header title="Produk" subtitle="Kelola katalog, harga, dan stok Anda.">
        <button type="button" class="btn btn-secondary" @click="exportCsv()">
            <x-icon name="download" class="size-4.5"/> Ekspor CSV
        </button>
        <button type="button" class="btn btn-primary" @click="openCreate()" x-show="$store.posy.can('manageProducts')">
            <x-icon name="plus" class="size-4.5"/> Tambah Produk
        </button>
    </x-page-header>

    {{-- ================= STATISTIK ================= --}}
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-[22px] mt-6 max-[480px]:gap-3" role="group" aria-label="Statistik katalog">
        <div class="card p-5 min-h-[120px] flex flex-col">
            <div class="flex items-center gap-3.5 min-w-0">
                <span class="grid size-11 shrink-0 place-items-center rounded-full bg-primary-soft text-focus">
                    <x-icon name="package" class="size-5.5"/>
                </span>
                <div class="min-w-0">
                    <p class="text-[13px]/[18px] text-ink-secondary truncate">Total Produk</p>
                    <p class="text-[26px]/[32px] font-semibold tabular-nums" x-text="stats.total"></p>
                </div>
            </div>
            <p class="mt-auto pt-2.5 text-[12px]/[17px] text-ink-muted">Seluruh katalog</p>
        </div>
        <div class="card p-5 min-h-[120px] flex flex-col">
            <div class="flex items-center gap-3.5 min-w-0">
                <span class="grid size-11 shrink-0 place-items-center rounded-full bg-primary-soft text-focus">
                    <x-icon name="check-circle" class="size-5.5"/>
                </span>
                <div class="min-w-0">
                    <p class="text-[13px]/[18px] text-ink-secondary truncate">Produk Aktif</p>
                    <p class="text-[26px]/[32px] font-semibold tabular-nums" x-text="stats.active"></p>
                </div>
            </div>
            <p class="mt-auto pt-2.5 text-[12px]/[17px] text-ink-muted">Seluruh katalog</p>
        </div>
        <div class="card p-5 min-h-[120px] flex flex-col">
            <div class="flex items-center gap-3.5 min-w-0">
                <span class="grid size-11 shrink-0 place-items-center rounded-full bg-amber-soft text-amber">
                    <x-icon name="alert-circle" class="size-5.5"/>
                </span>
                <div class="min-w-0">
                    <p class="text-[13px]/[18px] text-ink-secondary truncate">Stok Menipis</p>
                    <p class="text-[26px]/[32px] font-semibold tabular-nums" x-text="stats.low"></p>
                </div>
            </div>
            <p class="mt-auto pt-2.5 text-[12px]/[17px] text-ink-muted">Seluruh katalog</p>
        </div>
        <div class="card p-5 min-h-[120px] flex flex-col">
            <div class="flex items-center gap-3.5 min-w-0">
                <span class="grid size-11 shrink-0 place-items-center rounded-full bg-primary-soft text-focus">
                    <x-icon name="package-plus" class="size-5.5"/>
                </span>
                <div class="min-w-0">
                    <p class="text-[13px]/[18px] text-ink-secondary truncate">Total Unit Stok</p>
                    <p class="text-[26px]/[32px] font-semibold tabular-nums" x-text="stats.totalStock"></p>
                </div>
            </div>
            <p class="mt-auto pt-2.5 text-[12px]/[17px] text-ink-muted">Seluruh katalog</p>
        </div>
    </div>

    {{-- ================= PANEL KATALOG ================= --}}
    <section class="panel mt-6 overflow-hidden" aria-labelledby="products-list-title">
        <h2 id="products-list-title" class="sr-only">Daftar produk</h2>
        <div class="p-5 pb-0">
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative flex-1 min-w-[240px] max-w-[380px]">
                    <x-icon name="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-ink-muted pointer-events-none"/>
                    <label class="sr-only" for="products-search">Cari produk</label>
                    <input id="products-search" type="search" class="input pl-10" placeholder="Cari produk atau SKU…" x-model="query" @input="resetPage()">
                </div>
                <div>
                    <label class="sr-only" for="products-status">Status</label>
                    <select id="products-status" class="select !w-[160px]" x-model="status" @change="resetPage()">
                        <option value="all">Semua Status</option>
                        <option value="active">Tersedia / Aktif</option>
                        <option value="outofstock">Ditandai Habis</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                </div>
                <div>
                    <label class="sr-only" for="products-stock">Tingkat stok</label>
                    <select id="products-stock" class="select !w-[150px]" x-model="stockLevel" @change="resetPage()">
                        <option value="all">Semua Stok</option>
                        <option value="in">Tersedia</option>
                        <option value="low">Stok Menipis</option>
                        <option value="out">Stok Habis</option>
                    </select>
                </div>
                <div>
                    <label class="sr-only" for="products-sort">Urutkan</label>
                    <select id="products-sort" class="select !w-[190px]" x-model="sortBy" @change="resetPage()">
                        <option value="catalog">Urutan Katalog</option>
                        <option value="name-asc">Nama: A–Z</option>
                        <option value="name-desc">Nama: Z–A</option>
                        <option value="price-asc">Harga: Rendah ke Tinggi</option>
                        <option value="price-desc">Harga: Tinggi ke Rendah</option>
                        <option value="stock-asc">Stok: Rendah ke Tinggi</option>
                    </select>
                </div>
                <button type="button" class="btn btn-secondary" @click="clearFilters()">
                    <x-icon name="x" class="size-4"/> Hapus Filter
                </button>
            </div>

            {{-- pills kategori --}}
            <div class="flex items-center gap-2 mt-4 overflow-x-auto no-scrollbar min-w-0 py-0.5" role="group" aria-label="Filter kategori">
                <template x-for="cat in ['Semua', ...categories]" :key="cat">
                    <button type="button" class="chip !h-10" :aria-pressed="category === cat" @click="category = cat; resetPage()" x-text="cat"></button>
                </template>
                <div class="flex-1"></div>
                <div class="flex items-center gap-2.5 shrink-0">
                    <span class="text-[12.5px] text-ink-muted tabular-nums" x-text="`${totalRows} produk`"></span>
                    <div class="flex items-center rounded-lg border border-line overflow-hidden">
                        <button type="button" class="grid size-10 place-items-center" :class="view === 'table' ? 'bg-primary-soft text-focus' : 'text-ink-muted hover:bg-surface-muted'"
                                @click="view = 'table'" aria-pressed="true" aria-label="Tampilan tabel">
                            <x-icon name="list" class="size-4.5"/>
                        </button>
                        <button type="button" class="grid size-10 place-items-center" :class="view === 'grid' ? 'bg-primary-soft text-focus' : 'text-ink-muted hover:bg-surface-muted'"
                                @click="view = 'grid'" aria-pressed="false" aria-label="Tampilan kisi">
                            <x-icon name="grid" class="size-4.5"/>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- bar aksi massal --}}
        <div x-show="selected.length > 0" x-cloak class="mx-5 mt-4 rounded-xl bg-primary-soft/60 border border-primary/30 px-4 py-3 flex items-center justify-between gap-3 flex-wrap">
            <p class="text-[13.5px]/[19px] font-medium" x-text="`${selected.length} produk dipilih`"></p>
            <div class="flex items-center gap-2 flex-wrap">
                <button type="button" class="btn btn-secondary btn-xs" @click="bulkApply('Active')">Set Aktif</button>
                <button type="button" class="btn btn-secondary btn-xs" @click="bulkApply('Inactive')">Set Nonaktif</button>
                <button type="button" class="btn btn-muted btn-xs" @click="selected = []">Kosongkan Pilihan</button>
            </div>
        </div>

        {{-- tabel --}}
        <div class="mt-4 hidden md:block" x-show="view === 'table'">
            <template x-if="totalRows === 0">
                <div class="py-16 text-center">
                    <span class="mx-auto mb-3 grid size-12 place-items-center rounded-full bg-surface-muted text-ink-muted">
                        <x-icon name="package" class="size-6"/>
                    </span>
                    <p class="text-[15px]/[22px] font-medium" x-text="query || category !== 'Semua' || status !== 'all' || stockLevel !== 'all' ? 'Produk tidak ditemukan' : 'Belum ada produk'"></p>
                    <p class="text-[13px]/[19px] text-ink-secondary mt-1" x-text="query || category !== 'Semua' || status !== 'all' || stockLevel !== 'all' ? 'Coba pencarian atau filter lain.' : 'Tambahkan produk pertama Anda untuk membangun katalog.'"></p>
                    <button type="button" class="btn btn-secondary btn-sm mt-4" x-show="query || category !== 'Semua' || status !== 'all' || stockLevel !== 'all'" @click="clearFilters()">Hapus Filter</button>
                </div>
            </template>
            <div class="table-wrap" x-show="totalRows > 0">
                <table class="table min-w-[1000px]">
                    <thead>
                        <tr>
                            <th class="w-12">
                                <input type="checkbox" class="size-4 accent-[#49ADAB]" :checked="allPageSelected" @change="toggleSelectAll()" aria-label="Pilih semua produk di halaman">
                            </th>
                            <th>Produk</th>
                            <th>SKU</th>
                            <th>Kategori</th>
                            <th class="num">Harga</th>
                            <th class="num">Stok</th>
                            <th>Status</th>
                            <th class="w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="p in paged" :key="p.id">
                            <tr :class="selected.includes(p.id) && 'selected'">
                                <td @click.stop>
                                    <input type="checkbox" class="size-4 accent-[#49ADAB]" :checked="selected.includes(p.id)" @change="toggleSelect(p.id)" :aria-label="`Pilih ${p.name}`">
                                </td>
                                <td>
                                    <button type="button" class="flex items-center gap-3 text-left" @click="openDetail(p.id)">
                                        <img :src="p.image || `/images/products/${p.id}.jpg`" onerror="PM.imgFallback(this)" :alt="`Foto ${p.name}`" class="thumb size-11" loading="lazy">
                                        <span class="font-medium" x-text="p.name"></span>
                                    </button>
                                </td>
                                <td class="font-mono text-[12.5px] text-ink-secondary" x-text="p.sku"></td>
                                <td><span class="badge badge-teal" x-text="p.category"></span></td>
                                <td class="num font-medium" x-text="$rp(p.price)"></td>
                                <td class="num">
                                    <template x-if="p.trackStock !== false">
                                        <div>
                                            <span class="tabular-nums font-semibold" x-text="p.stock"></span>
                                            <span class="block text-[11.5px]" :class="stockBadge(p).cls === 'badge-danger' ? 'text-danger font-medium' : stockBadge(p).cls === 'badge-amber' ? 'text-amber font-medium' : 'text-ink-muted'" x-text="stockBadge(p).label"></span>
                                        </div>
                                    </template>
                                    <template x-if="p.trackStock === false">
                                        <div>
                                            <span class="badge" :class="p.status === 'OutOfStock' ? 'badge-danger' : 'badge-neutral'"
                                                  x-text="p.status === 'OutOfStock' ? 'Habis' : 'Tersedia'"></span>
                                            <span class="block text-[11px] text-ink-muted mt-0.5">Cek Lapangan</span>
                                        </div>
                                    </template>
                                </td>
                                <td>
                                    <span class="badge" :class="p.status === 'Active' ? 'badge-positive' : p.status === 'OutOfStock' ? 'badge-danger' : 'badge-neutral'"
                                          x-text="p.status === 'Active' ? 'Aktif' : p.status === 'OutOfStock' ? 'Habis' : 'Nonaktif'"></span>
                                </td>
                                <td>
                                    <div class="flex items-center justify-end gap-1.5" @click.stop>
                                        {{-- Tombol Cepat Tandai Habis/Tersedia --}}
                                        <button type="button" class="btn btn-xs shrink-0 text-[11px] font-semibold transition"
                                                :class="p.status === 'OutOfStock' ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-300' : 'bg-rose-50 text-rose-600 hover:bg-rose-100 border border-rose-200'"
                                                @click="toggleAvailability(p.id)"
                                                :title="p.status === 'OutOfStock' ? 'Tandai Tersedia' : 'Tandai Habis'"
                                                x-show="$store.posy.can('manageProducts') && p.status !== 'Inactive'">
                                            <span x-text="p.status === 'OutOfStock' ? 'Set Tersedia' : 'Set Habis'"></span>
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-icon btn-xs" @click="openDetail(p.id)" :aria-label="`Lihat detail ${p.name}`">
                                            <x-icon name="eye" class="size-4"/>
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-icon btn-xs" @click="openEdit(p.id)" :aria-label="`Ubah ${p.name}`" x-show="$store.posy.can('manageProducts')">
                                            <x-icon name="pencil" class="size-4"/>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- grid --}}
        <div class="p-5" x-show="view === 'grid'" x-cloak>
            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
                <template x-for="p in paged" :key="p.id">
                    <article class="rounded-[10px] border border-line bg-white overflow-hidden">
                        <div class="aspect-square bg-surface-muted relative">
                            <img :src="p.image || `/images/products/${p.id}.jpg`" onerror="PM.imgFallback(this)" :alt="`Foto ${p.name}`" class="size-full object-cover" loading="lazy">
                            <span x-show="p.status === 'OutOfStock' || (p.trackStock !== false && p.stock <= 0)" x-cloak class="absolute top-2 left-2 badge badge-danger">Habis</span>
                            <span x-show="p.trackStock === false && p.status === 'Active'" x-cloak class="absolute top-2 left-2 badge badge-teal text-[10.5px]">Cek Lapangan</span>
                        </div>
                        <div class="p-3 space-y-1.5">
                            <p class="text-[13.5px]/[18px] font-medium line-clamp-2" x-text="p.name"></p>
                            <p class="font-mono text-[11.5px] text-ink-muted" x-text="p.sku"></p>
                            <div class="flex items-center justify-between">
                                <span class="text-[13px] font-medium tabular-nums" x-text="$rp(p.price)"></span>
                                <span class="text-[12px] tabular-nums text-ink-secondary" x-text="p.trackStock !== false ? `Stok ${p.stock}` : (p.status === 'OutOfStock' ? 'Habis' : 'Tersedia')"></span>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <span class="badge" :class="p.status === 'Active' ? 'badge-positive' : p.status === 'OutOfStock' ? 'badge-danger' : 'badge-neutral'" x-text="p.status === 'Active' ? 'Aktif' : p.status === 'OutOfStock' ? 'Habis' : 'Nonaktif'"></span>
                                <div class="flex gap-1">
                                    <button type="button" class="btn btn-secondary btn-xs" @click="openDetail(p.id)">Detail</button>
                                    <button type="button" class="btn btn-secondary btn-icon btn-xs" @click="openEdit(p.id)" :aria-label="`Ubah ${p.name}`" x-show="$store.posy.can('manageProducts')">
                                        <x-icon name="pencil" class="size-3.5"/>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </article>
                </template>
            </div>
        </div>

        {{-- kartu mobile --}}
        <div class="md:hidden px-4 pb-2" x-show="view === 'table'" x-cloak>
            <template x-for="p in paged" :key="p.id">
                <div class="rounded-xl border border-line p-4 mb-2.5 flex gap-3">
                    <img :src="p.image || `/images/products/${p.id}.jpg`" onerror="PM.imgFallback(this)" :alt="`Foto ${p.name}`" class="thumb size-14" loading="lazy">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <p class="font-medium truncate" x-text="p.name"></p>
                            <span class="badge" :class="p.status === 'Active' ? 'badge-positive' : p.status === 'OutOfStock' ? 'badge-danger' : 'badge-neutral'" x-text="p.status === 'Active' ? 'Aktif' : p.status === 'OutOfStock' ? 'Habis' : 'Nonaktif'"></span>
                        </div>
                        <p class="text-[12px] text-ink-muted font-mono" x-text="`${p.sku} · ${p.category}`"></p>
                        <div class="flex items-center justify-between mt-1 text-[13px]">
                            <span class="font-medium tabular-nums" x-text="$rp(p.price)"></span>
                            <span class="tabular-nums" x-text="p.trackStock !== false ? `Stok ${p.stock}` : (p.status === 'OutOfStock' ? 'Habis' : 'Tersedia (Cek Lapangan)')"></span>
                        </div>
                        <div class="flex gap-2 mt-2">
                            <button type="button" class="btn btn-secondary btn-xs flex-1"
                                    :class="p.status === 'OutOfStock' ? 'text-emerald-700 bg-emerald-50' : 'text-rose-600 bg-rose-50'"
                                    @click="toggleAvailability(p.id)"
                                    x-show="$store.posy.can('manageProducts') && p.status !== 'Inactive'">
                                <span x-text="p.status === 'OutOfStock' ? 'Set Tersedia' : 'Set Habis'"></span>
                            </button>
                            <button type="button" class="btn btn-secondary btn-xs flex-1" @click="openDetail(p.id)">Detail</button>
                            <button type="button" class="btn btn-secondary btn-xs flex-1" @click="openEdit(p.id)" x-show="$store.posy.can('manageProducts')">Ubah</button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <x-pagination row-label="produk"/>
    </section>

    {{-- ================= DRAWER FORM PRODUK ================= --}}
    <div x-show="formOpen" x-cloak class="fixed inset-0 z-[75]" role="dialog" aria-modal="true" :aria-label="editingId ? 'Ubah Produk' : 'Tambah Produk'">
        <div class="absolute inset-0 bg-[#1c2b2b]/40" @click="closeForm()" x-transition:enter="fade-enter" x-transition:leave="fade-leave"></div>
        <div class="absolute inset-y-0 right-0 w-[600px] max-w-[92vw] bg-white flex flex-col"
             x-ref="formPanel" @keydown.tab="PM.trap($event, $refs.formPanel)" @keydown.escape="closeForm()"
             x-transition:enter="slide-enter" x-transition:leave="slide-leave">
            <div class="px-6 py-5 border-b border-divider flex items-center justify-between">
                <h2 class="text-[18px]/[24px] font-semibold" x-text="editingId ? 'Ubah Produk' : 'Tambah Produk'"></h2>
                <button type="button" class="btn btn-secondary btn-icon btn-sm" @click="closeForm()" aria-label="Tutup form">
                    <x-icon name="x" class="size-4"/>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto nice-scroll px-6 py-5">
                <p class="text-[12px]/[17px] text-ink-muted mb-4">Kolom bertanda <span class="text-danger">*</span> wajib diisi.</p>
                <div class="grid grid-cols-1 min-[560px]:grid-cols-2 gap-4">
                    <div class="min-[560px]:col-span-2">
                        <label class="field-label" for="fld-name">Nama Produk <span class="text-danger">*</span></label>
                        <input id="fld-name" type="text" class="input" x-model="form.name" @blur="form.name && (form.name.trim().length < 2 || form.name.trim().length > 80) && (formErrors.name = 'Masukkan nama produk (2–80 karakter).')" maxlength="80" :aria-invalid="!!formErrors.name">
                        <p class="field-error" x-show="formErrors.name" x-text="formErrors.name"></p>
                    </div>
                    <div>
                        <label class="field-label" for="fld-sku">SKU <span class="text-danger">*</span></label>
                        <input id="fld-sku" type="text" class="input font-mono" x-model="form.sku" maxlength="32" :aria-invalid="!!formErrors.sku" placeholder="POS-013">
                        <p class="field-error" x-show="formErrors.sku" x-text="formErrors.sku"></p>
                    </div>
                    <div>
                        <label class="field-label" for="fld-category">Kategori <span class="text-danger">*</span></label>
                        <select id="fld-category" class="select" x-model="form.category" :aria-invalid="!!formErrors.category">
                            <option value="" disabled>Pilih kategori</option>
                            <template x-for="c in categories" :key="c"><option :value="c" x-text="c"></option></template>
                        </select>
                        <p class="field-error" x-show="formErrors.category" x-text="formErrors.category"></p>
                    </div>
                    <div>
                        <label class="field-label" for="fld-price">Harga (Rp) <span class="text-danger">*</span></label>
                        <input id="fld-price" type="number" min="0" step="500" class="input tabular-nums" x-model="form.price" :aria-invalid="!!formErrors.price" placeholder="35000">
                        <p class="field-help">Harga dalam Rupiah bulat.</p>
                        <p class="field-error" x-show="formErrors.price" x-text="formErrors.price"></p>
                    </div>
                    {{-- Pilihan Mode Manajemen Stok --}}
                    <div class="min-[560px]:col-span-2 p-3.5 rounded-xl border border-line bg-surface-muted/60">
                        <label class="field-label mb-2 text-ink flex items-center justify-between">
                            <span class="font-semibold">Aturan Manajemen Stok</span>
                            <span class="text-[11px] font-normal text-ink-muted">Pilih mode ketersediaan</span>
                        </label>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            {{-- Opsi 1: Tanpa Hitungan Stok (Cek Lapangan Langsung) --}}
                            <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition"
                                   :class="!form.trackStock ? 'border-primary bg-primary-soft/30 text-focus ring-1 ring-primary/40' : 'border-line bg-white hover:border-slate-300'">
                                <input type="radio" :value="false" x-model="form.trackStock" class="mt-0.5 size-4 accent-[#49ADAB]">
                                <div class="min-w-0 flex-1">
                                    <span class="block text-[13px] font-semibold text-ink">Tanpa Jumlah Stok (Cek Lapangan)</span>
                                    <span class="block text-[11.5px]/[16px] text-ink-muted mt-0.5">
                                        Cocok untuk racikan kopi & minuman. Tidak menghitung unit angka, ketersediaan ditandai langsung <strong>Tersedia</strong> atau <strong>Habis</strong>.
                                    </span>
                                </div>
                            </label>

                            {{-- Opsi 2: Lacak Jumlah Stok Numerik --}}
                            <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition"
                                   :class="form.trackStock ? 'border-primary bg-primary-soft/30 text-focus ring-1 ring-primary/40' : 'border-line bg-white hover:border-slate-300'">
                                <input type="radio" :value="true" x-model="form.trackStock" class="mt-0.5 size-4 accent-[#49ADAB]">
                                <div class="min-w-0 flex-1">
                                    <span class="block text-[13px] font-semibold text-ink">Lacak Jumlah Stok Numerik</span>
                                    <span class="block text-[11.5px]/[16px] text-ink-muted mt-0.5">
                                        Cocok untuk roti kemasan atau kaleng. Menampilkan hitungan unit stok (misal: 24) dan berkurang otomatis.
                                    </span>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Kolom Input Angka Stok (Hanya Tampil Jika form.trackStock == true) --}}
                    <div x-show="form.trackStock" x-transition>
                        <label class="field-label" for="fld-stock">Jumlah Stok <span class="text-danger">*</span></label>
                        <input id="fld-stock" type="number" min="0" max="99999" class="input tabular-nums" x-model="form.stock" :aria-invalid="!!formErrors.stock">
                        <p class="field-help">Jumlah unit fisik saat ini.</p>
                        <p class="field-error" x-show="formErrors.stock" x-text="formErrors.stock"></p>
                    </div>

                    <div x-show="form.trackStock" x-transition>
                        <label class="field-label" for="fld-threshold">Ambang Stok Menipis <span class="text-danger">*</span></label>
                        <input id="fld-threshold" type="number" min="0" max="99999" class="input tabular-nums" x-model="form.lowStockThreshold" :aria-invalid="!!formErrors.lowStockThreshold">
                        <p class="field-help">Ditandai 'Stok Menipis' jika berada di bawah angka ini.</p>
                        <p class="field-error" x-show="formErrors.lowStockThreshold" x-text="formErrors.lowStockThreshold"></p>
                    </div>

                    {{-- Status Ketersediaan --}}
                    <div :class="!form.trackStock ? 'min-[560px]:col-span-2' : ''">
                        <label class="field-label" for="fld-status">Status Ketersediaan</label>
                        <select id="fld-status" class="select" x-model="form.status">
                            <option value="Active">Tersedia (Aktif di Katalog)</option>
                            <option value="OutOfStock">Habis (Tampil 'Habis' & Tidak Bisa Dipesan)</option>
                            <option value="Inactive">Nonaktif (Disembunyikan Sepenuhnya)</option>
                        </select>
                        <p class="field-help">
                            <span x-show="form.status === 'Active'">Menu siap dipesan oleh pelanggan & kasir.</span>
                            <span x-show="form.status === 'OutOfStock'">Menu ditandai habis di kasir & scan meja pelanggan tanpa perlu menghapus produk.</span>
                            <span x-show="form.status === 'Inactive'">Menu disembunyikan sepenuhnya dari daftar penjualan.</span>
                        </p>
                    </div>
                    <div class="min-[560px]:col-span-2">
                        <label class="field-label" for="fld-description">Deskripsi</label>
                        <textarea id="fld-description" class="textarea" x-model="form.description" maxlength="500"></textarea>
                        <p class="field-help"><span x-text="(form.description || '').length"></span>/500 karakter</p>
                    </div>
                    <div class="min-[560px]:col-span-2">
                        <span class="field-label">Foto Produk</span>
                        <div class="flex items-start gap-4 flex-wrap">
                            <div class="size-40 rounded-xl border border-line bg-surface-muted overflow-hidden shrink-0 grid place-items-center">
                                <img x-show="form.image" :src="form.image" alt="Pratinjau foto produk" class="size-full object-cover">
                                <x-icon name="image" class="size-8 text-ink-muted" x-show="!form.image"/>
                            </div>
                            <div class="flex-1 min-w-[180px]">
                                <label class="btn btn-secondary btn-sm w-full cursor-pointer">
                                    <x-icon name="upload" class="size-4"/> Unggah Foto
                                    <input type="file" accept="image/png,image/jpeg,image/webp" class="sr-only" @change="onImageChange($event)">
                                </label>
                                <p class="field-help">PNG, JPG, atau WebP hingga 5 MB. File hanya dibaca lokal.</p>
                                <p class="field-error" x-show="formErrors.image" x-text="formErrors.image"></p>
                                <button type="button" class="btn btn-danger-soft btn-xs mt-2" x-show="form.image" @click="removeImage()">Hapus Foto</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-divider flex justify-end gap-2.5">
                <button type="button" class="btn btn-secondary" @click="closeForm()" :disabled="saving">Batal</button>
                <button type="button" class="btn btn-primary" @click="saveForm()" :disabled="saving" x-text="saving ? 'Menyimpan…' : 'Simpan Produk'"></button>
            </div>
        </div>
    </div>

    {{-- ================= DRAWER DETAIL PRODUK ================= --}}
    <div x-show="detailId" x-cloak class="fixed inset-0 z-[75]" role="dialog" aria-modal="true" aria-label="Detail produk">
        <div class="absolute inset-0 bg-[#1c2b2b]/40" @click="closeDetail()" x-transition:enter="fade-enter" x-transition:leave="fade-leave"></div>
        <div class="absolute inset-y-0 right-0 w-[600px] max-w-[92vw] bg-white flex flex-col"
             x-ref="detailPanel" @keydown.tab="PM.trap($event, $refs.detailPanel)" @keydown.escape="closeDetail()"
             x-transition:enter="slide-enter" x-transition:leave="slide-leave">
            <template x-if="detailProduct">
                <div class="flex flex-col min-h-0 flex-1">
                    <div class="px-6 py-5 border-b border-divider flex items-start justify-between gap-3">
                        <div class="flex items-center gap-4 min-w-0">
                            <img :src="detailProduct.image || `/images/products/${detailProduct.id}.jpg`" onerror="PM.imgFallback(this)" :alt="`Foto ${detailProduct.name}`" class="thumb size-14">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h2 class="text-[19px]/[25px] font-semibold truncate" x-text="detailProduct.name"></h2>
                                    <span class="badge" :class="detailProduct.status === 'Active' ? 'badge-positive' : detailProduct.status === 'OutOfStock' ? 'badge-danger' : 'badge-neutral'"
                                          x-text="detailProduct.status === 'Active' ? 'Aktif' : detailProduct.status === 'OutOfStock' ? 'Habis' : 'Nonaktif'"></span>
                                </div>
                                <p class="text-[12.5px]/[17px] text-ink-muted mt-0.5"><span class="font-mono" x-text="detailProduct.sku"></span> · <span x-text="detailProduct.id"></span></p>
                            </div>
                        </div>
                        <button type="button" class="btn btn-secondary btn-icon btn-sm" @click="closeDetail()" aria-label="Tutup detail">
                            <x-icon name="x" class="size-4"/>
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto nice-scroll px-6 py-5 space-y-5">
                        <dl class="grid grid-cols-2 gap-x-4 gap-y-3.5 text-[13.5px]/[19px]">
                            <div><dt class="text-ink-muted">Kategori</dt><dd class="mt-0.5"><span class="badge badge-teal" x-text="detailProduct.category"></span></dd></div>
                            <div><dt class="text-ink-muted">Harga</dt><dd class="mt-0.5 font-medium tabular-nums" x-text="$rp(detailProduct.price)"></dd></div>
                            <div>
                                <dt class="text-ink-muted">Aturan Stok</dt>
                                <dd class="mt-0.5 font-medium" x-text="detailProduct.trackStock !== false ? 'Lacak Jumlah Unit' : 'Tanpa Kuota (Cek Lapangan)'"></dd>
                            </div>
                            <div>
                                <dt class="text-ink-muted">Ketersediaan / Stok</dt>
                                <dd class="mt-0.5">
                                    <template x-if="detailProduct.trackStock !== false">
                                        <span class="tabular-nums"><span x-text="detailProduct.stock"></span> unit (<span :class="stockBadge(detailProduct).cls === 'badge-danger' ? 'text-danger font-semibold' : stockBadge(detailProduct).cls === 'badge-amber' ? 'text-amber font-semibold' : 'text-ink-muted'" x-text="stockBadge(detailProduct).label"></span>)</span>
                                    </template>
                                    <template x-if="detailProduct.trackStock === false">
                                        <span class="badge" :class="detailProduct.status === 'OutOfStock' ? 'badge-danger' : 'badge-neutral'"
                                              x-text="detailProduct.status === 'OutOfStock' ? 'Habis' : 'Tersedia'"></span>
                                    </template>
                                </dd>
                            </div>
                            <div x-show="detailProduct.trackStock !== false"><dt class="text-ink-muted">Ambang Stok Menipis</dt><dd class="mt-0.5 tabular-nums" x-text="detailProduct.lowStockThreshold"></dd></div>
                        </dl>
                        <div>
                            <p class="text-[12.5px]/[17px] text-ink-muted mb-1">Deskripsi</p>
                            <p class="text-[13.5px]/[20px]" x-text="detailProduct.description || 'Tidak ada deskripsi.'"></p>
                        </div>
                        <div x-show="detailProduct.trackStock !== false">
                            <p class="text-[13px]/[18px] font-medium mb-2">Aktivitas Stok</p>
                            <template x-if="detailActivity.length === 0">
                                <p class="text-[13px]/[19px] text-ink-secondary">Belum ada penyesuaian stok.</p>
                            </template>
                            <div class="space-y-2">
                                <template x-for="a in detailActivity" :key="a.id">
                                    <div class="rounded-lg border border-line px-3.5 py-2.5 text-[12.5px]/[18px]">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="font-medium" x-text="`${a.type === 'add' ? 'Tambah' : a.type === 'remove' ? 'Kurangi' : 'Atur'} Stok`"></span>
                                            <span class="tabular-nums text-ink-secondary" x-text="`${a.quantityBefore} → ${a.quantityAfter}`"></span>
                                        </div>
                                        <p class="text-ink-secondary" x-text="a.reason"></p>
                                        <p class="text-ink-muted text-[11.5px]" x-text="`${$rpfmt.dateTime(a.at)} · oleh ${a.by}`"></p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-divider flex items-center gap-2.5 flex-wrap">
                        <button type="button" class="btn btn-secondary" @click="closeDetail()">Tutup</button>
                        <button type="button" class="btn btn-primary" @click="openEdit(detailProduct.id)">Ubah Produk</button>
                        <div class="flex-1"></div>
                        <div class="relative" @click.stop>
                            <button type="button" class="btn btn-secondary btn-icon" @click="kebabRowId = kebabRowId === 'detail' ? null : 'detail'" aria-label="Aksi lainnya">
                                <x-icon name="more" class="size-4"/>
                            </button>
                            <div x-show="kebabRowId === 'detail'" x-cloak @click.outside="kebabRowId = null"
                                 x-transition:enter="pop-enter" x-transition:leave="pop-leave"
                                 class="absolute right-0 bottom-[calc(100%+8px)] w-56 rounded-xl bg-white border border-line shadow-[0_12px_40px_rgba(30,41,59,0.14)] p-1.5 z-40" role="menu">
                                <button type="button" class="w-full text-left px-3 py-2 rounded-lg text-[13px] hover:bg-surface-muted" role="menuitem"
                                        x-show="detailProduct.trackStock !== false"
                                        @click="openAdjust(detailProduct.id)">Sesuaikan Stok</button>
                                <button type="button" class="w-full text-left px-3 py-2 rounded-lg text-[13px] hover:bg-surface-muted font-medium"
                                        :class="detailProduct.status === 'OutOfStock' ? 'text-emerald-700' : 'text-rose-600'"
                                        role="menuitem"
                                        @click="toggleAvailability(detailProduct.id); kebabRowId = null"
                                        x-text="detailProduct.status === 'OutOfStock' ? 'Tandai Tersedia' : 'Tandai Habis'"></button>
                                <button type="button" class="w-full text-left px-3 py-2 rounded-lg text-[13px] hover:bg-surface-muted" role="menuitem"
                                        @click="openStatus(detailProduct.id, detailProduct.status === 'Active' ? 'Inactive' : 'Active')"
                                        x-text="detailProduct.status === 'Active' ? 'Set Nonaktif' : 'Set Aktif'"></button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- ================= DIALOG SESUAIKAN STOK ================= --}}
    <div x-show="adjustOpen" x-cloak class="fixed inset-0 z-[85] grid place-items-center p-4" role="dialog" aria-modal="true" aria-label="Sesuaikan stok">
        <div class="absolute inset-0 bg-[#1c2b2b]/45" @click="adjustOpen = false" x-transition:enter="fade-enter" x-transition:leave="fade-leave"></div>
        <div class="relative w-full max-w-[440px] rounded-2xl bg-white shadow-[0_24px_80px_rgba(30,41,59,0.22)] p-6" x-transition:enter="pop-enter" x-transition:leave="pop-leave">
            <template x-if="adjustTarget">
                <div>
                    <h2 class="text-[18px]/[24px] font-semibold">Sesuaikan Stok</h2>
                    <p class="text-[13.5px]/[20px] text-ink-secondary mt-1"><span class="font-medium text-ink" x-text="adjustTarget.name"></span> — stok saat ini <span class="tabular-nums font-medium" x-text="adjustTarget.stock"></span></p>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="field-label" for="adj-type">Jenis penyesuaian</label>
                            <select id="adj-type" class="select" x-model="adjustForm.type">
                                <option value="add">Tambah Stok</option>
                                <option value="remove">Kurangi Stok</option>
                                <option value="set">Atur Jumlah</option>
                            </select>
                        </div>
                        <div>
                            <label class="field-label" for="adj-qty">Jumlah</label>
                            <input id="adj-qty" type="number" min="0" class="input tabular-nums" x-model="adjustForm.quantity">
                        </div>
                        <div>
                            <label class="field-label" for="adj-reason">Alasan <span class="text-danger">*</span></label>
                            <textarea id="adj-reason" class="textarea !min-h-[72px]" x-model="adjustForm.reason" maxlength="200" placeholder="mis. Penerimaan pembelian, rusak, inventaris…"></textarea>
                        </div>
                        <div class="rounded-xl bg-surface-muted px-4 py-3 flex items-center justify-between text-[13.5px]/[19px]">
                            <span class="text-ink-secondary">Stok baru</span>
                            <span class="font-semibold tabular-nums" x-text="adjustResult ?? '—'"></span>
                        </div>
                        <p class="field-error" x-show="adjustError" x-text="adjustError"></p>
                    </div>
                    <div class="grid grid-cols-2 gap-3 mt-5">
                        <button type="button" class="btn btn-secondary" @click="adjustOpen = false">Batal</button>
                        <button type="button" class="btn btn-primary" @click="saveAdjust()">Simpan Penyesuaian</button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- ================= DIALOG STATUS PRODUK ================= --}}
    <div x-show="statusOpen" x-cloak class="fixed inset-0 z-[85] grid place-items-center p-4" role="dialog" aria-modal="true" aria-label="Ubah status produk">
        <div class="absolute inset-0 bg-[#1c2b2b]/45" @click="statusOpen = false" x-transition:enter="fade-enter" x-transition:leave="fade-leave"></div>
        <div class="relative w-full max-w-[420px] rounded-2xl bg-white shadow-[0_24px_80px_rgba(30,41,59,0.22)] p-6" x-transition:enter="pop-enter" x-transition:leave="pop-leave">
            <template x-if="statusTarget">
                <div>
                    <h2 class="text-[18px]/[24px] font-semibold" x-text="statusTo === 'Active' ? 'Aktifkan produk ini?' : 'Nonaktifkan produk ini?'"></h2>
                    <p class="text-[13.5px]/[20px] text-ink-secondary mt-2">
                        <span class="font-medium text-ink" x-text="statusTarget.name"></span>
                        <span x-text="statusTo === 'Active' ? ' akan kembali tampil pada katalog penjualan baru.' : ' akan disembunyikan dari katalog penjualan baru. Penjualan historis tetap tersimpan.'"></span>
                    </p>
                    <div class="grid grid-cols-2 gap-3 mt-5">
                        <button type="button" class="btn btn-secondary" @click="statusOpen = false">Batal</button>
                        <button type="button" class="btn btn-primary" @click="confirmStatus()" x-text="statusTo === 'Active' ? 'Set Aktif' : 'Set Nonaktif'"></button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- ================= DIALOG BUANG PERUBAHAN ================= --}}
    <div x-show="discardOpen" x-cloak class="fixed inset-0 z-[95] grid place-items-center p-4" role="dialog" aria-modal="true" aria-label="Buang perubahan">
        <div class="absolute inset-0 bg-[#1c2b2b]/50" @click="discardOpen = false"></div>
        <div class="relative w-full max-w-[420px] rounded-2xl bg-white shadow-[0_24px_80px_rgba(30,41,59,0.22)] p-6" x-transition:enter="pop-enter" x-transition:leave="pop-leave">
            <h2 class="text-[18px]/[24px] font-semibold">Buang perubahan?</h2>
            <p class="text-[13.5px]/[20px] text-ink-secondary mt-2">Perubahan yang belum disimpan akan hilang.</p>
            <div class="grid grid-cols-2 gap-3 mt-5">
                <button type="button" class="btn btn-secondary" @click="discardOpen = false">Lanjut Mengedit</button>
                <button type="button" class="btn btn-danger" @click="discardOpen = false; closeForm(true)">Buang</button>
            </div>
        </div>
    </div>

    {{-- ================= DIALOG REVIEW BULK ================= --}}
    <div x-show="bulkReviewOpen" x-cloak class="fixed inset-0 z-[85] grid place-items-center p-4" role="dialog" aria-modal="true" aria-label="Konfirmasi perubahan massal">
        <div class="absolute inset-0 bg-[#1c2b2b]/45" @click="bulkReviewOpen = false" x-transition:enter="fade-enter" x-transition:leave="fade-leave"></div>
        <div class="relative w-full max-w-[440px] rounded-2xl bg-white shadow-[0_24px_80px_rgba(30,41,59,0.22)] p-6" x-transition:enter="pop-enter" x-transition:leave="pop-leave">
            <h2 class="text-[18px]/[24px] font-semibold" x-text="bulkTarget === 'Active' ? 'Aktifkan produk terpilih?' : 'Nonaktifkan produk terpilih?'"></h2>
            <p class="text-[13.5px]/[20px] text-ink-secondary mt-2"><span class="font-medium text-ink" x-text="selected.length"></span> produk akan diubah statusnya:</p>
            <ul class="mt-3 space-y-1 max-h-40 overflow-y-auto nice-scroll">
                <template x-for="id in selected" :key="id">
                    <li class="text-[13px]/[19px] text-ink-secondary" x-text="$store.posy.product(id)?.name"></li>
                </template>
            </ul>
            <div class="grid grid-cols-2 gap-3 mt-5">
                <button type="button" class="btn btn-secondary" @click="bulkReviewOpen = false">Batal</button>
                <button type="button" class="btn btn-primary" @click="bulkConfirm()" x-text="bulkTarget === 'Active' ? 'Set Aktif' : 'Set Nonaktif'"></button>
            </div>
        </div>
    </div>
</div>
@endsection
