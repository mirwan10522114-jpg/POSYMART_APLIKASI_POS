/**
 * PosyMart — Halaman Penjualan (design.md §17).
 * Riwayat transaksi, filter, detail drawer, void, struk, ekspor CSV.
 */

import Alpine from 'alpinejs';
import { formatRp, moneyPlain, roundHalfUp } from '../utils/money.js';
import { fmtDateTime, fmtDate, zonedDateKey, dateKeysBetween, makeIso, todayKey, daysAgoKey, startOfMonthKey } from '../utils/date.js';
import { toCsv, downloadFile, printHtml, generate80mmReceiptHtml } from '../utils/files.js';

Alpine.data('salesPage', () => ({
    // filter
    query: '',
    datePreset: 'all', // all | today | last7 | month | custom
    orderTypeFilter: 'all', // all | dine_in | takeaway
    customStart: todayKey(),
    customEnd: todayKey(),
    customStartInput: todayKey(),
    customEndInput: todayKey(),
    customOpen: false,
    customError: '',
    status: 'all',
    payment: 'all',
    cashier: 'all',
    moreOpen: false,
    // sort & pagination
    sortKey: 'date',
    sortDir: 'desc',
    page: 1,
    pageSize: 10,
    // drawer & dialog
    drawerId: null,
    kebabRowId: null,
    voidOpen: false,
    voidTarget: null,
    voidReason: '',
    voidState: 'idle',
    loaded: false,

    async init() {
        this.$nextTick(() => (this.loaded = true));
        if (this.$store.posy.syncSalesWithApi) {
            await this.$store.posy.syncSalesWithApi();
        }
        const params = new URLSearchParams(window.location.search);
        const id = params.get('sale');
        if (id) {
            const tryOpen = () => {
                if (this.$store.posy.sale(id)) {
                    this.drawerId = id;
                    this.drawerNotFound = false;
                    return true;
                }
                return false;
            };

            if (!tryOpen()) {
                setTimeout(async () => {
                    if (this.$store.posy.syncSalesWithApi) {
                        await this.$store.posy.syncSalesWithApi();
                    }
                    if (!tryOpen()) {
                        this.drawerNotFound = true;
                    }
                }, 350);
            }
        }
    },

    drawerNotFound: false,

    get sales() {
        return this.$store.posy.sales;
    },

    get rangeStart() {
        if (this.datePreset === 'today') return todayKey();
        if (this.datePreset === 'last7') return daysAgoKey(6);
        if (this.datePreset === 'month') return startOfMonthKey();
        if (this.datePreset === 'custom') return this.customStart;
        return null;
    },

    get rangeEnd() {
        if (this.datePreset === 'all') return null;
        if (this.datePreset === 'custom') return this.customEnd;
        return todayKey();
    },

    get rangeLabel() {
        if (this.datePreset === 'today') return 'Hari Ini';
        if (this.datePreset === 'last7') return '7 Hari Terakhir';
        if (this.datePreset === 'month') return 'Bulan Ini';
        if (this.datePreset === 'all') return 'Semua Waktu';
        return `${fmtDate(this.customStart + 'T00:00:00+07:00')} – ${fmtDate(this.customEnd + 'T00:00:00+07:00')}`;
    },

    get filtered() {
        const q = this.query.trim().toLowerCase();
        const start = this.rangeStart;
        const end = this.rangeEnd;
        return this.sales.filter((s) => {
            if (start && zonedDateKey(s.createdAt) < start) return false;
            if (end && zonedDateKey(s.createdAt) > end) return false;
            if (this.orderTypeFilter === 'dine_in' && s.orderType === 'takeaway') return false;
            if (this.orderTypeFilter === 'takeaway' && s.orderType !== 'takeaway') return false;
            if (this.status === 'completed' && s.status !== 'Completed') return false;
            if (this.status === 'voided' && s.status !== 'Voided') return false;
            if (this.payment === 'cash' && s.paymentMethod !== 'Cash') return false;
            if (this.payment === 'card' && s.paymentMethod !== 'Card') return false;
            if (this.payment === 'wallet' && s.paymentMethod !== 'Digital Wallet') return false;
            if (this.cashier !== 'all' && s.cashierNameSnapshot !== this.cashier) return false;
            if (q) {
                const hay = `${s.id} ${s.customerNameSnapshot} ${s.cashierNameSnapshot} ${s.tableNumber || ''}`.toLowerCase();
                if (!hay.includes(q)) return false;
            }
            return true;
        });
    },

    get sorted() {
        const arr = [...this.filtered];
        const dir = this.sortDir === 'asc' ? 1 : -1;
        arr.sort((a, b) => {
            let r = 0;
            if (this.sortKey === 'date') r = a.createdAt.localeCompare(b.createdAt);
            else if (this.sortKey === 'total') r = a.total - b.total;
            if (r === 0) r = a.id.localeCompare(b.id);
            return r * dir;
        });
        return arr;
    },

    get totalRows() {
        return this.sorted.length;
    },

    get totalPages() {
        return Math.max(1, Math.ceil(this.totalRows / this.pageSize));
    },

    get pageNumbers() {
        const n = this.totalPages;
        const cur = this.page;
        const set = new Set([1, n, cur - 1, cur, cur + 1]);
        return [...set].filter((x) => x >= 1 && x <= n).sort((a, b) => a - b);
    },

    get paged() {
        if (this.page > this.totalPages) this.page = this.totalPages;
        return this.sorted.slice((this.page - 1) * this.pageSize, this.page * this.pageSize);
    },

    get stats() {
        const completed = this.filtered.filter((s) => s.status === 'Completed');
        const total = completed.reduce((a, s) => a + s.total, 0);
        const avg = completed.length ? roundHalfUp(total / completed.length) : 0;
        return {
            total,
            completed: completed.length,
            avg,
            voided: this.filtered.filter((s) => s.status === 'Voided').length,
        };
    },

    get cashiers() {
        return [...new Set(this.sales.map((s) => s.cashierNameSnapshot))];
    },

    unitCount(s) {
        return s.items.reduce((a, it) => a + it.quantity, 0);
    },

    setSort(key) {
        if (this.sortKey === key) {
            this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortKey = key;
            this.sortDir = key === 'date' ? 'desc' : 'asc';
        }
        this.page = 1;
    },

    resetPage() {
        this.page = 1;
    },

    clearFilters() {
        this.query = '';
        this.datePreset = 'all';
        this.orderTypeFilter = 'all';
        this.customStart = this.customEnd = todayKey();
        this.status = this.payment = this.cashier = 'all';
        this.page = 1;
    },

    applyCustom() {
        if (!this.customStartInput || !this.customEndInput) {
            this.customError = 'Tanggal mulai dan akhir wajib diisi.';
            return;
        }
        if (this.customStartInput > this.customEndInput) {
            this.customError = 'Tanggal akhir harus sama atau setelah tanggal mulai.';
            return;
        }
        this.customError = '';
        this.customStart = this.customStartInput;
        this.customEnd = this.customEndInput;
        this.datePreset = 'custom';
        this.customOpen = false;
        this.page = 1;
    },

    get drawerSale() {
        return this.drawerId ? this.$store.posy.sale(this.drawerId) : null;
    },

    get drawerRefunds() {
        if (!this.drawerId) return [];
        return this.$store.posy.returns.filter((r) => r.saleId === this.drawerId && r.status === 'Completed');
    },

    get refundedTotal() {
        return this.drawerRefunds.reduce((a, r) => a + r.refundTotal, 0);
    },

    openDrawer(id) {
        this.drawerId = id;
        this.drawerNotFound = false;
        this.kebabRowId = null;
        const url = new URL(window.location);
        url.searchParams.set('sale', id);
        window.history.replaceState({}, '', url);
        this.$nextTick(() => PM.lockBody(true));
    },

    closeDrawer() {
        this.drawerId = null;
        this.drawerNotFound = false;
        const url = new URL(window.location);
        url.searchParams.delete('sale');
        window.history.replaceState({}, '', url);
        this.$nextTick(() => PM.lockBody(false));
    },

    get canVoid() {
        return (sale) => {
            if (!this.$store.posy.can('voidSales')) return { ok: false, reason: 'Hanya Admin/Manager yang dapat membatalkan penjualan.' };
            if (sale.status !== 'Completed') return { ok: false, reason: 'Penjualan yang sudah dibatalkan tidak dapat dibatalkan lagi.' };
            const hasActiveReturn = this.$store.posy.returns.some(
                (r) => r.saleId === sale.id && (r.status === 'Pending' || r.status === 'Completed')
            );
            if (hasActiveReturn) return { ok: false, reason: 'Terdapat permintaan retur aktif untuk transaksi ini.' };
            return { ok: true };
        };
    },

    openVoid(sale) {
        this.voidTarget = sale;
        this.voidReason = '';
        this.voidState = 'idle';
        this.voidOpen = true;
        this.kebabRowId = null;
    },

    async confirmVoid() {
        const reason = this.voidReason.trim();
        if (reason.length < 3 || reason.length > 300) return;
        this.voidState = 'processing';
        await new Promise((r) => setTimeout(r, 700));
        const ok = this.$store.posy.voidSale(this.voidTarget.id, reason);
        if (ok) {
            this.voidOpen = false;
            this.voidState = 'idle';
            this.$store.posy.toast('Penjualan dibatalkan');
        } else {
            this.voidState = 'error';
        }
    },

    downloadReceipt(sale) {
        const set = this.$store.posy.settings || {};
        const methodLabel = sale.paymentMethod === 'Cash'
            ? 'Tunai (Cash)'
            : sale.paymentMethod === 'Card'
            ? 'Kartu (EDC)'
            : 'Dompet Digital (QRIS)';

        const html = generate80mmReceiptHtml({
            storeName: set.storeName || 'PosyMart Coffee & Eatery',
            storeAddress: set.receiptShowAddress ? `${set.streetAddress || ''}, ${set.city || ''} ${set.postalCode || ''}`.trim().replace(/^,\s*/, '') : '',
            storePhone: set.receiptShowAddress ? (set.storePhone || '') : '',
            receiptHeader: set.receiptHeader || 'PosyMart',
            receiptFooter: set.receiptFooter || 'Terima kasih telah berbelanja bersama kami!',
            saleId: sale.id,
            createdAt: sale.createdAt,
            orderType: sale.orderType,
            tableNumber: sale.tableNumber,
            customerName: sale.customerNameSnapshot || 'Pelanggan Umum',
            cashierName: set.receiptShowCashier ? (sale.cashierNameSnapshot || 'Kasir PosyMart') : '',
            items: sale.items || [],
            subtotal: sale.subtotal || 0,
            discount: sale.discount || 0,
            tax: sale.tax || 0,
            taxName: sale.taxNameSnapshot || set.taxName || 'Pajak PB1',
            taxRatePercent: (sale.taxRateBpsSnapshot ? sale.taxRateBpsSnapshot / 100 : set.taxRatePercent) || 0,
            total: sale.total || 0,
            paymentMethod: methodLabel,
            tenderedAmount: sale.tenderedAmount ?? (sale.paymentMethod === 'Cash' ? sale.total : null),
            changeAmount: sale.changeAmount ?? 0,
            status: sale.status || 'Completed',
            note: sale.note || '',
            width: set.receiptWidth || '80 mm',
        });

        printHtml(`Struk ${sale.id}`, html, { width: set.receiptWidth || '80 mm' });
    },

    exportCsv() {
        const headers = ['transaction_id', 'date_time', 'order_type', 'table', 'customer', 'cashier', 'items', 'payment_method', 'status', 'subtotal', 'discount', 'tax', 'total'];
        const rows = this.sorted.map((s) => [
            s.id,
            fmtDateTime(s.createdAt),
            s.orderType === 'takeaway' ? 'Takeaway' : 'Dine In',
            s.tableNumber || '',
            s.customerNameSnapshot,
            s.cashierNameSnapshot,
            this.unitCount(s),
            s.paymentMethod,
            s.status,
            moneyPlain(s.subtotal),
            moneyPlain(s.discount),
            moneyPlain(s.tax),
            moneyPlain(s.total),
        ]);
        downloadFile(`posymart-sales-${todayKey()}.csv`, toCsv(headers, rows));
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
