@extends('layouts.app')

@section('title', 'Diskon')
@section('subtitle', 'Buat dan kelola promosi untuk toko Anda.')

@section('content')
<div x-data="discountsPage" x-cloak @keydown.window.escape="formOpen ? closeForm() : (detailId && closeDetail())">

    <x-page-header title="Diskon" subtitle="Buat dan kelola promosi untuk toko Anda.">
        <button type="button" class="btn btn-secondary" @click="exportCsv()">
            <x-icon name="download" class="size-4.5"/> Ekspor
        </button>
        <button type="button" class="btn btn-primary" @click="openCreate()" x-show="$store.posy.can('manageDiscounts')">
            <x-icon name="plus" class="size-4.5"/> Buat Diskon
        </button>
    </x-page-header>

    {{-- ================= STATISTIK ================= --}}
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-[22px] mt-6 max-[480px]:gap-3" role="group" aria-label="Statistik diskon">
        <div class="card p-5 min-h-[120px] flex flex-col">
            <div class="flex items-center gap-3.5 min-w-0">
                <span class="grid size-11 shrink-0 place-items-center rounded-full bg-primary-soft text-focus"><x-icon name="tag" class="size-5.5"/></span>
                <div class="min-w-0">
                    <p class="text-[13px]/[18px] text-ink-secondary truncate">Diskon Aktif</p>
                    <p class="text-[26px]/[32px] font-semibold tabular-nums" x-text="stats.active"></p>
                </div>
            </div>
            <p class="mt-auto pt-2.5 text-[12px]/[17px] text-ink-muted">Seluruh daftar diskon</p>
        </div>
        <div class="card p-5 min-h-[120px] flex flex-col">
            <div class="flex items-center gap-3.5 min-w-0">
                <span class="grid size-11 shrink-0 place-items-center rounded-full bg-info-soft text-info"><x-icon name="calendar" class="size-5.5"/></span>
                <div class="min-w-0">
                    <p class="text-[13px]/[18px] text-ink-secondary truncate">Terjadwal</p>
                    <p class="text-[26px]/[32px] font-semibold tabular-nums" x-text="stats.scheduled"></p>
                </div>
            </div>
            <p class="mt-auto pt-2.5 text-[12px]/[17px] text-ink-muted">Seluruh daftar diskon</p>
        </div>
        <div class="card p-5 min-h-[120px] flex flex-col">
            <div class="flex items-center gap-3.5 min-w-0">
                <span class="grid size-11 shrink-0 place-items-center rounded-full bg-primary-soft text-focus"><x-icon name="badge-percent" class="size-5.5"/></span>
                <div class="min-w-0">
                    <p class="text-[13px]/[18px] text-ink-secondary truncate">Total Pemakaian</p>
                    <p class="text-[26px]/[32px] font-semibold tabular-nums" x-text="stats.redemptions"></p>
                </div>
            </div>
            <p class="mt-auto pt-2.5 text-[12px]/[17px] text-ink-muted">Baseline historis</p>
        </div>
        <div class="card p-5 min-h-[120px] flex flex-col">
            <div class="flex items-center gap-3.5 min-w-0">
                <span class="grid size-11 shrink-0 place-items-center rounded-full bg-amber-soft text-amber"><x-icon name="clock" class="size-5.5"/></span>
                <div class="min-w-0">
                    <p class="text-[13px]/[18px] text-ink-secondary truncate">Dipakai Hari Ini</p>
                    <p class="text-[26px]/[32px] font-semibold tabular-nums" x-text="stats.usedToday"></p>
                </div>
            </div>
            <p class="mt-auto pt-2.5 text-[12px]/[17px] text-ink-muted">Bertambah setelah checkout sukses</p>
        </div>
    </div>

    {{-- ================= PANEL DAFTAR ================= --}}
    <section class="panel mt-6 overflow-hidden" aria-labelledby="discounts-list-title">
        <h2 id="discounts-list-title" class="sr-only">Daftar diskon</h2>
        <div class="p-5 pb-0">
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative w-[300px] max-w-full">
                    <x-icon name="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-ink-muted pointer-events-none"/>
                    <label class="sr-only" for="disc-search">Cari diskon</label>
                    <input id="disc-search" type="search" class="input pl-10" placeholder="Cari diskon…" x-model="query" @input="resetPage()">
                </div>
                <div>
                    <label class="sr-only" for="disc-status">Status</label>
                    <select id="disc-status" class="select !w-[150px]" x-model="status" @change="resetPage()">
                        <option value="all">Semua Status</option>
                        <option value="Aktif">Aktif</option>
                        <option value="Terjadwal">Terjadwal</option>
                        <option value="Draf">Draf</option>
                        <option value="Kedaluwarsa">Kedaluwarsa</option>
                        <option value="Nonaktif">Nonaktif</option>
                        <option value="Habis">Habis</option>
                    </select>
                </div>
                <div>
                    <label class="sr-only" for="disc-type">Jenis</label>
                    <select id="disc-type" class="select !w-[150px]" x-model="type" @change="resetPage()">
                        <option value="all">Semua Jenis</option>
                        <option value="percentage">Persentase</option>
                        <option value="fixed">Nominal Tetap</option>
                    </select>
                </div>
                <div>
                    <label class="sr-only" for="disc-applies">Berlaku untuk</label>
                    <select id="disc-applies" class="select !w-[160px]" x-model="appliesTo" @change="resetPage()">
                        <option value="all">Semua Cakupan</option>
                        <option value="all-products">Semua Produk</option>
                        <option value="categories">Kategori Terpilih</option>
                        <option value="products">Produk Terpilih</option>
                    </select>
                </div>
                <div>
                    <label class="sr-only" for="disc-sort">Urutkan</label>
                    <select id="disc-sort" class="select !w-[180px]" x-model="sortBy" @change="resetPage()">
                        <option value="created-desc">Dibuat: Terbaru</option>
                        <option value="name-asc">Nama: A–Z</option>
                        <option value="name-desc">Nama: Z–A</option>
                        <option value="value-desc">Nilai: Tinggi ke Rendah</option>
                        <option value="value-asc">Nilai: Rendah ke Tinggi</option>
                        <option value="start-desc">Tanggal Mulai: Terbaru</option>
                        <option value="usage-desc">Pemakaian: Terbanyak</option>
                    </select>
                </div>
                <button type="button" class="btn btn-secondary" @click="resetFilters()">
                    <x-icon name="x" class="size-4"/> Reset Filter
                </button>
            </div>
        </div>

        <div class="mt-4 hidden md:block">
            <template x-if="totalRows === 0">
                <div class="py-16 text-center">
                    <span class="mx-auto mb-3 grid size-12 place-items-center rounded-full bg-surface-muted text-ink-muted"><x-icon name="tag" class="size-6"/></span>
                    <p class="text-[15px]/[22px] font-medium" x-text="query || status !== 'all' || type !== 'all' ? 'Tidak ada diskon yang cocok dengan filter' : 'Belum ada diskon'"></p>
                    <p class="text-[13px]/[19px] text-ink-secondary mt-1" x-text="query || status !== 'all' || type !== 'all' ? 'Ubah filter Anda atau reset untuk melihat semua.' : 'Buat diskon pertama Anda untuk mulai berpromosi.'"></p>
                    <button type="button" class="btn btn-secondary btn-sm mt-4" x-show="query || status !== 'all' || type !== 'all'" @click="resetFilters()">Reset Filter</button>
                </div>
            </template>
            <div class="table-wrap" x-show="totalRows > 0">
                <table class="table min-w-[1068px]">
                    <thead>
                        <tr>
                            <th>Diskon</th>
                            <th>Kode</th>
                            <th>Jenis</th>
                            <th class="num">Nilai</th>
                            <th>Berlaku Untuk</th>
                            <th>Masa Berlaku</th>
                            <th class="num">Pemakaian</th>
                            <th>Status</th>
                            <th class="w-14">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="d in paged" :key="d.id">
                            <tr class="row-click" @click="openDetail(d.id)">
                                <td>
                                    <p class="font-medium" x-text="d.name"></p>
                                    <p class="text-[11.5px] text-ink-muted" x-show="d.minSpend > 0" x-text="`Min. belanja ${$rp(d.minSpend)}`"></p>
                                </td>
                                <td><span class="font-mono text-[12px] bg-surface-muted rounded-md px-1.5 py-0.5" x-text="d.code"></span></td>
                                <td class="text-ink-secondary" x-text="d.type === 'percentage' ? 'Persentase' : 'Nominal Tetap'"></td>
                                <td class="num font-medium tabular-nums" x-text="valueLabel(d)"></td>
                                <td class="text-ink-secondary" x-text="appliesLabel(d)"></td>
                                <td class="text-ink-secondary text-[12.5px]/[17px]">
                                    <template x-if="d.startDate && d.endDate">
                                        <div>
                                            <span x-text="$rpfmt.dateTime(d.startDate)"></span><br>
                                            <span class="text-ink-muted" x-text="`→ ${$rpfmt.dateTime(d.endDate)}`"></span>
                                        </div>
                                    </template>
                                    <template x-if="!d.startDate || !d.endDate">
                                        <span class="text-ink-muted">Belum dijadwalkan</span>
                                    </template>
                                </td>
                                <td class="num text-ink-secondary tabular-nums" x-text="`${d.usedCount} / ${d.usageLimit ?? 'Tanpa Batas'}`"></td>
                                <td><span class="badge" :class="statusBadge(d.eff)" x-text="d.eff"></span></td>
                                <td>
                                    <div class="flex justify-end" @click.stop>
                                        <div class="relative">
                                            <button type="button" class="btn btn-secondary btn-icon btn-xs" @click="kebabRowId = kebabRowId === d.id ? null : d.id" :aria-label="`Aksi lainnya ${d.name}`">
                                                <x-icon name="more" class="size-4"/>
                                            </button>
                                            <div x-show="kebabRowId === d.id" x-cloak @click.outside="kebabRowId = null" @keydown.escape="kebabRowId = null"
                                                 x-transition:enter="pop-enter" x-transition:leave="pop-leave"
                                                 class="absolute right-0 top-[calc(100%+6px)] w-52 rounded-xl bg-white border border-line shadow-[0_12px_40px_rgba(30,41,59,0.14)] p-1.5 z-40" role="menu">
                                                <button type="button" class="w-full text-left px-3 py-2 rounded-lg text-[13px] hover:bg-surface-muted" role="menuitem" @click="openDetail(d.id)">Lihat Detail</button>
                                                <button type="button" class="w-full text-left px-3 py-2 rounded-lg text-[13px] hover:bg-surface-muted" role="menuitem" @click="openEdit(d.id)">Ubah</button>
                                                <button type="button" class="w-full text-left px-3 py-2 rounded-lg text-[13px] hover:bg-surface-muted" role="menuitem" @click="duplicate(d.id)">Duplikat</button>
                                                <button type="button" class="w-full text-left px-3 py-2 rounded-lg text-[13px] hover:bg-surface-muted" role="menuitem"
                                                        @click="toggleEnabled(d.id)" x-text="d.enabled ? 'Nonaktifkan' : 'Aktifkan'"></button>
                                                <button type="button" class="w-full text-left px-3 py-2 rounded-lg text-[13px] hover:bg-danger-soft text-danger" role="menuitem"
                                                        @click="openDelete(d.id)">Hapus</button>
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
            <template x-for="d in paged" :key="d.id">
                <div class="rounded-xl border border-line p-4 mb-2.5">
                    <div class="flex items-center justify-between gap-2">
                        <p class="font-medium" x-text="d.name"></p>
                        <span class="badge" :class="statusBadge(d.eff)" x-text="d.eff"></span>
                    </div>
                    <p class="font-mono text-[12px] text-ink-muted mt-1" x-text="d.code"></p>
                    <div class="flex items-center justify-between mt-1.5 text-[13px]">
                        <span class="text-ink-secondary" x-text="`${d.type === 'percentage' ? 'Persentase' : 'Nominal Tetap'} · ${valueLabel(d)} · ${appliesLabel(d)}`"></span>
                    </div>
                    <p class="text-[12px] text-ink-muted mt-0.5" x-text="validityLabel(d)"></p>
                    <div class="flex items-center justify-between mt-1 text-[13px]">
                        <span class="text-ink-secondary" x-text="`Dipakai ${d.usedCount} / ${d.usageLimit ?? '∞'}`"></span>
                        <button type="button" class="btn btn-secondary btn-xs" @click="openDetail(d.id)">Lihat Detail</button>
                    </div>
                </div>
            </template>
        </div>

        <x-pagination row-label="diskon"/>
    </section>

    {{-- ================= DRAWER FORM DISKON ================= --}}
    <div x-show="formOpen" x-cloak class="fixed inset-0 z-[75]" role="dialog" aria-modal="true" :aria-label="editingId ? 'Ubah Diskon' : 'Buat Diskon'">
        <div class="absolute inset-0 bg-[#1c2b2b]/40" @click="closeForm()" x-transition:enter="fade-enter" x-transition:leave="fade-leave"></div>
        <div class="absolute inset-y-0 right-0 w-[560px] max-w-[94vw] bg-white flex flex-col"
             x-ref="formPanel" @keydown.tab="PM.trap($event, $refs.formPanel)" @keydown.escape="closeForm()"
             x-transition:enter="slide-enter" x-transition:leave="slide-leave">
            <div class="px-6 py-5 border-b border-divider flex items-center justify-between">
                <h2 class="text-[18px]/[24px] font-semibold" x-text="editingId ? 'Ubah Diskon' : 'Buat Diskon'"></h2>
                <button type="button" class="btn btn-secondary btn-icon btn-sm" @click="closeForm()" aria-label="Tutup form"><x-icon name="x" class="size-4"/></button>
            </div>
            <div class="flex-1 overflow-y-auto nice-scroll px-6 py-5">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="field-label" for="df-name">Nama Diskon <span class="text-danger">*</span></label>
                        <input id="df-name" type="text" class="input" x-model="form.name" maxlength="80" :aria-invalid="!!formErrors.name">
                        <p class="field-error" x-show="formErrors.name" x-text="formErrors.name"></p>
                    </div>
                    <div class="col-span-2">
                        <label class="field-label" for="df-code">Kode <span class="text-danger">*</span></label>
                        <input id="df-code" type="text" class="input font-mono uppercase" x-model="form.code" maxlength="24" :aria-invalid="!!formErrors.code" placeholder="PROMO10">
                        <p class="field-help">3–24 karakter: A–Z, 0–9, garis bawah, tanda hubung. Unik.</p>
                        <p class="field-error" x-show="formErrors.code" x-text="formErrors.code"></p>
                    </div>
                    <div class="col-span-2">
                        <label class="field-label" for="df-desc">Deskripsi</label>
                        <textarea id="df-desc" class="textarea !min-h-[60px]" x-model="form.description" maxlength="240"></textarea>
                    </div>
                    <div class="col-span-2">
                        <span class="field-label">Jenis Diskon <span class="text-danger">*</span></span>
                        <div class="grid grid-cols-2 gap-2.5">
                            <button type="button" class="rounded-xl border px-4 py-3 text-[13.5px] font-medium transition-colors"
                                    :class="form.type === 'percentage' ? 'border-primary bg-primary-soft/60 text-focus' : 'border-line hover:border-[#dfe3e8]'"
                                    @click="form.type = 'percentage'" :aria-pressed="form.type === 'percentage'">Persentase</button>
                            <button type="button" class="rounded-xl border px-4 py-3 text-[13.5px] font-medium transition-colors"
                                    :class="form.type === 'fixed' ? 'border-primary bg-primary-soft/60 text-focus' : 'border-line hover:border-[#dfe3e8]'"
                                    @click="form.type = 'fixed'" :aria-pressed="form.type === 'fixed'">Nominal Tetap</button>
                        </div>
                    </div>
                    <div class="col-span-2">
                        <label class="field-label" for="df-value">Nilai Diskon <span class="text-danger">*</span></label>
                        <div class="relative">
                            <input id="df-value" type="number" min="0" :max="form.type === 'percentage' ? 100 : 999999999" step="0.01" class="input tabular-nums pr-12" x-model="form.value" :aria-invalid="!!formErrors.value">
                            <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-ink-muted" x-text="form.type === 'percentage' ? '%' : 'Rp'"></span>
                        </div>
                        <p class="field-error" x-show="formErrors.value" x-text="formErrors.value"></p>
                    </div>
                    <div class="col-span-2">
                        <label class="field-label" for="df-applies">Berlaku Untuk</label>
                        <select id="df-applies" class="select" x-model="form.appliesTo">
                            <option value="all">Semua produk</option>
                            <option value="categories">Kategori terpilih</option>
                            <option value="products">Produk terpilih</option>
                        </select>
                    </div>
                    <div class="col-span-2" x-show="form.appliesTo === 'categories'" x-cloak>
                        <span class="field-label">Kategori <span class="text-danger">*</span></span>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="c in categories" :key="c">
                                <button type="button" class="chip !h-9" :aria-pressed="form.categories.includes(c)" @click="toggleIn(form.categories, c)" x-text="c"></button>
                            </template>
                        </div>
                        <p class="field-error" x-show="formErrors.categories" x-text="formErrors.categories"></p>
                    </div>
                    <div class="col-span-2" x-show="form.appliesTo === 'products'" x-cloak>
                        <label class="field-label" for="df-psearch">Produk <span class="text-danger">*</span></label>
                        <input id="df-psearch" type="search" class="input" placeholder="Cari produk…" x-model="productSearch">
                        <div class="mt-2 max-h-40 overflow-y-auto nice-scroll rounded-xl border border-line divide-y divide-divider">
                            <template x-for="p in formCatalog.slice(0, 20)" :key="p.id">
                                <label class="flex items-center gap-3 px-4 py-2.5 cursor-pointer hover:bg-[#f8fbfb]">
                                    <input type="checkbox" class="size-4 accent-[#49ADAB]" :checked="form.products.includes(p.id)" @change="toggleIn(form.products, p.id)">
                                    <span class="text-[13px] flex-1" x-text="p.name"></span>
                                    <span class="text-[12px] text-ink-muted tabular-nums" x-text="$rp(p.price)"></span>
                                </label>
                            </template>
                        </div>
                        <p class="field-error" x-show="formErrors.products" x-text="formErrors.products"></p>
                    </div>
                    <div class="col-span-2">
                        <label class="field-label" for="df-elig">Kelayakan Pelanggan</label>
                        <select id="df-elig" class="select" x-model="form.customerEligibility">
                            <option value="all">Semua pelanggan</option>
                            <option value="selected">Pelanggan terpilih</option>
                        </select>
                    </div>
                    <div class="col-span-2" x-show="form.customerEligibility === 'selected'" x-cloak>
                        <span class="field-label">Pelanggan <span class="text-danger">*</span></span>
                        <div class="max-h-40 overflow-y-auto nice-scroll rounded-xl border border-line divide-y divide-divider">
                            <template x-for="c in $store.posy.customers" :key="c.id">
                                <label class="flex items-center gap-3 px-4 py-2.5 cursor-pointer hover:bg-[#f8fbfb]">
                                    <input type="checkbox" class="size-4 accent-[#49ADAB]" :checked="form.customers.includes(c.id)" @change="toggleIn(form.customers, c.id)">
                                    <span class="text-[13px] flex-1" x-text="c.name"></span>
                                    <span class="text-[11.5px] text-ink-muted" x-text="c.id"></span>
                                </label>
                            </template>
                        </div>
                        <p class="field-error" x-show="formErrors.customers" x-text="formErrors.customers"></p>
                    </div>
                    <div>
                        <label class="field-label" for="df-min">Minimum Belanja (Rp)</label>
                        <input id="df-min" type="number" min="0" step="500" class="input tabular-nums" x-model="form.minSpend">
                        <p class="field-help">Diperiksa terhadap subtotal sebelum diskon/pajak.</p>
                        <p class="field-error" x-show="formErrors.minSpend" x-text="formErrors.minSpend"></p>
                    </div>
                    <div>
                        <span class="field-label">Batas Pemakaian</span>
                        <label class="flex items-center gap-2.5 mt-2 text-[13.5px]">
                            <input type="checkbox" class="size-4 accent-[#49ADAB]" x-model="form.unlimited">
                            Tanpa batas
                        </label>
                        <input type="number" min="1" max="100000" class="input mt-2 tabular-nums" x-model="form.usageLimit" :disabled="form.unlimited" aria-label="Batas pemakaian">
                        <p class="field-error" x-show="formErrors.usageLimit" x-text="formErrors.usageLimit"></p>
                    </div>
                    <div>
                        <label class="field-label" for="df-start">Tanggal &amp; Waktu Mulai</label>
                        <input id="df-start" type="date" class="input" x-model="form.startDate">
                        <input type="time" class="input mt-2" x-model="form.startTime" aria-label="Waktu mulai">
                        <p class="field-help">Zona: Asia/Jakarta</p>
                        <p class="field-error" x-show="formErrors.startDate" x-text="formErrors.startDate"></p>
                    </div>
                    <div>
                        <label class="field-label" for="df-end">Tanggal &amp; Waktu Berakhir</label>
                        <input id="df-end" type="date" class="input" x-model="form.endDate">
                        <input type="time" class="input mt-2" x-model="form.endTime" aria-label="Waktu berakhir">
                        <p class="field-error" x-show="formErrors.endDate" x-text="formErrors.endDate"></p>
                    </div>
                    <div class="col-span-2 flex items-center justify-between rounded-xl border border-line px-4 py-3.5">
                        <div>
                            <p class="text-[13.5px]/[19px] font-medium">Aktifkan diskon</p>
                            <p class="text-[12px]/[16px] text-ink-muted">Promo terbit yang dinonaktifkan berstatus Nonaktif.</p>
                        </div>
                        <button type="button" role="switch" :aria-checked="form.enabled" @click="form.enabled = !form.enabled"
                                class="relative w-12 h-7 rounded-full transition-colors shrink-0" :class="form.enabled ? 'bg-primary' : 'bg-[#d6dade]'">
                            <span class="absolute top-1 size-5 rounded-full bg-white shadow transition-all" :class="form.enabled ? 'left-6' : 'left-1'"></span>
                        </button>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-divider flex items-center gap-2.5">
                <button type="button" class="btn btn-secondary" @click="closeForm()" :disabled="saving">Batal</button>
                <div class="flex-1"></div>
                <button type="button" class="btn btn-secondary" x-show="!editingId || $store.posy.discount(editingId)?.published === false"
                        @click="saveForm(false)" :disabled="saving">Simpan Draf</button>
                <button type="button" class="btn btn-primary" @click="saveForm(true)" :disabled="saving"
                        x-text="saving ? 'Menyimpan…' : (editingId ? 'Simpan Perubahan' : 'Buat Diskon')"></button>
            </div>
        </div>
    </div>

    {{-- ================= DRAWER DETAIL DISKON ================= --}}
    <div x-show="detailId" x-cloak class="fixed inset-0 z-[75]" role="dialog" aria-modal="true" aria-label="Detail diskon">
        <div class="absolute inset-0 bg-[#1c2b2b]/40" @click="closeDetail()" x-transition:enter="fade-enter" x-transition:leave="fade-leave"></div>
        <div class="absolute inset-y-0 right-0 w-[480px] max-w-[92vw] bg-white flex flex-col"
             x-ref="detailPanel" @keydown.tab="PM.trap($event, $refs.detailPanel)" @keydown.escape="closeDetail()"
             x-transition:enter="slide-enter" x-transition:leave="slide-leave">
            <template x-if="detailDiscount">
                <div class="flex flex-col min-h-0 flex-1">
                    <div class="px-6 py-5 border-b border-divider flex items-start justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2.5 flex-wrap">
                                <h2 class="text-[19px]/[25px] font-semibold" x-text="detailDiscount.name"></h2>
                                <span class="badge" :class="statusBadge(effStatus(detailDiscount))" x-text="effStatus(detailDiscount)"></span>
                            </div>
                            <p class="font-mono text-[12.5px] text-ink-muted mt-1" x-text="detailDiscount.code"></p>
                        </div>
                        <button type="button" class="btn btn-secondary btn-icon btn-sm" @click="closeDetail()" aria-label="Tutup detail"><x-icon name="x" class="size-4"/></button>
                    </div>
                    <div class="flex-1 overflow-y-auto nice-scroll px-6 py-5">
                        <dl class="space-y-3.5 text-[13.5px]/[19px]">
                            <div class="flex justify-between gap-4"><dt class="text-ink-muted">Nilai</dt><dd class="font-medium" x-text="valueLabel(detailDiscount)"></dd></div>
                            <div class="flex justify-between gap-4"><dt class="text-ink-muted">Jenis</dt><dd x-text="detailDiscount.type === 'percentage' ? 'Persentase' : 'Nominal Tetap'"></dd></div>
                            <div class="flex justify-between gap-4"><dt class="text-ink-muted">Berlaku untuk</dt><dd class="text-right" x-text="appliesLabel(detailDiscount)"></dd></div>
                            <div class="flex justify-between gap-4"><dt class="text-ink-muted">Minimum belanja</dt><dd class="tabular-nums" x-text="$rp(detailDiscount.minSpend)"></dd></div>
                            <div class="flex justify-between gap-4"><dt class="text-ink-muted">Pelanggan</dt>
                                <dd class="text-right" x-text="(detailDiscount.customers?.length ?? 0) > 0 ? detailDiscount.customers.map(id => $store.posy.customer(id)?.name ?? id).join(', ') : 'Semua pelanggan'"></dd>
                            </div>
                            <div class="flex justify-between gap-4"><dt class="text-ink-muted">Masa berlaku</dt>
                                <dd class="text-right">
                                    <template x-if="detailDiscount.startDate && detailDiscount.endDate">
                                        <span x-text="`${$rpfmt.dateTime(detailDiscount.startDate)} → ${$rpfmt.dateTime(detailDiscount.endDate)}`"></span>
                                    </template>
                                    <template x-if="!detailDiscount.startDate || !detailDiscount.endDate"><span>Belum dijadwalkan</span></template>
                                </dd>
                            </div>
                            <div class="flex justify-between gap-4"><dt class="text-ink-muted">Pemakaian</dt>
                                <dd class="tabular-nums" x-text="`${detailDiscount.usedCount} / ${detailDiscount.usageLimit ?? 'Tanpa Batas'}`"></dd>
                            </div>
                            <div class="flex justify-between gap-4"><dt class="text-ink-muted">Dibuat</dt><dd x-text="detailDiscount.createdAt ? $rpfmt.dateTime(detailDiscount.createdAt) : '—'"></dd></div>
                            <div class="flex justify-between gap-4"><dt class="text-ink-muted">Terakhir diubah</dt><dd x-text="detailDiscount.updatedAt ? $rpfmt.dateTime(detailDiscount.updatedAt) : '—'"></dd></div>
                        </dl>
                        <div x-show="detailDiscount.description" class="mt-5">
                            <p class="text-[12.5px]/[17px] text-ink-muted mb-1">Deskripsi</p>
                            <p class="text-[13.5px]/[20px]" x-text="detailDiscount.description"></p>
                        </div>
                        <div class="mt-5 rounded-xl bg-surface-muted px-4 py-3.5">
                            <p class="text-[12.5px]/[18px] text-ink-secondary">Diskon diterapkan pada Penjualan Saat Ini melalui menu <span class="font-medium text-ink">Aksi lainnya → Terapkan Diskon</span>. Satu diskon per penjualan.</p>
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-divider flex items-center gap-2.5">
                        <button type="button" class="btn btn-secondary" @click="closeDetail()">Tutup</button>
                        <button type="button" class="btn btn-primary" @click="openEdit(detailDiscount.id)">Ubah Diskon</button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- ================= DIALOG HAPUS ================= --}}
    <div x-show="deleteOpen" x-cloak class="fixed inset-0 z-[85] grid place-items-center p-4" role="dialog" aria-modal="true" aria-label="Hapus diskon">
        <div class="absolute inset-0 bg-[#1c2b2b]/45" @click="deleteOpen = false" x-transition:enter="fade-enter" x-transition:leave="fade-leave"></div>
        <div class="relative w-full max-w-[420px] rounded-2xl bg-white shadow-[0_24px_80px_rgba(30,41,59,0.22)] p-6" x-transition:enter="pop-enter" x-transition:leave="pop-leave">
            <h2 class="text-[18px]/[24px] font-semibold">Hapus diskon?</h2>
            <p class="text-[13.5px]/[20px] text-ink-secondary mt-2"><span class="font-medium text-ink" x-text="deleteTarget?.name"></span> akan dihapus dari demo lokal.</p>
            <div class="grid grid-cols-2 gap-3 mt-5">
                <button type="button" class="btn btn-secondary" @click="deleteOpen = false">Batal</button>
                <button type="button" class="btn btn-danger" @click="confirmDelete()">Hapus Diskon</button>
            </div>
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
                <button type="button" class="btn btn-danger" @click="discardOpen = false; closeForm(true)">Buang Perubahan</button>
            </div>
        </div>
    </div>
</div>
@endsection
