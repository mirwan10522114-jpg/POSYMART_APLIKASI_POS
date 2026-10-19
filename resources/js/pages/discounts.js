/**
 * PosyMart — Halaman Diskon (design.md §23).
 * Pengelolaan promosi: daftar, form, detail, duplikat, hapus.
 */

import Alpine from 'alpinejs';
import { CATEGORIES } from '../data/seed.js';

const emptyForm = () => ({
    name: '',
    code: '',
    description: '',
    type: 'percentage',
    value: '',
    appliesTo: 'all',
    categories: [],
    products: [],
    customers: [],
    customerEligibility: 'all',
    minSpend: 0,
    unlimited: true,
    usageLimit: 100,
    startDate: '',
    startTime: '00:00',
    endDate: '',
    endTime: '00:00',
    enabled: true,
    published: true,
});

Alpine.data('discountsPage', () => ({
    categories: CATEGORIES,
    query: '',
    status: 'all',
    type: 'all',
    appliesTo: 'all',
    sortBy: 'created-desc',
    page: 1,
    pageSize: 10,
    // form
    formOpen: false,
    editingId: null,
    form: emptyForm(),
    formErrors: {},
    saving: false,
    discardOpen: false,
    productSearch: '',
    // detail
    detailId: null,
    kebabRowId: null,
    deleteOpen: false,
    deleteTarget: null,
    loaded: false,

    init() {
        this.$nextTick(() => (this.loaded = true));
    },

    get discounts() {
        return this.$store.posy.discounts;
    },

    effStatus(d) {
        return this.$store.posy.discountEffectiveStatus(d);
    },

    statusBadge(s) {
        return {
            Aktif: 'badge-positive',
            Terjadwal: 'badge-info',
            Draf: 'badge-neutral',
            Kedaluwarsa: 'badge-neutral',
            Nonaktif: 'badge-neutral',
            Habis: 'badge-amber',
        }[s] ?? 'badge-neutral';
    },

    get stats() {
        const list = this.discounts.map((d) => ({ ...d, eff: this.effStatus(d) }));
        return {
            active: list.filter((d) => d.eff === 'Aktif').length,
            scheduled: list.filter((d) => d.eff === 'Terjadwal').length,
            redemptions: list.reduce((a, d) => a + d.usedCount, 0),
            usedToday: 0,
        };
    },

    get filtered() {
        const q = this.query.trim().toLowerCase();
        return this.discounts
            .map((d) => ({ ...d, eff: this.effStatus(d) }))
            .filter((d) => {
                if (this.status !== 'all' && d.eff !== this.status) return false;
                if (this.type !== 'all' && d.type !== this.type) return false;
                if (this.appliesTo !== 'all' && d.appliesTo !== this.appliesTo) return false;
                if (q && !`${d.name} ${d.code}`.toLowerCase().includes(q)) return false;
                return true;
            });
    },

    get sorted() {
        const arr = [...this.filtered];
        const idOf = (d) => d.createdAt ?? '';
        switch (this.sortBy) {
            case 'name-asc': arr.sort((a, b) => a.name.localeCompare(b.name) || a.code.localeCompare(b.code)); break;
            case 'name-desc': arr.sort((a, b) => b.name.localeCompare(a.name) || a.code.localeCompare(b.code)); break;
            case 'value-desc': arr.sort((a, b) => b.value - a.value); break;
            case 'value-asc': arr.sort((a, b) => a.value - b.value); break;
            case 'start-desc': arr.sort((a, b) => (b.startDate ?? '').localeCompare(a.startDate ?? '')); break;
            case 'usage-desc': arr.sort((a, b) => b.usedCount - a.usedCount); break;
            default: arr.sort((a, b) => idOf(b).localeCompare(idOf(a)));
        }
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
        return [...new Set([1, n, this.page - 1, this.page, this.page + 1])].filter((x) => x >= 1 && x <= n).sort((a, b) => a - b);
    },

    get paged() {
        if (this.page > this.totalPages) this.page = this.totalPages;
        return this.sorted.slice((this.page - 1) * this.pageSize, this.page * this.pageSize);
    },

    resetPage() {
        this.page = 1;
    },

    resetFilters() {
        this.query = '';
        this.status = 'all';
        this.type = 'all';
        this.appliesTo = 'all';
        this.sortBy = 'created-desc';
        this.page = 1;
    },

    valueLabel(d) {
        return d.type === 'percentage' ? `${d.value}%` : this.$rp(d.value);
    },

    appliesLabel(d) {
        if (d.appliesTo === 'all') return 'Semua produk';
        if (d.appliesTo === 'categories') return d.categories.join(', ');
        return `${d.products.length} produk terpilih`;
    },

    validityLabel(d) {
        if (!d.startDate || !d.endDate) return 'Belum dijadwalkan';
        const f = (iso) => this.$rpfmt.dateTime(iso);
        return `${f(d.startDate)} → ${f(d.endDate)}`;
    },

    /* ---------------- form ---------------- */
    openCreate() {
        if (!this.$store.posy.can('manageDiscounts')) {
            this.$store.posy.toast('Peran demo Anda tidak dapat mengelola diskon.', { type: 'warning' });
            return;
        }
        this.editingId = null;
        this.form = emptyForm();
        this.formErrors = {};
        this.productSearch = '';
        this.formOpen = true;
        this.$nextTick(() => PM.lockBody(true));
    },

    openEdit(id) {
        if (!this.$store.posy.can('manageDiscounts')) return;
        const d = this.$store.posy.discount(id);
        if (!d) return;
        this.editingId = id;
        this.form = {
            name: d.name,
            code: d.code,
            description: d.description ?? '',
            type: d.type,
            value: d.value,
            appliesTo: d.appliesTo,
            categories: [...(d.categories ?? [])],
            products: [...(d.products ?? [])],
            customers: [...(d.customers ?? [])],
            customerEligibility: (d.customers?.length ?? 0) > 0 ? 'selected' : 'all',
            minSpend: d.minSpend ?? 0,
            unlimited: d.usageLimit === null,
            usageLimit: d.usageLimit ?? 100,
            startDate: d.startDate ? d.startDate.slice(0, 10) : '',
            startTime: d.startDate ? d.startDate.slice(11, 16) : '00:00',
            endDate: d.endDate ? d.endDate.slice(0, 10) : '',
            endTime: d.endDate ? d.endDate.slice(11, 16) : '00:00',
            enabled: d.enabled,
            published: d.published,
        };
        this.formErrors = {};
        this.productSearch = '';
        this.formOpen = true;
        this.detailId = null;
        this.$nextTick(() => PM.lockBody(true));
    },

    get formDirty() {
        if (this.editingId) return true;
        return Object.values(this.form).some((v) => v !== '' && v !== null && v !== 0 && v !== true && !(Array.isArray(v) && v.length === 0));
    },

    closeForm(force = false) {
        if (!force && this.formDirty) {
            this.discardOpen = true;
            return;
        }
        this.formOpen = false;
        this.$nextTick(() => PM.lockBody(false));
    },

    get formCatalog() {
        const q = this.productSearch.trim().toLowerCase();
        return this.$store.posy.products.filter((p) => !q || p.name.toLowerCase().includes(q));
    },

    toggleIn(list, value) {
        const i = list.indexOf(value);
        i > -1 ? list.splice(i, 1) : list.push(value);
    },

    validateForm(publishing) {
        const e = {};
        if (this.form.name.trim().length < 2 || this.form.name.trim().length > 80) e.name = 'Masukkan nama diskon (2–80 karakter).';
        const code = this.form.code.trim().toUpperCase();
        if (!/^[A-Z0-9_-]{3,24}$/.test(code)) e.code = 'Kode 3–24 karakter (A–Z, 0–9, garis bawah, tanda hubung).';
        else if (this.discounts.some((d) => d.code.toUpperCase() === code && d.id !== this.editingId)) e.code = 'Kode ini sudah ada.';
        const value = Number(this.form.value);
        if (!Number.isFinite(value) || value <= 0) e.value = 'Masukkan nilai lebih dari 0.';
        else if (this.form.type === 'percentage' && (value > 100 || !Number.isFinite(value))) e.value = 'Masukkan nilai antara 0 dan 100.';
        if ((this.form.description ?? '').length > 240) e.description = 'Maksimum 240 karakter.';
        if (this.form.appliesTo === 'categories' && !this.form.categories.length) e.categories = 'Pilih minimal satu kategori.';
        if (this.form.appliesTo === 'products' && !this.form.products.length) e.products = 'Pilih minimal satu produk.';
        if (this.form.customerEligibility === 'selected' && !this.form.customers.length) e.customers = 'Pilih minimal satu pelanggan.';
        if (Number(this.form.minSpend) < 0 || !Number.isFinite(Number(this.form.minSpend))) e.minSpend = 'Minimum belanja tidak boleh negatif.';
        if (!this.form.unlimited) {
            const lim = Number(this.form.usageLimit);
            if (!Number.isInteger(lim) || lim < 1 || lim > 100000) e.usageLimit = 'Batas pemakaian 1–100000.';
            else if (this.editingId) {
                const d = this.$store.posy.discount(this.editingId);
                if (d && lim < d.usedCount) e.usageLimit = 'Batas tidak boleh kurang dari pemakaian saat ini.';
            }
        }
        if (publishing) {
            if (!this.form.startDate) e.startDate = 'Tanggal mulai wajib untuk promo terbit.';
            if (!this.form.endDate) e.endDate = 'Tanggal berakhir wajib untuk promo terbit.';
            if (this.form.startDate && this.form.endDate) {
                const s = `${this.form.startDate}T${this.form.startTime || '00:00'}:00+07:00`;
                const en = `${this.form.endDate}T${this.form.endTime || '00:00'}:00+07:00`;
                if (en <= s) e.endDate = 'Tanggal berakhir harus setelah tanggal mulai.';
            }
        }
        this.formErrors = e;
        return Object.keys(e).length === 0;
    },

    async saveForm(publishing) {
        if (!this.validateForm(publishing)) return;
        this.saving = true;
        await new Promise((r) => setTimeout(r, 450));
        const startIso = this.form.startDate ? `${this.form.startDate}T${this.form.startTime || '00:00'}:00+07:00` : null;
        const endIso = this.form.endDate ? `${this.form.endDate}T${this.form.endTime || '00:00'}:00+07:00` : null;
        this.$store.posy.saveDiscount(
            {
                ...this.form,
                name: this.form.name.trim(),
                code: this.form.code.trim().toUpperCase(),
                value: Number(this.form.value),
                minSpend: Number(this.form.minSpend) || 0,
                usageLimit: this.form.unlimited ? null : Number(this.form.usageLimit),
                startDate: startIso,
                endDate: endIso,
                published: publishing,
                customers: this.form.customerEligibility === 'selected' ? this.form.customers : [],
            },
            this.editingId
        );
        this.saving = false;
        this.formOpen = false;
        this.$store.posy.toast(this.editingId ? 'Diskon diperbarui' : publishing ? 'Diskon dibuat' : 'Draf diskon disimpan');
        this.$nextTick(() => PM.lockBody(false));
    },

    /* ---------------- detail ---------------- */
    get detailDiscount() {
        return this.detailId ? this.$store.posy.discount(this.detailId) : null;
    },

    openDetail(id) {
        this.detailId = id;
        this.kebabRowId = null;
        this.$nextTick(() => PM.lockBody(true));
    },

    closeDetail() {
        this.detailId = null;
        this.$nextTick(() => PM.lockBody(false));
    },

    toggleEnabled(id) {
        if (!this.$store.posy.can('manageDiscounts')) return;
        const d = this.$store.posy.discount(id);
        if (d) {
            this.$store.posy.setDiscountEnabled(id, !d.enabled);
            this.$store.posy.toast(d.enabled ? 'Diskon dinonaktifkan' : 'Diskon diaktifkan');
        }
    },

    duplicate(id) {
        const rec = this.$store.posy.duplicateDiscount(id);
        if (rec) {
            this.$store.posy.toast('Salinan draf dibuat — atur kode baru');
            this.openEdit(rec.id);
        }
    },

    openDelete(id) {
        const d = this.$store.posy.discount(id);
        if (!d) return;
        if (!(d.published === false || d.usedCount === 0)) {
            this.$store.posy.toast('Diskon yang pernah dipakai tidak dapat dihapus — gunakan Nonaktifkan.', { type: 'warning' });
            return;
        }
        this.deleteTarget = d;
        this.deleteOpen = true;
        this.kebabRowId = null;
    },

    confirmDelete() {
        if (this.$store.posy.deleteDiscount(this.deleteTarget.id)) {
            this.deleteOpen = false;
            this.detailId = null;
            this.$store.posy.toast('Diskon dihapus');
        }
    },

    exportCsv() {
        import('../utils/files.js').then(({ toCsv, downloadFile }) => {
            const headers = ['name', 'code', 'type', 'value', 'applies_to', 'start_date', 'end_date', 'used_count', 'usage_limit', 'status', 'enabled'];
            const rows = this.sorted.map((d) => [
                d.name, d.code, d.type === 'percentage' ? 'Percentage' : 'Fixed Amount', d.value,
                d.appliesTo === 'all' ? 'Semua produk' : d.appliesTo === 'categories' ? d.categories.join(';') : `${d.products.length} produk`,
                d.startDate ?? '', d.endDate ?? '', d.usedCount, d.usageLimit ?? 'Unlimited', d.eff, d.enabled ? 'Yes' : 'No',
            ]);
            downloadFile('posymart-discounts.csv', toCsv(headers, rows));
            this.$store.posy.toast('CSV diunduh');
        });
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
