/**
 * PosyMart — Halaman Pengaturan (design.md §25).
 * Konfigurasi toko lokal: 7 tab, pratinjau struk, reset demo.
 */

import Alpine from 'alpinejs';
import { DEFAULT_SETTINGS } from '../data/seed.js';
import { downloadFile, printHtml, generate80mmReceiptHtml } from '../utils/files.js';
import { WEEKDAY_LABELS } from '../utils/date.js';

Alpine.data('settingsPage', () => ({
    tabs: [
        ['general', 'Umum', 'settings'],
        ['store', 'Toko', 'package'],
        ['delivery', 'Layanan Meja', 'truck'],
        ['payments', 'Pembayaran', 'wallet'],
        ['taxes', 'Pajak', 'percent'],
        ['receipt', 'Struk', 'receipt'],
        ['notifications', 'Notifikasi', 'bell'],
        ['appearance', 'Tampilan', 'sun'],
    ],
    weekdays: WEEKDAY_LABELS,
    activeTab: 'general',
    draft: {},
    saved: {},
    errors: {},
    saving: false,
    discardOpen: false,
    resetDefaultsOpen: false,
    resetDataOpen: false,
    loaded: false,

    init() {
        this.saved = JSON.parse(JSON.stringify(this.$store.posy.settings));
        if (this.saved.tableServiceFeeEnabled === undefined) this.saved.tableServiceFeeEnabled = true;
        if (this.saved.tableServiceFeeCents === undefined) this.saved.tableServiceFeeCents = 2000;
        this.draft = JSON.parse(JSON.stringify(this.saved));
        this.$nextTick(() => (this.loaded = true));

        fetch('/api/v1/settings')
            .then((r) => r.json())
            .then((s) => {
                if (s && s.id) {
                    if (s.table_service_fee_enabled !== undefined) this.draft.tableServiceFeeEnabled = !!s.table_service_fee_enabled;
                    if (s.table_service_fee_cents !== undefined) this.draft.tableServiceFeeCents = s.table_service_fee_cents;
                    if (s.tax_enabled !== undefined) this.draft.taxEnabled = !!s.tax_enabled;
                    if (s.tax_name !== undefined) this.draft.taxName = s.tax_name;
                    if (s.tax_rate_bps !== undefined) this.draft.taxRatePercent = Number(s.tax_rate_bps) / 100;
                    if (s.store_name !== undefined) this.draft.storeName = s.store_name;
                    if (s.store_email !== undefined) this.draft.storeEmail = s.store_email;
                    if (s.store_phone !== undefined) this.draft.storePhone = s.store_phone;
                    if (s.store_address !== undefined) this.draft.streetAddress = s.store_address;
                    this.saved = JSON.parse(JSON.stringify(this.draft));
                }
            })
            .catch(() => {});
    },

    get allowed() {
        return this.$store.posy.can('manageSettings');
    },

    get tabMeta() {
        return {
            general: ['Umum', 'Identitas bisnis dan preferensi regional.'],
            store: ['Toko', 'Kontak, lokasi, jam buka, dan inventaris.'],
            delivery: ['Layanan Meja', 'Konfigurasi pemesanan via scan QR meja dan biaya antar.'],
            payments: ['Pembayaran', 'Metode pembayaran yang tersedia pada simulasi.'],
            taxes: ['Pajak', 'Konfigurasi pajak demo untuk penjualan baru.'],
            receipt: ['Struk', 'Tampilan dan isi struk demo.'],
            notifications: ['Notifikasi', 'Pemberitahuan dalam aplikasi.'],
            appearance: ['Tampilan', 'Tema dan preferensi tampilan.'],
        }[this.activeTab] ?? ['', ''];
    },

    get dirty() {
        return JSON.stringify(this.draft) !== JSON.stringify(this.saved);
    },

    switchTab(key) {
        this.activeTab = key;
    },

    cancel() {
        if (this.dirty) {
            this.discardOpen = true;
            return;
        }
        this.draft = JSON.parse(JSON.stringify(this.saved));
        this.$store.posy.toast('Tidak ada perubahan untuk dibatalkan.', { type: 'info' });
    },

    validateAll() {
        const e = {};
        const d = this.draft;
        if (d.businessName.trim().length < 2 || d.businessName.trim().length > 80) e.businessName = 'Nama bisnis 2–80 karakter.';
        if (d.storeName.trim().length < 2 || d.storeName.trim().length > 80) e.storeName = 'Nama toko 2–80 karakter.';
        if (d.storeEmail && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(d.storeEmail)) e.storeEmail = 'Format email tidak valid.';
        if (d.storePhone && d.storePhone.trim().length < 7) e.storePhone = 'Telepon minimal 7 karakter.';
        if (d.streetAddress.trim().length < 3 || d.streetAddress.trim().length > 160) e.streetAddress = 'Alamat 3–160 karakter.';
        if (d.city.trim().length < 2 || d.city.trim().length > 80) e.city = 'Kota 2–80 karakter.';
        if (d.postalCode && d.postalCode.length > 16) e.postalCode = 'Kode pos maksimal 16 karakter.';
        if (!d.country) e.country = 'Pilih negara.';
        if (d.openingTime >= d.closingTime) e.closingTime = 'Jam tutang harus setelah jam buka (overnight tidak didukung demo).';
        if (!d.openDays.length) e.openDays = 'Pilih minimal satu hari buka.';
        const thr = Number(d.defaultLowStockThreshold);
        if (!Number.isInteger(thr) || thr < 0 || thr > 99999) e.defaultLowStockThreshold = 'Angka bulat 0–99999.';
        const anyPayment = d.payments.cash || d.payments.card || d.payments.wallet;
        if (!anyPayment) e.payments = 'Minimal satu metode pembayaran harus aktif.';
        else {
            const defActive =
                (d.defaultPaymentMethod === 'Cash' && d.payments.cash) ||
                (d.defaultPaymentMethod === 'Card' && d.payments.card) ||
                (d.defaultPaymentMethod === 'Digital Wallet' && d.payments.wallet);
            if (!defActive) e.defaultPaymentMethod = 'Metode default harus aktif.';
        }
        if (d.taxEnabled && d.taxName.trim().length < 1) e.taxName = 'Nama pajak wajib diisi.';
        if (d.taxEnabled && d.taxName.trim().length > 30) e.taxName = 'Nama pajak maksimal 30 karakter.';
        const rate = Number(d.taxRatePercent);
        if (d.taxEnabled && (!Number.isFinite(rate) || rate < 0 || rate > 25)) e.taxRatePercent = 'Tarif 0–25 dengan maksimal 2 desimal.';
        if (d.receiptHeader.trim().length < 1 || d.receiptHeader.trim().length > 80) e.receiptHeader = 'Header struk 1–80 karakter.';
        if (d.receiptFooter.length > 160) e.receiptFooter = 'Maksimal 160 karakter.';
        this.errors = e;
        return Object.keys(e).length === 0;
    },

    errorFor(field) {
        return this.errors[field];
    },

    async save() {
        if (!this.validateAll()) {
            // buka tab error pertama
            const fieldTab = {
                businessName: 'general', storeName: 'store', storeEmail: 'store', storePhone: 'store',
                streetAddress: 'store', city: 'store', postalCode: 'store', country: 'store',
                closingTime: 'store', openDays: 'store', defaultLowStockThreshold: 'store',
                payments: 'payments', defaultPaymentMethod: 'payments', taxName: 'taxes', taxRatePercent: 'taxes',
                receiptHeader: 'receipt', receiptFooter: 'receipt',
            };
            const first = Object.keys(this.errors)[0];
            this.activeTab = fieldTab[first] ?? 'general';
            this.$nextTick(() => document.querySelector('[aria-invalid="true"]')?.focus());
            this.$store.posy.toast('Periksa kembali bidang yang ditandai.', { type: 'warning' });
            return;
        }
        this.saving = true;
        await new Promise((r) => setTimeout(r, 400));
        this.$store.posy.saveSettings(this.draft);
        this.saved = JSON.parse(JSON.stringify(this.draft));

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            await fetch('/api/v1/settings', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                },
                body: JSON.stringify({
                    table_service_fee_enabled: !!this.draft.tableServiceFeeEnabled,
                    table_service_fee_cents: Number(this.draft.tableServiceFeeCents || 0),
                    tax_enabled: !!this.draft.taxEnabled,
                    tax_name: this.draft.taxName,
                    tax_rate_bps: Math.round(Number(this.draft.taxRatePercent || 0) * 100),
                    store_name: this.draft.storeName,
                    store_email: this.draft.storeEmail,
                    store_phone: this.draft.storePhone,
                    store_address: this.draft.streetAddress,
                }),
            });
        } catch (e) {}

        this.saving = false;
        this.$store.posy.toast('Pengaturan berhasil disimpan');
    },

    resetToDefaults() {
        this.draft = JSON.parse(JSON.stringify(DEFAULT_SETTINGS));
        this.resetDefaultsOpen = false;
        this.$store.posy.toast('Default dimuat ke draf — tekan Simpan Perubahan untuk menerapkan.', { type: 'info' });
    },

    resetDemoData() {
        this.$store.posy.resetDemoData();
        this.resetDataOpen = false;
        this.saved = JSON.parse(JSON.stringify(this.$store.posy.settings));
        this.draft = JSON.parse(JSON.stringify(this.saved));
        this.$store.posy.toast('Seluruh data demo dikembalikan ke kondisi awal');
    },

    /* toggle helper */
    toggle(list, value) {
        const i = list.indexOf(value);
        i > -1 ? list.splice(i, 1) : list.push(value);
    },

    /* pratinjau struk memakai fixture keranjang referensi */
    get previewItems() {
        return [
            { name: 'Cappuccino', quantity: 1, unitPrice: 35000 },
            { name: 'Bagel', quantity: 2, unitPrice: 28000 },
            { name: 'Caesar Salad', quantity: 1, unitPrice: 65000 },
            { name: 'Orange Juice', quantity: 1, unitPrice: 29000 },
        ];
    },

    get previewTotals() {
        const subtotal = this.previewItems.reduce((a, it) => a + it.unitPrice * it.quantity, 0);
        const tax = this.draft.taxEnabled ? Math.round((subtotal * this.draft.taxRatePercent) / 100) : 0;
        return { subtotal, tax, total: subtotal + tax };
    },

    downloadSampleReceipt() {
        const d = this.draft;
        const html = generate80mmReceiptHtml({
            storeName: d.storeName || 'PosyMart Coffee & Eatery',
            storeAddress: d.receiptShowAddress ? `${d.streetAddress || ''}, ${d.city || ''} ${d.postalCode || ''}`.trim() : '',
            storePhone: d.receiptShowAddress ? (d.storePhone || '') : '',
            receiptHeader: d.receiptHeader || 'PosyMart',
            receiptFooter: d.receiptFooter || 'Terima kasih telah berbelanja bersama kami!',
            saleId: 'TX-SAMPEL',
            createdAt: new Date(),
            orderType: 'dine_in',
            tableNumber: '08',
            customerName: d.receiptShowCustomer ? 'Pelanggan Umum' : '',
            cashierName: d.receiptShowCashier ? 'Kasir PosyMart' : '',
            items: this.previewItems.map(it => ({
                name: it.name,
                quantity: it.quantity,
                unitPrice: it.unitPrice,
            })),
            subtotal: this.previewTotals.subtotal,
            discount: 0,
            tax: this.previewTotals.tax,
            taxName: d.taxName || 'Pajak PB1',
            taxRatePercent: d.taxRatePercent || 8,
            total: this.previewTotals.total,
            paymentMethod: 'Tunai (Cash)',
            tenderedAmount: this.previewTotals.total + 10000,
            changeAmount: 10000,
            status: 'Completed',
            width: d.receiptWidth || '80 mm',
        });
        printHtml('Contoh Struk 80 mm', html, { width: d.receiptWidth || '80 mm' });
    },

    testNotification() {
        this.$store.posy.toast('Contoh notifikasi dalam aplikasi — ini hanya pratinjur.', { type: 'info' });
    },
}));
