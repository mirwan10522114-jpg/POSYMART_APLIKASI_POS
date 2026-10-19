@extends('layouts.app')

@section('title', 'Pengembalian Dana (Refunds)')
@section('subtitle', 'Persetujuan dan pemantauan pengembalian dana transaksi QRIS.')

@section('content')
<div x-data="refundsPage" x-cloak class="space-y-6">

    <x-page-header title="Pengembalian Dana (Refunds)" subtitle="Daftar permintaan refund, persetujuan admin, dan rekonsiliasi pengembalian dana ke pelanggan.">
        <button type="button" class="btn btn-secondary" @click="loadRefunds()">
            <x-icon name="refresh" class="size-4.5"/> Segarkan Data
        </button>
    </x-page-header>

    {{-- Rules Banner --}}
    <div class="rounded-2xl bg-slate-50 border border-slate-200 p-4 text-xs text-slate-700 space-y-1">
        <p class="font-bold text-slate-900 text-sm">Ketentuan Refund Meja (PRD Bagian 13):</p>
        <p>• Pembatalan pesanan Paid mewajibkan pencatatan alasan dan persetujuan Admin/Manager.</p>
        <p>• Minuman atau makanan yang sudah dibuat tidak otomatis dimasukkan kembali ke stok fisik (restock).</p>
        <p>• Refund tidak menghapus catatan penerimaan awal atau menghilangkan Order dari ledger keuangan.</p>
    </div>

    {{-- Refunds Table --}}
    <div class="card overflow-hidden">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Waktu Request</th>
                        <th>Kode Pesanan</th>
                        <th>Nominal Refund</th>
                        <th>Alasan Pembatalan</th>
                        <th>Diminta Oleh</th>
                        <th>Status Refund</th>
                        <th>Disetujui Oleh</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="r in refunds" :key="r.id">
                        <tr>
                            <td class="text-xs text-slate-500 whitespace-nowrap" x-text="formatDate(r.created_at)"></td>
                            <td class="font-bold text-slate-900" x-text="r.order?.order_code || r.order_id"></td>
                            <td class="font-bold text-slate-900 tabular-nums" x-text="formatRp(r.amount_cents)"></td>
                            <td class="text-xs text-slate-700 max-w-[200px] truncate" x-text="r.reason"></td>
                            <td class="text-xs text-slate-600" x-text="r.requested_by || 'Staf'"></td>
                            <td>
                                <span class="badge"
                                      :class="r.status === 'Succeeded' ? 'badge-completed' : (r.status === 'Requested' ? 'badge-pending' : 'badge-voided')"
                                      x-text="r.status">
                                </span>
                            </td>
                            <td class="text-xs text-slate-600" x-text="r.approved_by || '-'"></td>
                            <td class="text-right">
                                <template x-if="r.status === 'Requested'">
                                    <button type="button" @click="approveRefund(r)" class="btn btn-xs btn-primary">
                                        Setujui Refund
                                    </button>
                                </template>
                                <template x-if="r.status === 'Succeeded'">
                                    <span class="text-xs font-semibold text-emerald-600">Selesai</span>
                                </template>
                            </td>
                        </tr>
                    </template>
                    <template x-if="refunds.length === 0">
                        <tr>
                            <td colspan="8" class="text-center py-10 text-slate-400 text-xs">
                                Belum ada permohonan pengembalian dana (refund).
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
    Alpine.data('refundsPage', () => ({
        refunds: [],

        init() {
            this.loadRefunds();
        },

        async loadRefunds() {
            try {
                const res = await fetch('/api/v1/admin/refunds', {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                this.refunds = data.data || [];
            } catch (err) {
                console.error(err);
            }
        },

        formatRp(cents) {
            return 'Rp ' + Number(cents || 0).toLocaleString('id-ID');
        },

        formatDate(iso) {
            if (!iso) return '-';
            const d = new Date(iso);
            return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' · ' + d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
        },

        async approveRefund(r) {
            if (!confirm(`Setujui pengembalian dana sebesar ${this.formatRp(r.amount_cents)} untuk ${r.order?.order_code}?`)) return;

            try {
                const res = await fetch(`/api/v1/admin/refunds/${r.id}/approve`, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (res.ok) {
                    this.$store.posy.toast(data.message, { type: 'success' });
                    this.loadRefunds();
                }
            } catch (err) {
                console.error(err);
            }
        }
    }));
});
</script>
@endsection
