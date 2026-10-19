@extends('layouts.app')

@section('title', 'Retur')
@section('subtitle', 'Tinjau permintaan retur dan lacak pengembalian dana.')

@section('content')
<div x-data="returnsPage" x-cloak @keydown.window.escape="wizardOpen ? closeWizard() : (detailId && closeDetail())">

    <x-page-header title="Retur" subtitle="Tinjau permintaan retur dan lacak pengembalian dana.">
        <button type="button" class="btn btn-secondary" @click="exportCsv()">
            <x-icon name="download" class="size-4.5"/> Ekspor CSV
        </button>
        <button type="button" class="btn btn-primary" @click="openWizard()">
            <x-icon name="rotate-ccw" class="size-4.5"/> Retur Baru
        </button>
    </x-page-header>

    {{-- ================= STATISTIK ================= --}}
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-[22px] mt-6 max-[480px]:gap-3" role="group" aria-label="Statistik retur">
        <div class="card p-5 min-h-[120px] flex flex-col">
            <div class="flex items-center gap-3.5 min-w-0">
                <span class="grid size-11 shrink-0 place-items-center rounded-full bg-primary-soft text-focus"><x-icon name="rotate-ccw" class="size-5.5"/></span>
                <div class="min-w-0">
                    <p class="text-[13px]/[18px] text-ink-secondary truncate">Permintaan Retur</p>
                    <p class="text-[26px]/[32px] font-semibold tabular-nums" x-text="stats.requests"></p>
                </div>
            </div>
            <p class="mt-auto pt-2.5 text-[12px]/[17px] text-ink-muted">Setelah pencarian &amp; filter</p>
        </div>
        <div class="card p-5 min-h-[120px] flex flex-col">
            <div class="flex items-center gap-3.5 min-w-0">
                <span class="grid size-11 shrink-0 place-items-center rounded-full bg-amber-soft text-amber"><x-icon name="clock" class="size-5.5"/></span>
                <div class="min-w-0">
                    <p class="text-[13px]/[18px] text-ink-secondary truncate">Menunggu Peninjauan</p>
                    <p class="text-[26px]/[32px] font-semibold tabular-nums" x-text="stats.pending"></p>
                </div>
            </div>
            <p class="mt-auto pt-2.5 text-[12px]/[17px] text-ink-muted">Setelah pencarian &amp; filter</p>
        </div>
        <div class="card p-5 min-h-[120px] flex flex-col">
            <div class="flex items-center gap-3.5 min-w-0">
                <span class="grid size-11 shrink-0 place-items-center rounded-full bg-primary-soft text-focus"><x-icon name="dollar" class="size-5.5"/></span>
                <div class="min-w-0">
                    <p class="text-[13px]/[18px] text-ink-secondary truncate">Refund Selesai</p>
                    <p class="text-[24px]/[30px] font-semibold tabular-nums tracking-tight" x-text="$rp(stats.completedRefunds)"></p>
                </div>
            </div>
            <p class="mt-auto pt-2.5 text-[12px]/[17px] text-ink-muted">Hanya retur berstatus Selesai</p>
        </div>
        <div class="card p-5 min-h-[120px] flex flex-col">
            <div class="flex items-center gap-3.5 min-w-0">
                <span class="grid size-11 shrink-0 place-items-center rounded-full bg-primary-soft text-focus"><x-icon name="package" class="size-5.5"/></span>
                <div class="min-w-0">
                    <p class="text-[13px]/[18px] text-ink-secondary truncate">Unit Kembali</p>
                    <p class="text-[26px]/[32px] font-semibold tabular-nums" x-text="stats.units"></p>
                </div>
            </div>
            <p class="mt-auto pt-2.5 text-[12px]/[17px] text-ink-muted">Dari permintaan selesai</p>
        </div>
    </div>

    {{-- ================= PANEL DAFTAR ================= --}}
    <section class="panel mt-6 overflow-hidden" aria-labelledby="returns-list-title">
        <h2 id="returns-list-title" class="sr-only">Daftar retur</h2>
        <div class="p-5 pb-0">
            <div class="flex items-center gap-2 overflow-x-auto no-scrollbar min-w-0 py-0.5" role="tablist" aria-label="Status retur">
                <template x-for="[key, label] in tabs" :key="key">
                    <button type="button" role="tab" :aria-selected="tab === key" class="chip !h-10"
                            :class="tab === key ? '' : '!bg-surface-muted'"
                            @click="tab = key; resetPage()">
                        <span x-text="label"></span>
                        <span class="ml-1.5 min-w-[20px] h-5 px-1 rounded-md grid place-items-center text-[11px] font-semibold tabular-nums"
                              :class="tab === key ? 'bg-white/25' : 'bg-white text-ink-secondary'"
                              x-text="tabCounts[key]"></span>
                    </button>
                </template>
            </div>
            <div class="flex flex-wrap items-center gap-3 mt-5">
                <div class="relative flex-1 min-w-[240px] max-w-[380px]">
                    <x-icon name="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-ink-muted pointer-events-none"/>
                    <label class="sr-only" for="returns-search">Cari retur</label>
                    <input id="returns-search" type="search" class="input pl-10" placeholder="Cari retur…" x-model="query" @input="resetPage()">
                </div>
                <div>
                    <label class="sr-only" for="returns-date">Tanggal</label>
                    <select id="returns-date" class="select !w-[160px]" x-model="datePreset" @change="resetPage()">
                        <option value="all">Semua Waktu</option>
                        <option value="today">Hari Ini</option>
                        <option value="last7">7 Hari Terakhir</option>
                    </select>
                </div>
                <div>
                    <label class="sr-only" for="returns-method">Metode</label>
                    <select id="returns-method" class="select !w-[170px]" x-model="method" @change="resetPage()">
                        <option value="all">Semua Metode</option>
                        <option value="Cash">Tunai</option>
                        <option value="Card">Kartu</option>
                        <option value="Digital Wallet">Dompet Digital</option>
                    </select>
                </div>
                <div>
                    <label class="sr-only" for="returns-sort">Urutkan</label>
                    <select id="returns-sort" class="select !w-[190px]" x-model="sortBy" @change="resetPage()">
                        <option value="newest">Terbaru</option>
                        <option value="oldest">Terlama</option>
                        <option value="refund-desc">Refund: Tinggi ke Rendah</option>
                        <option value="refund-asc">Refund: Rendah ke Tinggi</option>
                    </select>
                </div>
                <button type="button" class="btn btn-secondary" @click="resetFilters()">
                    <x-icon name="x" class="size-4"/> Reset Filter
                </button>
            </div>
        </div>

        {{-- tabel desktop --}}
        <div class="mt-4 hidden md:block">
            <template x-if="totalRows === 0">
                <div class="py-16 text-center">
                    <span class="mx-auto mb-3 grid size-12 place-items-center rounded-full bg-surface-muted text-ink-muted"><x-icon name="rotate-ccw" class="size-6"/></span>
                    <p class="text-[15px]/[22px] font-medium" x-text="query || tab !== 'all' || datePreset !== 'all' || method !== 'all' ? 'Tidak ada retur yang cocok dengan filter' : 'Belum ada permintaan retur'"></p>
                    <p class="text-[13px]/[19px] text-ink-secondary mt-1" x-text="query || tab !== 'all' || datePreset !== 'all' || method !== 'all' ? 'Ubah filter Anda atau reset untuk melihat semua.' : 'Buat retur pertama Anda dari transaksi yang selesai.'"></p>
                    <button type="button" class="btn btn-secondary btn-sm mt-4" x-show="query || tab !== 'all' || datePreset !== 'all' || method !== 'all'" @click="resetFilters()">Reset Filter</button>
                    <button type="button" class="btn btn-primary btn-sm mt-4" x-show="!(query || tab !== 'all' || datePreset !== 'all' || method !== 'all')" @click="openWizard()">Retur Baru</button>
                </div>
            </template>
            <div class="table-wrap" x-show="totalRows > 0">
                <table class="table min-w-[940px]">
                    <thead>
                        <tr>
                            <th>ID Retur</th>
                            <th>ID Transaksi</th>
                            <th>Pelanggan</th>
                            <th>Diajukan</th>
                            <th class="num">Item</th>
                            <th class="num">Refund</th>
                            <th>Status</th>
                            <th class="w-20">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="r in paged" :key="r.id">
                            <tr class="row-click" @click="openDetail(r.id)">
                                <td class="font-medium text-focus" x-text="r.id"></td>
                                <td>
                                    <a class="text-primary hover:text-primary-hover font-medium" :href="`/sales?sale=${r.saleId}`" @click.stop x-text="r.saleId"></a>
                                </td>
                                <td class="text-ink-secondary" x-text="r.customerName"></td>
                                <td class="text-ink-secondary whitespace-nowrap" x-text="$rpfmt.dateTime(r.requestedAt)"></td>
                                <td class="num text-ink-secondary tabular-nums" x-text="r.unitQuantity"></td>
                                <td class="num font-medium" x-text="$rp(r.refundTotal)"></td>
                                <td>
                                    <span class="badge" :class="{'Completed': 'badge-positive', 'Pending': 'badge-amber', 'Rejected': 'badge-danger', 'Draft': 'badge-neutral'}[r.status]"
                                          x-text="statusLabel(r.status)"></span>
                                </td>
                                <td>
                                    <div class="flex items-center justify-end gap-1" @click.stop>
                                        <button type="button" class="btn btn-secondary btn-icon btn-xs" @click="openDetail(r.id)" :aria-label="`Lihat detail ${r.id}`">
                                            <x-icon name="eye" class="size-4"/>
                                        </button>
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
            <template x-for="r in paged" :key="r.id">
                <div class="rounded-xl border border-line p-4 mb-2.5">
                    <div class="flex items-center justify-between gap-2">
                        <p class="font-medium text-focus" x-text="r.id"></p>
                        <span class="badge" :class="{'Completed': 'badge-positive', 'Pending': 'badge-amber', 'Rejected': 'badge-danger', 'Draft': 'badge-neutral'}[r.status]"
                              x-text="statusLabel(r.status)"></span>
                    </div>
                    <p class="text-[12.5px]/[18px] text-ink-secondary mt-1" x-text="`${r.saleId} · ${$rpfmt.dateTime(r.requestedAt)}`"></p>
                    <div class="flex items-center justify-between mt-1.5 text-[13px]">
                        <span class="text-ink-secondary" x-text="`${r.unitQuantity} unit · ${r.customerName}`"></span>
                        <span class="font-semibold tabular-nums" x-text="$rp(r.refundTotal)"></span>
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm w-full mt-3" @click="openDetail(r.id)">Lihat Detail</button>
                </div>
            </template>
        </div>

        <x-pagination row-label="retur"/>
    </section>

    {{-- ================= WIZARD RETUR BARU ================= --}}
    <div x-show="wizardOpen" x-cloak class="fixed inset-0 z-[80]" role="dialog" aria-modal="true" aria-label="Retur baru">
        <div class="absolute inset-0 bg-[#1c2b2b]/40" @click="closeWizard()" x-transition:enter="fade-enter" x-transition:leave="fade-leave"></div>
        <div class="absolute inset-y-0 right-0 w-[600px] max-w-[94vw] bg-white flex flex-col"
             x-ref="wizardPanel" @keydown.tab="PM.trap($event, $refs.wizardPanel)" @keydown.escape="closeWizard()"
             x-transition:enter="slide-enter" x-transition:leave="slide-leave">
            <div class="px-6 py-5 border-b border-divider flex items-center justify-between">
                <div>
                    <h2 class="text-[18px]/[24px] font-semibold">Retur Baru</h2>
                    <p class="text-[12.5px]/[17px] text-ink-secondary mt-0.5">Jendela retur demo: 14 hari sejak transaksi.</p>
                </div>
                <button type="button" class="btn btn-secondary btn-icon btn-sm" @click="closeWizard()" aria-label="Tutup wizard"><x-icon name="x" class="size-4"/></button>
            </div>

            {{-- indikator langkah --}}
            <div class="px-6 py-4 border-b border-divider flex items-center gap-2" aria-label="Langkah wizard">
                <template x-for="(s, i) in ['Cari Transaksi', 'Pilih Item', 'Tinjau']" :key="s">
                    <div class="flex items-center gap-2">
                        <span class="grid size-7 place-items-center rounded-full text-[12px] font-semibold"
                              :class="step > i ? 'bg-primary text-white' : step === i + 1 ? 'bg-primary-soft text-focus border border-primary' : 'bg-surface-muted text-ink-muted'"
                              x-text="i + 1"></span>
                        <span class="text-[12.5px] font-medium" :class="step === i + 1 ? 'text-ink' : 'text-ink-muted'" x-text="s"></span>
                        <span x-show="i < 2" class="w-8 h-px bg-divider mx-1"></span>
                    </div>
                </template>
            </div>

            <div class="flex-1 overflow-y-auto nice-scroll px-6 py-5">
                {{-- LANGKAH 1 --}}
                <div x-show="step === 1">
                    <label class="field-label" for="rz-sale">ID Transaksi atau Nomor Struk <span class="text-danger">*</span></label>
                    <div class="flex gap-2.5">
                        <input id="rz-sale" type="text" class="input flex-1 font-mono" placeholder="mis. TX-1001" x-model="saleQuery"
                               @keydown.enter="findSale()">
                        <button type="button" class="btn btn-primary" @click="findSale()">Cari Transaksi</button>
                    </div>
                    <p class="field-error" x-show="findError" x-text="findError"></p>

                    <template x-if="foundSale">
                        <div class="mt-5 rounded-xl border border-primary/40 bg-primary-soft/40 px-5 py-4">
                            <div class="flex items-center justify-between gap-2 flex-wrap">
                                <p class="text-[15px]/[21px] font-semibold" x-text="foundSale.id"></p>
                                <span class="badge badge-positive">Selesai</span>
                            </div>
                            <dl class="grid grid-cols-2 gap-x-5 gap-y-2.5 mt-3 text-[13px]/[18px]">
                                <div><dt class="text-ink-muted">Pelanggan</dt><dd class="font-medium" x-text="foundSale.customerNameSnapshot"></dd></div>
                                <div><dt class="text-ink-muted">Tanggal</dt><dd x-text="$rpfmt.dateTime(foundSale.createdAt)"></dd></div>
                                <div><dt class="text-ink-muted">Kasir</dt><dd x-text="foundSale.cashierNameSnapshot"></dd></div>
                                <div><dt class="text-ink-muted">Metode</dt>
                                    <dd x-text="foundSale.paymentMethod === 'Cash' ? 'Tunai' : foundSale.paymentMethod === 'Card' ? 'Kartu' : 'Dompet Digital'"></dd>
                                </div>
                                <div><dt class="text-ink-muted">Total Dibayar</dt><dd class="font-semibold tabular-nums" x-text="$rp(foundSale.total)"></dd></div>
                                <div><dt class="text-ink-muted">Sudah Direfund</dt><dd class="tabular-nums" x-text="$rp(alreadyRefunded)"></dd></div>
                            </dl>
                        </div>
                    </template>
                </div>

                {{-- LANGKAH 2 --}}
                <div x-show="step === 2" x-cloak>
                    <p class="field-error mb-3" x-show="wizardErrors.step2" x-text="wizardErrors.step2"></p>
                    <div class="rounded-xl border border-line divide-y divide-divider overflow-hidden">
                        <template x-for="p in picks" :key="p.productId">
                            <div class="px-4 py-3.5" :class="!p.available && 'opacity-60'">
                                <div class="flex items-center gap-3.5">
                                    <input type="checkbox" class="size-4 accent-[#49ADAB]" :checked="p.checked" @change="togglePick(p)"
                                           :disabled="p.available === 0" :aria-label="`Pilih ${p.name} untuk retur`">
                                    <img :src="`/images/products/${p.productId}.jpg`" onerror="PM.imgFallback(this)" :alt="`Foto ${p.name}`" class="thumb size-11" loading="lazy">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[13.5px]/[18px] font-medium" x-text="p.name"></p>
                                        <p class="text-[12px]/[16px] text-ink-secondary tabular-nums">
                                            <span x-text="$rp(p.unitPrice)"></span> · Terjual <span x-text="p.sold"></span> · Sudah diretur <span x-text="p.previouslyReturned"></span> · Tersedia <span x-text="p.available"></span>
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2" x-show="p.checked">
                                        <label class="sr-only" :for="`qty-${p.productId}`">Jumlah retur</label>
                                        <input :id="`qty-${p.productId}`" type="number" min="1" :max="p.available" class="input !w-20 !min-h-10 text-center tabular-nums" x-model.number="p.quantity">
                                    </div>
                                </div>
                                <div class="flex items-center gap-4 mt-2.5 pl-[54px] flex-wrap" x-show="p.checked">
                                    <label class="flex items-center gap-1.5 text-[12.5px] text-ink-secondary">
                                        Kondisi
                                        <select class="select !w-auto !min-h-9 !py-1 !text-[12.5px]" x-model="p.condition" :aria-label="`Kondisi ${p.name}`">
                                            <option value="Unopened">Belum dibuka</option>
                                            <option value="Opened">Sudah dibuka</option>
                                            <option value="Damaged">Rusak</option>
                                        </select>
                                    </label>
                                    <label class="flex items-center gap-1.5 text-[12.5px] text-ink-secondary">
                                        <input type="checkbox" class="size-4 accent-[#49ADAB]" x-model="p.restock" :disabled="p.condition !== 'Unopened'">
                                        Kembalikan ke stok
                                    </label>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- LANGKAH 3 --}}
                <div x-show="step === 3" x-cloak class="space-y-4">
                    <div>
                        <label class="field-label" for="rz-reason">Alasan Retur <span class="text-danger">*</span></label>
                        <select id="rz-reason" class="select" x-model="reason">
                            <option value="" disabled>Pilih alasan…</option>
                            <template x-for="r in reasons" :key="r"><option :value="r" x-text="r"></option></template>
                        </select>
                        <p class="field-error" x-show="wizardErrors.reason" x-text="wizardErrors.reason"></p>
                    </div>
                    <div x-show="reason === 'Lainnya'">
                        <label class="field-label" for="rz-other">Jelaskan Alasan Lainnya <span class="text-danger">*</span></label>
                        <textarea id="rz-other" class="textarea !min-h-[60px]" x-model="otherNotes" maxlength="200"></textarea>
                    </div>
                    <p class="field-error" x-show="wizardErrors.condition" x-text="wizardErrors.condition"></p>
                    <div>
                        <label class="field-label" for="rz-notes">Catatan</label>
                        <textarea id="rz-notes" class="textarea !min-h-[60px]" x-model="notes" maxlength="500"></textarea>
                    </div>

                    <div class="rounded-xl border border-line overflow-hidden">
                        <table class="table">
                            <thead><tr><th>Item</th><th class="num">Jml</th><th class="num">Neto</th><th class="num">Pajak</th><th class="num">Total</th></tr></thead>
                            <tbody>
                                <template x-for="p in picks.filter(x => x.checked && x.quantity >= 1)" :key="p.productId">
                                    <tr>
                                        <td class="font-medium" x-text="p.name"></td>
                                        <td class="num text-ink-secondary" x-text="p.quantity"></td>
                                        <td class="num text-ink-secondary tabular-nums" x-text="$rp(p.item.unitAllocations.slice(0, p.quantity).reduce((a,u)=>a+u.netCents,0))"></td>
                                        <td class="num text-ink-secondary tabular-nums" x-text="$rp(p.item.unitAllocations.slice(0, p.quantity).reduce((a,u)=>a+u.taxCents,0))"></td>
                                        <td class="num font-medium tabular-nums" x-text="$rp(p.item.unitAllocations.slice(0, p.quantity).reduce((a,u)=>a+u.netCents+u.taxCents,0))"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div class="rounded-xl bg-surface-muted px-5 py-4 space-y-1.5">
                        <div class="flex justify-between text-[13.5px]/[19px]"><span class="text-ink-secondary">Metode refund (asli)</span><span x-text="foundSale ? (foundSale.paymentMethod === 'Cash' ? 'Tunai' : foundSale.paymentMethod === 'Card' ? 'Kartu' : 'Dompet Digital') : '—'"></span></div>
                        <div class="flex justify-between text-[13.5px]/[19px]"><span class="text-ink-secondary">Dibayar Awal</span><span class="tabular-nums" x-text="$rp(foundSale ? foundSale.total : 0)"></span></div>
                        <div class="flex justify-between text-[13.5px]/[19px]"><span class="text-ink-secondary">Sudah Direfund</span><span class="tabular-nums" x-text="$rp(alreadyRefunded)"></span></div>
                        <div class="flex justify-between text-[13.5px]/[19px]"><span class="text-ink-secondary">Refund Neto</span><span class="tabular-nums" x-text="$rp(pickRefund.net)"></span></div>
                        <div class="flex justify-between text-[13.5px]/[19px]"><span class="text-ink-secondary">Pajak Direfund</span><span class="tabular-nums" x-text="$rp(pickRefund.tax)"></span></div>
                        <div class="flex justify-between items-end pt-2 border-t border-line">
                            <span class="font-semibold">Total Refund</span>
                            <span class="text-[22px]/[28px] font-semibold tabular-nums" x-text="$rp(pickRefund.total)"></span>
                        </div>
                        <div class="flex justify-between text-[12.5px]/[18px]"><span class="text-ink-muted">Sisa bisa direfund</span><span class="tabular-nums text-ink-muted" x-text="$rp((foundSale ? foundSale.total : 0) - alreadyRefunded - pickRefund.total)"></span></div>
                    </div>
                    <p class="text-[12.5px]/[18px] text-ink-muted flex items-start gap-1.5">
                        <x-icon name="info" class="size-4 shrink-0 mt-0.5"/>
                        Refund demo — tidak ada dana yang ditransfer.
                    </p>
                </div>
            </div>

            <div class="px-6 py-4 border-t border-divider flex items-center gap-2.5">
                <button type="button" class="btn btn-secondary" @click="closeWizard()">Batal</button>
                <button type="button" class="btn btn-secondary" x-show="step > 1" @click="prevStep()">Kembali</button>
                <div class="flex-1"></div>
                <button type="button" class="btn btn-secondary" x-show="step === 3" @click="saveWizard(false)" :disabled="saving || !foundSale">Simpan Draf</button>
                <button type="button" class="btn btn-primary" x-show="step < 3" @click="nextStep()" :disabled="step === 1 && !foundSale">Lanjut</button>
                <button type="button" class="btn btn-primary" x-show="step === 3" @click="saveWizard(true)" :disabled="saving || !foundSale"
                        x-text="saving ? 'Menyimpan…' : 'Ajukan untuk Peninjauan'"></button>
            </div>
        </div>
    </div>

    {{-- ================= DRAWER DETAIL RETUR ================= --}}
    <div x-show="detailId" x-cloak class="fixed inset-0 z-[75]" role="dialog" aria-modal="true" aria-label="Detail retur">
        <div class="absolute inset-0 bg-[#1c2b2b]/40" @click="closeDetail()" x-transition:enter="fade-enter" x-transition:leave="fade-leave"></div>
        <div class="absolute inset-y-0 right-0 w-[600px] max-w-[92vw] bg-white flex flex-col"
             x-ref="detailPanel" @keydown.tab="PM.trap($event, $refs.detailPanel)" @keydown.escape="closeDetail()"
             x-transition:enter="slide-enter" x-transition:leave="slide-leave">
            <template x-if="detailReturn">
                <div class="flex flex-col min-h-0 flex-1">
                    <div class="px-6 py-5 border-b border-divider flex items-start justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2.5 flex-wrap">
                                <h2 class="text-[19px]/[25px] font-semibold" x-text="detailReturn.id"></h2>
                                <span class="badge" :class="{'Completed': 'badge-positive', 'Pending': 'badge-amber', 'Rejected': 'badge-danger', 'Draft': 'badge-neutral'}[detailReturn.status]"
                                      x-text="statusLabel(detailReturn.status)"></span>
                            </div>
                            <p class="text-[13px]/[19px] text-ink-secondary mt-1">
                                Transaksi <a class="text-primary hover:text-primary-hover font-medium" :href="`/sales?sale=${detailReturn.saleId}`" x-text="detailReturn.saleId"></a>
                                · <span x-text="detailReturn.customerName"></span>
                            </p>
                        </div>
                        <button type="button" class="btn btn-secondary btn-icon btn-sm" @click="closeDetail()" aria-label="Tutup detail"><x-icon name="x" class="size-4"/></button>
                    </div>

                    <div class="flex-1 overflow-y-auto nice-scroll px-6 py-5 space-y-5">
                        <dl class="grid grid-cols-2 gap-x-4 gap-y-3.5 text-[13.5px]/[19px]">
                            <div><dt class="text-ink-muted">Tanggal Transaksi Asal</dt><dd class="mt-0.5" x-text="$rpfmt.dateTime(detailReturn.originalDate)"></dd></div>
                            <div><dt class="text-ink-muted">Metode Asal</dt>
                                <dd class="mt-0.5" x-text="detailReturn.originalMethod === 'Cash' ? 'Tunai' : detailReturn.originalMethod === 'Card' ? 'Kartu' : 'Dompet Digital'"></dd>
                            </div>
                            <div><dt class="text-ink-muted">Diajukan</dt><dd class="mt-0.5" x-text="$rpfmt.dateTime(detailReturn.requestedAt)"></dd></div>
                            <div x-show="detailReturn.reviewedAt"><dt class="text-ink-muted">Ditinjau</dt><dd class="mt-0.5" x-text="$rpfmt.dateTime(detailReturn.reviewedAt)"></dd></div>
                            <div x-show="detailReturn.completedAt"><dt class="text-ink-muted">Selesai</dt><dd class="mt-0.5" x-text="$rpfmt.dateTime(detailReturn.completedAt)"></dd></div>
                            <div x-show="detailReturn.receiptNo"><dt class="text-ink-muted">No. Struk Refund</dt><dd class="mt-0.5 font-mono" x-text="detailReturn.receiptNo"></dd></div>
                        </dl>

                        <div x-show="detailReturn.rejectedReason" x-cloak class="rounded-xl bg-danger-soft border border-danger/25 px-4 py-3.5">
                            <p class="text-[13px]/[18px] font-medium text-danger">Alasan Penolakan</p>
                            <p class="text-[12.5px]/[18px] text-danger/85 mt-1" x-text="detailReturn.rejectedReason"></p>
                        </div>

                        <div>
                            <table class="table">
                                <thead><tr><th>Produk</th><th class="num">Jml</th><th class="num">Neto</th><th class="num">Pajak</th><th class="num">Total</th><th>Kondisi</th><th>Stok</th></tr></thead>
                                <tbody>
                                    <template x-for="it in detailReturn.items" :key="it.productId">
                                        <tr>
                                            <td class="font-medium" x-text="it.name"></td>
                                            <td class="num text-ink-secondary" x-text="it.quantity"></td>
                                            <td class="num text-ink-secondary tabular-nums" x-text="$rp(it.refundNet)"></td>
                                            <td class="num text-ink-secondary tabular-nums" x-text="$rp(it.refundTax)"></td>
                                            <td class="num font-medium tabular-nums" x-text="$rp(it.refundTotal)"></td>
                                            <td class="text-ink-secondary" x-text="it.condition === 'Unopened' ? 'Belum dibuka' : it.condition === 'Opened' ? 'Sudah dibuka' : 'Rusak'"></td>
                                            <td>
                                                <span class="badge" :class="it.restock ? 'badge-positive' : 'badge-neutral'" x-text="it.restock ? 'Kembali' : 'Tidak'"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <div>
                            <p class="text-[12.5px]/[17px] text-ink-muted mb-1">Alasan</p>
                            <p class="text-[13.5px]/[20px]" x-text="detailReturn.reason"></p>
                        </div>
                        <div x-show="detailReturn.notes">
                            <p class="text-[12.5px]/[17px] text-ink-muted mb-1">Catatan</p>
                            <p class="text-[13.5px]/[20px]" x-text="detailReturn.notes"></p>
                        </div>

                        <div class="rounded-xl bg-surface-muted px-5 py-4 space-y-1.5">
                            <div class="flex justify-between text-[13.5px]/[19px]"><span class="text-ink-secondary">Refund Neto</span><span class="tabular-nums" x-text="$rp(detailReturn.refundNet)"></span></div>
                            <div class="flex justify-between text-[13.5px]/[19px]"><span class="text-ink-muted">Pajak Direfund</span><span class="tabular-nums" x-text="$rp(detailReturn.refundTax)"></span></div>
                            <div class="flex justify-between items-end pt-2 border-t border-line">
                                <span class="font-semibold">Total Refund</span>
                                <span class="text-[22px]/[28px] font-semibold tabular-nums" x-text="$rp(detailReturn.refundTotal)"></span>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-divider flex items-center gap-2.5 flex-wrap">
                        <button type="button" class="btn btn-secondary" @click="closeDetail()">Tutup</button>
                        <template x-if="detailReturn.status === 'Draft'">
                            <div class="flex items-center gap-2.5 flex-wrap">
                                <button type="button" class="btn btn-primary" @click="submitDraft(detailReturn.id)">Ajukan untuk Peninjauan</button>
                                <button type="button" class="btn btn-danger-soft" @click="openDelete(detailReturn.id)">Hapus Draf</button>
                            </div>
                        </template>
                        <template x-if="detailReturn.status === 'Pending'">
                            <div class="flex items-center gap-2.5 flex-wrap">
                                <button type="button" class="btn btn-primary" @click="openApprove(detailReturn.id)">Setujui Refund</button>
                                <button type="button" class="btn btn-danger-soft" @click="openReject(detailReturn.id)">Tolak Permintaan</button>
                            </div>
                        </template>
                        <template x-if="detailReturn.status === 'Completed'">
                            <div class="flex items-center gap-2.5 flex-wrap">
                                <button type="button" class="btn btn-primary" @click="printRefundReceipt(detailReturn)">
                                    <x-icon name="printer" class="size-4"/> Unduh Struk Refund
                                </button>
                            </div>
                        </template>
                        <template x-if="detailReturn.status === 'Rejected'">
                            <button type="button" class="btn btn-primary" @click="duplicate(detailReturn.id)">Gandakan sebagai Draf</button>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- ================= DIALOG SETUJUI REFUND ================= --}}
    <div x-show="approveOpen" x-cloak class="fixed inset-0 z-[85] grid place-items-center p-4" role="dialog" aria-modal="true" aria-label="Setujui refund">
        <div class="absolute inset-0 bg-[#1c2b2b]/45" @click="approveOpen = false" x-transition:enter="fade-enter" x-transition:leave="fade-leave"></div>
        <div class="relative w-full max-w-[440px] rounded-2xl bg-white shadow-[0_24px_80px_rgba(30,41,59,0.22)] p-6" x-transition:enter="pop-enter" x-transition:leave="pop-leave">
            <template x-if="approveTarget">
                <div>
                    <h2 class="text-[18px]/[24px] font-semibold">Setujui Refund?</h2>
                    <div class="mt-3 rounded-xl bg-surface-muted px-5 py-4 flex items-end justify-between">
                        <span class="text-[13.5px]/[19px] text-ink-secondary">Total refund</span>
                        <span class="text-[28px]/[34px] font-semibold tabular-nums" x-text="$rp(approveTarget.refundTotal)"></span>
                    </div>
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-3 mt-4 text-[13.5px]/[19px]">
                        <div><dt class="text-ink-muted">Transaksi asal</dt><dd class="font-medium" x-text="approveTarget.saleId"></dd></div>
                        <div><dt class="text-ink-muted">Metode asal</dt>
                            <dd x-text="approveTarget.originalMethod === 'Cash' ? 'Tunai' : approveTarget.originalMethod === 'Card' ? 'Kartu' : 'Dompet Digital'"></dd>
                        </div>
                        <div class="col-span-2"><dt class="text-ink-muted">Item dikembalikan ke stok</dt>
                            <dd class="font-medium" x-text="approveTarget.items.filter(i => i.restock && i.condition === 'Unopened').map(i => `${i.name} ×${i.quantity}`).join(', ') || 'Tidak ada'"></dd>
                        </div>
                    </dl>
                    <p class="text-[12.5px]/[18px] text-ink-muted mt-3">Refund demo — tidak ada dana yang ditransfer.</p>
                    <p class="text-[12.5px]/[18px] text-danger mt-2" x-show="approveState === 'error'" x-cloak>Simulasi gagal. Status tetap Menunggu — coba lagi.</p>
                    <div class="grid grid-cols-2 gap-3 mt-5">
                        <button type="button" class="btn btn-secondary" @click="approveOpen = false" :disabled="approveState === 'processing'">Kembali</button>
                        <button type="button" class="btn btn-primary" @click="confirmApprove()" :disabled="approveState === 'processing'"
                                x-text="approveState === 'processing' ? 'Memproses…' : 'Konfirmasi Refund'"></button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- ================= DIALOG TOLAK ================= --}}
    <div x-show="rejectOpen" x-cloak class="fixed inset-0 z-[85] grid place-items-center p-4" role="dialog" aria-modal="true" aria-label="Tolak permintaan retur">
        <div class="absolute inset-0 bg-[#1c2b2b]/45" @click="rejectOpen = false" x-transition:enter="fade-enter" x-transition:leave="fade-leave"></div>
        <div class="relative w-full max-w-[440px] rounded-2xl bg-white shadow-[0_24px_80px_rgba(30,41,59,0.22)] p-6" x-transition:enter="pop-enter" x-transition:leave="pop-leave">
            <template x-if="rejectTarget">
                <div>
                    <h2 class="text-[18px]/[24px] font-semibold">Tolak Permintaan <span x-text="rejectTarget.id"></span>?</h2>
                    <div class="mt-4">
                        <label class="field-label" for="reject-reason">Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea id="reject-reason" class="textarea !min-h-[72px]" x-model="rejectReason" maxlength="200"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-3 mt-5">
                        <button type="button" class="btn btn-secondary" @click="rejectOpen = false">Pertahankan</button>
                        <button type="button" class="btn btn-danger" @click="confirmReject()"
                                :disabled="rejectReason.trim().length < 5 || rejectReason.trim().length > 200">Tolak Permintaan</button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- ================= DIALOG HAPUS DRAF ================= --}}
    <div x-show="deleteOpen" x-cloak class="fixed inset-0 z-[85] grid place-items-center p-4" role="dialog" aria-modal="true" aria-label="Hapus draf retur">
        <div class="absolute inset-0 bg-[#1c2b2b]/45" @click="deleteOpen = false" x-transition:enter="fade-enter" x-transition:leave="fade-leave"></div>
        <div class="relative w-full max-w-[420px] rounded-2xl bg-white shadow-[0_24px_80px_rgba(30,41,59,0.22)] p-6" x-transition:enter="pop-enter" x-transition:leave="pop-leave">
            <h2 class="text-[18px]/[24px] font-semibold">Hapus draf <span x-text="deleteTarget?.id"></span>?</h2>
            <p class="text-[13.5px]/[20px] text-ink-secondary mt-2">Draf retur ini akan dihapus permanen dari demo lokal.</p>
            <div class="grid grid-cols-2 gap-3 mt-5">
                <button type="button" class="btn btn-secondary" @click="deleteOpen = false">Batal</button>
                <button type="button" class="btn btn-danger" @click="confirmDelete()">Hapus Draf</button>
            </div>
        </div>
    </div>
</div>
@endsection
