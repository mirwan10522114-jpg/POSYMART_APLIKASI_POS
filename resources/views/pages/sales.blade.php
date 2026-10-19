@extends('layouts.app')

@section('title', 'Penjualan')
@section('subtitle', 'Lihat riwayat transaksi dan kelola penjualan Anda.')

@section('content')
<div x-data="salesPage" x-cloak @keydown.window.escape="drawerId && closeDrawer()">

    <x-page-header title="Penjualan" subtitle="Lihat riwayat transaksi dan kelola penjualan Anda.">
        <button type="button" class="btn btn-secondary" @click="exportCsv()">
            <x-icon name="download" class="size-4.5"/> Ekspor CSV
        </button>
        <a href="/dashboard" class="btn btn-primary">
            <x-icon name="plus" class="size-4.5"/> Penjualan Baru
        </a>
    </x-page-header>

    {{-- ================= STATISTIK (dihitung setelah filter) ================= --}}
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-[22px] mt-6 max-[480px]:gap-3" role="group" aria-label="Statistik penjualan">
        <div class="card p-5 min-h-[120px] flex flex-col min-w-0">
            <div class="flex items-center gap-3.5 min-w-0">
                <span class="grid size-[52px] shrink-0 place-items-center rounded-full bg-primary-soft text-focus max-[480px]:size-11">
                    <x-icon name="dollar" class="size-[26px] max-[480px]:size-6"/>
                </span>
                <div class="min-w-0">
                    <p class="text-[14px]/[20px] text-ink-secondary truncate">Total Penjualan</p>
                    <p class="font-semibold text-ink tabular-nums mt-0.5 tracking-tight" :class="String(stats.total).length > 10 ? 'text-[21px]/[27px]' : 'text-[28px]/[34px]'" x-text="$rp(stats.total)"></p>
                </div>
            </div>
            <p class="mt-auto pt-3 text-[12px]/[18px] text-ink-muted">Untuk filter terpilih</p>
        </div>
        <div class="card p-5 min-h-[120px] flex flex-col min-w-0">
            <div class="flex items-center gap-3.5 min-w-0">
                <span class="grid size-[52px] shrink-0 place-items-center rounded-full bg-primary-soft text-focus max-[480px]:size-11">
                    <x-icon name="check-circle" class="size-[26px] max-[480px]:size-6"/>
                </span>
                <div class="min-w-0">
                    <p class="text-[14px]/[20px] text-ink-secondary truncate">Penjualan Selesai</p>
                    <p class="text-[28px]/[34px] font-semibold text-ink tabular-nums mt-0.5" x-text="stats.completed"></p>
                </div>
            </div>
            <p class="mt-auto pt-3 text-[12px]/[18px] text-ink-muted">Untuk filter terpilih</p>
        </div>
        <div class="card p-5 min-h-[120px] flex flex-col min-w-0">
            <div class="flex items-center gap-3.5 min-w-0">
                <span class="grid size-[52px] shrink-0 place-items-center rounded-full bg-primary-soft text-focus max-[480px]:size-11">
                    <x-icon name="trending-up" class="size-[26px] max-[480px]:size-6"/>
                </span>
                <div class="min-w-0">
                    <p class="text-[14px]/[20px] text-ink-secondary truncate">Rata-rata Penjualan</p>
                    <p class="font-semibold text-ink tabular-nums mt-0.5 tracking-tight" :class="String(stats.avg).length > 10 ? 'text-[21px]/[27px]' : 'text-[24px]/[30px]'" x-text="$rp(stats.avg)"></p>
                </div>
            </div>
            <p class="mt-auto pt-3 text-[12px]/[18px] text-ink-muted">Untuk filter terpilih</p>
        </div>
        <div class="card p-5 min-h-[120px] flex flex-col min-w-0">
            <div class="flex items-center gap-3.5 min-w-0">
                <span class="grid size-[52px] shrink-0 place-items-center rounded-full bg-danger-soft text-danger max-[480px]:size-11">
                    <x-icon name="ban" class="size-[26px] max-[480px]:size-6"/>
                </span>
                <div class="min-w-0">
                    <p class="text-[14px]/[20px] text-ink-secondary truncate">Penjualan Dibatalkan</p>
                    <p class="text-[28px]/[34px] font-semibold text-ink tabular-nums mt-0.5" x-text="stats.voided"></p>
                </div>
            </div>
            <p class="mt-auto pt-3 text-[12px]/[18px] text-ink-muted">Untuk filter terpilih</p>
        </div>
    </div>

    {{-- ================= PANEL DAFTAR ================= --}}
    <section class="panel mt-6 overflow-hidden" aria-labelledby="sales-list-title">
        <h2 id="sales-list-title" class="sr-only">Riwayat penjualan</h2>
        <div class="p-5 pb-0">
            {{-- toolbar filter --}}
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative flex-1 min-w-[240px] max-w-[420px]">
                    <x-icon name="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-ink-muted pointer-events-none"/>
                    <label class="sr-only" for="sales-search">Cari penjualan</label>
                    <input id="sales-search" type="search" class="input pl-10" placeholder="Cari ID transaksi atau pelanggan…" x-model="query" @input="resetPage()">
                </div>
                <div class="relative">
                    <label class="sr-only" for="sales-date">Rentang tanggal</label>
                    <select id="sales-date" class="select !w-[240px]" x-model="datePreset" @change="resetPage(); if (datePreset === 'custom') { customStartInput = customStart; customEndInput = customEnd; customOpen = true; }">
                        <option value="all">Semua Waktu</option>
                        <option value="today">Hari Ini</option>
                        <option value="last7">7 Hari Terakhir</option>
                        <option value="month">Bulan Ini</option>
                        <option value="custom">Kustom…</option>
                    </select>
                </div>
                <div>
                    <label class="sr-only" for="sales-status">Status</label>
                    <select id="sales-status" class="select !w-[150px]" x-model="status" @change="resetPage()">
                        <option value="all">Semua Status</option>
                        <option value="completed">Selesai</option>
                        <option value="voided">Dibatalkan</option>
                    </select>
                </div>
                <div>
                    <label class="sr-only" for="sales-payment">Metode pembayaran</label>
                    <select id="sales-payment" class="select !w-[170px]" x-model="payment" @change="resetPage()">
                        <option value="all">Semua Metode</option>
                        <option value="cash">Tunai</option>
                        <option value="card">Kartu</option>
                        <option value="wallet">Dompet Digital</option>
                    </select>
                </div>
                <div>
                    <label class="sr-only" for="sales-order-type">Tipe Layanan</label>
                    <select id="sales-order-type" class="select !w-[160px]" x-model="orderTypeFilter" @change="resetPage()">
                        <option value="all">Semua Tipe</option>
                        <option value="dine_in">🍽️ Dine In</option>
                        <option value="takeaway">🛍️ Takeaway</option>
                    </select>
                </div>
                <div class="relative">
                    <button type="button" class="btn btn-secondary" @click="moreOpen = !moreOpen" :aria-expanded="moreOpen">
                        <x-icon name="sliders" class="size-4.5"/> Filter Lainnya
                    </button>
                    <div x-show="moreOpen" x-cloak @click.outside="moreOpen = false" @keydown.escape="moreOpen = false"
                         x-transition:enter="pop-enter" x-transition:leave="pop-leave"
                         class="absolute left-0 top-[calc(100%+8px)] w-60 rounded-xl bg-white border border-line shadow-[0_12px_40px_rgba(30,41,59,0.14)] p-4 z-30">
                        <label class="field-label" for="sales-cashier">Kasir</label>
                        <select id="sales-cashier" class="select" x-model="cashier" @change="resetPage(); moreOpen = false">
                            <option value="all">Semua Kasir</option>
                            <template x-for="c in cashiers" :key="c">
                                <option :value="c" x-text="c"></option>
                            </template>
                        </select>
                        <p class="field-help">Tanggal demo: 10 Okt 2026</p>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary max-[520px]:w-full" @click="clearFilters()">
                    <x-icon name="x" class="size-4"/> Hapus Filter
                </button>
            </div>

            {{-- popover rentang kustom --}}
            <div x-show="customOpen" x-cloak @click.outside="customOpen = false"
                 x-transition:enter="pop-enter" x-transition:leave="pop-leave"
                 class="mt-3 rounded-xl border border-line bg-white shadow-[0_12px_40px_rgba(30,41,59,0.12)] p-4 max-w-[420px] z-20 relative">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="field-label" for="cs">Tanggal mulai</label>
                        <input id="cs" type="date" class="input" x-model="customStartInput">
                    </div>
                    <div>
                        <label class="field-label" for="ce">Tanggal akhir</label>
                        <input id="ce" type="date" class="input" x-model="customEndInput">
                    </div>
                </div>
                <p class="field-error" x-show="customError" x-text="customError"></p>
                <div class="flex justify-end gap-2 mt-3">
                    <button type="button" class="btn btn-secondary btn-sm" @click="customOpen = false; if (datePreset === 'custom') datePreset = 'today'">Batal</button>
                    <button type="button" class="btn btn-primary btn-sm" @click="applyCustom()">Terapkan</button>
                </div>
            </div>
            <p class="text-[12px]/[18px] text-ink-muted mt-3">
                Rentang aktif: <span class="font-medium text-ink-secondary" x-text="rangeLabel"></span>
            </p>
        </div>

        {{-- tabel desktop --}}
        <div class="mt-4 hidden md:block">
            <template x-if="loaded && totalRows === 0">
                <div class="py-16 text-center">
                    <span class="mx-auto mb-3 grid size-12 place-items-center rounded-full bg-surface-muted text-ink-muted">
                        <x-icon name="receipt" class="size-6"/>
                    </span>
                    <p class="text-[15px]/[22px] font-medium" x-text="query || status !== 'all' || payment !== 'all' || datePreset !== 'today' ? 'Penjualan tidak ditemukan' : 'Belum ada penjualan'"></p>
                    <p class="text-[13px]/[19px] text-ink-secondary mt-1" x-text="query || status !== 'all' || payment !== 'all' || datePreset !== 'today' ? 'Coba pencarian atau rentang tanggal lain.' : 'Buat penjualan demo pertama Anda untuk memulai.'"></p>
                    <button type="button" class="btn btn-secondary btn-sm mt-4" x-show="query || status !== 'all' || payment !== 'all' || datePreset !== 'today'" @click="clearFilters()">Hapus Filter</button>
                    <a href="/dashboard" class="btn btn-primary btn-sm mt-4" x-show="!(query || status !== 'all' || payment !== 'all' || datePreset !== 'today')">Penjualan Baru</a>
                </div>
            </template>
            <div class="table-wrap" x-show="totalRows > 0">
                <table class="table min-w-[920px]">
                    <thead>
                        <tr>
                            <th>ID Transaksi</th>
                            <th>
                                <button type="button" class="inline-flex items-center gap-1 hover:text-ink" @click="setSort('date')"
                                        :aria-sort="sortKey === 'date' ? (sortDir === 'asc' ? 'ascending' : 'descending') : 'none'">
                                    Tanggal &amp; Waktu
                                    <x-icon name="arrow-up" class="size-3.5" x-show="sortKey === 'date' && sortDir === 'asc'"/>
                                    <x-icon name="arrow-down" class="size-3.5" x-show="sortKey === 'date' && sortDir === 'desc'"/>
                                </button>
                            </th>
                            <th>Tipe Layanan</th>
                            <th>Pelanggan</th>
                            <th>Kasir</th>
                            <th class="num text-right">Item</th>
                            <th>Pembayaran</th>
                            <th>Status</th>
                            <th class="num text-right">
                                <button type="button" class="w-full inline-flex items-center justify-end gap-1 hover:text-ink font-semibold" @click="setSort('total')"
                                        :aria-sort="sortKey === 'total' ? (sortDir === 'asc' ? 'ascending' : 'descending') : 'none'">
                                    <span>Total</span>
                                    <x-icon name="arrow-up" class="size-3.5" x-show="sortKey === 'total' && sortDir === 'asc'"/>
                                    <x-icon name="arrow-down" class="size-3.5" x-show="sortKey === 'total' && sortDir === 'desc'"/>
                                </button>
                            </th>
                            <th class="w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="s in paged" :key="s.id">
                            <tr class="row-click" @click="openDrawer(s.id)">
                                <td class="font-medium text-focus" x-text="s.id"></td>
                                <td class="text-ink-secondary whitespace-nowrap" x-text="$rpfmt.dateTime(s.createdAt)"></td>
                                <td>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold"
                                          :class="s.orderType === 'takeaway' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'">
                                        <x-icon name="shopping-bag" class="size-3" x-show="s.orderType === 'takeaway'"/>
                                        <x-icon name="utensils" class="size-3" x-show="s.orderType !== 'takeaway'"/>
                                        <span x-text="s.orderType === 'takeaway' ? 'Takeaway' : ('Dine In' + (s.tableNumber ? ' • ' + (s.tableNumber.toLowerCase().includes('meja') ? s.tableNumber : 'Meja ' + s.tableNumber) : ''))"></span>
                                    </span>
                                </td>
                                <td>
                                    <span x-text="s.customerNameSnapshot"></span>
                                    <span class="block text-[11.5px] text-ink-muted" x-show="s.customerId" x-text="s.customerId"></span>
                                </td>
                                <td class="text-ink-secondary" x-text="s.cashierNameSnapshot"></td>
                                <td class="num text-ink-secondary" x-text="unitCount(s)"></td>
                                <td>
                                    <span class="inline-flex items-center gap-1.5 text-ink-secondary">
                                        <x-icon name="banknote" class="size-4" x-show="s.paymentMethod === 'Cash'"/>
                                        <x-icon name="credit-card" class="size-4" x-show="s.paymentMethod === 'Card'"/>
                                        <x-icon name="wallet" class="size-4" x-show="s.paymentMethod === 'Digital Wallet'"/>
                                        <span x-text="s.paymentMethod === 'Cash' ? 'Tunai' : s.paymentMethod === 'Card' ? 'Kartu' : 'Dompet Digital'"></span>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge" :class="s.status === 'Completed' ? 'badge-positive' : 'badge-danger'"
                                          x-text="s.status === 'Completed' ? 'Selesai' : 'Dibatalkan'"></span>
                                </td>
                                <td class="num font-medium" x-text="$rp(s.total)"></td>
                                <td>
                                    <div class="flex items-center justify-end gap-1" @click.stop>
                                        <button type="button" class="btn btn-secondary btn-icon btn-xs" @click="openDrawer(s.id)" :aria-label="`Lihat detail ${s.id}`">
                                            <x-icon name="eye" class="size-4"/>
                                        </button>
                                        <div class="relative">
                                            <button type="button" class="btn btn-secondary btn-icon btn-xs" @click="kebabRowId = kebabRowId === s.id ? null : s.id" :aria-label="`Aksi lainnya ${s.id}`">
                                                <x-icon name="more" class="size-4"/>
                                            </button>
                                            <div x-show="kebabRowId === s.id" x-cloak @click.outside="kebabRowId = null" @keydown.escape="kebabRowId = null"
                                                 x-transition:enter="pop-enter" x-transition:leave="pop-leave"
                                                 class="absolute right-0 top-[calc(100%+6px)] w-56 rounded-xl bg-white border border-line shadow-[0_12px_40px_rgba(30,41,59,0.14)] p-1.5 z-40" role="menu">
                                                <button type="button" class="w-full text-left px-3 py-2 rounded-lg text-[13px] hover:bg-surface-muted" role="menuitem"
                                                        @click="openDrawer(s.id)">Lihat Detail</button>
                                                <button type="button" class="w-full text-left px-3 py-2 rounded-lg text-[13px] hover:bg-surface-muted" role="menuitem"
                                                        @click="downloadReceipt(s)">Unduh Struk</button>
                                                <button type="button" class="w-full text-left px-3 py-2 rounded-lg text-[13px] hover:bg-surface-muted" role="menuitem"
                                                        x-show="s.status === 'Completed'"
                                                        @click="window.location.href = `/returns?sale=${s.id}`">Buat Retur</button>
                                                <button type="button" class="w-full text-left px-3 py-2 rounded-lg text-[13px] hover:bg-danger-soft text-danger" role="menuitem"
                                                        x-show="s.status === 'Completed'" :disabled="!canVoid(s).ok" :title="canVoid(s).reason"
                                                        @click="openVoid(s)">Batalkan Penjualan</button>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- kartu mobile --}}
        <div class="md:hidden px-4 pb-2">
            <template x-if="totalRows === 0">
                <div class="py-14 text-center">
                    <p class="text-[15px]/[22px] font-medium">Penjualan tidak ditemukan</p>
                    <p class="text-[13px]/[19px] text-ink-secondary mt-1">Coba pencarian atau rentang tanggal lain.</p>
                    <button type="button" class="btn btn-secondary btn-sm mt-4" @click="clearFilters()">Hapus Filter</button>
                </div>
            </template>
            <template x-for="s in paged" :key="s.id">
                <div class="rounded-xl border border-line p-4 mb-2.5">
                    <div class="flex items-center justify-between gap-2">
                        <p class="font-medium text-focus" x-text="s.id"></p>
                        <span class="badge" :class="s.status === 'Completed' ? 'badge-positive' : 'badge-danger'"
                              x-text="s.status === 'Completed' ? 'Selesai' : 'Dibatalkan'"></span>
                    </div>
                    <p class="text-[12.5px]/[18px] text-ink-secondary mt-1" x-text="$rpfmt.dateTime(s.createdAt)"></p>
                    <div class="flex items-center justify-between mt-2 text-[13px]/[19px]">
                        <span class="text-ink-secondary" x-text="s.customerNameSnapshot"></span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold"
                              :class="s.orderType === 'takeaway' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'">
                            <span x-text="s.orderType === 'takeaway' ? '🛍️ Takeaway' : ('🍽️ Dine In' + (s.tableNumber ? ' • ' + (s.tableNumber.toLowerCase().includes('meja') ? s.tableNumber : 'Meja ' + s.tableNumber) : ''))"></span>
                        </span>
                    </div>
                    <div class="flex items-center justify-between mt-1 text-[13px]/[19px]">
                        <span class="text-ink-secondary" x-text="`${unitCount(s)} item`"></span>
                        <span class="text-ink-secondary" x-text="s.paymentMethod === 'Cash' ? 'Tunai' : s.paymentMethod === 'Card' ? 'Kartu' : 'Dompet Digital'"></span>
                    </div>
                    <div class="flex items-center justify-between mt-1.5 pt-1.5 border-t border-slate-100 text-[13px]/[19px]">
                        <span class="text-ink-muted text-xs">Total Pembayaran</span>
                        <span class="font-semibold text-[20px] tabular-nums" x-text="$rp(s.total)"></span>
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm w-full mt-3" @click="openDrawer(s.id)">Lihat Detail</button>
                </div>
            </template>
        </div>

        <x-pagination row-label="penjualan"/>
    </section>

    {{-- ================= DRAWER DETAIL ================= --}}
    <div x-show="drawerId || drawerNotFound" x-cloak class="fixed inset-0 z-[75]" role="dialog" aria-modal="true" aria-label="Detail penjualan">
        <div class="absolute inset-0 bg-[#1c2b2b]/40" @click="closeDrawer()" x-transition:enter="fade-enter" x-transition:leave="fade-leave"></div>
        <div class="absolute inset-y-0 right-0 w-[560px] max-w-[90vw] bg-white flex flex-col"
             x-ref="detailPanel" @keydown.tab="PM.trap($event, $refs.detailPanel)"
             x-transition:enter="slide-enter" x-transition:leave="slide-leave">
            <template x-if="drawerNotFound">
                <div class="p-8 text-center">
                    <p class="text-[16px] font-medium">Penjualan tidak ditemukan</p>
                    <button type="button" class="btn btn-secondary mt-4" @click="closeDrawer()">Tutup</button>
                </div>
            </template>
            <template x-if="drawerSale">
                <div class="flex flex-col min-h-0 flex-1">
                    <div class="px-6 py-5 border-b border-divider flex items-start justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2.5 flex-wrap">
                                <h2 class="text-[19px]/[25px] font-semibold" x-text="drawerSale.id"></h2>
                                <span class="badge" :class="drawerSale.status === 'Completed' ? 'badge-positive' : 'badge-danger'"
                                      x-text="drawerSale.status === 'Completed' ? 'Selesai' : 'Dibatalkan'"></span>
                            </div>
                            <p class="text-[13px]/[19px] text-ink-secondary mt-1" x-text="$rpfmt.dateTime(drawerSale.createdAt)"></p>
                        </div>
                        <button type="button" class="btn btn-secondary btn-icon btn-sm" @click="closeDrawer()" aria-label="Tutup detail">
                            <x-icon name="x" class="size-4"/>
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto nice-scroll px-6 py-5 space-y-5">
                        <dl class="grid grid-cols-2 gap-x-4 gap-y-3.5 text-[13.5px]/[19px]">
                            <div>
                                <dt class="text-ink-muted">Tipe Layanan</dt>
                                <dd class="mt-0.5">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold"
                                          :class="drawerSale.orderType === 'takeaway' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'">
                                        <span x-text="drawerSale.orderType === 'takeaway' ? '🛍️ Takeaway (Bawa Pulang)' : ('🍽️ Dine In (Makan di Tempat)' + (drawerSale.tableNumber ? ' • ' + (drawerSale.tableNumber.toLowerCase().includes('meja') ? drawerSale.tableNumber : 'Meja ' + drawerSale.tableNumber) : ' • Diantar ke Meja'))"></span>
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-ink-muted">Pelanggan</dt>
                                <dd class="mt-0.5">
                                    <template x-if="drawerSale.customerId">
                                        <a class="text-primary hover:text-primary-hover font-medium" :href="`/customers?customer=${drawerSale.customerId}`" x-text="drawerSale.customerNameSnapshot"></a>
                                    </template>
                                    <template x-if="!drawerSale.customerId">
                                        <span x-text="drawerSale.customerNameSnapshot"></span>
                                    </template>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-ink-muted">Kasir</dt>
                                <dd class="mt-0.5" x-text="drawerSale.cashierNameSnapshot"></dd>
                            </div>
                            <div>
                                <dt class="text-ink-muted">Metode Pembayaran</dt>
                                <dd class="mt-0.5" x-text="drawerSale.paymentMethod === 'Cash' ? 'Tunai' : drawerSale.paymentMethod === 'Card' ? 'Kartu' : 'Dompet Digital'"></dd>
                            </div>
                            <div x-show="drawerSale.sourceOrderId">
                                <dt class="text-ink-muted">Pesanan Sumber</dt>
                                <dd class="mt-0.5"><a class="text-primary hover:text-primary-hover font-medium" :href="`/orders`" x-text="drawerSale.sourceOrderId"></a></dd>
                            </div>
                        </dl>

                        <div x-show="drawerSale.status === 'Voided'" x-cloak class="rounded-xl bg-danger-soft border border-danger/25 px-4 py-3.5">
                            <p class="text-[13px]/[18px] font-medium text-danger">Informasi Pembatalan</p>
                            <p class="text-[12.5px]/[18px] text-danger/85 mt-1">Alasan: <span x-text="drawerSale.voidReason"></span></p>
                            <p class="text-[12.5px]/[18px] text-danger/85">Dibatalkan: <span x-text="$rpfmt.dateTime(drawerSale.voidedAt)"></span></p>
                        </div>

                        <div>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th class="num">Harga Satuan</th>
                                        <th class="num">Jml</th>
                                        <th class="num">Nominal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="it in drawerSale.items" :key="it.id">
                                        <tr>
                                            <td class="font-medium" x-text="it.name"></td>
                                            <td class="num text-ink-secondary" x-text="$rp(it.unitPrice)"></td>
                                            <td class="num text-ink-secondary" x-text="it.quantity"></td>
                                            <td class="num font-medium" x-text="$rp(it.unitPrice * it.quantity)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <div class="rounded-xl bg-surface-muted px-5 py-4 space-y-1.5">
                            <div class="flex justify-between text-[13.5px]/[19px]">
                                <span class="text-ink-secondary">Subtotal</span>
                                <span class="tabular-nums" x-text="$rp(drawerSale.subtotal)"></span>
                            </div>
                            <div class="flex justify-between text-[13.5px]/[19px]" x-show="drawerSale.discount > 0">
                                <span class="text-ink-secondary">Diskon</span>
                                <span class="tabular-nums text-positive" x-text="'-' + $rp(drawerSale.discount)"></span>
                            </div>
                            <div class="flex justify-between text-[13.5px]/[19px]">
                                <span class="text-ink-secondary" x-text="`${drawerSale.taxNameSnapshot} (${drawerSale.taxRateBpsSnapshot / 100}%)`"></span>
                                <span class="tabular-nums" x-text="$rp(drawerSale.tax)"></span>
                            </div>
                            <div class="flex justify-between items-end pt-2 border-t border-line">
                                <span class="font-semibold">Total</span>
                                <span class="text-[22px]/[28px] font-semibold tabular-nums" x-text="$rp(drawerSale.total)"></span>
                            </div>
                        </div>

                        <div x-show="drawerRefunds.length" x-cloak class="rounded-xl border border-line px-5 py-4">
                            <p class="text-[13px]/[18px] font-medium mb-1.5">Pengembalian Dana</p>
                            <div class="flex justify-between text-[13px]/[19px]">
                                <span class="text-ink-secondary">Total Direfund</span>
                                <span class="tabular-nums text-danger" x-text="$rp(refundedTotal)"></span>
                            </div>
                            <div class="flex justify-between text-[13px]/[19px]">
                                <span class="text-ink-secondary">Sisa Bisa Direfund</span>
                                <span class="tabular-nums" x-text="$rp(drawerSale.total - refundedTotal)"></span>
                            </div>
                            <div class="mt-2 space-y-1">
                                <template x-for="r in drawerRefunds" :key="r.id">
                                    <a :href="`/returns`" class="flex items-center gap-2 text-[12.5px] text-primary hover:text-primary-hover">
                                        <x-icon name="rotate-ccw" class="size-3.5"/>
                                        <span x-text="`${r.id} — ${$rp(r.refundTotal)} (${r.status})`"></span>
                                    </a>
                                </template>
                            </div>
                        </div>

                        <div>
                            <p class="text-[12.5px]/[17px] text-ink-muted mb-1">Catatan</p>
                            <p class="text-[13.5px]/[20px]" x-text="drawerSale.note || 'Tidak ada catatan'"></p>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-divider flex items-center gap-2.5 flex-wrap">
                        <button type="button" class="btn btn-secondary" @click="closeDrawer()">Tutup</button>
                        <button type="button" class="btn btn-secondary" @click="downloadReceipt(drawerSale)">
                            <x-icon name="download" class="size-4"/> Unduh Struk
                        </button>
                        <div class="flex-1"></div>
                        <div class="relative">
                            <button type="button" class="btn btn-secondary btn-icon" @click="kebabRowId = kebabRowId === 'drawer' ? null : 'drawer'" aria-label="Aksi lainnya">
                                <x-icon name="more" class="size-4"/>
                            </button>
                            <div x-show="kebabRowId === 'drawer'" x-cloak @click.outside="kebabRowId = null"
                                 x-transition:enter="pop-enter" x-transition:leave="pop-leave"
                                 class="absolute right-0 bottom-[calc(100%+8px)] w-56 rounded-xl bg-white border border-line shadow-[0_12px_40px_rgba(30,41,59,0.14)] p-1.5 z-40" role="menu">
                                <button type="button" class="w-full text-left px-3 py-2 rounded-lg text-[13px] hover:bg-surface-muted" role="menuitem"
                                        x-show="drawerSale.status === 'Completed'"
                                        @click="window.location.href = `/returns?sale=${drawerSale.id}`">Buat Retur</button>
                                <button type="button" class="w-full text-left px-3 py-2 rounded-lg text-[13px] hover:bg-danger-soft text-danger" role="menuitem"
                                        x-show="drawerSale.status === 'Completed'" :disabled="!canVoid(drawerSale).ok" :title="canVoid(drawerSale).reason"
                                        @click="openVoid(drawerSale)">Batalkan Penjualan</button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- ================= DIALOG BATALKAN PENJUALAN ================= --}}
    <div x-show="voidOpen" x-cloak class="fixed inset-0 z-[85] grid place-items-center p-4" role="dialog" aria-modal="true" aria-label="Batalkan penjualan">
        <div class="absolute inset-0 bg-[#1c2b2b]/45" @click="voidOpen = false" x-transition:enter="fade-enter" x-transition:leave="fade-leave"></div>
        <div class="relative w-full max-w-[440px] rounded-2xl bg-white shadow-[0_24px_80px_rgba(30,41,59,0.22)] p-6" x-transition:enter="pop-enter" x-transition:leave="pop-leave">
            <h2 class="text-[18px]/[24px] font-semibold">Batalkan Penjualan?</h2>
            <p class="text-[13.5px]/[20px] text-ink-secondary mt-2">
                <span x-text="voidTarget?.id"></span> senilai <span class="font-medium text-ink" x-text="voidTarget ? $rp(voidTarget.total) : ''"></span> akan ditandai dibatalkan. Ini hanya memperbarui demo lokal.
            </p>
            <div class="mt-4">
                <label class="field-label" for="void-reason">Alasan <span class="text-danger">*</span></label>
                <textarea id="void-reason" class="textarea" placeholder="Tulis alasan pembatalan (3–300 karakter)…" x-model="voidReason" maxlength="300"></textarea>
                <p class="field-error" x-show="voidReason.trim().length > 0 && voidReason.trim().length < 3">Alasan minimal 3 karakter.</p>
            </div>
            <p class="text-[12.5px]/[18px] text-danger mt-3" x-show="voidState === 'error'" x-cloak>Gagal membatalkan penjualan. Coba lagi.</p>
            <div class="grid grid-cols-2 gap-3 mt-5">
                <button type="button" class="btn btn-secondary" @click="voidOpen = false" :disabled="voidState === 'processing'">Batal</button>
                <button type="button" class="btn btn-danger" @click="confirmVoid()"
                        :disabled="voidReason.trim().length < 3 || voidReason.trim().length > 300 || voidState === 'processing'"
                        x-text="voidState === 'processing' ? 'Membatalkan…' : 'Batalkan Penjualan'"></button>
            </div>
        </div>
    </div>
</div>
@endsection
