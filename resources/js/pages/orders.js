/**
 * PosyMart — Halaman Pesanan (design.md §20).
 * Antrean pesanan, transisi status, Complete & Pay.
 */

import Alpine from 'alpinejs';
import { CATEGORIES } from '../data/seed.js';
import { moneyPlain } from '../utils/money.js';
import { fmtDateTime, zonedDateKey, todayKey, daysAgoKey } from '../utils/date.js';
import { toCsv, downloadFile, printHtml, generate80mmReceiptHtml } from '../utils/files.js';

const STATUS_TABS = [
    ['all', 'Semua'],
    ['Draft', 'Draf'],
    ['Pending', 'Menunggu'],
    ['Preparing', 'Disiapkan'],
    ['Ready', 'Siap'],
    ['Completed', 'Selesai'],
    ['Cancelled', 'Dibatalkan'],
];

const emptyOrder = () => ({
    customerId: '',
    fulfillment: 'Pickup',
    pickupDate: todayKey(),
    pickupTime: '14:30',
    tableNumber: '',
    items: [],
    note: '',
});

Alpine.data('ordersPage', () => ({
    tabs: STATUS_TABS,
    categories: CATEGORIES,
    tab: 'all',
    query: '',
    datePreset: 'all',
    fulfillment: 'all',
    sortBy: 'newest',
    page: 1,
    pageSize: 10,
    // editor
    editorOpen: false,
    editingId: null,
    order: emptyOrder(),
    productSearch: '',
    productCategory: 'Semua',
    editorErrors: {},
    saving: false,
    discardOpen: false,
    // detail
    detailId: null,
    // cancel
    cancelOpen: false,
    cancelTarget: null,
    cancelReason: '',
    // pembayaran
    payOpen: false,
    payTarget: null,
    payMethod: 'Cash',
    payState: 'idle',
    payError: '',
    // edit note
    noteEditOpen: false,
    noteEditTarget: null,
    noteDraft: '',
    loaded: false,

    // --- Table Orders Live Queue State ---
    orderMode: 'table', // 'table' | 'pos'
    tableOrders: [],
    selectedTableOrder: null,
    tableTab: 'all',
    tableCounts: { all: 0, new: 0, accepted: 0, preparing: 0, ready: 0, served: 0, review_required: 0 },
    tableEscalationCount: 0,
    soundEnabled: false,

    init() {
        this.$nextTick(() => (this.loaded = true));
        this.loadTableOrders();
        setInterval(() => this.loadTableOrders(), 8000);
    },

    openTableOrderDetail(ord) {
        this.selectedTableOrder = ord;
        if (window.PM && typeof window.PM.lockBody === 'function') {
            window.PM.lockBody(true);
        }
    },

    closeTableOrderDetail() {
        this.selectedTableOrder = null;
        if (window.PM && typeof window.PM.lockBody === 'function') {
            window.PM.lockBody(false);
        }
    },

    printTableOrderReceipt(ord) {
        if (!ord) return;
        const itemsHtml = (ord.items || []).map(it => `
            <div style="display:flex; justify-content:space-between; margin-bottom:4px; font-size:12px;">
                <div>
                    <strong>${it.quantity}x ${it.name}</strong>
                    ${it.note || it.notes ? `<div style="font-size:11px; font-style:italic; color:#666;">Catatan: ${it.note || it.notes}</div>` : ''}
                </div>
                <span>Rp ${(it.unit_price_cents * it.quantity).toLocaleString('id-ID')}</span>
            </div>
        `).join('');

        const html = `
            <div style="font-family:sans-serif; max-width:320px; margin:0 auto; padding:12px; color:#111;">
                <div style="text-align:center; border-bottom:1px dashed #999; padding-bottom:8px; margin-bottom:8px;">
                    <h2 style="margin:0; font-size:16px; font-weight:bold;">POSYMART KITCHEN</h2>
                    <p style="margin:2px 0; font-size:12px; color:#555;">TIKET ANTRIAN PESANAN</p>
                    <p style="margin:2px 0; font-size:11px; font-family:monospace;">${ord.order_code}</p>
                </div>
                <div style="font-size:12px; margin-bottom:8px; border-bottom:1px dashed #999; padding-bottom:8px;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:2px;">
                        <span>Tujuan:</span>
                        <strong>${ord.table_label_snapshot || 'Meja'}</strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:2px;">
                        <span>Pemesan:</span>
                        <strong>${ord.customer_name || 'Pelanggan'}</strong>
                    </div>
                    ${ord.customer && ord.customer.phone ? `
                    <div style="display:flex; justify-content:space-between; margin-bottom:2px;">
                        <span>Kontak HP:</span>
                        <span>${ord.customer.phone}</span>
                    </div>` : ''}
                    <div style="display:flex; justify-content:space-between; margin-bottom:2px;">
                        <span>Tipe Antar:</span>
                        <span>${ord.delivery_type === 'counter' ? 'Ambil di Kasir' : 'Diantar ke Meja'}</span>
                    </div>
                    <div style="display:flex; justify-content:space-between;">
                        <span>Status Bayar:</span>
                        <strong>${ord.payment_status === 'Paid' ? 'QRIS LUNAS' : 'Belum Bayar'}</strong>
                    </div>
                </div>
                <div style="margin-bottom:8px; border-bottom:1px dashed #999; padding-bottom:8px;">
                    ${itemsHtml}
                </div>
                <div style="font-size:12px; margin-bottom:8px; border-bottom:1px dashed #999; padding-bottom:6px;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:2px;">
                        <span>Subtotal:</span>
                        <span>Rp ${(ord.subtotal_cents || ord.total_cents || 0).toLocaleString('id-ID')}</span>
                    </div>
                    ${ord.discount_cents > 0 ? `
                    <div style="display:flex; justify-content:space-between; margin-bottom:2px; color:#0f766e; font-weight:bold;">
                        <span>Diskon Voucher:</span>
                        <span>- Rp ${(ord.discount_cents).toLocaleString('id-ID')}</span>
                    </div>` : ''}
                    ${(ord.service_fee_cents || ord.service_cents) > 0 ? `
                    <div style="display:flex; justify-content:space-between; margin-bottom:2px;">
                        <span>Biaya Antar Meja:</span>
                        <span>Rp ${(ord.service_fee_cents || ord.service_cents).toLocaleString('id-ID')}</span>
                    </div>` : ''}
                    ${ord.tax_cents > 0 ? `
                    <div style="display:flex; justify-content:space-between; margin-bottom:2px;">
                        <span>Pajak (PB1):</span>
                        <span>Rp ${(ord.tax_cents).toLocaleString('id-ID')}</span>
                    </div>` : ''}
                </div>
                <div style="display:flex; justify-content:space-between; font-weight:bold; font-size:13px; margin-bottom:12px;">
                    <span>TOTAL:</span>
                    <span>Rp ${(ord.total_cents || 0).toLocaleString('id-ID')}</span>
                </div>
                ${ord.notes ? `<div style="font-size:11px; background:#f5f5f5; padding:6px; margin-bottom:8px;">Catatan: ${ord.notes}</div>` : ''}
                <div style="text-align:center; font-size:10px; color:#888;">
                    Dicetak pada ${new Date().toLocaleString('id-ID')}
                </div>
            </div>
        `;

        const printWin = window.open('', '_blank', 'width=380,height=500');
        if (printWin) {
            printWin.document.write(`<html><head><title>Struk Pesanan ${ord.order_code}</title></head><body style="margin:0; padding:10px;">${html}</body></html>`);
            printWin.document.close();
            printWin.focus();
            setTimeout(() => {
                printWin.print();
                printWin.close();
            }, 300);
        }
    },

    async loadTableOrders() {
        try {
            const res = await fetch('/api/v1/admin/orders');
            if (res.ok) {
                const data = await res.json();
                this.tableOrders = data.data || [];
                if (data.status_counts) {
                    this.tableCounts = {
                        all: data.status_counts.all ?? this.tableOrders.length,
                        ...data.status_counts,
                    };
                }
                if (this.selectedTableOrder) {
                    const fresh = this.tableOrders.find((o) => o.id === this.selectedTableOrder.id);
                    if (fresh) this.selectedTableOrder = fresh;
                }
            }
            const nres = await fetch('/api/v1/admin/notifications');
            if (nres.ok) {
                const ndata = await nres.json();
                this.tableEscalationCount = ndata.unhandled_count || 0;
                if (this.soundEnabled && ndata.unhandled_count > 0) {
                    this.playChime();
                }
            }
        } catch (e) {
            console.error('Failed to load table orders', e);
        }
    },

    playChime() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.setValueAtTime(587.33, ctx.currentTime);
            osc.frequency.setValueAtTime(880, ctx.currentTime + 0.15);
            gain.gain.setValueAtTime(0.3, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.4);
            osc.start();
            osc.stop(ctx.currentTime + 0.4);
        } catch (e) {}
    },

    async transitionTableOrder(orderId, action) {
        try {
            const res = await fetch(`/api/v1/admin/orders/${orderId}/transitions`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ action }),
            });
            const data = await res.json();
            if (res.ok) {
                this.$store.posy.toast(data.message, { type: 'success' });
                await this.loadTableOrders();
                if (this.selectedTableOrder && this.selectedTableOrder.id === orderId) {
                    const fresh = this.tableOrders.find((o) => o.id === orderId);
                    if (fresh) this.selectedTableOrder = fresh;
                }
            } else {
                this.$store.posy.toast(data.error?.message || 'Gagal mengubah status', { type: 'error' });
            }
        } catch (e) {
            console.error(e);
        }
    },

    get filteredTableOrders() {
        let list = this.tableOrders;
        if (this.tableTab !== 'all') {
            list = list.filter((o) => o.fulfillment_status === this.tableTab);
        }
        if (this.query.trim()) {
            const q = this.query.toLowerCase();
            list = list.filter((o) =>
                (o.order_code && o.order_code.toLowerCase().includes(q)) ||
                (o.customer_name && o.customer_name.toLowerCase().includes(q)) ||
                (o.table_label_snapshot && o.table_label_snapshot.toLowerCase().includes(q))
            );
        }
        return list;
    },

    get orders() {
        return this.$store.posy.orders;
    },

    get statusLabel() {
        return (s) => Object.fromEntries(STATUS_TABS)[s] ?? s;
    },

    get baseFiltered() {
        const q = this.query.trim().toLowerCase();
        const nowKey = todayKey();
        return this.orders.filter((o) => {
            if (this.fulfillment !== 'all' && o.fulfillment !== this.fulfillment) return false;
            if (this.datePreset === 'today' && zonedDateKey(o.placedAt) !== nowKey) return false;
            if (this.datePreset === 'last7' && zonedDateKey(o.placedAt) < daysAgoKey(6)) return false;
            if (q) {
                const hay = `${o.id} ${o.customerNameSnapshot} ${o.saleId ?? ''}`.toLowerCase();
                if (!hay.includes(q)) return false;
            }
            return true;
        });
    },

    get tabCounts() {
        const counts = { all: this.baseFiltered.length };
        for (const [key] of STATUS_TABS.slice(1)) {
            counts[key] = this.baseFiltered.filter((o) => o.status === key).length;
        }
        return counts;
    },

    get filtered() {
        const arr = this.tab === 'all' ? this.baseFiltered : this.baseFiltered.filter((o) => o.status === this.tab);
        const withDir = [...arr];
        switch (this.sortBy) {
            case 'oldest': withDir.sort((a, b) => a.placedAt.localeCompare(b.placedAt) || a.id.localeCompare(b.id)); break;
            case 'total-desc': withDir.sort((a, b) => b.total - a.total || a.id.localeCompare(b.id)); break;
            case 'total-asc': withDir.sort((a, b) => a.total - b.total || a.id.localeCompare(b.id)); break;
            default: withDir.sort((a, b) => b.placedAt.localeCompare(a.placedAt) || a.id.localeCompare(b.id));
        }
        return withDir;
    },

    get stats() {
        const inQueue = this.baseFiltered.filter((o) => ['Pending', 'Preparing', 'Ready'].includes(o.status));
        const readyPickup = this.baseFiltered.filter((o) => o.status === 'Ready' && o.fulfillment === 'Pickup');
        return {
            all: this.baseFiltered.length,
            inQueue: inQueue.length,
            readyPickup: readyPickup.length,
            queuedValue: inQueue.reduce((a, o) => a + o.total, 0),
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
        this.fulfillment = 'all';
        this.sortBy = 'newest';
        this.tab = 'all';
        this.page = 1;
    },

    unitCount(o) {
        return o.items.reduce((a, it) => a + it.quantity, 0);
    },

    get activeCustomers() {
        return this.$store.posy.customers.filter((c) => c.status === 'Active');
    },

    /* ---------------- editor ---------------- */
    openCreate() {
        this.editingId = null;
        this.order = emptyOrder();
        this.editorErrors = {};
        this.productSearch = '';
        this.productCategory = 'Semua';
        this.editorOpen = true;
        this.$nextTick(() => PM.lockBody(true));
    },

    openEdit(id) {
        const o = this.$store.posy.order(id);
        if (!o || !['Draft', 'Pending'].includes(o.status)) return;
        this.editingId = id;
        this.order = {
            customerId: o.customerId ?? '',
            fulfillment: o.fulfillment,
            pickupDate: o.pickupAt ? o.pickupAt.slice(0, 10) : todayKey(),
            pickupTime: o.pickupAt ? o.pickupAt.slice(11, 16) : '14:30',
            tableNumber: o.tableNumber ?? '',
            items: o.items.map((it) => ({ ...it })),
            note: o.note ?? '',
        };
        this.editorErrors = {};
        this.productSearch = '';
        this.productCategory = 'Semua';
        this.editorOpen = true;
        this.detailId = null;
        this.$nextTick(() => PM.lockBody(true));
    },

    get editorDirty() {
        if (this.editingId) return true; // penyederhanaan: selalu konfirmasi saat edit
        return this.order.items.length > 0 || this.order.customerId !== '' || this.order.note !== '' || this.order.tableNumber !== '';
    },

    closeEditor(force = false) {
        if (!force && this.editorDirty) {
            this.discardOpen = true;
            return;
        }
        this.editorOpen = false;
        this.$nextTick(() => PM.lockBody(false));
    },

    get catalogProducts() {
        const q = this.productSearch.trim().toLowerCase();
        return this.$store.posy.products.filter((p) => {
            if (p.status !== 'Active') return false;
            if (this.productCategory !== 'Semua' && p.category !== this.productCategory) return false;
            if (q && !p.name.toLowerCase().includes(q)) return false;
            return true;
        });
    },

    addItem(productId) {
        const p = this.$store.posy.product(productId);
        if (!p) return;
        const existing = this.order.items.find((it) => it.productId === productId);
        if (existing) {
            if (existing.quantity >= 99) return;
            existing.quantity += 1;
        } else {
            this.order.items.push({ productId, name: p.name, unitPrice: p.price, quantity: 1 });
        }
    },

    setItemQty(productId, qty) {
        const it = this.order.items.find((x) => x.productId === productId);
        if (!it) return;
        const n = Number(qty);
        if (!Number.isInteger(n) || n < 1 || n > 99) {
            this.editorErrors.items = 'Jumlah harus bilangan bulat 1–99.';
            return;
        }
        this.editorErrors.items = '';
        it.quantity = n;
    },

    removeItem(productId) {
        const i = this.order.items.findIndex((x) => x.productId === productId);
        if (i > -1) this.order.items.splice(i, 1);
    },

    get editorTotals() {
        const s = this.$store.posy.settings;
        const subtotal = this.order.items.reduce((a, it) => a + it.unitPrice * it.quantity, 0);
        const tax = s.taxEnabled ? Math.round((subtotal * s.taxRatePercent) / 100 + 0.0001) : 0;
        return { subtotal, tax, total: subtotal + tax };
    },

    validateEditor() {
        const e = {};
        if (!this.order.items.length) e.items = 'Tambahkan minimal satu produk.';
        if (this.order.fulfillment === 'Pickup') {
            if (!this.order.pickupDate) e.pickup = 'Tanggal ambil wajib diisi.';
            else if (!this.order.pickupTime) e.pickup = 'Waktu ambil wajib diisi.';
            else {
                const dt = `${this.order.pickupDate}T${this.order.pickupTime}:00+07:00`;
                if (new Date(dt).getTime() < Date.now() - 3600000) {
                    e.pickup = 'Waktu ambil tidak boleh di masa lampau untuk pesanan baru.';
                }
            }
        } else {
            const t = this.order.tableNumber.trim();
            if (!t) e.table = 'Nomor meja wajib diisi untuk Makan di Tempat.';
            else if (t.length < 1 || t.length > 10) e.table = 'Nomor meja 1–10 karakter.';
        }
        if ((this.order.note ?? '').length > 500) e.note = 'Maksimum 500 karakter.';
        this.editorErrors = e;
        return Object.keys(e).length === 0;
    },

    async saveOrder(targetStatus) {
        if (!this.validateEditor()) return;
        this.saving = true;
        await new Promise((r) => setTimeout(r, 450));
        const pickupIso = this.order.fulfillment === 'Pickup' ? `${this.order.pickupDate}T${this.order.pickupTime}:00+07:00` : null;
        const form = {
            customerId: this.order.customerId || null,
            fulfillment: this.order.fulfillment,
            pickupAt: pickupIso,
            tableNumber: this.order.fulfillment === 'Dine-in' ? this.order.tableNumber.trim() : null,
            items: this.order.items,
            note: this.order.note,
            targetStatus,
        };
        const rec = this.$store.posy.saveOrder(form, this.editingId);
        this.saving = false;
        this.editorOpen = false;
        this.$store.posy.toast(rec.status === 'Draft' ? 'Draf pesanan disimpan' : 'Pesanan dibuat — Menunggu diproses');
        this.$nextTick(() => PM.lockBody(false));
    },

    /* ---------------- detail & transisi ---------------- */
    get detailOrder() {
        return this.detailId ? this.$store.posy.order(this.detailId) : null;
    },

    openDetail(id) {
        this.detailId = id;
        this.$nextTick(() => PM.lockBody(true));
    },

    closeDetail() {
        this.detailId = null;
        this.$nextTick(() => PM.lockBody(false));
    },

    get canAdvance() {
        return (o) => {
            if (o.status === 'Draft') return { ok: this.$store.posy.can('cancelOrders') || true, action: 'Place Order', label: 'Buat Pesanan' };
            if (o.status === 'Pending') return { ok: true, action: 'Start Preparing', label: 'Mulai Disiapkan' };
            if (o.status === 'Preparing') return { ok: true, action: 'Mark as Ready', label: 'Tandai Siap' };
            return { ok: false };
        };
    },

    async advance(id) {
        const ok = await this.$store.posy.advanceOrder(id);
        if (ok) {
            const o = this.$store.posy.order(id);
            this.$store.posy.toast(`Status ${id} diperbarui menjadi ${this.statusLabel(o.status)}`);
        }
    },

    openCancel(id) {
        this.cancelTarget = this.$store.posy.order(id);
        this.cancelReason = '';
        this.cancelOpen = true;
        this.detailId = null;
    },

    confirmCancel() {
        const reason = this.cancelReason.trim();
        if (reason.length < 5 || reason.length > 200) return;
        const ok = this.$store.posy.cancelOrder(this.cancelTarget.id, reason);
        if (ok) {
            this.cancelOpen = false;
            this.$store.posy.toast('Pesanan dibatalkan');
        }
    },

    openNoteEdit(id) {
        this.noteEditTarget = this.$store.posy.order(id);
        this.noteDraft = this.noteEditTarget.note ?? '';
        this.noteEditOpen = true;
        this.detailId = null;
    },

    saveNoteEdit() {
        this.noteEditTarget.note = this.noteDraft.trim().slice(0, 500);
        this.$store.posy.persist();
        this.noteEditOpen = false;
        this.$store.posy.toast('Catatan pesanan diperbarui', { type: 'info' });
    },

    /* ---------------- pembayaran ---------------- */
    get paymentMethods() {
        const s = this.$store.posy.settings;
        const list = [];
        if (s.payments.cash) list.push('Cash');
        if (s.payments.card) list.push('Card');
        if (s.payments.wallet) list.push('Digital Wallet');
        return list;
    },

    openPay(id) {
        this.payTarget = this.$store.posy.order(id);
        if (!this.payTarget || this.payTarget.saleId) return;
        this.payMethod = this.paymentMethods.includes(this.$store.posy.settings.defaultPaymentMethod)
            ? this.$store.posy.settings.defaultPaymentMethod
            : this.paymentMethods[0];
        this.payState = 'idle';
        this.payError = '';
        this.payOpen = true;
        this.detailId = null;
        this.$nextTick(() => PM.lockBody(true));
    },

    async confirmPay() {
        if (this.payState === 'processing' || !this.payTarget) return;
        this.payState = 'processing';
        const res = await this.$store.posy.completeOrderPay(this.payTarget.id, this.payMethod);
        if (res.ok) {
            this.payState = 'success';
            this.$store.posy.toast(`Pesanan selesai — ${res.saleId} dibuat`, {
                actionLabel: 'Lihat Transaksi',
                onAction: () => (window.location.href = `/sales?sale=${res.saleId}`),
            });
        } else {
            this.payState = 'error';
            this.payError = res.reason ?? 'Pembayaran simulasi gagal. Pesanan tetap Siap.';
        }
    },

    closePay() {
        if (this.payState === 'processing') return;
        this.payOpen = false;
        this.payState = 'idle';
        this.$nextTick(() => PM.lockBody(false));
    },

    duplicate(id) {
        const rec = this.$store.posy.duplicateOrderAsDraft(id);
        if (rec) this.$store.posy.toast(`Draf baru ${rec.id} dibuat dari ${id}`);
        this.detailId = null;
    },

    printReceipt(o) {
        const set = this.$store.posy.settings || {};
        const html = generate80mmReceiptHtml({
            storeName: set.storeName || 'PosyMart Coffee & Eatery',
            storeAddress: set.receiptShowAddress ? `${set.streetAddress || ''}, ${set.city || ''} ${set.postalCode || ''}`.trim().replace(/^,\s*/, '') : '',
            storePhone: set.receiptShowAddress ? (set.storePhone || '') : '',
            receiptHeader: set.receiptHeader || 'PosyMart',
            receiptFooter: set.receiptFooter || 'Terima kasih telah berbelanja bersama kami!',
            saleId: o.id || o.order_code,
            createdAt: o.paidAt || o.placedAt || o.created_at || new Date(),
            orderType: o.delivery_type === 'counter' ? 'takeaway' : 'dine_in',
            tableNumber: o.table_label_snapshot || o.table_number || '',
            customerName: o.customerNameSnapshot || o.customer_name || 'Pelanggan Umum',
            cashierName: set.receiptShowCashier ? 'Kasir PosyMart' : '',
            items: (o.items || []).map((it) => ({
                name: it.name,
                quantity: it.quantity,
                unitPrice: it.unitPrice || it.unit_price_cents || 0,
            })),
            subtotal: o.subtotal || ((o.items || []).reduce((acc, it) => acc + (it.quantity * (it.unitPrice || it.unit_price_cents || 0)), 0)),
            discount: o.discount || 0,
            tax: o.tax || 0,
            taxName: o.taxNameSnapshot || set.taxName || 'Pajak PB1',
            taxRatePercent: (o.taxRateBpsSnapshot ? o.taxRateBpsSnapshot / 100 : set.taxRatePercent) || 0,
            serviceFee: o.service_fee_cents || 0,
            total: o.total || o.total_cents || 0,
            paymentMethod: o.paymentMethod || o.payment_method || 'QRIS / Cashless',
            status: 'Completed',
            width: set.receiptWidth || '80 mm',
        });

        printHtml(`Struk ${o.id || o.order_code}`, html, { width: set.receiptWidth || '80 mm' });
    },

    exportCsv() {
        const headers = ['Kode Pesanan', 'Meja / Layanan', 'Nama Pelanggan', 'Status Antrean', 'Status Bayar', 'Total Tagihan', 'Waktu Masuk'];
        const rows = this.filteredTableOrders.map((o) => [
            o.order_code,
            o.table_label_snapshot || (o.delivery_type === 'counter' ? 'Ambil di Kasir' : 'Meja'),
            o.customer_name || 'Pelanggan',
            o.fulfillment_status,
            o.payment_status,
            moneyPlain(o.total_cents),
            fmtDateTime(o.created_at),
        ]);
        downloadFile(`antrean-pesanan-${todayKey()}.csv`, toCsv(headers, rows));
        this.$store.posy.toast('Data antrean berhasil diunduh ke CSV');
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
