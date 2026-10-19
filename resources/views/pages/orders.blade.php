@extends('layouts.app')

@section('title', 'Antrean Pesanan')
@section('subtitle', 'Papan live antrean pesanan pelanggan (QR Meja & Ambil di Kasir).')

@section('content')
<div x-data="ordersPage" x-cloak>

    <x-page-header title="Antrean Pesanan" subtitle="Papan live antrean pesanan pelanggan (QR Meja & Ambil di Kasir).">
        <button type="button" @click="soundEnabled = !soundEnabled; if (soundEnabled) playChime();"
                :class="soundEnabled ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : 'bg-white text-slate-600 border-slate-200'"
                class="border px-3.5 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 transition hover:shadow-xs">
            <span :class="soundEnabled ? 'text-emerald-600' : 'text-slate-400'">
                <x-icon name="bell" class="size-4"/>
            </span>
            <span x-text="soundEnabled ? '🔔 Alert Suara: Aktif' : '🔕 Alert Suara: Senyap'"></span>
        </button>
        <a href="/order" target="_blank" class="btn btn-secondary text-xs flex items-center gap-1.5" title="Buka Portal QR Tamu Satu Pintu">
            <x-icon name="qr-code" class="size-4 text-emerald-600"/>
            <span>Portal QR Tamu (/order)</span>
        </a>
        <button type="button" class="btn btn-secondary" @click="exportCsv()">
            <x-icon name="download" class="size-4.5"/> Ekspor CSV
        </button>
    </x-page-header>

    {{-- ================= ESCALATION WARNING BANNER (> 60s) ================= --}}
    <div x-show="tableEscalationCount > 0" x-cloak
         class="mt-4 rounded-2xl bg-rose-50 border-2 border-rose-300 p-4 flex items-center justify-between text-rose-950 shadow-sm animate-pulse">
        <div class="flex items-center gap-3.5">
            <span class="size-10 rounded-xl bg-rose-600 text-white font-extrabold text-lg grid place-items-center">⚠️</span>
            <div>
                <p class="font-extrabold text-sm">Peringatan Eskalasi Waktu Tunggu (&gt; 60 Detik)</p>
                <p class="text-xs text-rose-700">Terdapat <span class="font-bold underline" x-text="tableEscalationCount"></span> pesanan baru (New) dari tamu yang belum diterima staf selama lebih dari 60 detik!</p>
            </div>
        </div>
        <button type="button" @click="tableTab = 'New'" class="btn btn-sm btn-danger font-bold">
            Tangani Segera (Tab New)
        </button>
    </div>

    {{-- ================= TABLE ORDERS LIVE BOARD ================= --}}
    <div class="space-y-6 mt-4">
        {{-- Table Status Tabs --}}
        <div class="card p-4">
            <div class="flex items-center gap-2 overflow-x-auto no-scrollbar py-0.5">
                <button type="button" @click="tableTab = 'all'"
                        :class="tableTab === 'all' ? 'bg-[#4CB0AE] text-white font-bold' : 'bg-slate-100 text-slate-600 font-medium'"
                        class="px-3.5 py-2 rounded-xl text-xs transition flex items-center gap-1.5 whitespace-nowrap">
                    <span>Semua Antrean</span>
                    <span class="px-1.5 py-0.5 rounded-md text-[10px] bg-white/20 font-bold" x-text="tableCounts.all || 0"></span>
                </button>
                <button type="button" @click="tableTab = 'New'"
                        :class="tableTab === 'New' ? 'bg-amber-500 text-white font-bold' : 'bg-slate-100 text-slate-600 font-medium'"
                        class="px-3.5 py-2 rounded-xl text-xs transition flex items-center gap-1.5 whitespace-nowrap">
                    <span>Pesanan Baru (New)</span>
                    <span class="px-1.5 py-0.5 rounded-md text-[10px] bg-white/20 font-bold" x-text="tableCounts.new || 0"></span>
                </button>
                <button type="button" @click="tableTab = 'Accepted'"
                        :class="tableTab === 'Accepted' ? 'bg-sky-500 text-white font-bold' : 'bg-slate-100 text-slate-600 font-medium'"
                        class="px-3.5 py-2 rounded-xl text-xs transition flex items-center gap-1.5 whitespace-nowrap">
                    <span>Diterima (Accepted)</span>
                    <span class="px-1.5 py-0.5 rounded-md text-[10px] bg-white/20 font-bold" x-text="tableCounts.accepted || 0"></span>
                </button>
                <button type="button" @click="tableTab = 'Preparing'"
                        :class="tableTab === 'Preparing' ? 'bg-indigo-500 text-white font-bold' : 'bg-slate-100 text-slate-600 font-medium'"
                        class="px-3.5 py-2 rounded-xl text-xs transition flex items-center gap-1.5 whitespace-nowrap">
                    <span>Sedang Dibuat (Preparing)</span>
                    <span class="px-1.5 py-0.5 rounded-md text-[10px] bg-white/20 font-bold" x-text="tableCounts.preparing || 0"></span>
                </button>
                <button type="button" @click="tableTab = 'Ready'"
                        :class="tableTab === 'Ready' ? 'bg-emerald-600 text-white font-bold' : 'bg-slate-100 text-slate-600 font-medium'"
                        class="px-3.5 py-2 rounded-xl text-xs transition flex items-center gap-1.5 whitespace-nowrap">
                    <span>Siap Diantar (Ready)</span>
                    <span class="px-1.5 py-0.5 rounded-md text-[10px] bg-white/20 font-bold" x-text="tableCounts.ready || 0"></span>
                </button>
                <button type="button" @click="tableTab = 'Served'"
                        :class="tableTab === 'Served' ? 'bg-slate-700 text-white font-bold' : 'bg-slate-100 text-slate-600 font-medium'"
                        class="px-3.5 py-2 rounded-xl text-xs transition flex items-center gap-1.5 whitespace-nowrap">
                    <span>Selesai (Served)</span>
                    <span class="px-1.5 py-0.5 rounded-md text-[10px] bg-white/20 font-bold" x-text="tableCounts.served || 0"></span>
                </button>
                <button type="button" @click="tableTab = 'ReviewRequired'"
                        :class="tableTab === 'ReviewRequired' ? 'bg-rose-600 text-white font-bold' : 'bg-slate-100 text-slate-600 font-medium'"
                        class="px-3.5 py-2 rounded-xl text-xs transition flex items-center gap-1.5 whitespace-nowrap">
                    <span>Perlu Tinjauan (Review)</span>
                    <span class="px-1.5 py-0.5 rounded-md text-[10px] bg-white/20 font-bold" x-text="tableCounts.review_required || 0"></span>
                </button>
            </div>
        </div>

        {{-- Table Orders Cards Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            <template x-for="ord in filteredTableOrders" :key="ord.id">
                <div class="card p-5 flex flex-col justify-between border-slate-200/90 hover:shadow-md transition relative">
                    <div>
                        {{-- Card Header: Table + Nama Pemesan + Status Badge --}}
                        <div class="flex items-start justify-between pb-3 border-b border-slate-100 gap-2">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="font-black text-slate-900 text-lg leading-tight" x-text="ord.table_label_snapshot || 'Meja'"></h3>
                                    <span class="text-slate-300 font-bold">•</span>
                                    <span class="text-teal-800 font-extrabold text-base truncate" x-text="ord.customer_name || 'Pelanggan'"></span>
                                    <span class="text-[10px] px-2 py-0.5 rounded-full font-bold shrink-0"
                                          :class="ord.delivery_type === 'counter' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'"
                                          x-text="ord.delivery_type === 'counter' ? 'Ambil di Kasir' : 'Diantar ke Meja'">
                                    </span>
                                </div>
                                <p class="text-[11px] font-mono text-slate-400 mt-1" x-text="ord.order_code"></p>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="badge"
                                      :class="{
                                          'NotQueued': 'badge-amber',
                                          'New': 'badge-amber',
                                          'Accepted': 'badge-info',
                                          'Preparing': 'badge-info',
                                          'Ready': 'badge-positive',
                                          'Served': 'badge-neutral',
                                          'ReviewRequired': 'badge-danger'
                                      }[ord.fulfillment_status]"
                                      x-text="ord.fulfillment_status === 'NotQueued' ? 'Menunggu Bayar' : ord.fulfillment_status">
                                </span>
                            </div>
                        </div>

                        {{-- Order Items Breakdown (Bisa diklik untuk buka detail pesanan) --}}
                        <div @click="openTableOrderDetail(ord)"
                             class="py-3 border-b border-slate-100 cursor-pointer rounded-xl -mx-1 px-2 hover:bg-teal-50/50 transition group"
                             title="Klik untuk membuka detail pesanan lengkap">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Detail Pesanan</span>
                                <span class="text-[11px] font-bold text-teal-700 group-hover:text-teal-900 group-hover:underline flex items-center gap-1">
                                    <span>Lihat Detail</span>
                                    <x-icon name="arrow-right" class="size-3 transition-transform group-hover:translate-x-0.5"/>
                                </span>
                            </div>
                            <div class="space-y-2 text-xs">
                                <template x-for="item in ord.items" :key="item.id || item.product_id">
                                    <div class="flex items-start justify-between">
                                        <div class="pr-2">
                                            <p class="font-semibold text-slate-800">
                                                <span class="text-teal-700 font-bold" x-text="item.quantity + 'x '"></span>
                                                <span x-text="item.name"></span>
                                            </p>
                                            <template x-if="item.note || item.notes">
                                                <p class="text-[11px] text-amber-700 italic mt-0.5" x-text="'Catatan: ' + (item.note || item.notes)"></p>
                                            </template>
                                        </div>
                                        <span class="font-bold text-slate-900 tabular-nums shrink-0" x-text="$rp(item.unit_price_cents * item.quantity)"></span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Total & Payment Badge --}}
                        <div class="py-2.5 flex items-center justify-between text-xs">
                            <div>
                                <template x-if="ord.payment_status === 'Paid'">
                                    <div class="flex items-center gap-1.5">
                                        <span class="size-2 rounded-full bg-emerald-500"></span>
                                        <span class="font-semibold text-emerald-700">QRIS Terverifikasi (Paid)</span>
                                    </div>
                                </template>
                                <template x-if="ord.payment_status !== 'Paid'">
                                    <div class="flex items-center gap-1.5">
                                        <span class="size-2 rounded-full bg-amber-500 animate-pulse"></span>
                                        <span class="font-semibold text-amber-700">Menunggu Pembayaran</span>
                                    </div>
                                </template>
                            </div>
                            <div class="text-right flex items-center gap-3">
                                <button type="button" @click.stop="openTableOrderDetail(ord)"
                                        class="text-[11px] font-bold text-teal-700 hover:text-teal-900 hover:underline">
                                    Detail
                                </button>
                                <div>
                                    <span class="text-slate-400 text-[11px]">Total:</span>
                                    <span class="font-bold text-slate-900 text-sm" x-text="$rp(ord.total_cents)"></span>
                                    <template x-if="ord.discount_cents > 0">
                                        <span class="block text-[10px] text-emerald-600 font-bold" x-text="'(Voucher -' + $rp(ord.discount_cents) + ')'"></span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Actions Stepper according to status --}}
                    <div class="mt-4 pt-3 border-t border-slate-100">
                        <template x-if="ord.fulfillment_status === 'New'">
                            <button type="button" @click="transitionTableOrder(ord.id, 'accept')"
                                    class="w-full py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs shadow-xs transition flex items-center justify-center gap-2">
                                <x-icon name="check" class="size-4"/>
                                <span>Terima Pesanan (Accept)</span>
                            </button>
                        </template>

                        <template x-if="ord.fulfillment_status === 'Accepted'">
                            <button type="button" @click="transitionTableOrder(ord.id, 'start-prepare')"
                                    class="w-full py-2.5 rounded-xl bg-sky-600 hover:bg-sky-700 text-white font-bold text-xs shadow-xs transition flex items-center justify-center gap-2">
                                <x-icon name="coffee" class="size-4"/>
                                <span>Mulai Siapkan (Start Preparing)</span>
                            </button>
                        </template>

                        <template x-if="ord.fulfillment_status === 'Preparing'">
                            <button type="button" @click="transitionTableOrder(ord.id, 'mark-ready')"
                                    class="w-full py-2.5 rounded-xl bg-[#4CB0AE] hover:bg-[#3BA09E] text-white font-bold text-xs shadow-xs transition flex items-center justify-center gap-2">
                                <x-icon name="check-circle" class="size-4"/>
                                <span x-text="ord.delivery_type === 'counter' ? ('Siap Diambil di Kasir • ' + (ord.customer_name || 'Pelanggan')) : ('Siap Diantar ke ' + (ord.table_label_snapshot || 'Meja') + ' • ' + (ord.customer_name || 'Pelanggan'))"></span>
                            </button>
                        </template>

                        <template x-if="ord.fulfillment_status === 'Ready'">
                            <button type="button" @click="transitionTableOrder(ord.id, 'mark-served')"
                                    class="w-full py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition flex items-center justify-center gap-2">
                                <x-icon name="utensils" class="size-4"/>
                                <span x-text="ord.delivery_type === 'counter' ? ('Selesai Diambil • ' + (ord.customer_name || 'Pelanggan')) : ('Antar & Sajikan ke ' + (ord.table_label_snapshot || 'Meja') + ' • ' + (ord.customer_name || 'Pelanggan'))"></span>
                            </button>
                        </template>

                        <template x-if="ord.fulfillment_status === 'ReviewRequired'">
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" @click="transitionTableOrder(ord.id, 'resolve-review')"
                                        class="py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition">
                                    Setujui Proses
                                </button>
                                <a href="/refunds" class="py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs text-center transition">
                                    Buka Refund
                                </a>
                            </div>
                        </template>

                        <template x-if="ord.fulfillment_status === 'Served'">
                            <div class="p-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-center">
                                <p class="text-xs font-bold text-emerald-800"
                                   x-text="ord.delivery_type === 'counter' ? '✓ Selesai diambil di kasir' : ('✓ Selesai disajikan ke ' + (ord.table_label_snapshot || 'Meja'))"></p>
                                <p class="text-[10px] text-emerald-600 mt-0.5">Pesanan sudah sampai ke pelanggan</p>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- ================= MODAL DETAIL PESANAN LENGKAP ================= --}}
    <div x-show="selectedTableOrder" x-cloak
         class="fixed inset-0 z-[80] grid place-items-center p-4 overflow-y-auto"
         role="dialog" aria-modal="true" aria-label="Detail Pesanan">
        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs transition-opacity"
             @click="closeTableOrderDetail()"
             x-transition:enter="fade-enter" x-transition:leave="fade-leave"></div>

        {{-- Modal Box --}}
        <div class="relative w-full max-w-2xl bg-white rounded-3xl shadow-2xl border border-slate-100 overflow-hidden my-8 z-10"
             x-transition:enter="pop-enter" x-transition:leave="pop-leave"
             @keydown.escape.window="closeTableOrderDetail()">
            <template x-if="selectedTableOrder">
                <div>
                    {{-- Modal Header --}}
                    <div class="p-6 bg-gradient-to-r from-teal-700 via-teal-800 to-[#2A6B6A] text-white flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-2.5 flex-wrap">
                                <span class="text-xs px-2.5 py-1 rounded-lg bg-white/20 font-bold backdrop-blur-xs"
                                      x-text="selectedTableOrder.table_label_snapshot || 'Meja'"></span>
                                <h2 class="text-xl font-black tracking-tight" x-text="selectedTableOrder.customer_name || 'Pelanggan'"></h2>
                                <span class="text-xs px-2.5 py-1 rounded-lg font-bold"
                                      :class="selectedTableOrder.delivery_type === 'counter' ? 'bg-amber-400 text-amber-950' : 'bg-emerald-400 text-emerald-950'"
                                      x-text="selectedTableOrder.delivery_type === 'counter' ? 'Ambil di Kasir' : 'Diantar ke Meja'"></span>
                            </div>
                            <template x-if="selectedTableOrder.customer">
                                <div class="flex items-center gap-3 mt-1.5 text-xs text-teal-100 font-medium flex-wrap">
                                    <span x-show="selectedTableOrder.customer?.phone" class="flex items-center gap-1">
                                        <span>📱</span>
                                        <span x-text="selectedTableOrder.customer?.phone"></span>
                                    </span>
                                    <span x-show="selectedTableOrder.customer?.email" class="flex items-center gap-1">
                                        <span>✉️</span>
                                        <span x-text="selectedTableOrder.customer?.email"></span>
                                    </span>
                                </div>
                            </template>
                            <div class="flex items-center gap-3 mt-2 text-xs text-teal-100 font-mono">
                                <span>No: <strong class="text-white font-bold" x-text="selectedTableOrder.order_code"></strong></span>
                                <span>•</span>
                                <span x-text="selectedTableOrder.created_at ? new Date(selectedTableOrder.created_at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : 'Hari ini'"></span>
                            </div>
                        </div>
                        <button type="button" @click="closeTableOrderDetail()"
                                class="size-8 rounded-xl bg-white/10 hover:bg-white/20 text-white grid place-items-center transition"
                                aria-label="Tutup detail pesanan">
                            <x-icon name="x" class="size-5"/>
                        </button>
                    </div>

                    {{-- Modal Body --}}
                    <div class="p-6 space-y-5 max-h-[70vh] overflow-y-auto nice-scroll">
                        {{-- Status & Payment Bar --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3.5 bg-slate-50 rounded-2xl border border-slate-100 text-xs">
                            <div class="flex items-center justify-between sm:justify-start gap-2">
                                <span class="text-slate-500 font-medium">Status Antrean:</span>
                                <span class="badge"
                                      :class="{
                                          'NotQueued': 'badge-amber',
                                          'New': 'badge-amber',
                                          'Accepted': 'badge-info',
                                          'Preparing': 'badge-info',
                                          'Ready': 'badge-positive',
                                          'Served': 'badge-neutral',
                                          'ReviewRequired': 'badge-danger'
                                      }[selectedTableOrder.fulfillment_status]"
                                      x-text="selectedTableOrder.fulfillment_status === 'NotQueued' ? 'Menunggu Bayar' : selectedTableOrder.fulfillment_status">
                                </span>
                            </div>
                            <div class="flex items-center justify-between sm:justify-end gap-2">
                                <span class="text-slate-500 font-medium">Pembayaran:</span>
                                <template x-if="selectedTableOrder.payment_status === 'Paid'">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-100 text-emerald-800 font-bold">
                                        <span class="size-2 rounded-full bg-emerald-500"></span>
                                        <span>QRIS Lunas (Paid)</span>
                                    </span>
                                </template>
                                <template x-if="selectedTableOrder.payment_status !== 'Paid'">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-100 text-amber-800 font-bold">
                                        <span class="size-2 rounded-full bg-amber-500 animate-pulse"></span>
                                        <span>Belum Bayar</span>
                                    </span>
                                </template>
                            </div>
                        </div>

                        {{-- Order General Note (if any) --}}
                        <template x-if="selectedTableOrder.notes">
                            <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-900">
                                <p class="font-bold mb-0.5 flex items-center gap-1.5">
                                    <x-icon name="file-text" class="size-3.5 text-amber-600"/>
                                    <span>Catatan Umum Pesanan:</span>
                                </p>
                                <p class="text-slate-700 italic" x-text="selectedTableOrder.notes"></p>
                            </div>
                        </template>

                        {{-- Items Table --}}
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2.5">Daftar Menu & Detail Produk</h4>
                            <div class="border border-slate-100 rounded-2xl overflow-hidden divide-y divide-slate-100">
                                <template x-for="(item, idx) in (selectedTableOrder.items || [])" :key="item.id || idx">
                                    <div class="p-3.5 hover:bg-slate-50/60 transition flex items-start justify-between gap-4 text-xs">
                                        <div class="flex-1">
                                            <div class="flex items-baseline gap-2">
                                                <span class="font-extrabold text-teal-800 text-sm" x-text="item.quantity + 'x'"></span>
                                                <span class="font-bold text-slate-900 text-sm" x-text="item.name"></span>
                                            </div>
                                            <template x-if="item.note || item.notes">
                                                <div class="mt-1.5 inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-50 border border-amber-200 text-amber-800 text-[11px] font-medium">
                                                    <span>Catatan:</span>
                                                    <span class="font-semibold" x-text="item.note || item.notes"></span>
                                                </div>
                                            </template>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <p class="font-bold text-slate-900 text-sm tabular-nums" x-text="$rp(item.unit_price_cents * item.quantity)"></p>
                                            <p class="text-[11px] text-slate-400 tabular-nums" x-text="'@ ' + $rp(item.unit_price_cents)"></p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Total Calculation Breakdown --}}
                        <div class="p-4 bg-slate-50 rounded-2xl space-y-2 text-xs border border-slate-100">
                            <div class="flex justify-between text-slate-600">
                                <span>Subtotal Menu</span>
                                <span class="font-semibold tabular-nums text-slate-800" x-text="$rp(selectedTableOrder.subtotal_cents || selectedTableOrder.total_cents)"></span>
                            </div>
                            <template x-if="selectedTableOrder.discount_cents > 0">
                                <div class="flex justify-between text-emerald-700 font-semibold">
                                    <span>Potongan Diskon / Voucher</span>
                                    <span class="tabular-nums" x-text="'-' + $rp(selectedTableOrder.discount_cents)"></span>
                                </div>
                            </template>
                            <template x-if="(selectedTableOrder.service_cents || selectedTableOrder.service_fee_cents) > 0">
                                <div class="flex justify-between text-slate-600">
                                    <span>Biaya Layanan Antar Meja</span>
                                    <span class="font-semibold tabular-nums text-slate-800" x-text="$rp(selectedTableOrder.service_cents || selectedTableOrder.service_fee_cents)"></span>
                                </div>
                            </template>
                            <template x-if="selectedTableOrder.tax_cents > 0">
                                <div class="flex justify-between text-slate-600">
                                    <span>Pajak (PB1)</span>
                                    <span class="font-semibold tabular-nums text-slate-800" x-text="$rp(selectedTableOrder.tax_cents)"></span>
                                </div>
                            </template>
                            <div class="border-t border-slate-200 pt-2 flex justify-between items-baseline font-black text-slate-900">
                                <span class="text-sm">Total Tagihan</span>
                                <span class="text-lg text-teal-800 tabular-nums" x-text="$rp(selectedTableOrder.total_cents)"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-3 flex-wrap">
                        <button type="button" @click="printTableOrderReceipt(selectedTableOrder)"
                                class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-bold flex items-center gap-1.5 shadow-2xs transition">
                            <x-icon name="printer" class="size-4 text-slate-500"/>
                            <span>Cetak Tiket Dapur</span>
                        </button>

                        <div class="flex items-center gap-2">
                            <template x-if="selectedTableOrder.fulfillment_status === 'New'">
                                <button type="button" @click="transitionTableOrder(selectedTableOrder.id, 'accept')"
                                        class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs transition shadow-xs">
                                    Terima Pesanan (Accept)
                                </button>
                            </template>
                            <template x-if="selectedTableOrder.fulfillment_status === 'Accepted'">
                                <button type="button" @click="transitionTableOrder(selectedTableOrder.id, 'start-prepare')"
                                        class="px-4 py-2 rounded-xl bg-sky-600 hover:bg-sky-700 text-white font-bold text-xs transition shadow-xs">
                                    Mulai Siapkan (Start Preparing)
                                </button>
                            </template>
                            <template x-if="selectedTableOrder.fulfillment_status === 'Preparing'">
                                <button type="button" @click="transitionTableOrder(selectedTableOrder.id, 'mark-ready')"
                                        class="px-4 py-2 rounded-xl bg-[#4CB0AE] hover:bg-[#3BA09E] text-white font-bold text-xs transition shadow-xs">
                                    Tandai Siap (Mark Ready)
                                </button>
                            </template>
                            <template x-if="selectedTableOrder.fulfillment_status === 'Ready'">
                                <button type="button" @click="transitionTableOrder(selectedTableOrder.id, 'mark-served')"
                                        class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition shadow-xs">
                                    Selesai Disajikan (Served)
                                </button>
                            </template>
                            <button type="button" class="btn btn-secondary text-xs" @click="closeTableOrderDetail()">
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection
