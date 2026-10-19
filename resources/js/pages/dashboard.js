/**
 * PosyMart — Halaman Dasbor (design.md §4–15).
 * Rekonstruksi referensi: statistik, Current Sale, katalog POS, simulasi pembayaran.
 */

import Alpine from 'alpinejs';
import { CATEGORIES } from '../data/seed.js';
import { formatRp } from '../utils/money.js';
import { printHtml, generate80mmReceiptHtml } from '../utils/files.js';

Alpine.data('dashboard', () => ({
    activeCategory: 'Semua',
    categories: ['Semua', ...CATEGORIES],
    // modal pembayaran
    payOpen: false,
    payMethod: 'Cash',
    payCashReceived: '',
    payState: 'idle', // idle | processing | success | error
    payError: '',
    payResult: null,
    // catatan
    noteOpen: false,
    noteDraft: '',

    saveNote() {
        this.$store.posy.setCartNote(this.noteDraft.trim());
        this.noteOpen = false;
        this.$store.posy.toast('Catatan disimpan', { type: 'info' });
    },
    // diskon
    discountOpen: false,
    discountQuery: '',
    // kebab
    kebabOpen: false,
    // validasi stok
    stockProblem: null,

    init() {
        const params = new URLSearchParams(window.location.search);
        if (params.get('customer')) {
            const c = this.$store.posy.customer(params.get('customer'));
            if (c && c.status === 'Active') {
                if (this.$store.posy.draftSale.customerId && this.$store.posy.draftSale.customerId !== c.id) {
                    this.changeCustomerFor = c;
                    this.changeCustomerOpen = true;
                } else {
                    this.$store.posy.setCartCustomer(c.id);
                }
            }
        }
    },

    get search() {
        return this.$store.posy.catalogSearch || '';
    },

    set search(v) {
        this.$store.posy.catalogSearch = v;
    },

    get todaySales() {
        const completed = (this.$store.posy.sales || []).filter((s) => s.status === 'Completed');
        return completed.reduce((sum, s) => sum + (s.totalCents || 0), 0);
    },

    get todayOrdersCount() {
        const completedSales = (this.$store.posy.sales || []).filter((s) => s.status === 'Completed').length;
        return (this.$store.posy.orders?.length || 0) + completedSales;
    },

    get totalCustomersCount() {
        return this.$store.posy.customers?.length || 0;
    },

    get todayAov() {
        const completed = (this.$store.posy.sales || []).filter((s) => s.status === 'Completed');
        if (!completed.length) return 0;
        return Math.round(this.todaySales / completed.length);
    },

    get filteredProducts() {
        const q = this.search.trim().toLowerCase();
        return this.$store.posy.products.filter((p) => {
            if (p.status !== 'Active' && p.status !== 'OutOfStock') return false;
            if (this.activeCategory !== 'Semua' && p.category !== this.activeCategory) return false;
            if (q && !p.name.toLowerCase().includes(q)) return false;
            return true;
        });
    },

    get cartLines() {
        return this.$store.posy.draftSale.items.map((it) => {
            const p = this.$store.posy.product(it.productId);
            return { ...it, product: p, lineTotal: it.unitPrice * it.quantity };
        });
    },

    get totals() {
        return this.$store.posy.draftTotals();
    },

    get settings() {
        return this.$store.posy.settings;
    },

    get hasStockIssue() {
        if (!this.settings.trackInventory) return false;
        return this.cartLines.some((l) => {
            if (!l.product) return false;
            if (l.product.status === 'OutOfStock') return true;
            const isTracked = l.product.trackStock !== false && l.product.track_stock !== false && l.product.track_stock !== 0;
            if (isTracked && l.product.stock < l.quantity) return true;
            return false;
        });
    },

    productName(id) {
        return this.$store.posy.product(id)?.name ?? '';
    },

    /* ---------------- aksi keranjang ---------------- */
    add(productId) {
        const p = this.$store.posy.product(productId);
        if (!p) return;
        if (p.status === 'OutOfStock') {
            this.$store.posy.toast(`Menu ${p.name} sedang ditandai habis.`, { type: 'warning' });
            return;
        }
        const isTracked = p.trackStock !== false && p.track_stock !== false && p.track_stock !== 0;
        if (this.settings.trackInventory && isTracked) {
            const inCart = this.$store.posy.draftSale.items
                .filter((x) => x.productId === productId)
                .reduce((a, x) => a + x.quantity, 0);
            if (inCart >= p.stock) {
                this.$store.posy.toast(`Stok ${p.name} tidak mencukupi (tersisa ${p.stock}).`, { type: 'warning' });
                return;
            }
        }
        this.$store.posy.addToCart(productId);
    },

    dec(productId) {
        this.$store.posy.changeQty(productId, -1);
    },

    inc(productId) {
        const p = this.$store.posy.product(productId);
        if (p) {
            if (p.status === 'OutOfStock') {
                this.$store.posy.toast(`Menu ${p.name} sedang ditandai habis.`, { type: 'warning' });
                return;
            }
            const isTracked = p.trackStock !== false && p.track_stock !== false && p.track_stock !== 0;
            if (this.settings.trackInventory && isTracked) {
                const inCart = this.$store.posy.draftSale.items
                    .filter((x) => x.productId === productId)
                    .reduce((a, x) => a + x.quantity, 0);
                if (inCart >= p.stock) {
                    this.$store.posy.toast(`Stok ${p.name} tidak mencukupi (tersisa ${p.stock}).`, { type: 'warning' });
                    return;
                }
            }
        }
        this.$store.posy.changeQty(productId, 1);
    },

    remove(productId) {
        this.$store.posy.removeLine(productId);
    },

    clearCart() {
        if (!this.$store.posy.draftSale.items.length) return;
        this.$store.posy.clearCart();
        this.$store.posy.toast('Keranjang dikosongkan', {
            type: 'info',
            actionLabel: 'Urungkan',
            onAction: () => this.$store.posy.undoClearCart(),
        });
    },

    get canDec() {
        return (id) => {
            const it = this.$store.posy.draftSale.items.find((x) => x.productId === id);
            return it && it.quantity > 1;
        };
    },

    /* ---------------- pembayaran ---------------- */
    get paymentMethods() {
        const s = this.settings;
        const list = [];
        if (s.payments.cash) list.push('Cash');
        if (s.payments.card) list.push('Card');
        if (s.payments.wallet) list.push('Digital Wallet');
        return list;
    },

    openPay() {
        if (!this.cartLines.length || this.payState === 'processing') return;
        if (this.hasStockIssue) {
            const probs = this.cartLines.filter((l) => l.product && l.product.stock < l.quantity);
            this.stockProblem = probs.map((l) => `${l.product.name} — tersisa ${l.product.stock}, diminta ${l.quantity}`).join(', ');
            return;
        }
        this.stockProblem = null;
        this.payMethod = this.paymentMethods.includes(this.settings.defaultPaymentMethod)
            ? this.settings.defaultPaymentMethod
            : this.paymentMethods[0];
        this.payCashReceived = '';
        this.payError = '';
        this.payState = 'idle';
        this.payOpen = true;
        this.$nextTick(() => PM.lockBody(true));
    },

    closePay() {
        if (this.payState === 'processing') return;
        this.payOpen = false;
        this.$nextTick(() => PM.lockBody(false));
    },

    get cashNumeric() {
        const v = String(this.payCashReceived).replace(/[^\d]/g, '');
        return v === '' ? NaN : Number(v);
    },

    get cashShort() {
        return this.payMethod === 'Cash' && !Number.isNaN(this.cashNumeric) && this.cashNumeric < this.totals.total;
    },

    get changeAmount() {
        if (this.payMethod !== 'Cash' || Number.isNaN(this.cashNumeric)) return null;
        return this.cashNumeric - this.totals.total;
    },

    get payDisabled() {
        if (this.payState === 'processing') return true;
        if (!this.cartLines.length) return true;
        if (this.payMethod === 'Cash') {
            if (Number.isNaN(this.cashNumeric)) return true;
            if (this.cashNumeric < this.totals.total) return true;
            if (!this.settings.allowCashChange && this.cashNumeric !== this.totals.total) return true;
        }
        return false;
    },

    async confirmPay() {
        if (this.payDisabled) return;
        this.payState = 'processing';
        this.payError = '';
        const received = this.payMethod === 'Cash' ? this.cashNumeric : null;
        const curLines = this.cartLines.map((l) => ({
            product_id: l.productId,
            name: l.product?.name || l.name,
            quantity: l.quantity,
            unit_price: l.unitPrice,
        }));
        const curCust = this.customerName;
        const curType = this.orderType;
        const curTable = this.tableNumber;
        const curTotal = this.totals.total;

        const res = await this.$store.posy.completePayment(this.payMethod, received);
        if (res.ok) {
            this.payState = 'success';
            this.payResult = {
                saleId: res.saleId,
                method: this.payMethod,
                tendered: received,
                change: this.changeAmount,
                total: curTotal,
            };
            this.$store.posy.toast(`Pembayaran simulasi berhasil — ${res.saleId} tersimpan di Penjualan.`, { type: 'success' });

            // Kirim tiket pesanan ke antrean dapur / pelayan di background
            try {
                fetch('/api/v1/admin/orders', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        customer_name: curCust,
                        order_type: curType,
                        table_number: curTable,
                        sale_id: res.saleId,
                        payment_method: this.payMethod,
                        items: curLines,
                        total: curTotal,
                    }),
                }).catch(() => {});
            } catch (e) {
                // background fail-safe
            }
        } else {
            this.payState = 'error';
            this.payError = res.reason || 'Pembayaran simulasi gagal. Keranjang tetap tersedia.';
        }
    },

    finishPay() {
        this.payOpen = false;
        this.payState = 'idle';
        this.$nextTick(() => PM.lockBody(false));
    },

    printReceipt(saleId) {
        const s = this.$store.posy.sale(saleId);
        if (!s) return;
        const set = this.$store.posy.settings || {};
        const methodLabel = s.paymentMethod === 'Cash'
            ? 'Tunai (Cash)'
            : s.paymentMethod === 'Card'
            ? 'Kartu (EDC)'
            : 'Dompet Digital (QRIS)';

        const html = generate80mmReceiptHtml({
            storeName: set.storeName || 'PosyMart Coffee & Eatery',
            storeAddress: set.receiptShowAddress ? `${set.streetAddress || ''}, ${set.city || ''} ${set.postalCode || ''}`.trim().replace(/^,\s*/, '') : '',
            storePhone: set.receiptShowAddress ? (set.storePhone || '') : '',
            receiptHeader: set.receiptHeader || 'PosyMart',
            receiptFooter: set.receiptFooter || 'Terima kasih telah berbelanja bersama kami!',
            saleId: s.id,
            createdAt: s.createdAt,
            orderType: s.orderType,
            tableNumber: s.tableNumber,
            customerName: s.customerNameSnapshot || 'Pelanggan Umum',
            cashierName: set.receiptShowCashier ? (s.cashierNameSnapshot || 'Kasir PosyMart') : '',
            items: s.items || [],
            subtotal: s.subtotal || 0,
            discount: s.discount || 0,
            tax: s.tax || 0,
            taxName: s.taxNameSnapshot || set.taxName || 'Pajak PB1',
            taxRatePercent: (s.taxRateBpsSnapshot ? s.taxRateBpsSnapshot / 100 : set.taxRatePercent) || 0,
            total: s.total || 0,
            paymentMethod: methodLabel,
            tenderedAmount: this.payResult?.tendered ?? (s.paymentMethod === 'Cash' ? s.total : null),
            changeAmount: this.payResult?.change ?? 0,
            status: s.status || 'Completed',
            note: s.note || '',
            width: set.receiptWidth || '80 mm',
        });

        printHtml(`Struk Pembayaran ${s.id}`, html, { width: set.receiptWidth || '80 mm' });
    },

    /* ---------------- diskon ---------------- */
    get eligibleDiscounts() {
        const q = this.discountQuery.trim().toLowerCase();
        return this.$store.posy.discounts
            .map((d) => ({ ...d, effective: this.$store.posy.discountEffectiveStatus(d) }))
            .filter((d) => d.code.toLowerCase().includes(q) || d.name.toLowerCase().includes(q));
    },

    discountReason(d) {
        const now = Date.now();
        if (!d.published) return 'Masih berupa draf';
        if (!d.enabled) return 'Dinonaktifkan';
        if (d.endDate && new Date(d.endDate).getTime() <= now) return 'Sudah berakhir';
        if (d.startDate && new Date(d.startDate).getTime() > now) return 'Belum dimulai';
        if (d.usageLimit !== null && d.usedCount >= d.usageLimit) return 'Batas pemakaian tercapai';
        if (d.appliesTo === 'customers') {
            const c = this.$store.posy.draftSale.customerId;
            if (!c || !(d.customers || []).includes(c)) return 'Hanya untuk pelanggan tertentu';
        }
        const subtotal = this.$store.posy.draftSale.items.reduce((a, it) => a + it.unitPrice * it.quantity, 0);
        if (subtotal < (d.minSpend || 0)) return `Minimum belanja ${formatRp(d.minSpend)}`;
        return '';
    },

    tryApplyDiscount(d) {
        const res = this.$store.posy.applyDiscount(d.id);
        if (res.ok) {
            this.discountOpen = false;
            this.$store.posy.toast(`Diskon ${d.code} diterapkan`);
        }
        return res;
    },

    removeDiscount() {
        this.$store.posy.removeDiscount();
    },

    /* ---------------- pelanggan (state lanjutan) ---------------- */
    changeCustomerOpen: false,
    changeCustomerFor: null,
    customerDropdownOpen: false,

    get customerName() {
        return this.$store.posy.draftSale?.customerNameSnapshot ?? (this.selectedCustomer?.name ?? '');
    },

    set customerName(val) {
        this.$store.posy.setCartCustomerName(val);
    },

    get orderType() {
        return this.$store.posy.draftSale?.orderType ?? 'dine_in';
    },

    set orderType(val) {
        this.$store.posy.setCartOrderType(val);
    },

    get tableNumber() {
        return this.$store.posy.draftSale?.tableNumber ?? '';
    },

    set tableNumber(val) {
        this.$store.posy.setCartTableNumber(val);
    },

    get selectedCustomer() {
        return this.$store.posy.draftSale?.customerId ? this.$store.posy.customer(this.$store.posy.draftSale.customerId) : null;
    },

    get matchingCustomers() {
        const q = (this.customerName || '').trim().toLowerCase();
        const all = (this.$store.posy.customers || []).filter((c) => c.status !== 'Archived');
        if (!q) {
            return all.slice(0, 10);
        }
        return all.filter((c) =>
            (c.name && c.name.toLowerCase().includes(q)) ||
            (c.phone && c.phone.includes(q)) ||
            (c.id && c.id.toLowerCase().includes(q))
        ).slice(0, 10);
    },

    selectCustomer(c) {
        if (!c) {
            this.$store.posy.setCartCustomer(null, '');
        } else {
            this.$store.posy.setCartCustomer(c.id, c.name);
        }
        this.customerDropdownOpen = false;
    },

    clearCustomer() {
        this.$store.posy.setCartCustomer(null, '');
        this.customerDropdownOpen = false;
    },

    get customerDisplayName() {
        const raw = (this.customerName || '').trim();
        if (raw) return raw;
        if (this.selectedCustomer) return this.selectedCustomer.name;
        return 'Pelanggan Umum (Walk-in)';
    },
}));
