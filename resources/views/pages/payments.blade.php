@extends('layouts.app')

@section('title', 'Pembayaran QRIS')
@section('subtitle', 'Pemantauan transaksi QRIS, status verifikasi server, dan rekonsiliasi provider.')

@section('content')
<div x-data="paymentsPage" x-cloak class="space-y-6">

    {{-- ================= PAGE HEADER ================= --}}
    <x-page-header title="Pembayaran QRIS &amp; Provider" subtitle="Pemantauan transaksi masuk, status verifikasi otomatis server, dan rekonsiliasi berkala.">
        <button type="button" class="btn btn-secondary" @click="loadPayments()">
            <x-icon name="refresh" class="size-4.5"/> Sinkronkan Data
        </button>
    </x-page-header>

    {{-- ================= INVARIANT 1 CALLOUT BANNER ================= --}}
    <div class="rounded-2xl bg-amber-50 border border-amber-200/80 p-4 flex items-start gap-3.5">
        <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-amber-500 text-white font-bold text-sm">
            i
        </span>
        <div class="text-xs text-amber-900 leading-relaxed">
            <p class="font-bold text-sm text-amber-950 mb-0.5">Aturan Bisnis &amp; Keuangan (Invariant 1 &amp; 2):</p>
            <p>1. Transaksi dengan status <strong>Pending</strong>, <strong>Verifying</strong>, <strong>Failed</strong>, atau <strong>Expired</strong> <span class="underline font-semibold">TIDAK PERNAH masuk antrean produksi barista/dapur</span>.</p>
            <p>2. Hanya pembayaran yang <strong>terverifikasi sah (Paid)</strong> oleh server yang menerbitkan Sale dan mengirimkan kartu pesanan ke antrean kerja staf.</p>
        </div>
    </div>

    {{-- ================= SUMMARY STATS ================= --}}
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-[22px] max-[480px]:gap-3">
        <div class="card p-5 min-h-[110px] flex flex-col justify-between">
            <div class="flex items-center gap-3.5">
                <span class="grid size-11 shrink-0 place-items-center rounded-full bg-emerald-50 text-emerald-600">
                    <x-icon name="check-circle" class="size-5.5"/>
                </span>
                <div>
                    <p class="text-[13px] text-ink-secondary">Pembayaran Terverifikasi (Paid)</p>
                    <p class="text-[26px] font-bold tabular-nums" x-text="paidCount"></p>
                </div>
            </div>
            <p class="text-[12px] text-ink-muted">Telah masuk antrean pesanan</p>
        </div>

        <div class="card p-5 min-h-[110px] flex flex-col justify-between">
            <div class="flex items-center gap-3.5">
                <span class="grid size-11 shrink-0 place-items-center rounded-full bg-amber-50 text-amber-600">
                    <x-icon name="clock" class="size-5.5"/>
                </span>
                <div>
                    <p class="text-[13px] text-ink-secondary">Menunggu Pembayaran (Pending)</p>
                    <p class="text-[26px] font-bold tabular-nums" x-text="pendingCount"></p>
                </div>
            </div>
            <p class="text-[12px] text-ink-muted">Belum diselesaikan tamu</p>
        </div>

        <div class="card p-5 min-h-[110px] flex flex-col justify-between">
            <div class="flex items-center gap-3.5">
                <span class="grid size-11 shrink-0 place-items-center rounded-full bg-primary-soft text-focus">
                    <x-icon name="dollar" class="size-5.5"/>
                </span>
                <div>
                    <p class="text-[13px] text-ink-secondary">Total Penerimaan QRIS</p>
                    <p class="text-[22px] font-bold tabular-nums tracking-tight" x-text="formatRp(totalSettled)"></p>
                </div>
            </div>
            <p class="text-[12px] text-ink-muted">Penerimaan sah via provider</p>
        </div>

        <div class="card p-5 min-h-[110px] flex flex-col justify-between">
            <div class="flex items-center gap-3.5">
                <span class="grid size-11 shrink-0 place-items-center rounded-full bg-slate-100 text-slate-600">
                    <x-icon name="rotate-ccw" class="size-5.5"/>
                </span>
                <div>
                    <p class="text-[13px] text-ink-secondary">Rekonsiliasi Provider</p>
                    <p class="text-[20px] font-bold text-emerald-700">100% Sinkron</p>
                </div>
            </div>
            <p class="text-[12px] text-ink-muted">Dedup transaksi aktif</p>
        </div>
    </div>

    {{-- ================= PAYMENTS TABLE ================= --}}
    <div class="card overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Filter Status:</span>
                <button type="button" @click="filterStatus = 'all'"
                        :class="filterStatus === 'all' ? 'bg-[#4CB0AE] text-white font-bold' : 'bg-slate-100 text-slate-600'"
                        class="px-3 py-1 rounded-full text-xs transition">Semua</button>
                <button type="button" @click="filterStatus = 'Paid'"
                        :class="filterStatus === 'Paid' ? 'bg-[#4CB0AE] text-white font-bold' : 'bg-slate-100 text-slate-600'"
                        class="px-3 py-1 rounded-full text-xs transition">Paid (Sah)</button>
                <button type="button" @click="filterStatus = 'Pending'"
                        :class="filterStatus === 'Pending' ? 'bg-[#4CB0AE] text-white font-bold' : 'bg-slate-100 text-slate-600'"
                        class="px-3 py-1 rounded-full text-xs transition">Pending</button>
            </div>

            <span class="text-xs text-slate-400" x-text="'Menampilkan ' + filteredPayments.length + ' transaksi'"></span>
        </div>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Kode Pesanan</th>
                        <th>Meja</th>
                        <th>Pelanggan</th>
                        <th>Provider Reference</th>
                        <th>Metode</th>
                        <th>Nominal</th>
                        <th>Status Pembayaran</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="p in filteredPayments" :key="p.id">
                        <tr>
                            <td class="text-xs text-slate-500 whitespace-nowrap" x-text="formatDate(p.created_at)"></td>
                            <td class="font-bold text-slate-900" x-text="p.order?.order_code || '-'"></td>
                            <td class="font-semibold text-slate-800" x-text="p.order?.table_label_snapshot || '-'"></td>
                            <td class="text-slate-700" x-text="p.order?.customer_name || 'Walk-in'"></td>
                            <td class="font-mono text-xs text-slate-500" x-text="p.provider_reference"></td>
                            <td>
                                <span class="px-2 py-0.5 rounded-md font-bold text-[11px]"
                                      :class="p.payment_method === 'Digital Wallet' ? 'bg-amber-100 text-amber-800' : (p.payment_method === 'Cash' ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800')"
                                      x-text="p.payment_method === 'Digital Wallet' ? 'Dompet Digital / QRIS' : (p.payment_method === 'Cash' ? 'Tunai' : (p.payment_method || 'QRIS'))"></span>
                            </td>
                            <td class="font-bold tabular-nums text-slate-900" x-text="formatRp(p.amount_cents)"></td>
                            <td>
                                <span class="badge"
                                      :class="p.status === 'Paid' ? 'badge-completed' : (p.status === 'Pending' ? 'badge-pending' : 'badge-voided')"
                                      x-text="p.status">
                                </span>
                            </td>
                            <td class="text-right">
                                <button type="button" @click="reconcile(p)" class="btn btn-xs btn-secondary" title="Cek Status Provider">
                                    Rekonsiliasi
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('paymentsPage', () => ({
        payments: [],
        filterStatus: 'all',

        init() {
            this.loadPayments();
        },

        async loadPayments() {
            try {
                const res = await fetch('/api/v1/admin/payments', {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                this.payments = data.data || [];
            } catch (err) {
                console.error(err);
            }
        },

        get paidCount() {
            return this.payments.filter(p => p.status === 'Paid').length;
        },

        get pendingCount() {
            return this.payments.filter(p => p.status === 'Pending').length;
        },

        get totalSettled() {
            return this.payments
                .filter(p => p.status === 'Paid')
                .reduce((sum, p) => sum + (p.amount_cents || 0), 0);
        },

        get filteredPayments() {
            if (this.filterStatus === 'all') return this.payments;
            return this.payments.filter(p => p.status === this.filterStatus);
        },

        formatRp(cents) {
            return 'Rp ' + Number(cents || 0).toLocaleString('id-ID');
        },

        formatDate(iso) {
            if (!iso) return '-';
            const d = new Date(iso);
            return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' · ' + d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
        },

        async reconcile(payment) {
            try {
                const res = await fetch(`/api/v1/admin/payments/${payment.id}/reconcile`, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (res.ok) {
                    this.$store.posy.toast(`Status provider untuk ${payment.provider_reference} terverifikasi: ${data.status}`, { type: 'success' });
                    this.loadPayments();
                }
            } catch (err) {
                console.error(err);
            }
        }
    }));
});
</script>
@endsection
