@extends('layouts.app')

@section('title', 'Pelanggan Terdaftar')
@section('subtitle', 'Data akun pelanggan yang telah terdaftar dari scan QR meja maupun portal registrasi.')

@section('content')
<div x-data="customersPage" x-cloak @keydown.window.escape="formOpen ? closeForm() : (profileId && closeProfile())">

    <x-page-header title="Pelanggan Terdaftar" subtitle="Kelola akun pelanggan yang terdaftar melalui scan QR meja maupun registrasi mandiri.">
        <button type="button" class="btn btn-secondary" @click="loadCustomers()" title="Segarkan data pelanggan dari server">
            <x-icon name="refresh" class="size-4.5" x-bind:class="loading && 'animate-spin'"/>
            <span x-text="loading ? 'Memuat...' : 'Muat Ulang'"></span>
        </button>
        <button type="button" class="btn btn-secondary" @click="exportCsv()" title="Unduh data dalam format CSV">
            <x-icon name="download" class="size-4.5"/> Ekspor CSV
        </button>
        <button type="button" class="btn btn-primary" @click="openCreate()">
            <x-icon name="plus" class="size-4.5"/> Tambah Pelanggan
        </button>
    </x-page-header>

    {{-- ================= STATISTIK PELANGGAN TERDAFTAR ================= --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-[22px] mt-6 max-[480px]:gap-3" role="group" aria-label="Statistik pelanggan">
        <div class="card p-5 min-h-[120px] flex flex-col">
            <div class="flex items-center gap-3.5 min-w-0">
                <span class="grid size-11 shrink-0 place-items-center rounded-full bg-primary-soft text-focus">
                    <x-icon name="users" class="size-5.5"/>
                </span>
                <div class="min-w-0">
                    <p class="text-[13px]/[18px] text-ink-secondary truncate">Total Pelanggan</p>
                    <p class="text-[26px]/[32px] font-semibold tabular-nums" x-text="stats.total"></p>
                </div>
            </div>
            <p class="mt-auto pt-2.5 text-[12px]/[17px] text-ink-muted">Akun terdaftar di database</p>
        </div>

        <div class="card p-5 min-h-[120px] flex flex-col">
            <div class="flex items-center gap-3.5 min-w-0">
                <span class="grid size-11 shrink-0 place-items-center rounded-full bg-emerald-50 text-emerald-700">
                    <x-icon name="check-circle" class="size-5.5"/>
                </span>
                <div class="min-w-0">
                    <p class="text-[13px]/[18px] text-ink-secondary truncate">Akun Aktif</p>
                    <p class="text-[26px]/[32px] font-semibold tabular-nums text-emerald-700" x-text="stats.active"></p>
                </div>
            </div>
            <p class="mt-auto pt-2.5 text-[12px]/[17px] text-emerald-700 font-medium">Akun aktif & terverifikasi</p>
        </div>

        <div class="card p-5 min-h-[120px] flex flex-col">
            <div class="flex items-center gap-3.5 min-w-0">
                <span class="grid size-11 shrink-0 place-items-center rounded-full bg-info-soft text-info">
                    <x-icon name="clipboard-list" class="size-5.5"/>
                </span>
                <div class="min-w-0">
                    <p class="text-[13px]/[18px] text-ink-secondary truncate">Pesanan Selesai</p>
                    <p class="text-[26px]/[32px] font-semibold tabular-nums" x-text="stats.totalOrders + ' Pesanan'"></p>
                </div>
            </div>
            <p class="mt-auto pt-2.5 text-[12px]/[17px] text-ink-muted">Total transaksi oleh pelanggan</p>
        </div>

        <div class="card p-5 min-h-[120px] flex flex-col">
            <div class="flex items-center gap-3.5 min-w-0">
                <span class="grid size-11 shrink-0 place-items-center rounded-full bg-amber-soft text-amber-700">
                    <x-icon name="dollar" class="size-5.5"/>
                </span>
                <div class="min-w-0">
                    <p class="text-[13px]/[18px] text-ink-secondary truncate">Total Belanja</p>
                    <p class="text-[22px]/[28px] font-semibold tabular-nums text-focus tracking-tight" x-text="$rp(stats.totalSpent)"></p>
                </div>
            </div>
            <p class="mt-auto pt-2.5 text-[12px]/[17px] text-ink-muted">Akumulasi nilai pesanan lunas</p>
        </div>
    </div>

    {{-- ================= PANEL DIREKTORI PELANGGAN ================= --}}
    <section class="panel mt-6 overflow-hidden" aria-labelledby="customers-list-title">
        <h2 id="customers-list-title" class="sr-only">Direktori Pelanggan Terdaftar</h2>
        <div class="p-5 pb-0">
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative flex-1 min-w-[240px] max-w-[380px]">
                    <x-icon name="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-ink-muted pointer-events-none"/>
                    <label class="sr-only" for="cust-search">Cari pelanggan</label>
                    <input id="cust-search" type="search" class="input pl-10" placeholder="Cari nama, WhatsApp, email, ID..." x-model="query" @input="resetPage()">
                </div>
                <div>
                    <label class="sr-only" for="cust-status">Status</label>
                    <select id="cust-status" class="select !w-[150px]" x-model="status" @change="resetPage()">
                        <option value="all">Semua Status</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                </div>
                <div>
                    <label class="sr-only" for="cust-source">Sumber</label>
                    <select id="cust-source" class="select !w-[170px]" x-model="source" @change="resetPage()">
                        <option value="all">Semua Sumber</option>
                        <option value="qr">Scan QR Meja</option>
                        <option value="manual">Registrasi Akun</option>
                    </select>
                </div>
                <div>
                    <label class="sr-only" for="cust-sort">Urutkan</label>
                    <select id="cust-sort" class="select !w-[190px]" x-model="sortBy" @change="resetPage()">
                        <option value="newest">Terbaru Mendaftar</option>
                        <option value="most_orders">Paling Sering Pesan</option>
                        <option value="spent-desc">Total Belanja: Tertinggi</option>
                        <option value="name-asc">Nama: A–Z</option>
                        <option value="name-desc">Nama: Z–A</option>
                    </select>
                </div>
                <button type="button" class="btn btn-secondary" @click="clearFilters()">
                    <x-icon name="x" class="size-4"/> Hapus Filter
                </button>
            </div>
        </div>

        {{-- Banner seleksi massal --}}
        <div x-show="selected.length > 0" x-cloak class="mx-5 mt-4 rounded-xl bg-primary-soft/60 border border-primary/30 px-4 py-3 flex items-center justify-between gap-3 flex-wrap">
            <p class="text-[13.5px]/[19px] font-medium" x-text="`${selected.length} pelanggan dipilih`"></p>
            <div class="flex items-center gap-2 flex-wrap">
                <button type="button" class="btn btn-secondary btn-xs" @click="bulkApply('Active')">Set Aktif</button>
                <button type="button" class="btn btn-secondary btn-xs" @click="bulkApply('Inactive')">Set Nonaktif</button>
                <button type="button" class="btn btn-muted btn-xs" @click="selected = []">Kosongkan Pilihan</button>
            </div>
        </div>

        {{-- Tabel Desktop --}}
        <div class="mt-4 hidden md:block">
            <template x-if="totalRows === 0">
                <div class="py-16 text-center">
                    <span class="mx-auto mb-3 grid size-12 place-items-center rounded-full bg-surface-muted text-ink-muted">
                        <x-icon name="users" class="size-6"/>
                    </span>
                    <p class="text-[15px]/[22px] font-medium" x-text="query || status !== 'all' || source !== 'all' ? 'Pelanggan tidak ditemukan' : 'Belum ada data pelanggan terdaftar'"></p>
                    <p class="text-[13px]/[19px] text-ink-secondary mt-1" x-text="query || status !== 'all' || source !== 'all' ? 'Coba ubah kata kunci pencarian atau reset filter.' : 'Pelanggan yang mendaftar via scan QR meja atau registrasi akan muncul di sini secara otomatis.'"></p>
                    <button type="button" class="btn btn-secondary btn-sm mt-4" x-show="query || status !== 'all' || source !== 'all'" @click="clearFilters()">Hapus Filter</button>
                </div>
            </template>
            <div class="table-wrap" x-show="totalRows > 0">
                <table class="table min-w-[1020px]">
                    <thead>
                        <tr>
                            <th class="w-12">
                                <input type="checkbox" class="size-4 accent-[#49ADAB]" :checked="allPageSelected" @change="toggleSelectAll()" aria-label="Pilih semua pelanggan di halaman">
                            </th>
                            <th>Pelanggan</th>
                            <th>No. WhatsApp / HP</th>
                            <th>Email</th>
                            <th>Waktu Mendaftar</th>
                            <th>Sumber Akun</th>
                            <th class="text-right">Total Pesanan</th>
                            <th class="text-right">Total Belanja</th>
                            <th>Status</th>
                            <th class="w-24 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="c in paged" :key="c.id">
                            <tr class="row-click hover:bg-slate-50/75 transition" :class="selected.includes(c.id) && 'selected'" @click="openProfile(c.id)">
                                <td @click.stop>
                                    <input type="checkbox" class="size-4 accent-[#49ADAB]" :checked="selected.includes(c.id)" @change="toggleSelect(c.id)" :aria-label="`Pilih ${c.name}`">
                                </td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="size-9 rounded-full bg-gradient-to-tr from-[#4CB0AE] to-teal-700 text-white font-bold text-xs grid place-items-center shrink-0"
                                             x-text="initials(c.name)"></div>
                                        <div>
                                            <p class="font-semibold text-slate-900" x-text="c.name"></p>
                                            <p class="text-[11px] font-mono text-slate-400" x-text="c.id"></p>
                                        </div>
                                    </div>
                                </td>
                                <td @click.stop>
                                    <template x-if="c.phone">
                                        <a :href="waLink(c.phone)" target="_blank"
                                           class="font-mono text-xs text-emerald-700 hover:text-emerald-800 hover:underline inline-flex items-center gap-1.5 font-semibold px-2 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 transition"
                                           title="Chat pelanggan via WhatsApp">
                                            <span class="size-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                            <span x-text="c.phone"></span>
                                        </a>
                                    </template>
                                    <template x-if="!c.phone">
                                        <span class="text-slate-400 text-xs">—</span>
                                    </template>
                                </td>
                                <td>
                                    <span class="text-xs text-slate-600" x-text="c.email || '—'"></span>
                                </td>
                                <td class="tabular-nums text-xs text-slate-500" x-text="fmtDate(c.joined_at || c.joinedAt || c.created_at)"></td>
                                <td>
                                    <template x-if="(c.notes || '').toLowerCase().includes('qr')">
                                        <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-teal-100 text-[#2E8B89] inline-flex items-center gap-1">
                                            <x-icon name="qr-code" class="size-3"/>
                                            <span>Scan QR Meja</span>
                                        </span>
                                    </template>
                                    <template x-if="!(c.notes || '').toLowerCase().includes('qr')">
                                        <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-blue-100 text-blue-700 inline-flex items-center gap-1">
                                            <x-icon name="user" class="size-3"/>
                                            <span>Registrasi Akun</span>
                                        </span>
                                    </template>
                                </td>
                                <td class="text-right">
                                    <span class="px-2 py-0.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-800 tabular-nums"
                                          x-text="(metricsFor(c).orders || 0) + ' Pesanan'"></span>
                                </td>
                                <td class="text-right tabular-nums font-bold text-slate-900" x-text="$rp(metricsFor(c).spent || 0)"></td>
                                <td>
                                    <span class="badge" :class="(c.status || 'Active') === 'Active' ? 'badge-positive' : 'badge-neutral'"
                                          x-text="(c.status || 'Active') === 'Active' ? 'Aktif' : 'Nonaktif'"></span>
                                </td>
                                <td @click.stop class="text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" class="btn btn-secondary btn-icon btn-xs" @click="openProfile(c.id)" :aria-label="`Lihat detail ${c.name}`" title="Lihat profil & pesanan">
                                            <x-icon name="eye" class="size-4"/>
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-icon btn-xs" @click="openEdit(c.id)" :aria-label="`Ubah data ${c.name}`" title="Ubah data pelanggan">
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

        {{-- Kartu Mobile --}}
        <div class="md:hidden px-4 pb-2">
            <template x-for="c in paged" :key="c.id">
                <div class="rounded-xl border border-line p-4 mb-2.5 bg-white shadow-xs">
                    <div class="flex items-center gap-3">
                        <div class="size-10 rounded-full bg-gradient-to-tr from-[#4CB0AE] to-teal-700 text-white font-bold text-xs grid place-items-center shrink-0"
                             x-text="initials(c.name)"></div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-bold text-slate-900 truncate" x-text="c.name"></p>
                                <span class="badge" :class="(c.status || 'Active') === 'Active' ? 'badge-positive' : 'badge-neutral'" x-text="(c.status || 'Active') === 'Active' ? 'Aktif' : 'Nonaktif'"></span>
                            </div>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="text-[11px] font-mono text-slate-400" x-text="c.id"></span>
                                <span class="text-[10px] font-bold px-1.5 py-0.2 rounded"
                                      :class="(c.notes || '').toLowerCase().includes('qr') ? 'bg-teal-100 text-teal-800' : 'bg-blue-100 text-blue-800'"
                                      x-text="(c.notes || '').toLowerCase().includes('qr') ? 'Scan QR' : 'Akun'"></span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 text-xs space-y-1">
                        <template x-if="c.phone">
                            <div class="flex items-center gap-1.5 text-emerald-700 font-semibold font-mono">
                                <span class="size-1.5 rounded-full bg-emerald-500"></span>
                                <a :href="waLink(c.phone)" target="_blank" class="hover:underline" x-text="c.phone"></a>
                            </div>
                        </template>
                        <p class="text-slate-500 truncate" x-text="c.email || '—'"></p>
                    </div>
                    <div class="flex items-center justify-between mt-3 pt-2.5 border-t border-slate-100 text-xs">
                        <span class="text-slate-600 font-medium" x-text="`${metricsFor(c).orders} pesanan selesai`"></span>
                        <span class="font-bold text-slate-900 tabular-nums" x-text="$rp(metricsFor(c).spent)"></span>
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm w-full mt-3" @click="openProfile(c.id)">Lihat Profil &amp; Riwayat</button>
                </div>
            </template>
        </div>

        {{-- Paginasi --}}
        <div class="flex flex-wrap items-center justify-between gap-3 p-5 border-t border-slate-200/80">
            <p class="text-[13px] text-slate-500">
                Menampilkan <span class="font-semibold text-slate-900" x-text="totalRows > 0 ? ((page - 1) * pageSize + 1) : 0"></span>–<span class="font-semibold text-slate-900" x-text="Math.min(page * pageSize, totalRows)"></span> dari <span class="font-semibold text-slate-900" x-text="totalRows"></span> pelanggan
            </p>
            <div class="flex items-center gap-2">
                <button type="button" class="btn btn-secondary btn-sm" :disabled="page <= 1" @click="prev()">Sebelumnya</button>
                <span class="text-xs text-slate-500">Hal. <strong x-text="page"></strong> / <span x-text="totalPages"></span></span>
                <button type="button" class="btn btn-secondary btn-sm" :disabled="page >= totalPages" @click="next()">Berikutnya</button>
            </div>
        </div>
    </section>

    {{-- ================= DRAWER PROFIL & RIWAYAT PESANAN ================= --}}
    <div x-show="profileId" x-cloak class="fixed inset-0 z-[75]" role="dialog" aria-modal="true" aria-label="Profil Pelanggan">
        <div class="absolute inset-0 bg-[#1c2b2b]/40" @click="closeProfile()" x-transition:enter="fade-enter" x-transition:leave="fade-leave"></div>
        <div class="absolute inset-y-0 right-0 w-[600px] max-w-[94vw] bg-white flex flex-col shadow-2xl"
             x-ref="profilePanel" @keydown.tab="PM.trap($event, $refs.profilePanel)" @keydown.escape="closeProfile()"
             x-transition:enter="slide-enter" x-transition:leave="slide-leave">

            <template x-if="profileCustomer">
                <div class="flex-1 flex flex-col min-h-0">
                    {{-- Header Drawer --}}
                    <div class="p-6 border-b border-divider bg-slate-50/50">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3.5 min-w-0">
                                <div class="size-12 rounded-full bg-gradient-to-tr from-[#4CB0AE] to-teal-700 text-white font-bold text-base grid place-items-center shrink-0"
                                     x-text="initials(profileCustomer.name)"></div>
                                <div class="min-w-0">
                                    <h2 class="text-[19px]/[25px] font-bold text-slate-900 truncate" x-text="profileCustomer.name"></h2>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-xs font-mono text-slate-500" x-text="profileCustomer.id"></span>
                                        <span class="badge" :class="(profileCustomer.status || 'Active') === 'Active' ? 'badge-positive' : 'badge-neutral'"
                                              x-text="(profileCustomer.status || 'Active') === 'Active' ? 'Aktif' : 'Nonaktif'"></span>
                                        <template x-if="(profileCustomer.notes || '').toLowerCase().includes('qr')">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-100 text-teal-800">Scan QR Meja</span>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-secondary btn-icon btn-sm" @click="closeProfile()" aria-label="Tutup"><x-icon name="x" class="size-4"/></button>
                        </div>

                        {{-- Nav Tab Drawer --}}
                        <div class="flex gap-2 mt-5 border-b border-slate-200">
                            <button type="button" class="px-3.5 py-2 text-xs font-bold border-b-2 transition"
                                    :class="profileTab === 'overview' ? 'border-[#4CB0AE] text-[#2E8B89]' : 'border-transparent text-slate-500 hover:text-slate-800'"
                                    @click="profileTab = 'overview'">Ringkasan Profil</button>
                            <button type="button" class="px-3.5 py-2 text-xs font-bold border-b-2 transition flex items-center gap-1.5"
                                    :class="profileTab === 'orders' ? 'border-[#4CB0AE] text-[#2E8B89]' : 'border-transparent text-slate-500 hover:text-slate-800'"
                                    @click="profileTab = 'orders'">
                                <span>Riwayat Pesanan</span>
                                <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-slate-200 text-slate-700"
                                      x-text="profileCustomer.orders ? profileCustomer.orders.length : (profileMetrics ? profileMetrics.orders : 0)"></span>
                            </button>
                        </div>
                    </div>

                    {{-- Isi Drawer --}}
                    <div class="flex-1 overflow-y-auto nice-scroll p-6">
                        {{-- TAB 1: RINGKASAN PROFIL --}}
                        <div x-show="profileTab === 'overview'" class="space-y-6">
                            {{-- Kontak --}}
                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Informasi Kontak</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                    <div class="p-3.5 rounded-xl border border-slate-200 bg-white">
                                        <p class="text-xs text-slate-400">Nomor Telepon / WhatsApp</p>
                                        <div class="flex items-center justify-between gap-2 mt-1">
                                            <span class="font-mono text-sm font-semibold text-slate-800" x-text="profileCustomer.phone || 'Belum diisi'"></span>
                                            <template x-if="profileCustomer.phone">
                                                <a :href="waLink(profileCustomer.phone)" target="_blank"
                                                   class="btn btn-secondary btn-xs inline-flex items-center gap-1 text-emerald-700 font-bold" title="Buka WhatsApp">
                                                    <span class="size-1.5 rounded-full bg-emerald-500"></span> WhatsApp
                                                </a>
                                            </template>
                                        </div>
                                    </div>
                                    <div class="p-3.5 rounded-xl border border-slate-200 bg-white">
                                        <p class="text-xs text-slate-400">Alamat Email</p>
                                        <div class="flex items-center justify-between gap-2 mt-1">
                                            <span class="text-sm font-medium text-slate-800 truncate" x-text="profileCustomer.email || 'Belum diisi'"></span>
                                            <template x-if="profileCustomer.email">
                                                <button type="button" @click="copy(profileCustomer.email, 'Email')" class="btn btn-secondary btn-icon btn-xs" title="Salin Email">
                                                    <x-icon name="copy" class="size-3.5"/>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                    <div class="p-3.5 rounded-xl border border-slate-200 bg-white sm:col-span-2">
                                        <p class="text-xs text-slate-400">Alamat</p>
                                        <p class="text-sm text-slate-800 mt-1" x-text="profileCustomer.address || 'Belum ada alamat'"></p>
                                    </div>
                                </div>
                            </div>

                            {{-- Statistik Pembelian --}}
                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Statistik Transaksi</h3>
                                <div class="grid grid-cols-3 gap-3">
                                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                                        <p class="text-xs text-slate-500">Pesanan Selesai</p>
                                        <p class="text-xl font-bold text-slate-900 mt-1 tabular-nums" x-text="profileMetrics ? profileMetrics.orders : (profileCustomer.orders_count || 0)"></p>
                                    </div>
                                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                                        <p class="text-xs text-slate-500">Total Belanja</p>
                                        <p class="text-xl font-bold text-focus mt-1 tabular-nums" x-text="$rp(profileMetrics ? profileMetrics.spent : (profileCustomer.total_spent_cents || 0))"></p>
                                    </div>
                                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                                        <p class="text-xs text-slate-500">Rata-rata Order</p>
                                        <p class="text-xl font-bold text-slate-900 mt-1 tabular-nums" x-text="$rp(profileMetrics ? profileMetrics.avg : 0)"></p>
                                    </div>
                                </div>
                            </div>

                            {{-- Catatan Pelanggan --}}
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Catatan Pelanggan</h3>
                                    <button type="button" class="text-xs text-[#2E8B89] font-bold hover:underline" x-show="!notesEditing" @click="startEditNotes()">Ubah Catatan</button>
                                </div>
                                <template x-if="!notesEditing">
                                    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 min-h-[72px]">
                                        <p class="text-sm text-slate-700 whitespace-pre-wrap" x-text="profileCustomer.notes || 'Belum ada catatan khusus untuk pelanggan ini.'"></p>
                                    </div>
                                </template>
                                <template x-if="notesEditing">
                                    <div class="space-y-2">
                                        <textarea class="textarea" x-model="notesDraft" rows="3" placeholder="Tambahkan preferensi pelanggan, alergi, atau catatan meja..."></textarea>
                                        <div class="flex justify-end gap-2">
                                            <button type="button" class="btn btn-secondary btn-sm" @click="notesEditing = false">Batal</button>
                                            <button type="button" class="btn btn-primary btn-sm" @click="saveNotes()">Simpan Catatan</button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- TAB 2: RIWAYAT PESANAN --}}
                        <div x-show="profileTab === 'orders'" class="space-y-3">
                            <template x-if="profileCustomer.orders && profileCustomer.orders.length > 0">
                                <div class="space-y-2.5">
                                    <template x-for="ord in profileCustomer.orders" :key="ord.id">
                                        <div class="p-4 rounded-xl border border-slate-200 bg-white hover:border-slate-300 transition">
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-mono font-bold text-sm text-slate-900" x-text="ord.id"></span>
                                                    <template x-if="ord.table_id || ord.table">
                                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-700"
                                                              x-text="ord.table ? 'Meja ' + ord.table.code : 'Meja #' + ord.table_id"></span>
                                                    </template>
                                                </div>
                                                <span class="badge"
                                                      :class="ord.payment_status === 'Paid' ? 'badge-positive' : ord.payment_status === 'Pending' ? 'badge-warning' : 'badge-neutral'"
                                                      x-text="ord.payment_status === 'Paid' ? 'Lunas' : (ord.payment_status || 'Menunggu')"></span>
                                            </div>
                                            <div class="flex items-center justify-between mt-2 pt-2 border-t border-slate-100 text-xs">
                                                <span class="text-slate-500 tabular-nums" x-text="fmtDate(ord.created_at)"></span>
                                                <span class="font-bold text-slate-900 tabular-nums" x-text="$rp(ord.total_cents || ord.total || 0)"></span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <template x-if="!profileCustomer.orders || profileCustomer.orders.length === 0">
                                <div class="py-12 text-center text-slate-400">
                                    <x-icon name="clipboard-list" class="size-10 mx-auto text-slate-300 mb-2"/>
                                    <p class="font-medium text-slate-700">Belum ada riwayat pesanan</p>
                                    <p class="text-xs text-slate-400 mt-1">Pesanan yang dibuat via scan QR meja oleh pelanggan ini akan tampil di sini.</p>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Footer Drawer --}}
                    <div class="p-5 border-t border-divider flex items-center justify-between gap-3 bg-slate-50/70">
                        <div class="flex items-center gap-2">
                            <button type="button" class="btn btn-secondary" @click="closeProfile()">Tutup</button>
                            <button type="button" class="btn btn-primary" @click="openEdit(profileCustomer.id)">Ubah Profil</button>
                        </div>
                        <button type="button"
                                :class="(profileCustomer.status || 'Active') === 'Active' ? 'btn btn-danger-soft' : 'btn btn-secondary'"
                                @click="openStatus((profileCustomer.status || 'Active') === 'Active' ? 'Inactive' : 'Active')"
                                x-text="(profileCustomer.status || 'Active') === 'Active' ? 'Nonaktifkan Akun' : 'Aktifkan Akun'"></button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- ================= DRAWER FORM TAMBAH / UBAH PELANGGAN ================= --}}
    <div x-show="formOpen" x-cloak class="fixed inset-0 z-[80]" role="dialog" aria-modal="true" :aria-label="editingId ? 'Ubah Pelanggan' : 'Tambah Pelanggan'">
        <div class="absolute inset-0 bg-[#1c2b2b]/40" @click="closeForm()" x-transition:enter="fade-enter" x-transition:leave="fade-leave"></div>
        <div class="absolute inset-y-0 right-0 w-[560px] max-w-[94vw] bg-white flex flex-col shadow-2xl"
             x-ref="formPanel" @keydown.tab="PM.trap($event, $refs.formPanel)" @keydown.escape="closeForm()"
             x-transition:enter="slide-enter" x-transition:leave="slide-leave">
            <div class="px-6 py-5 border-b border-divider flex items-center justify-between">
                <div>
                    <h2 class="text-[18px]/[24px] font-bold text-slate-900" x-text="editingId ? 'Ubah Data Pelanggan' : 'Tambah Pelanggan Baru'"></h2>
                    <p class="text-xs text-slate-500 mt-0.5">Lengkapi profil pelanggan untuk direktori dan WhatsApp.</p>
                </div>
                <button type="button" class="btn btn-secondary btn-icon btn-sm" @click="closeForm()" aria-label="Tutup form"><x-icon name="x" class="size-4"/></button>
            </div>
            <div class="flex-1 overflow-y-auto nice-scroll px-6 py-5 space-y-4">
                <div>
                    <label class="field-label" for="cf-name">Nama Lengkap Pelanggan <span class="text-danger">*</span></label>
                    <input id="cf-name" type="text" class="input" x-model="form.name" maxlength="120" placeholder="Contoh: Budi Santoso" :aria-invalid="!!formErrors.name">
                    <p class="field-error" x-show="formErrors.name" x-text="formErrors.name"></p>
                </div>
                <div>
                    <label class="field-label" for="cf-phone">Nomor WhatsApp / HP <span class="text-danger">*</span></label>
                    <input id="cf-phone" type="tel" class="input" x-model="form.phone" placeholder="08123456789 atau +628123456789" :aria-invalid="!!formErrors.phone">
                    <p class="field-help">Digunakan untuk login akun scan QR meja dan kontak via WhatsApp.</p>
                    <p class="field-error" x-show="formErrors.phone" x-text="formErrors.phone"></p>
                </div>
                <div>
                    <label class="field-label" for="cf-email">Alamat Email</label>
                    <input id="cf-email" type="email" class="input" x-model="form.email" placeholder="nama@email.com" :aria-invalid="!!formErrors.email">
                    <p class="field-error" x-show="formErrors.email" x-text="formErrors.email"></p>
                </div>
                <div>
                    <label class="field-label" for="cf-address">Alamat</label>
                    <textarea id="cf-address" class="textarea !min-h-[72px]" x-model="form.address" maxlength="300" placeholder="Alamat pelanggan (opsional)"></textarea>
                </div>
                <div>
                    <label class="field-label" for="cf-status">Status Akun</label>
                    <select id="cf-status" class="select" x-model="form.status">
                        <option value="Active">Aktif</option>
                        <option value="Inactive">Nonaktif</option>
                    </select>
                </div>
                <div>
                    <label class="field-label" for="cf-notes">Catatan Khusus</label>
                    <textarea id="cf-notes" class="textarea !min-h-[80px]" x-model="form.notes" maxlength="1000" placeholder="Catatan preferensi, meja favorit, dll..."></textarea>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-divider flex items-center justify-end gap-3 bg-slate-50">
                <button type="button" class="btn btn-secondary" @click="closeForm()">Batal</button>
                <button type="button" class="btn btn-primary" :disabled="saving" @click="saveForm()">
                    <span x-show="saving" class="inline-block size-3.5 rounded-full border-2 border-white border-t-transparent animate-spin mr-1.5"></span>
                    <span x-text="saving ? 'Menyimpan...' : (editingId ? 'Simpan Perubahan' : 'Daftarkan Pelanggan')"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- ================= DIALOG KONFIRMASI STATUS ================= --}}
    <div x-show="statusOpen" x-cloak class="fixed inset-0 z-[85] grid place-items-center p-4" role="dialog" aria-modal="true" aria-label="Ubah status akun">
        <div class="absolute inset-0 bg-[#1c2b2b]/45" @click="statusOpen = false" x-transition:enter="fade-enter" x-transition:leave="fade-leave"></div>
        <div class="relative w-full max-w-[420px] rounded-2xl bg-white shadow-2xl p-6" x-transition:enter="pop-enter" x-transition:leave="pop-leave">
            <h2 class="text-[18px]/[24px] font-bold text-slate-900" x-text="bulkTarget === 'Active' ? 'Aktifkan akun pelanggan ini?' : 'Nonaktifkan akun pelanggan ini?'"></h2>
            <p class="text-sm text-slate-600 mt-2">
                <span x-show="bulkTarget === 'Inactive'">Pelanggan yang dinonaktifkan tidak dapat login ke portal meja sampai diaktifkan kembali.</span>
                <span x-show="bulkTarget === 'Active'">Akun pelanggan akan aktif kembali untuk melakukan pesanan.</span>
            </p>
            <div class="grid grid-cols-2 gap-3 mt-6">
                <button type="button" class="btn btn-secondary" @click="statusOpen = false">Batal</button>
                <button type="button" class="btn btn-primary" @click="confirmStatus()" x-text="bulkTarget === 'Active' ? 'Aktifkan' : 'Nonaktifkan'"></button>
            </div>
        </div>
    </div>

    {{-- ================= DIALOG BUANG PERUBAHAN ================= --}}
    <div x-show="discardOpen" x-cloak class="fixed inset-0 z-[95] grid place-items-center p-4" role="dialog" aria-modal="true" aria-label="Buang perubahan">
        <div class="absolute inset-0 bg-[#1c2b2b]/50" @click="discardOpen = false"></div>
        <div class="relative w-full max-w-[420px] rounded-2xl bg-white shadow-2xl p-6" x-transition:enter="pop-enter" x-transition:leave="pop-leave">
            <h2 class="text-[18px]/[24px] font-bold text-slate-900">Buang perubahan?</h2>
            <p class="text-sm text-slate-600 mt-2">Data form yang belum disimpan akan hilang.</p>
            <div class="grid grid-cols-2 gap-3 mt-6">
                <button type="button" class="btn btn-secondary" @click="discardOpen = false">Lanjut Mengedit</button>
                <button type="button" class="btn btn-danger" @click="discardOpen = false; closeForm(true)">Buang</button>
            </div>
        </div>
    </div>

    {{-- ================= DIALOG REVIEW BULK ================= --}}
    <div x-show="bulkReviewOpen" x-cloak class="fixed inset-0 z-[85] grid place-items-center p-4" role="dialog" aria-modal="true" aria-label="Konfirmasi perubahan massal">
        <div class="absolute inset-0 bg-[#1c2b2b]/45" @click="bulkReviewOpen = false" x-transition:enter="fade-enter" x-transition:leave="fade-leave"></div>
        <div class="relative w-full max-w-[440px] rounded-2xl bg-white shadow-2xl p-6" x-transition:enter="pop-enter" x-transition:leave="pop-leave">
            <h2 class="text-[18px]/[24px] font-bold text-slate-900" x-text="bulkTarget === 'Active' ? 'Aktifkan pelanggan terpilih?' : 'Nonaktifkan pelanggan terpilih?'"></h2>
            <p class="text-sm text-slate-600 mt-2">
                <span class="font-bold text-slate-900" x-text="selected.length"></span> pelanggan akan diubah statusnya menjadi <span class="font-bold" x-text="bulkTarget === 'Active' ? 'Aktif' : 'Nonaktif'"></span>.
            </p>
            <div class="grid grid-cols-2 gap-3 mt-6">
                <button type="button" class="btn btn-secondary" @click="bulkReviewOpen = false">Batal</button>
                <button type="button" class="btn btn-primary" @click="bulkConfirm()" x-text="bulkTarget === 'Active' ? 'Set Aktif' : 'Set Nonaktif'"></button>
            </div>
        </div>
    </div>
</div>
@endsection
