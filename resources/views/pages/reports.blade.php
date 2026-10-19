@extends('layouts.app')

@section('title', 'Laporan Pendapatan Kafe')
@section('subtitle', 'Rekapitulasi omzet harian, mingguan, bulanan, dan tahunan PosyMart Coffee Shop.')

@section('content')
<div x-data="reportsPage" x-cloak>

    {{-- ================= HEADER HALAMAN ================= --}}
    <x-page-header title="Laporan Pendapatan Kafe" subtitle="Rekapitulasi omzet harian, mingguan, bulanan, dan tahunan PosyMart Coffee Shop.">
        <button type="button" class="btn btn-secondary" @click="openReceiptModal()">
            <x-icon name="clipboard-list" class="size-4.5 text-primary"/> Struk Closing Kasir
        </button>

        <div class="relative">
            <button type="button" class="btn btn-secondary" @click="exportOpen = !exportOpen" :aria-expanded="exportOpen">
                <x-icon name="download" class="size-4.5"/> Ekspor Data
            </button>
            <div x-show="exportOpen" x-cloak @click.outside="exportOpen = false" @keydown.escape="exportOpen = false"
                 x-transition:enter="pop-enter" x-transition:leave="pop-leave"
                 class="absolute right-0 top-[calc(100%+8px)] w-64 rounded-xl bg-white border border-line shadow-[0_12px_40px_rgba(30,41,59,0.14)] p-1.5 z-40" role="menu">
                <button type="button" class="w-full text-left px-3 py-2 rounded-lg text-[13px] hover:bg-surface-muted flex items-center gap-2" role="menuitem" @click="exportSummaryCsv()">
                    <x-icon name="bar-chart" class="size-4 text-ink-muted"/> Ringkasan Periode Aktif
                </button>
                <button type="button" class="w-full text-left px-3 py-2 rounded-lg text-[13px] hover:bg-surface-muted flex items-center gap-2" role="menuitem" @click="exportTransactionsCsv()">
                    <x-icon name="clipboard-list" class="size-4 text-ink-muted"/> Rincian Semua Transaksi
                </button>
                <button type="button" class="w-full text-left px-3 py-2 rounded-lg text-[13px] hover:bg-surface-muted flex items-center gap-2" role="menuitem" @click="exportMenuCsv()">
                    <x-icon name="package" class="size-4 text-ink-muted"/> Rekap Menu Terlaris
                </button>
            </div>
        </div>

        <button type="button" class="btn btn-primary" @click="printReport()">
            <x-icon name="printer" class="size-4.5"/> Cetak Laporan
        </button>
    </x-page-header>

    {{-- ================= PILIHAN PERIODE REKAP (HARIAN / MINGGUAN / BULANAN / TAHUNAN) ================= --}}
    <section class="panel mt-6 p-5" aria-labelledby="period-filter-heading">
        <h2 id="period-filter-heading" class="sr-only">Filter Periode Laporan Kafe</h2>
        <div class="flex flex-wrap items-center justify-between gap-4">
            {{-- Segmented Tab Buttons --}}
            <div class="inline-flex rounded-xl bg-canvas border border-line p-1 max-[640px]:w-full max-[640px]:grid max-[640px]:grid-cols-3" role="tablist" aria-label="Pilihan periode rekapitulasi">
                <button type="button" role="tab"
                        class="px-4 py-2 rounded-lg text-[13.5px] font-medium transition-all flex items-center justify-center gap-2"
                        :class="period === 'today' ? 'bg-white text-ink shadow-sm border border-line/60 font-semibold' : 'text-ink-secondary hover:text-ink'"
                        @click="setPeriod('today')">
                    <span>☀️</span>
                    <span>Harian</span>
                </button>
                <button type="button" role="tab"
                        class="px-4 py-2 rounded-lg text-[13.5px] font-medium transition-all flex items-center justify-center gap-2"
                        :class="period === 'week' ? 'bg-white text-ink shadow-sm border border-line/60 font-semibold' : 'text-ink-secondary hover:text-ink'"
                        @click="setPeriod('week')">
                    <span>📅</span>
                    <span>Mingguan</span>
                </button>
                <button type="button" role="tab"
                        class="px-4 py-2 rounded-lg text-[13.5px] font-medium transition-all flex items-center justify-center gap-2"
                        :class="period === 'month' ? 'bg-white text-ink shadow-sm border border-line/60 font-semibold' : 'text-ink-secondary hover:text-ink'"
                        @click="setPeriod('month')">
                    <span>📆</span>
                    <span>Bulanan</span>
                </button>
                <button type="button" role="tab"
                        class="px-4 py-2 rounded-lg text-[13.5px] font-medium transition-all flex items-center justify-center gap-2"
                        :class="period === 'year' ? 'bg-white text-ink shadow-sm border border-line/60 font-semibold' : 'text-ink-secondary hover:text-ink'"
                        @click="setPeriod('year')">
                    <span>🏛️</span>
                    <span>Tahunan</span>
                </button>
                <button type="button" role="tab"
                        class="px-4 py-2 rounded-lg text-[13.5px] font-medium transition-all flex items-center justify-center gap-2"
                        :class="period === 'custom' ? 'bg-white text-ink shadow-sm border border-line/60 font-semibold' : 'text-ink-secondary hover:text-ink'"
                        @click="setPeriod('custom')">
                    <span>🗓️</span>
                    <span>Kustom</span>
                </button>
            </div>

            {{-- Controls Sesuai Periode --}}
            <div class="flex flex-wrap items-center gap-3">
                {{-- Mode Harian: Pilih Tanggal --}}
                <div x-show="period === 'today'" class="flex items-center gap-2">
                    <label class="text-[12.5px] text-ink-muted font-medium" for="rp-date-pick">Pilih Hari:</label>
                    <input id="rp-date-pick" type="date" class="input !min-h-9 !py-1 !text-[13px] !w-[155px]" x-model="selectedDate">
                </div>

                {{-- Mode Bulanan: Pilih Bulan --}}
                <div x-show="period === 'month'" class="flex items-center gap-2" x-cloak>
                    <label class="text-[12.5px] text-ink-muted font-medium" for="rp-month-pick">Pilih Bulan:</label>
                    <input id="rp-month-pick" type="month" class="input !min-h-9 !py-1 !text-[13px] !w-[165px]" x-model="selectedMonth">
                </div>

                {{-- Mode Tahunan: Pilih Tahun --}}
                <div x-show="period === 'year'" class="flex items-center gap-2" x-cloak>
                    <label class="text-[12.5px] text-ink-muted font-medium" for="rp-year-pick">Pilih Tahun:</label>
                    <select id="rp-year-pick" class="select !min-h-9 !py-1 !text-[13px] !w-[110px]" x-model="selectedYear">
                        <option value="2026">2026</option>
                        <option value="2025">2025</option>
                        <option value="2024">2024</option>
                    </select>
                </div>

                {{-- Mode Kustom: Dari & Sampai --}}
                <div x-show="period === 'custom'" class="flex flex-wrap items-center gap-2" x-cloak>
                    <input type="date" class="input !min-h-9 !py-1 !text-[13px] !w-[145px]" x-model="customStart" title="Dari tanggal">
                    <span class="text-[12px] text-ink-muted">s/d</span>
                    <input type="date" class="input !min-h-9 !py-1 !text-[13px] !w-[145px]" x-model="customEnd" title="Sampai tanggal">
                    <button type="button" class="btn btn-primary btn-sm" @click="applyCustomRange()" :disabled="applying"
                            x-text="applying ? 'Memuat…' : 'Terapkan'"></button>
                </div>

                <div class="h-6 w-px bg-divider max-[640px]:hidden"></div>

                <span class="badge badge-teal font-medium tracking-tight text-[12px] py-1 px-2.5 flex items-center gap-1.5">
                    <x-icon name="calendar" class="size-3.5"/>
                    <span x-text="rangeLabel"></span>
                </span>
            </div>
        </div>

        <p class="field-error mt-2" x-show="rangeError" x-text="rangeError"></p>
    </section>

    {{-- ================= KARTU STATISTIK METRIK FINANSIAL KAFE ================= --}}
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4 mt-6 max-[480px]:gap-3" role="group" aria-label="Statistik keuangan kafe">
        {{-- 1. Penjualan Bersih (Net Sales) --}}
        <button type="button" @click="openMetricModal('netSales')"
            class="card p-5 min-h-[125px] flex flex-col justify-between text-left border-l-4 border-l-primary bg-gradient-to-br from-white to-primary-soft/10 hover:shadow-md hover:border-primary transition-all group cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/30"
            title="Klik untuk melihat rincian Pendapatan Bersih">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <div class="flex items-center gap-1.5">
                        <p class="text-[12.5px]/[17px] font-medium text-ink-secondary group-hover:text-primary transition-colors">Pendapatan Bersih</p>
                        <span class="text-[10px] bg-primary/10 text-primary px-1.5 py-0.2 rounded font-semibold opacity-0 group-hover:opacity-100 transition-opacity">Detail</span>
                    </div>
                    <p class="mt-1 font-bold text-[22px]/[28px] text-primary tracking-tight tabular-nums" x-text="$rp(totals.netSales)"></p>
                </div>
                <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-primary-soft text-primary group-hover:scale-105 transition-transform">
                    <x-icon name="dollar" class="size-5"/>
                </span>
            </div>
            <div class="flex items-center justify-between mt-2 text-[11.5px]/[15px] text-ink-muted">
                <span>Omzet murni (di luar pajak)</span>
                <x-icon name="chevron-right" class="size-3.5 text-ink-muted/50 group-hover:text-primary group-hover:translate-x-0.5 transition-all"/>
            </div>
        </button>

        {{-- 2. Penjualan Kotor (Gross Sales) --}}
        <button type="button" @click="openMetricModal('gross')"
            class="card p-5 min-h-[125px] flex flex-col justify-between text-left hover:shadow-md hover:border-ink/40 transition-all group cursor-pointer focus:outline-none focus:ring-2 focus:ring-ink/20"
            title="Klik untuk melihat rincian Penjualan Kotor">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <div class="flex items-center gap-1.5">
                        <p class="text-[12.5px]/[17px] font-medium text-ink-secondary group-hover:text-ink transition-colors">Penjualan Kotor</p>
                        <span class="text-[10px] bg-ink/10 text-ink px-1.5 py-0.2 rounded font-semibold opacity-0 group-hover:opacity-100 transition-opacity">Detail</span>
                    </div>
                    <p class="mt-1 font-bold text-[22px]/[28px] text-ink tracking-tight tabular-nums" x-text="$rp(totals.grossCollected)"></p>
                </div>
                <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-surface-muted text-ink-secondary group-hover:scale-105 transition-transform">
                    <x-icon name="bar-chart" class="size-5"/>
                </span>
            </div>
            <div class="flex items-center justify-between mt-2 text-[11.5px]/[15px] text-ink-muted">
                <span x-text="`Pajak: +${$rp(totals.netTax)} | Diskon: -${$rp(totals.discounts)}`"></span>
                <x-icon name="chevron-right" class="size-3.5 text-ink-muted/50 group-hover:text-ink group-hover:translate-x-0.5 transition-all"/>
            </div>
        </button>

        {{-- 3. UANG HASIL DARI PAJAK (PB1) - KARTU BARU SESUAI REQUEST USER --}}
        <button type="button" @click="openMetricModal('tax')"
            class="card p-5 min-h-[125px] flex flex-col justify-between text-left border-l-4 border-l-amber-500 bg-gradient-to-br from-white to-amber-50/40 hover:shadow-md hover:border-amber-500 transition-all group cursor-pointer focus:outline-none focus:ring-2 focus:ring-amber-500/30"
            title="Klik untuk melihat rincian Uang Hasil Pajak">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <div class="flex items-center gap-1.5">
                        <p class="text-[12.5px]/[17px] font-medium text-amber-800 group-hover:text-amber-900 transition-colors">Uang Hasil Pajak</p>
                        <span class="text-[10px] bg-amber-100 text-amber-800 px-1.5 py-0.2 rounded font-semibold opacity-0 group-hover:opacity-100 transition-opacity">Detail</span>
                    </div>
                    <p class="mt-1 font-bold text-[22px]/[28px] text-amber-700 tracking-tight tabular-nums" x-text="$rp(totals.netTax)"></p>
                </div>
                <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-amber-100 text-amber-700 group-hover:scale-105 transition-transform">
                    <x-icon name="percent" class="size-5"/>
                </span>
            </div>
            <div class="flex items-center justify-between mt-2 text-[11.5px]/[15px] text-ink-muted">
                <span>Pajak Restoran PB1</span>
                <x-icon name="chevron-right" class="size-3.5 text-ink-muted/50 group-hover:text-amber-700 group-hover:translate-x-0.5 transition-all"/>
            </div>
        </button>

        {{-- 4. Total Transaksi (Bills) --}}
        <button type="button" @click="openMetricModal('orders')"
            class="card p-5 min-h-[125px] flex flex-col justify-between text-left hover:shadow-md hover:border-emerald-500 transition-all group cursor-pointer focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
            title="Klik untuk melihat rincian Transaksi">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <div class="flex items-center gap-1.5">
                        <p class="text-[12.5px]/[17px] font-medium text-ink-secondary group-hover:text-emerald-700 transition-colors">Total Transaksi</p>
                        <span class="text-[10px] bg-emerald-100 text-emerald-800 px-1.5 py-0.2 rounded font-semibold opacity-0 group-hover:opacity-100 transition-opacity">Detail</span>
                    </div>
                    <p class="mt-1 font-bold text-[24px]/[30px] text-ink tracking-tight tabular-nums" x-text="totals.orders"></p>
                </div>
                <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-emerald-50 text-emerald-600 group-hover:scale-105 transition-transform">
                    <x-icon name="check-circle" class="size-5"/>
                </span>
            </div>
            <div class="flex items-center justify-between mt-2 text-[11.5px]/[15px] text-ink-muted">
                <span>Struk pesanan selesai</span>
                <x-icon name="chevron-right" class="size-3.5 text-ink-muted/50 group-hover:text-emerald-600 group-hover:translate-x-0.5 transition-all"/>
            </div>
        </button>

        {{-- 5. Porsi/Menu Terjual --}}
        <button type="button" @click="openMetricModal('itemsSold')"
            class="card p-5 min-h-[125px] flex flex-col justify-between text-left hover:shadow-md hover:border-amber-500 transition-all group cursor-pointer focus:outline-none focus:ring-2 focus:ring-amber-500/30"
            title="Klik untuk melihat rincian Menu Terjual">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <div class="flex items-center gap-1.5">
                        <p class="text-[12.5px]/[17px] font-medium text-ink-secondary group-hover:text-amber-800 transition-colors">Menu Terjual</p>
                        <span class="text-[10px] bg-amber-100 text-amber-800 px-1.5 py-0.2 rounded font-semibold opacity-0 group-hover:opacity-100 transition-opacity">Detail</span>
                    </div>
                    <p class="mt-1 font-bold text-[24px]/[30px] text-ink tracking-tight tabular-nums" x-text="`${totals.itemsSold} porsi`"></p>
                </div>
                <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-amber-soft text-amber group-hover:scale-105 transition-transform">
                    <x-icon name="package" class="size-5"/>
                </span>
            </div>
            <div class="flex items-center justify-between mt-2 text-[11.5px]/[15px] text-ink-muted">
                <span>Cup minuman &amp; makanan</span>
                <x-icon name="chevron-right" class="size-3.5 text-ink-muted/50 group-hover:text-amber group-hover:translate-x-0.5 transition-all"/>
            </div>
        </button>

        {{-- 6. Rata-rata Nilai Order (AOV) --}}
        <button type="button" @click="openMetricModal('aov')"
            class="card p-5 min-h-[125px] flex flex-col justify-between text-left hover:shadow-md hover:border-blue-500 transition-all group cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500/30"
            title="Klik untuk melihat rincian Rata-rata Nilai Order">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <div class="flex items-center gap-1.5">
                        <p class="text-[12.5px]/[17px] font-medium text-ink-secondary group-hover:text-blue-700 transition-colors">Rata-rata Order (AOV)</p>
                        <span class="text-[10px] bg-blue-100 text-blue-800 px-1.5 py-0.2 rounded font-semibold opacity-0 group-hover:opacity-100 transition-opacity">Detail</span>
                    </div>
                    <p class="mt-1 font-bold text-[20px]/[26px] text-ink tracking-tight tabular-nums" x-text="$rp(totals.aov)"></p>
                </div>
                <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-blue-50 text-blue-600 group-hover:scale-105 transition-transform">
                    <x-icon name="trending-up" class="size-5"/>
                </span>
            </div>
            <div class="flex items-center justify-between mt-2 text-[11.5px]/[15px] text-ink-muted">
                <span>Rata-rata belanja per tamu</span>
                <x-icon name="chevron-right" class="size-3.5 text-ink-muted/50 group-hover:text-blue-600 group-hover:translate-x-0.5 transition-all"/>
            </div>
        </button>
    </div>

    {{-- ================= BREAKDOWN KHAS OPERASIONAL KAFE ================= --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
        {{-- Card 1: Tipe Pesanan (Dine In vs Take Away) --}}
        <div class="card p-5 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-[15px]/[21px] font-semibold text-ink flex items-center gap-2">
                        <x-icon name="clipboard-list" class="size-4.5 text-primary"/>
                        Tipe Pesanan Kafe
                    </h3>
                    <span class="text-[12px] text-ink-muted" x-text="`${totals.orders} Total Order`"></span>
                </div>
                <div class="space-y-4">
                    <div>
                        <div class="flex items-center justify-between text-[13px] mb-1.5">
                            <span class="font-medium text-ink flex items-center gap-1.5">
                                <span class="size-2.5 rounded-full bg-primary"></span> Dine In (Makan di Tempat)
                            </span>
                            <span class="font-semibold tabular-nums text-ink" x-text="$rp(totals.dineInSales)"></span>
                        </div>
                        <div class="w-full bg-divider rounded-full h-2 overflow-hidden">
                            <div class="bg-primary h-full rounded-full transition-all duration-300"
                                 :style="`width: ${totals.orders > 0 ? ((totals.dineInOrders / totals.orders) * 100).toFixed(0) : 0}%`"></div>
                        </div>
                        <div class="flex items-center justify-between text-[11.5px] text-ink-muted mt-1">
                            <span x-text="`${totals.dineInOrders} pesanan`"></span>
                            <span x-text="`${totals.orders > 0 ? ((totals.dineInOrders / totals.orders) * 100).toFixed(1) : 0}%`"></span>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between text-[13px] mb-1.5">
                            <span class="font-medium text-ink flex items-center gap-1.5">
                                <span class="size-2.5 rounded-full bg-amber"></span> Take Away (Bawa Pulang)
                            </span>
                            <span class="font-semibold tabular-nums text-ink" x-text="$rp(totals.takeawaySales)"></span>
                        </div>
                        <div class="w-full bg-divider rounded-full h-2 overflow-hidden">
                            <div class="bg-amber h-full rounded-full transition-all duration-300"
                                 :style="`width: ${totals.orders > 0 ? ((totals.takeawayOrders / totals.orders) * 100).toFixed(0) : 0}%`"></div>
                        </div>
                        <div class="flex items-center justify-between text-[11.5px] text-ink-muted mt-1">
                            <span x-text="`${totals.takeawayOrders} pesanan`"></span>
                            <span x-text="`${totals.orders > 0 ? ((totals.takeawayOrders / totals.orders) * 100).toFixed(1) : 0}%`"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-divider text-[12px] text-ink-muted flex items-center justify-between">
                <span>Rasio Dine-in / Takeaway</span>
                <span class="font-semibold text-ink" x-text="totals.orders > 0 ? `${((totals.dineInOrders / totals.orders) * 100).toFixed(0)}% : ${((totals.takeawayOrders / totals.orders) * 100).toFixed(0)}%` : '0% : 0%'"></span>
            </div>
        </div>

        {{-- Card 2: Rekonsiliasi Kasir & Metode Pembayaran --}}
        <div class="card p-5 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-[15px]/[21px] font-semibold text-ink flex items-center gap-2">
                        <x-icon name="credit-card" class="size-4.5 text-primary"/>
                        Rekonsiliasi Kasir &amp; Pembayaran
                    </h3>
                    <span class="badge badge-neutral text-[11px]">Closing Check</span>
                </div>
                <div class="space-y-3">
                    <div class="p-2.5 rounded-xl bg-canvas border border-line flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <span class="grid size-8 place-items-center rounded-lg bg-emerald-100 text-emerald-700 font-bold text-[12px]">Rp</span>
                            <div>
                                <p class="text-[12.5px] font-medium text-ink">Uang Tunai di Laci (Cash)</p>
                                <p class="text-[11px] text-ink-muted">Wajib ada di fisik kasir</p>
                            </div>
                        </div>
                        <p class="font-bold text-[14.5px] tabular-nums text-emerald-700" x-text="$rp(totals.cash)"></p>
                    </div>

                    <div class="p-2.5 rounded-xl bg-canvas border border-line flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <span class="grid size-8 place-items-center rounded-lg bg-amber-100 text-amber-700 font-bold text-[12px]">QR</span>
                            <div>
                                <p class="text-[12.5px] font-medium text-ink">QRIS &amp; Dompet Digital</p>
                                <p class="text-[11px] text-ink-muted">GoPay / OVO / Shopee / DANA</p>
                            </div>
                        </div>
                        <p class="font-bold text-[14.5px] tabular-nums text-amber-800" x-text="$rp(totals.wallet)"></p>
                    </div>

                    <div class="p-2.5 rounded-xl bg-canvas border border-line flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <span class="grid size-8 place-items-center rounded-lg bg-blue-100 text-blue-700 font-bold text-[12px]">EDC</span>
                            <div>
                                <p class="text-[12.5px] font-medium text-ink">Kartu Debit &amp; Transfer</p>
                                <p class="text-[11px] text-ink-muted">BCA / Mandiri / EDC</p>
                            </div>
                        </div>
                        <p class="font-bold text-[14.5px] tabular-nums text-blue-700" x-text="$rp(totals.card)"></p>
                    </div>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-divider text-[12px] text-ink-muted flex items-center justify-between">
                <span>Total Penerimaan Bruto</span>
                <span class="font-bold text-ink" x-text="$rp(totals.cash + totals.wallet + totals.card)"></span>
            </div>
        </div>

        {{-- Card 3: Top Menu Terlaris Kafe --}}
        <div class="card p-5 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-[15px]/[21px] font-semibold text-ink flex items-center gap-2">
                        <x-icon name="package" class="size-4.5 text-primary"/>
                        Top 5 Menu Kafe Terlaris
                    </h3>
                    <span class="text-[12px] text-ink-muted">Best Sellers</span>
                </div>
                <div class="space-y-2.5">
                    <template x-for="(item, idx) in topProducts" :key="item.id">
                        <div class="flex items-center justify-between text-[13px] py-1 border-b border-divider/60 last:border-0">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="size-5 rounded-full grid place-items-center text-[11px] font-bold shrink-0"
                                      :class="idx === 0 ? 'bg-amber text-white' : (idx === 1 ? 'bg-slate-400 text-white' : (idx === 2 ? 'bg-amber-800 text-white' : 'bg-canvas text-ink-secondary'))"
                                      x-text="idx + 1"></span>
                                <div class="min-w-0">
                                    <p class="font-medium text-ink truncate" x-text="item.name"></p>
                                    <p class="text-[11px] text-ink-muted" x-text="`${item.quantity} porsi terjual`"></p>
                                </div>
                            </div>
                            <span class="font-semibold text-ink tabular-nums shrink-0 ml-2" x-text="$rp(item.revenue)"></span>
                        </div>
                    </template>
                    <template x-if="topProducts.length === 0">
                        <div class="py-8 text-center text-ink-muted text-[13px]">
                            <p>Belum ada data penjualan menu pada periode ini.</p>
                        </div>
                    </template>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-divider text-[12px] text-ink-muted text-right">
                <a href="/products" class="text-primary hover:underline font-medium">Kelola Produk &amp; Menu &rarr;</a>
            </div>
        </div>
    </div>

    {{-- ================= GRAFIK VISUAL (TREN, PEMBAYARAN, TIPE PESANAN) ================= --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mt-6">
        {{-- Grafik Tren Penjualan --}}
        <section class="panel p-6 xl:col-span-2" aria-label="Grafik tren omzet kafe">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-[17px]/[23px] font-semibold text-ink">Grafik Tren Penjualan Kafe</h2>
                    <p class="text-[12.5px] text-ink-muted" x-text="period === 'today' ? 'Distribusi transaksi per jam buka kafe (07:00 – 23:00)' : 'Tren pendapatan harian/bulanan'"></p>
                </div>
                <span class="badge badge-teal">Penjualan Bersih</span>
            </div>
            <div class="h-[280px]">
                <canvas data-chart="trend" role="img" aria-label="Grafik garis atau batang pendapatan bersih"></canvas>
            </div>
        </section>

        {{-- Grafik Komposisi Pembayaran & Dining --}}
        <section class="panel p-6 flex flex-col justify-between" aria-label="Grafik komposisi transaksi">
            <div>
                <h2 class="text-[17px]/[23px] font-semibold text-ink mb-1">Metode Pembayaran</h2>
                <p class="text-[12.5px] text-ink-muted mb-4">Komposisi penerimaan kasir</p>
                <div class="relative w-full max-w-[200px] mx-auto">
                    <div class="h-[180px]"><canvas data-chart="payment" role="img" aria-label="Grafik donat rincian pembayaran"></canvas></div>
                    <div class="absolute inset-0 grid place-items-center pointer-events-none">
                        <div class="text-center">
                            <p class="text-[11px] text-ink-muted">Total Diterima</p>
                            <p class="text-[15px]/[20px] font-bold tabular-nums text-ink" x-text="$rp(totals.gross)"></p>
                        </div>
                    </div>
                </div>
                <div class="mt-4 space-y-1.5 text-[12.5px]">
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-[#49ADAB]"></span> Tunai (Cash)</span>
                        <span class="font-semibold tabular-nums" x-text="$rp(totals.cash)"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-[#E89858]"></span> QRIS / E-Wallet</span>
                        <span class="font-semibold tabular-nums" x-text="$rp(totals.wallet)"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-[#7898AB]"></span> Kartu / EDC</span>
                        <span class="font-semibold tabular-nums" x-text="$rp(totals.card)"></span>
                    </div>
                </div>
            </div>
        </section>
    </div>

    {{-- ================= TABEL RINCIAN REKAPITULASI ================= --}}
    <section class="panel mt-6 overflow-hidden" aria-labelledby="breakdown-table-title">
        <div class="p-5 pb-0 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 id="breakdown-table-title" class="text-[18px]/[24px] font-semibold text-ink">
                    <span x-text="period === 'today' ? 'Rincian Penjualan per Jam (Peak Hours)' : (period === 'year' ? 'Rincian Penjualan per Bulan' : 'Rincian Penjualan per Hari')"></span>
                </h2>
                <p class="text-[12.5px] text-ink-muted" x-text="`Rekapitulasi lengkap ${rangeLabel}`"></p>
            </div>
            <div class="relative w-[260px] max-w-full">
                <x-icon name="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-ink-muted pointer-events-none"/>
                <label class="sr-only" for="rp-table-search">Cari periode atau tanggal</label>
                <input id="rp-table-search" type="search" class="input !min-h-10 pl-10 !text-[13px]" placeholder="Cari periode atau tanggal…" x-model="tableQuery" @input="page = 1">
            </div>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="table min-w-[980px]">
                <thead>
                    <tr>
                        <th class="text-left min-w-[160px]">
                            <button type="button" class="inline-flex items-center gap-1.5 hover:text-ink font-semibold" @click="setTableSort('key')">
                                Periode / Waktu
                                <x-icon name="arrow-up" class="size-3.5" x-show="tableSortKey === 'key' && tableSortDir === 'asc'"/>
                                <x-icon name="arrow-down" class="size-3.5" x-show="tableSortKey === 'key' && tableSortDir === 'desc'"/>
                            </button>
                        </th>
                        <th class="text-center min-w-[120px]">
                            <button type="button" class="w-full inline-flex items-center justify-center gap-1.5 hover:text-ink font-semibold" @click="setTableSort('orders')">
                                <span>Total Pesanan</span>
                                <x-icon name="arrow-up" class="size-3.5" x-show="tableSortKey === 'orders' && tableSortDir === 'asc'"/>
                                <x-icon name="arrow-down" class="size-3.5" x-show="tableSortKey === 'orders' && tableSortDir === 'desc'"/>
                            </button>
                        </th>
                        <th class="text-center min-w-[90px]">Dine In</th>
                        <th class="text-center min-w-[100px]">Take Away</th>
                        <th class="text-center min-w-[140px]">
                            <button type="button" class="w-full inline-flex items-center justify-center gap-1.5 hover:text-ink font-semibold" @click="setTableSort('gross')">
                                <span>Penjualan Kotor</span>
                                <x-icon name="arrow-up" class="size-3.5" x-show="tableSortKey === 'gross' && tableSortDir === 'asc'"/>
                                <x-icon name="arrow-down" class="size-3.5" x-show="tableSortKey === 'gross' && tableSortDir === 'desc'"/>
                            </button>
                        </th>
                        <th class="text-center text-danger min-w-[130px]">Diskon / Retur</th>
                        <th class="text-center min-w-[140px]">
                            <button type="button" class="w-full inline-flex items-center justify-center gap-1.5 hover:text-ink font-semibold" @click="setTableSort('netSales')">
                                <span>Penjualan Bersih</span>
                                <x-icon name="arrow-up" class="size-3.5" x-show="tableSortKey === 'netSales' && tableSortDir === 'asc'"/>
                                <x-icon name="arrow-down" class="size-3.5" x-show="tableSortKey === 'netSales' && tableSortDir === 'desc'"/>
                            </button>
                        </th>
                        <th class="text-center min-w-[130px]">Tunai (Cash)</th>
                        <th class="text-center min-w-[150px]">Cashless (QRIS/EDC)</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="row in paged" :key="row.key">
                        <tr class="hover:bg-canvas/60">
                            <td class="text-left">
                                <p class="font-medium text-ink" x-text="row.label"></p>
                                <p class="text-[11px] text-ink-muted" x-text="row.sublabel"></p>
                            </td>
                            <td class="text-center font-semibold tabular-nums text-ink whitespace-nowrap" x-text="row.orders"></td>
                            <td class="text-center text-ink-secondary tabular-nums whitespace-nowrap" x-text="row.dineInOrders"></td>
                            <td class="text-center text-ink-secondary tabular-nums whitespace-nowrap" x-text="row.takeawayOrders"></td>
                            <td class="text-center font-medium tabular-nums text-ink whitespace-nowrap" x-text="$rp(row.gross)"></td>
                            <td class="text-center tabular-nums text-danger whitespace-nowrap" x-text="row.discounts + row.refundTotal > 0 ? `-${$rp(row.discounts + row.refundTotal)}` : 'Rp0'"></td>
                            <td class="text-center font-bold tabular-nums text-primary whitespace-nowrap" x-text="$rp(row.netSales)"></td>
                            <td class="text-center text-emerald-700 font-medium tabular-nums whitespace-nowrap" x-text="$rp(row.cash)"></td>
                            <td class="text-center text-blue-700 font-medium tabular-nums whitespace-nowrap" x-text="$rp(row.cashless)"></td>
                        </tr>
                    </template>
                    <template x-if="paged.length === 0">
                        <tr>
                            <td colspan="9" class="py-12 text-center text-ink-muted">
                                <p class="text-[14px] font-medium">Belum ada transaksi pada periode ini.</p>
                                <p class="text-[12px] mt-1">Lakukan transaksi di Kasir POS atau Pesanan Meja untuk melihat rekapitulasi.</p>
                            </td>
                        </tr>
                    </template>
                </tbody>
                <tfoot>
                    <tr class="bg-surface-muted font-bold text-[13.5px]">
                        <td class="text-left font-bold text-ink">TOTAL REKAPITULASI</td>
                        <td class="text-center tabular-nums font-bold text-ink whitespace-nowrap" x-text="totals.orders"></td>
                        <td class="text-center tabular-nums font-semibold text-ink whitespace-nowrap" x-text="totals.dineInOrders"></td>
                        <td class="text-center tabular-nums font-semibold text-ink whitespace-nowrap" x-text="totals.takeawayOrders"></td>
                        <td class="text-center tabular-nums font-bold text-ink whitespace-nowrap" x-text="$rp(totals.gross)"></td>
                        <td class="text-center tabular-nums text-danger font-semibold whitespace-nowrap" x-text="totals.discounts + totals.refundTotal > 0 ? `-${$rp(totals.discounts + totals.refundTotal)}` : 'Rp0'"></td>
                        <td class="text-center tabular-nums text-primary font-black whitespace-nowrap" x-text="$rp(totals.netSales)"></td>
                        <td class="text-center tabular-nums text-emerald-700 font-bold whitespace-nowrap" x-text="$rp(totals.cash)"></td>
                        <td class="text-center tabular-nums text-blue-700 font-bold whitespace-nowrap" x-text="$rp(totals.wallet + totals.card)"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="p-4 border-t border-divider flex flex-wrap items-center justify-between gap-3" x-show="totalRows > pageSize">
            <x-pagination row-label="baris"/>
        </div>
    </section>

    {{-- ================= MODAL PRATINJAU STRUK CLOSING KASIR (THERMAL RECEIPT) ================= --}}
    <div x-show="receiptModalOpen" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label="Struk Rekap Closing Kasir">
        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-ink/50 backdrop-blur-sm" @click="closeReceiptModal()"
             x-transition:enter="fade-enter" x-transition:leave="fade-leave"></div>

        {{-- Dialog Card --}}
        <div class="relative w-full max-w-[380px] bg-white rounded-2xl shadow-2xl p-6 flex flex-col max-h-[92vh] overflow-hidden"
             x-transition:enter="pop-enter" x-transition:leave="pop-leave">
            <div class="flex items-center justify-between pb-3 border-b border-divider">
                <div class="flex items-center gap-2">
                    <span class="grid size-8 place-items-center rounded-lg bg-primary-soft text-primary">
                        <x-icon name="printer" class="size-4.5"/>
                    </span>
                    <h3 class="font-bold text-[15px] text-ink">Struk Rekap Closing Kasir</h3>
                </div>
                <button type="button" class="btn btn-secondary btn-sm btn-icon" @click="closeReceiptModal()" aria-label="Tutup pratinjau">
                    <x-icon name="x" class="size-4"/>
                </button>
            </div>

            {{-- Body Struk Thermal --}}
            <div class="flex-1 overflow-y-auto py-4 px-2 nice-scroll">
                <div class="bg-[#faf9f5] border border-line p-4 rounded-xl font-mono text-[11.5px] leading-relaxed text-ink shadow-inner">
                    <div class="text-center pb-3 border-b border-dashed border-ink/30">
                        <p class="font-bold text-[15px] tracking-wider">POSYMART CAFE</p>
                        <p class="text-[11px] text-ink-secondary">Coffee &amp; Eatery</p>
                        <p class="text-[10px] text-ink-muted mt-1">*** REKAP CLOSING SHIFT ***</p>
                    </div>

                    <div class="py-2.5 border-b border-dashed border-ink/30 space-y-1 text-[11px]">
                        <div class="flex justify-between">
                            <span class="text-ink-muted">Kasir:</span>
                            <span class="font-bold" x-text="$store.posy.currentUser?.()?.name || 'Administrator'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-ink-muted">Periode:</span>
                            <span class="font-medium" x-text="rangeLabel"></span>
                        </div>
                    </div>

                    {{-- Summary Penjualan --}}
                    <div class="py-2.5 border-b border-dashed border-ink/30 space-y-1.5">
                        <div class="flex justify-between">
                            <span>Total Transaksi</span>
                            <span class="font-bold tabular-nums" x-text="`${totals.orders} Struk`"></span>
                        </div>
                        <div class="flex justify-between">
                            <span>Total Menu Terjual</span>
                            <span class="font-bold tabular-nums" x-text="`${totals.itemsSold} Porsi`"></span>
                        </div>
                        <div class="flex justify-between">
                            <span>Penjualan Kotor</span>
                            <span class="tabular-nums" x-text="$rp(totals.gross)"></span>
                        </div>
                        <div class="flex justify-between text-danger" x-show="totals.discounts > 0">
                            <span>Total Diskon</span>
                            <span class="tabular-nums" x-text="`-${$rp(totals.discounts)}`"></span>
                        </div>
                        <div class="flex justify-between text-danger" x-show="totals.refundTotal > 0">
                            <span>Total Refund</span>
                            <span class="tabular-nums" x-text="`-${$rp(totals.refundTotal)}`"></span>
                        </div>
                        <div class="flex justify-between pt-1 border-t border-dotted border-ink/30 font-bold text-[12.5px]">
                            <span>PENJUALAN BERSIH</span>
                            <span class="text-primary tabular-nums" x-text="$rp(totals.netSales)"></span>
                        </div>
                    </div>

                    {{-- Rincian Menu & Porsi Terjual --}}
                    <div class="py-2.5 border-b border-dashed border-ink/30 space-y-1.5">
                        <div class="flex justify-between items-center">
                            <p class="font-bold text-[11px] uppercase tracking-wider text-ink">Rincian Menu Terjual:</p>
                            <span class="text-[10px] text-ink-muted tabular-nums" x-text="`${soldProducts.length} Menu (${totals.itemsSold} Porsi)`"></span>
                        </div>
                        <div class="space-y-1.5 pt-1">
                            <template x-for="item in soldProducts" :key="item.id">
                                <div class="text-[11px] leading-snug">
                                    <div class="font-semibold text-ink" x-text="item.name"></div>
                                    <div class="flex justify-between items-center text-ink-secondary text-[10.5px]">
                                        <span x-text="`${item.quantity} Porsi ${item.unitPrice ? '× ' + $rp(item.unitPrice) : ''}`"></span>
                                        <span class="font-bold tabular-nums text-ink" x-text="$rp(item.revenue)"></span>
                                    </div>
                                </div>
                            </template>
                            <template x-if="soldProducts.length === 0">
                                <p class="text-[10.5px] text-ink-muted italic py-1">Belum ada menu terjual pada periode ini</p>
                            </template>
                        </div>
                    </div>

                    {{-- Rekonsiliasi Kasir --}}
                    <div class="py-2.5 border-b border-dashed border-ink/30 space-y-1.5">
                        <p class="font-bold text-[11px] uppercase tracking-wider">Uang Masuk / Pembayaran:</p>
                        <div class="flex justify-between">
                            <span>Tunai di Laci (Cash)</span>
                            <span class="font-bold text-emerald-700 tabular-nums" x-text="$rp(totals.cash)"></span>
                        </div>
                        <div class="flex justify-between">
                            <span>QRIS / E-Wallet</span>
                            <span class="tabular-nums" x-text="$rp(totals.wallet)"></span>
                        </div>
                        <div class="flex justify-between">
                            <span>Kartu / Transfer</span>
                            <span class="tabular-nums" x-text="$rp(totals.card)"></span>
                        </div>
                    </div>

                    {{-- Tipe Pesanan --}}
                    <div class="py-2.5 border-b border-dashed border-ink/30 space-y-1.5">
                        <p class="font-bold text-[11px] uppercase tracking-wider">Tipe Pesanan:</p>
                        <div class="flex justify-between">
                            <span>Dine In (Makan di Tempat)</span>
                            <span class="tabular-nums" x-text="`${totals.dineInOrders} (${$rp(totals.dineInSales)})`"></span>
                        </div>
                        <div class="flex justify-between">
                            <span>Take Away (Bungkus)</span>
                            <span class="tabular-nums" x-text="`${totals.takeawayOrders} (${$rp(totals.takeawaySales)})`"></span>
                        </div>
                    </div>

                    {{-- Tanda Tangan --}}
                    <div class="pt-4 pb-2 flex justify-between text-center text-[10px]">
                        <div class="w-[45%]">
                            <p class="text-ink-muted">Kasir Bertugas,</p>
                            <div class="h-10"></div>
                            <p class="font-bold border-t border-ink/20 pt-1" x-text="$store.posy.currentUser?.()?.name || 'Kasir'"></p>
                        </div>
                        <div class="w-[45%]">
                            <p class="text-ink-muted">Supervisor / Owner,</p>
                            <div class="h-10"></div>
                            <p class="font-bold border-t border-ink/20 pt-1">( .................... )</p>
                        </div>
                    </div>

                    <div class="text-center pt-3 text-[9px] text-ink-muted">
                        Simpan struk ini sebagai bukti rekonsiliasi kasir harian.
                    </div>
                </div>
            </div>

            {{-- Footer Action --}}
            <div class="pt-3 border-t border-divider flex items-center justify-end gap-2.5">
                <button type="button" class="btn btn-secondary btn-sm" @click="closeReceiptModal()">Tutup</button>
                <button type="button" class="btn btn-primary btn-sm" @click="printClosingReceipt()">
                    <x-icon name="printer" class="size-4"/> Cetak Struk Kasir
                </button>
            </div>
        </div>
    </div>

    {{-- ================= MODAL DETAIL METRIK KEUANGAN & PAJAK ================= --}}
    <div class="modal-backdrop z-50 flex items-center justify-center p-4 bg-ink/50 backdrop-blur-sm fixed inset-0"
         x-show="metricModalOpen"
         x-cloak
         x-transition:enter="fade-enter"
         x-transition:leave="fade-leave"
         @keydown.escape.window="closeMetricModal()"
         role="dialog"
         aria-modal="true">
        
        {{-- Click backdrop to close --}}
        <div class="fixed inset-0" @click="closeMetricModal()"></div>

        <div class="relative w-full max-w-[560px] bg-white rounded-2xl shadow-2xl p-6 flex flex-col max-h-[90vh] overflow-hidden z-10"
             x-transition:enter="pop-enter" x-transition:leave="pop-leave">
            
            {{-- Modal Header --}}
            <div class="flex items-center justify-between pb-4 border-b border-divider shrink-0">
                <div class="flex items-center gap-3">
                    <span class="grid size-10 place-items-center rounded-xl shrink-0"
                          :class="{
                              'bg-primary-soft text-primary': activeMetric === 'netSales',
                              'bg-surface-muted text-ink': activeMetric === 'gross',
                              'bg-amber-100 text-amber-700': activeMetric === 'tax',
                              'bg-emerald-50 text-emerald-600': activeMetric === 'orders',
                              'bg-amber-soft text-amber': activeMetric === 'itemsSold',
                              'bg-blue-50 text-blue-600': activeMetric === 'aov'
                          }">
                        <template x-if="activeMetric === 'netSales'"><x-icon name="dollar" class="size-5"/></template>
                        <template x-if="activeMetric === 'gross'"><x-icon name="bar-chart" class="size-5"/></template>
                        <template x-if="activeMetric === 'tax'"><x-icon name="percent" class="size-5"/></template>
                        <template x-if="activeMetric === 'orders'"><x-icon name="check-circle" class="size-5"/></template>
                        <template x-if="activeMetric === 'itemsSold'"><x-icon name="package" class="size-5"/></template>
                        <template x-if="activeMetric === 'aov'"><x-icon name="trending-up" class="size-5"/></template>
                    </span>
                    <div>
                        <h3 class="font-bold text-[16px] text-ink"
                            x-text="activeMetric === 'netSales' ? 'Rincian Pendapatan Bersih' :
                                    (activeMetric === 'gross' ? 'Rincian Penjualan Kotor' :
                                    (activeMetric === 'tax' ? 'Rincian Uang Hasil Pajak (PB1)' :
                                    (activeMetric === 'orders' ? 'Rincian Total Transaksi' :
                                    (activeMetric === 'itemsSold' ? 'Rincian Menu & Porsi Terjual' : 'Rincian Rata-rata Order (AOV)'))))"></h3>
                        <p class="text-[12px] text-ink-muted flex items-center gap-1.5 mt-0.5">
                            <x-icon name="calendar" class="size-3.5"/>
                            <span x-text="rangeLabel"></span>
                        </p>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary btn-sm btn-icon" @click="closeMetricModal()" aria-label="Tutup detail">
                    <x-icon name="x" class="size-4"/>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="flex-1 overflow-y-auto py-4 px-1 nice-scroll space-y-4">
                
                {{-- 1. PENDAPATAN BERSIH DETAIL --}}
                <template x-if="activeMetric === 'netSales'">
                    <div class="space-y-4">
                        <div class="bg-gradient-to-br from-primary/10 via-primary/5 to-white border border-primary/20 rounded-xl p-4.5 text-center">
                            <p class="text-[12px] font-medium text-ink-secondary uppercase tracking-wider">Total Pendapatan Bersih (Net Sales)</p>
                            <p class="font-bold text-[32px]/[38px] text-primary tabular-nums mt-1" x-text="$rp(totals.netSales)"></p>
                            <span class="inline-block mt-2 text-[11px] font-semibold text-emerald-800 bg-emerald-100/80 px-2.5 py-0.5 rounded-full">
                                Omzet Murni Produk (Bebas dari Uang Pajak)
                            </span>
                        </div>

                        <div class="card p-4 space-y-2.5 text-[12.5px]">
                            <p class="font-bold text-ink text-[13px] border-b border-divider pb-2 flex items-center justify-between">
                                <span>Kalkulasi Pendapatan Bersih</span>
                                <span class="text-[11px] font-normal text-ink-muted">Rumus Transparan</span>
                            </p>
                            <div class="flex justify-between items-center text-ink-secondary">
                                <span>(+) Subtotal Harga Produk</span>
                                <span class="font-semibold tabular-nums text-ink" x-text="$rp(totals.subtotal)"></span>
                            </div>
                            <div class="flex justify-between items-center text-ink-secondary">
                                <span>(-) Potongan Diskon Promo</span>
                                <span class="font-semibold tabular-nums text-danger" x-text="totals.discounts > 0 ? `-${$rp(totals.discounts)}` : 'Rp0'"></span>
                            </div>
                            <div class="flex justify-between items-center text-ink-secondary">
                                <span>(-) Pengembalian / Refund Bersih</span>
                                <span class="font-semibold tabular-nums text-danger" x-text="totals.refundNet > 0 ? `-${$rp(totals.refundNet)}` : 'Rp0'"></span>
                            </div>
                            <div class="pt-2 border-t border-dashed border-divider flex justify-between items-center font-bold text-[14px]">
                                <span class="text-ink">(=) Total Pendapatan Bersih</span>
                                <span class="text-primary tabular-nums" x-text="$rp(totals.netSales)"></span>
                            </div>
                        </div>

                        <div class="bg-surface-muted/80 border border-line rounded-xl p-3.5 text-[12px] text-ink-secondary flex items-start gap-2.5">
                            <x-icon name="info" class="size-4.5 text-primary shrink-0 mt-0.5"/>
                            <p class="leading-relaxed">
                                <strong class="text-ink">Mengapa dipisahkan dari pajak?</strong> Pendapatan bersih adalah omzet murni penjualan menu yang menjadi hak pendapatan kafe. Uang pungutan pajak <strong>tidak dicampur</strong> di sini agar penghitungan laba/rugi dan performa menu Anda 100% akurat.
                            </p>
                        </div>

                        <div class="card p-4 text-[12.5px] space-y-2">
                            <p class="font-bold text-ink text-[13px] border-b border-divider pb-2">Sumber Pembayaran Diterima:</p>
                            <div class="flex justify-between">
                                <span class="text-ink-secondary">Uang Tunai di Laci (Cash)</span>
                                <span class="font-semibold tabular-nums text-emerald-700" x-text="$rp(totals.cash)"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-ink-secondary">QRIS / Dompet Digital</span>
                                <span class="font-semibold tabular-nums text-ink" x-text="$rp(totals.wallet)"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-ink-secondary">Kartu / EDC / Transfer</span>
                                <span class="font-semibold tabular-nums text-ink" x-text="$rp(totals.card)"></span>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- 2. PENJUALAN KOTOR DETAIL --}}
                <template x-if="activeMetric === 'gross'">
                    <div class="space-y-4">
                        <div class="bg-gradient-to-br from-surface-muted via-white to-surface-muted border border-line rounded-xl p-4.5 text-center">
                            <p class="text-[12px] font-medium text-ink-secondary uppercase tracking-wider">Total Penjualan Kotor (Gross Collected)</p>
                            <p class="font-bold text-[32px]/[38px] text-ink tabular-nums mt-1" x-text="$rp(totals.grossCollected)"></p>
                            <span class="inline-block mt-2 text-[11px] font-semibold text-ink-secondary bg-surface-muted px-2.5 py-0.5 rounded-full">
                                Akumulasi Seluruh Dana Bruto Masuk Kasir
                            </span>
                        </div>

                        <div class="card p-4 space-y-2.5 text-[12.5px]">
                            <p class="font-bold text-ink text-[13px] border-b border-divider pb-2 flex items-center justify-between">
                                <span>Komponen Dana Masuk</span>
                                <span class="text-[11px] font-normal text-ink-muted">Akumulasi Bruto</span>
                            </p>
                            <div class="flex justify-between items-center text-ink-secondary">
                                <span>(+) Pendapatan Bersih Menu</span>
                                <span class="font-semibold tabular-nums text-primary" x-text="$rp(totals.netSales)"></span>
                            </div>
                            <div class="flex justify-between items-center text-ink-secondary">
                                <span>(+) Uang Pungutan Pajak (PB1)</span>
                                <span class="font-semibold tabular-nums text-amber-700" x-text="`+${$rp(totals.netTax)}`"></span>
                            </div>
                            <div class="pt-2 border-t border-dashed border-divider flex justify-between items-center font-bold text-[14px]">
                                <span class="text-ink">(=) Total Penjualan Kotor Masuk</span>
                                <span class="text-ink tabular-nums" x-text="$rp(totals.grossCollected)"></span>
                            </div>
                        </div>

                        <div class="bg-surface-muted/80 border border-line rounded-xl p-3.5 text-[12px] text-ink-secondary flex items-start gap-2.5">
                            <x-icon name="info" class="size-4.5 text-ink shrink-0 mt-0.5"/>
                            <p class="leading-relaxed">
                                <strong class="text-ink">Penjualan Kotor (Gross)</strong> mencerminkan total fisik/elektronik seluruh uang yang dibayarkan pelanggan di kasir pada periode ini. Terdiri dari harga makanan/minuman yang laku ditambah titipan pajak sebelum disetorkan.
                            </p>
                        </div>

                        <div class="card p-4 text-[12.5px] space-y-2">
                            <p class="font-bold text-ink text-[13px] border-b border-divider pb-2">Berdasarkan Tipe Layanan:</p>
                            <div class="flex justify-between">
                                <span class="text-ink-secondary">Dine In (Makan di Tempat)</span>
                                <span class="font-semibold tabular-nums text-ink" x-text="`${totals.dineInOrders} order · ${$rp(totals.dineInSales)}`"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-ink-secondary">Take Away (Bungkus)</span>
                                <span class="font-semibold tabular-nums text-ink" x-text="`${totals.takeawayOrders} order · ${$rp(totals.takeawaySales)}`"></span>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- 3. UANG HASIL PAJAK DETAIL --}}
                <template x-if="activeMetric === 'tax'">
                    <div class="space-y-4">
                        <div class="bg-gradient-to-br from-amber-500/10 via-amber-50 to-white border border-amber-300 rounded-xl p-4.5 text-center">
                            <p class="text-[12px] font-medium text-amber-900 uppercase tracking-wider">Uang Hasil Pajak Restoran (PB1 Terkumpul)</p>
                            <p class="font-bold text-[32px]/[38px] text-amber-700 tabular-nums mt-1" x-text="$rp(totals.netTax)"></p>
                            <span class="inline-block mt-2 text-[11px] font-semibold text-amber-800 bg-amber-100 px-2.5 py-0.5 rounded-full">
                                Titipan Pajak Dari <span x-text="taxSales.length"></span> Transaksi Berpajak
                            </span>
                        </div>

                        <div class="card p-4 space-y-2.5 text-[12.5px]">
                            <p class="font-bold text-ink text-[13px] border-b border-divider pb-2 flex items-center justify-between">
                                <span>Kalkulasi Pajak Terkumpul</span>
                                <span class="text-[11px] font-normal text-ink-muted">Pajak Penjualan Kafe</span>
                            </p>
                            <div class="flex justify-between items-center text-ink-secondary">
                                <span>(+) Total Pajak Terpungut</span>
                                <span class="font-semibold tabular-nums text-ink" x-text="$rp(totals.tax)"></span>
                            </div>
                            <div class="flex justify-between items-center text-ink-secondary">
                                <span>(-) Pengembalian / Refund Pajak</span>
                                <span class="font-semibold tabular-nums text-danger" x-text="totals.refundTax > 0 ? `-${$rp(totals.refundTax)}` : 'Rp0'"></span>
                            </div>
                            <div class="pt-2 border-t border-dashed border-divider flex justify-between items-center font-bold text-[14px]">
                                <span class="text-ink">(=) Total Pajak Bersih Terkumpul</span>
                                <span class="text-amber-700 tabular-nums" x-text="$rp(totals.netTax)"></span>
                            </div>
                        </div>

                        <div class="bg-amber-50/70 border border-amber-200 rounded-xl p-3.5 text-[12px] text-amber-900 flex items-start gap-2.5">
                            <x-icon name="percent" class="size-4.5 text-amber-700 shrink-0 mt-0.5"/>
                            <p class="leading-relaxed">
                                <strong class="text-amber-950">Apa itu Uang Hasil Pajak?</strong> Ini adalah akumulasi uang pajak restoran (PB1) yang dibayarkan oleh pembeli di luar harga makanan. Pemilik kafe dapat mengalokasikannya untuk pembayaran setoran pajak daerah secara terpisah atau mengetahui porsi kas yang berasal dari pajak.
                            </p>
                        </div>

                        <div class="card p-4 space-y-2.5 text-[12px]">
                            <div class="flex justify-between items-center border-b border-divider pb-2">
                                <p class="font-bold text-ink text-[13px]">Daftar Struk Berpajak:</p>
                                <span class="text-[11px] text-ink-muted" x-text="`${taxSales.length} Struk`"></span>
                            </div>
                            <div class="max-h-[180px] overflow-y-auto space-y-2 nice-scroll pr-1">
                                <template x-for="s in taxSales" :key="s.id">
                                    <div class="flex justify-between items-center py-1.5 px-2.5 rounded-lg bg-surface-muted/50 border border-divider text-[11.5px]">
                                        <div>
                                            <span class="font-bold text-ink" x-text="s.receiptNo || s.id"></span>
                                            <span class="text-ink-muted text-[10.5px] ml-1.5" x-text="s.customerName || 'Umum'"></span>
                                        </div>
                                        <div class="text-right">
                                            <span class="font-bold tabular-nums text-amber-700" x-text="`+${$rp(s.tax)}`"></span>
                                            <div class="text-[10px] text-ink-muted" x-text="`Total: ${$rp(s.total)}`"></div>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="taxSales.length === 0">
                                    <p class="text-ink-muted text-center py-3 italic">Tidak ada transaksi yang dikenakan pajak pada rentang ini.</p>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- 4. TOTAL TRANSAKSI DETAIL --}}
                <template x-if="activeMetric === 'orders'">
                    <div class="space-y-4">
                        <div class="bg-gradient-to-br from-emerald-50 via-white to-emerald-50 border border-emerald-200 rounded-xl p-4.5 text-center">
                            <p class="text-[12px] font-medium text-emerald-800 uppercase tracking-wider">Total Struk Transaksi Selesai</p>
                            <p class="font-bold text-[36px]/[42px] text-emerald-700 tabular-nums mt-1" x-text="totals.orders"></p>
                            <span class="inline-block mt-2 text-[11px] font-semibold text-emerald-800 bg-emerald-100 px-2.5 py-0.5 rounded-full">
                                Struk Sukses Terbayar
                            </span>
                        </div>

                        <div class="card p-4 space-y-2.5 text-[12.5px]">
                            <p class="font-bold text-ink text-[13px] border-b border-divider pb-2">Komposisi Transaksi:</p>
                            <div class="flex justify-between items-center">
                                <span class="text-ink-secondary">Makan di Tempat (Dine In)</span>
                                <span class="font-bold tabular-nums text-ink" x-text="`${totals.dineInOrders} Struk (${$rp(totals.dineInSales)})`"></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-ink-secondary">Bawa Pulang (Take Away)</span>
                                <span class="font-bold tabular-nums text-ink" x-text="`${totals.takeawayOrders} Struk (${$rp(totals.takeawaySales)})`"></span>
                            </div>
                            <div class="flex justify-between items-center pt-2 border-t border-dashed border-divider">
                                <span class="text-ink-secondary">Rata-rata Nilai Order (AOV)</span>
                                <span class="font-bold tabular-nums text-primary" x-text="$rp(totals.aov)"></span>
                            </div>
                        </div>

                        <div class="card p-4 space-y-2 text-[12px]">
                            <p class="font-bold text-ink text-[13px] border-b border-divider pb-2">Transaksi Terbaru pada Periode Ini:</p>
                            <div class="max-h-[180px] overflow-y-auto space-y-1.5 nice-scroll pr-1">
                                <template x-for="s in activeSales.slice(0, 8)" :key="s.id">
                                    <div class="flex justify-between items-center py-1.5 px-2 border-b border-divider last:border-0 text-[11.5px]">
                                        <div>
                                            <span class="font-semibold text-ink" x-text="s.receiptNo || s.id"></span>
                                            <span class="text-ink-muted text-[10.5px] ml-1" x-text="`(${s.paymentMethod || 'Cash'})`"></span>
                                        </div>
                                        <span class="font-bold tabular-nums text-ink" x-text="$rp(s.total)"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- 5. MENU & PORSI TERJUAL DETAIL --}}
                <template x-if="activeMetric === 'itemsSold'">
                    <div class="space-y-4">
                        <div class="bg-gradient-to-br from-amber-50 via-white to-amber-50 border border-amber-200 rounded-xl p-4.5 text-center">
                            <p class="text-[12px] font-medium text-amber-800 uppercase tracking-wider">Total Menu / Porsi Terjual</p>
                            <p class="font-bold text-[36px]/[42px] text-amber-700 tabular-nums mt-1" x-text="`${totals.itemsSold} Porsi`"></p>
                            <span class="inline-block mt-2 text-[11px] font-semibold text-amber-800 bg-amber-100 px-2.5 py-0.5 rounded-full"
                                  x-text="`${soldProducts.length} Varian Menu Terjual`">
                            </span>
                        </div>

                        <div class="card p-4 space-y-2 text-[12px]">
                            <div class="flex justify-between items-center border-b border-divider pb-2">
                                <p class="font-bold text-ink text-[13px]">Daftar Menu &amp; Porsi Terjual:</p>
                                <span class="text-[11px] text-ink-muted">Porsi &amp; Total Pendapatan</span>
                            </div>
                            <div class="max-h-[240px] overflow-y-auto space-y-2 nice-scroll pr-1">
                                <template x-for="it in soldProducts" :key="it.id">
                                    <div class="p-2.5 rounded-xl border border-line bg-surface-muted/40 hover:bg-white transition-colors">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <p class="font-bold text-ink text-[12.5px]" x-text="it.name"></p>
                                                <p class="text-ink-muted text-[11px] mt-0.5" x-text="`${it.quantity} Porsi ${it.unitPrice ? '× ' + $rp(it.unitPrice) : ''}`"></p>
                                            </div>
                                            <p class="font-bold tabular-nums text-ink text-[13px]" x-text="$rp(it.revenue)"></span>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="soldProducts.length === 0">
                                    <p class="text-ink-muted text-center py-4 italic">Belum ada menu terjual pada rentang ini.</p>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- 6. AOV DETAIL --}}
                <template x-if="activeMetric === 'aov'">
                    <div class="space-y-4">
                        <div class="bg-gradient-to-br from-blue-50 via-white to-blue-50 border border-blue-200 rounded-xl p-4.5 text-center">
                            <p class="text-[12px] font-medium text-blue-800 uppercase tracking-wider">Rata-rata Nilai Belanja (AOV)</p>
                            <p class="font-bold text-[32px]/[38px] text-blue-700 tabular-nums mt-1" x-text="$rp(totals.aov)"></p>
                            <span class="inline-block mt-2 text-[11px] font-semibold text-blue-800 bg-blue-100 px-2.5 py-0.5 rounded-full">
                                Nilai Rata-rata per Struk Pelanggan
                            </span>
                        </div>

                        <div class="card p-4 space-y-2.5 text-[12.5px]">
                            <p class="font-bold text-ink text-[13px] border-b border-divider pb-2">Formula Perhitungan AOV:</p>
                            <div class="flex justify-between items-center text-ink-secondary">
                                <span>Total Penjualan Kotor</span>
                                <span class="font-semibold tabular-nums text-ink" x-text="$rp(totals.grossCollected)"></span>
                            </div>
                            <div class="flex justify-between items-center text-ink-secondary">
                                <span>Dibagi Total Transaksi</span>
                                <span class="font-semibold tabular-nums text-ink" x-text="`${totals.orders} Struk`"></span>
                            </div>
                            <div class="pt-2 border-t border-dashed border-divider flex justify-between items-center font-bold text-[14px]">
                                <span class="text-ink">(=) Rata-rata Nilai Order</span>
                                <span class="text-blue-700 tabular-nums" x-text="$rp(totals.aov)"></span>
                            </div>
                        </div>

                        <div class="bg-blue-50/70 border border-blue-200 rounded-xl p-3.5 text-[12px] text-blue-900 flex items-start gap-2.5">
                            <x-icon name="trending-up" class="size-4.5 text-blue-700 shrink-0 mt-0.5"/>
                            <p class="leading-relaxed">
                                <strong class="text-blue-950">Fungsi Metrik AOV:</strong> Membantu kasir dan barista mengetahui efektivitas strategi up-selling atau cross-selling. Semakin tinggi AOV, semakin banyak rata-rata pengeluaran yang dihabiskan satu meja di kafe Anda.
                            </p>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Modal Footer --}}
            <div class="pt-3 border-t border-divider flex items-center justify-between shrink-0">
                <button type="button" class="btn btn-secondary btn-sm" @click="closeMetricModal()">Tutup</button>
                <div class="flex items-center gap-2">
                    <button type="button" class="btn btn-secondary btn-sm text-[12px]" @click="closeMetricModal(); openReceiptModal();">
                        <x-icon name="printer" class="size-4"/> Lihat Struk Closing
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
