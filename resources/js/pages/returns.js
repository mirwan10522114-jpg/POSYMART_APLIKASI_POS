/**
 * PosyMart — Halaman Retur (design.md §22).
 * Wizard 3 langkah, approve/reject, alokasi refund per unit.
 */

import Alpine from 'alpinejs';
import { RETURN_REASONS, RETURN_CONDITIONS } from '../data/seed.js';
import { moneyPlain } from '../utils/money.js';
import { fmtDateTime, zonedDateKey, todayKey, daysAgoKey } from '../utils/date.js';
import { toCsv, downloadFile, printHtml } from '../utils/files.js';

Alpine.data('returnsPage', () => ({
    tabs: [['all', 'Semua'], ['Draft', 'Draf'], ['Pending', 'Menunggu'], ['Completed', 'Selesai'], ['Rejected', 'Ditolak']],
    reasons: RETURN_REASONS,
    conditions: RETURN_CONDITIONS,
    tab: 'all',
    query: '',
    datePreset: 'all',
    method: 'all',
    sortBy: 'newest',
    page: 1,
    pageSize: 10,
    // wizard
    wizardOpen: false,
    step: 1,
    saleQuery: '',
    foundSale: null,
    findError: '',
    picks: [], // { productId, name, unitPrice, quantity, available, condition, restock, refundedUnits }
    reason: '',
    otherNotes: '',
    notes: '',
    wizardErrors: {},
    saving: false,
    // detail
    detailId: null,
    // aksi
    approveOpen: false,
    approveTarget: null,
    approveState: 'idle',
    rejectOpen: false,
    rejectTarget: null,
    rejectReason: '',
    deleteOpen: false,
    deleteTarget: null,
    loaded: false,

    init() {
        this.$nextTick(() => (this.loaded = true));
        const params = new URLSearchParams(window.location.search);
        if (params.get('sale')) {
            this.wizardOpen = true;
            this.saleQuery = params.get('sale');
            this.findSale();
            this.$nextTick(() => PM.lockBody(true));
        }
    },

    get returns() {
        return this.$store.posy.returns;
    },

    get statusLabel() {
        return (s) => Object.fromEntries(this.tabs)[s] ?? s;
    },

    get baseFiltered() {
        const q = this.query.trim().toLowerCase();
        return this.returns.filter((r) => {
            if (this.method !== 'all' && r.originalMethod !== this.method) return false;
            if (this.datePreset === 'today' && zonedDateKey(r.requestedAt) !== todayKey()) return false;
            if (this.datePreset === 'last7' && zonedDateKey(r.requestedAt) < daysAgoKey(6)) return false;
            if (q) {
                const hay = `${r.id} ${r.saleId} ${r.customerName}`.toLowerCase();
                if (!hay.includes(q)) return false;
            }
            return true;
        });
    },

    get tabCounts() {
        const counts = { all: this.baseFiltered.length };
        for (const [key] of this.tabs.slice(1)) counts[key] = this.baseFiltered.filter((r) => r.status === key).length;
        return counts;
    },

    get filtered() {
        const arr = this.tab === 'all' ? this.baseFiltered : this.baseFiltered.filter((r) => r.status === this.tab);
        const withDir = [...arr];
        switch (this.sortBy) {
            case 'oldest': withDir.sort((a, b) => a.requestedAt.localeCompare(b.requestedAt) || a.id.localeCompare(b.id)); break;
            case 'refund-desc': withDir.sort((a, b) => b.refundTotal - a.refundTotal || a.id.localeCompare(b.id)); break;
            case 'refund-asc': withDir.sort((a, b) => a.refundTotal - b.refundTotal || a.id.localeCompare(b.id)); break;
            default: withDir.sort((a, b) => b.requestedAt.localeCompare(a.requestedAt) || a.id.localeCompare(b.id));
        }
        return withDir;
    },

    get stats() {
        const completed = this.baseFiltered.filter((r) => r.status === 'Completed');
        return {
            requests: this.baseFiltered.length,
            pending: this.baseFiltered.filter((r) => r.status === 'Pending').length,
            completedRefunds: completed.reduce((a, r) => a + r.refundTotal, 0),
            units: completed.reduce((a, r) => a + r.unitQuantity, 0),
        };
    },

    get totalRows() {
        return this.filtered.length;
    },

    get totalPages() {
        return Math.max(1, Math.ceil(this.totalRows / this.pageSize));
    },

    get pageNumbers() {
        const n = this.totalPages;
        return [...new Set([1, n, this.page - 1, this.page, this.page + 1])].filter((x) => x >= 1 && x <= n).sort((a, b) => a - b);
    },

    get paged() {
        if (this.page > this.totalPages) this.page = this.totalPages;
        return this.filtered.slice((this.page - 1) * this.pageSize, this.page * this.pageSize);
    },

    resetPage() {
        this.page = 1;
    },

    resetFilters() {
        this.query = '';
        this.datePreset = 'all';
        this.method = 'all';
        this.sortBy = 'newest';
        this.tab = 'all';
        this.page = 1;
    },

    /* ---------------- wizard ---------------- */
    openWizard() {
        this.wizardOpen = true;
        this.step = 1;
        this.saleQuery = '';
        this.foundSale = null;
        this.findError = '';
        this.picks = [];
        this.reason = '';
        this.otherNotes = '';
        this.notes = '';
        this.wizardErrors = {};
        this.$nextTick(() => PM.lockBody(true));
    },

    closeWizard() {
        this.wizardOpen = false;
        const url = new URL(window.location);
        url.searchParams.delete('sale');
        window.history.replaceState({}, '', url);
        this.$nextTick(() => PM.lockBody(false));
    },

    findSale() {
        this.findError = '';
        this.foundSale = null;
        const id = this.saleQuery.trim().toUpperCase();
        if (!id) {
            this.findError = 'Masukkan ID transaksi atau nomor struk.';
            return;
        }
        const sale = this.$store.posy.sale(id);
        if (!sale) {
            this.findError = 'Transaksi tidak ditemukan.';
            return;
        }
        if (sale.status !== 'Completed') {
            this.findError = 'Hanya transaksi selesai yang dapat diretur.';
            return;
        }
        // jendela retur: 14 hari inklusif
        const days = Math.round((Date.now() - new Date(sale.createdAt).getTime()) / 86400000);
        if (days > 14) {
            this.findError = 'Jendela retur: 14 hari — transaksi ini sudah lewat batas.';
            return;
        }
        const anyAvailable = sale.items.some((it) => this.$store.posy.availableUnits(sale.id, it.productId) > 0);
        if (!anyAvailable) {
            this.findError = 'Transaksi ini tidak memiliki item yang bisa direfund.';
            return;
        }
        this.foundSale = sale;
        this.picks = sale.items
            .map((it) => {
                const available = this.$store.posy.availableUnits(sale.id, it.productId);
                return {
                    productId: it.productId,
                    saleItemId: it.id,
                    name: it.name,
                    unitPrice: it.unitPrice,
                    sold: it.quantity,
                    previouslyReturned: it.quantity - available,
                    available,
                    quantity: 0,
                    checked: false,
                    condition: 'Unopened',
                    restock: false,
                    item: it,
                };
            })
            .filter((p) => p.available > 0 || p.previouslyReturned > 0);
    },

    togglePick(pick) {
        pick.checked = !pick.checked;
        if (pick.checked && pick.quantity === 0) pick.quantity = 1;
        if (!pick.checked) pick.quantity = 0;
    },

    get pickRefund() {
        // hitung refund item terpilih berdasar alokasi unit
        let net = 0, tax = 0, qty = 0;
        for (const p of this.picks) {
            if (!p.checked || p.quantity < 1) continue;
            const units = p.item.unitAllocations.slice(0, p.quantity);
            net += units.reduce((a, u) => a + u.netCents, 0);
            tax += units.reduce((a, u) => a + u.taxCents, 0);
            qty += p.quantity;
        }
        return { net, tax, total: net + tax, qty };
    },

    get alreadyRefunded() {
        if (!this.foundSale) return 0;
        return this.$store.posy.returns
            .filter((r) => r.saleId === this.foundSale.id && r.status === 'Completed')
            .reduce((a, r) => a + r.refundTotal, 0);
    },

    nextStep() {
        if (this.step === 1) {
            if (!this.foundSale) {
                this.findError = 'Cari transaksi terlebih dahulu.';
                return;
            }
            this.step = 2;
            return;
        }
        if (this.step === 2) {
            const any = this.picks.some((p) => p.checked && p.quantity >= 1);
            if (!any) {
                this.wizardErrors.step2 = 'Pilih minimal satu unit untuk diretur.';
                return;
            }
            const over = this.picks.find((p) => p.checked && (p.quantity < 1 || p.quantity > p.available));
            if (over) {
                this.wizardErrors.step2 = `Jumlah retur ${over.name} harus antara 1 dan ${over.available}.`;
                return;
            }
            this.wizardErrors.step2 = '';
            this.step = 3;
            return;
        }
    },

    prevStep() {
        if (this.step > 1) this.step--;
    },

    validateReview() {
        const e = {};
        if (!this.reason) e.reason = 'Pilih alasan retur.';
        else if (this.reason === 'Lainnya' && this.otherNotes.trim().length < 10) e.reason = 'Jelaskan alasan lain minimal 10 karakter.';
        for (const p of this.picks.filter((x) => x.checked)) {
            if (!p.condition) e.condition = `Pilih kondisi untuk ${p.name}.`;
        }
        if ((this.notes ?? '').length > 500) e.notes = 'Maksimum 500 karakter.';
        this.wizardErrors = e;
        return Object.keys(e).length === 0;
    },

    buildPayload() {
        const items = this.picks
            .filter((p) => p.checked && p.quantity >= 1)
            .map((p) => {
                const units = p.item.unitAllocations.slice(0, p.quantity);
                const net = units.reduce((a, u) => a + u.netCents, 0);
                const tax = units.reduce((a, u) => a + u.taxCents, 0);
                return {
                    saleItemId: p.saleItemId,
                    productId: p.productId,
                    name: p.name,
                    quantity: p.quantity,
                    unitPrice: p.unitPrice,
                    refundNet: net,
                    refundTax: tax,
                    refundTotal: net + tax,
                    condition: p.condition,
                    restock: p.restock && p.condition === 'Unopened',
                    refundedUnits: units.map((u) => u.unitIndex),
                };
            });
        const { net, tax } = this.pickRefund;
        return {
            saleId: this.foundSale.id,
            customerName: this.foundSale.customerNameSnapshot,
            items,
            reason: this.reason === 'Lainnya' ? `Lainnya: ${this.otherNotes.trim()}` : this.reason,
            notes: this.notes.trim(),
            refundNet: net,
            refundTax: tax,
            originalMethod: this.foundSale.paymentMethod,
            originalDate: this.foundSale.createdAt,
        };
    },

    async saveWizard(submit) {
        if (!this.validateReview()) return;
        this.saving = true;
        await new Promise((r) => setTimeout(r, 400));
        const rec = this.$store.posy.saveReturnDraft(this.buildPayload());
        if (submit) this.$store.posy.submitReturn(rec.id);
        this.saving = false;
        this.wizardOpen = false;
        this.$store.posy.toast(submit ? 'Retur diajukan untuk peninjauan' : 'Draf retur disimpan');
        this.$nextTick(() => PM.lockBody(false));
    },

    /* ---------------- detail & aksi ---------------- */
    get detailReturn() {
        return this.detailId ? this.$store.posy.returnRec(this.detailId) : null;
    },

    openDetail(id) {
        this.detailId = id;
        this.$nextTick(() => PM.lockBody(true));
    },

    closeDetail() {
        this.detailId = null;
        this.$nextTick(() => PM.lockBody(false));
    },

    openApprove(id) {
        if (!this.$store.posy.can('approveReturns')) {
            this.$store.posy.toast('Hanya Admin/Manager yang dapat menyetujui refund.', { type: 'warning' });
            return;
        }
        this.approveTarget = this.$store.posy.returnRec(id);
        this.approveState = 'idle';
        this.approveOpen = true;
        this.detailId = null;
    },

    async confirmApprove() {
        this.approveState = 'processing';
        const res = await this.$store.posy.approveReturn(this.approveTarget.id);
        if (res.ok) {
            this.approveOpen = false;
            this.approveState = 'idle';
            this.$store.posy.toast(`Refund ${this.approveTarget.id} selesai`);
        } else {
            this.approveState = 'error';
        }
    },

    openReject(id) {
        if (!this.$store.posy.can('approveReturns')) {
            this.$store.posy.toast('Hanya Admin/Manager yang dapat menolak permintaan.', { type: 'warning' });
            return;
        }
        this.rejectTarget = this.$store.posy.returnRec(id);
        this.rejectReason = '';
        this.rejectOpen = true;
        this.detailId = null;
    },

    confirmReject() {
        const reason = this.rejectReason.trim();
        if (reason.length < 5 || reason.length > 200) return;
        const ok = this.$store.posy.rejectReturn(this.rejectTarget.id, reason);
        if (ok) {
            this.rejectOpen = false;
            this.$store.posy.toast('Permintaan retur ditolak');
        }
    },

    openDelete(id) {
        this.deleteTarget = this.$store.posy.returnRec(id);
        this.deleteOpen = true;
        this.detailId = null;
    },

    confirmDelete() {
        if (this.$store.posy.deleteReturnDraft(this.deleteTarget.id)) {
            this.deleteOpen = false;
            this.$store.posy.toast('Draf retur dihapus', { type: 'info' });
        }
    },

    duplicate(id) {
        const rec = this.$store.posy.duplicateReturnAsDraft(id);
        if (rec) this.$store.posy.toast(`Draf baru ${rec.id} dibuat`);
        this.detailId = null;
    },

    submitDraft(id) {
        if (this.$store.posy.submitReturn(id)) this.$store.posy.toast('Retur diajukan untuk peninjauan');
    },

    printRefundReceipt(r) {
        const rows = r.items
            .map((it) => `
                <div class="item-block">
                    <div class="item-name">${it.name} (${it.condition})</div>
                    <div class="item-math">
                        <span>${it.quantity} unit</span>
                        <span class="item-total">Rp${Number(it.refundTotal).toLocaleString('id-ID')}</span>
                    </div>
                </div>
            `).join('');

        const set = this.$store.posy.settings || {};
        const storeName = set.storeName || set.receiptHeader || 'PosyMart';

        const bodyHtml = `
            <div class="store-header">${storeName}</div>
            <div class="receipt-title">BUKTI STRUK REFUND · 80 MM</div>
            <div class="row"><span>No. Refund</span><span class="font-bold">${r.receiptNo ?? r.id}</span></div>
            <div class="row"><span>Transaksi Asal</span><span class="font-bold">${r.saleId}</span></div>
            <div class="row"><span>Tanggal/Waktu</span><span>${fmtDateTime(r.completedAt ?? r.requestedAt)}</span></div>
            <div class="row"><span>Pelanggan</span><span>${r.customerName || 'Pelanggan'}</span></div>
            <div class="line"></div>
            <div class="item-header"><span>ITEM DIREFUND</span><span>NOMINAL</span></div>
            <div class="line"></div>
            ${rows}
            <div class="line"></div>
            <div class="row"><span>Refund Neto</span><span>Rp${Number(r.refundNet).toLocaleString('id-ID')}</span></div>
            <div class="row"><span>Pajak Direfund</span><span>Rp${Number(r.refundTax).toLocaleString('id-ID')}</span></div>
            <div class="line-double"></div>
            <div class="total-row"><span>TOTAL REFUND</span><span>Rp${Number(r.refundTotal).toLocaleString('id-ID')}</span></div>
            <div class="line-double"></div>
            <div class="row"><span>Metode Pengembalian</span><span class="font-bold">${r.originalMethod || 'Tunai'}</span></div>
            <div class="row" style="margin-top: 4px;"><span>Status</span><span class="status-badge">REFUND SELESAI</span></div>
            <div class="line"></div>
            <div class="center store-footer">Struk sah pengembalian dana & barang PosyMart.</div>
            <div class="center muted" style="font-size: 9.5px; margin-top: 8px;">*** POSYMART COFFEE SHOP ***</div>
        `;
        printHtml(`Struk Refund ${r.id}`, bodyHtml, { width: '80 mm' });
    },

    exportCsv() {
        const headers = ['return_id', 'source_sale_id', 'customer', 'requested_at', 'completed_at', 'status', 'unit_quantity', 'refund_net', 'refund_tax', 'refund_total', 'original_method', 'reason'];
        const rows = this.filtered.map((r) => [
            r.id, r.saleId, r.customerName, fmtDateTime(r.requestedAt), r.completedAt ? fmtDateTime(r.completedAt) : '',
            r.status, r.unitQuantity, moneyPlain(r.refundNet), moneyPlain(r.refundTax), moneyPlain(r.refundTotal),
            r.originalMethod, r.reason,
        ]);
        downloadFile(`posymart-returns-${todayKey()}.csv`, toCsv(headers, rows));
        this.$store.posy.toast('CSV diunduh');
    },

    prev() {
        if (this.page > 1) this.page--;
    },

    next() {
        if (this.page < this.totalPages) this.page++;
    },

    setPage(n) {
        this.page = n;
    },

    setPageSize(n) {
        this.pageSize = Number(n);
        this.page = 1;
    },
}));
