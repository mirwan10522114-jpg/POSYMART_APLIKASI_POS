/**
 * PosyMart — Halaman Produk (design.md §18).
 * Katalog, stok demo, tambah/edit produk, bulk actions, ekspor.
 */

import Alpine from 'alpinejs';
import { CATEGORIES } from '../data/seed.js';
import { formatRp, moneyPlain } from '../utils/money.js';
import { toCsv, downloadFile } from '../utils/files.js';

const emptyForm = () => ({
    name: '',
    sku: '',
    category: '',
    price: '',
    trackStock: false,
    stock: 0,
    lowStockThreshold: 8,
    status: 'Active',
    description: '',
    image: null,
});

Alpine.data('productsPage', () => ({
    categories: CATEGORIES,
    // filter
    query: '',
    category: 'Semua',
    status: 'all',
    stockLevel: 'all',
    sortBy: 'catalog',
    // view
    view: 'table',
    // pagination
    page: 1,
    pageSize: 10,
    // selection
    selected: [],
    // form
    formOpen: false,
    editingId: null,
    form: emptyForm(),
    formErrors: {},
    saving: false,
    discardOpen: false,
    // detail
    detailId: null,
    // stok
    adjustOpen: false,
    adjustTarget: null,
    adjustForm: { type: 'add', quantity: 1, reason: '' },
    adjustError: '',
    adjustPreview: null,
    // status dialog
    statusOpen: false,
    statusTarget: null,
    statusTo: 'Inactive',
    loaded: false,

    init() {
        this.$nextTick(() => (this.loaded = true));
    },

    get products() {
        return this.$store.posy.products;
    },

    get stats() {
        const active = this.products.filter((p) => p.status === 'Active');
        const isTracked = (p) => p.trackStock === true || p.track_stock === true;
        const low = active.filter((p) => isTracked(p) && p.stock > 0 && p.stock <= p.lowStockThreshold);
        const totalStock = this.products.filter(isTracked).reduce((a, p) => a + p.stock, 0);
        return { total: this.products.length, active: active.length, low: low.length, totalStock };
    },

    get filtered() {
        const q = this.query.trim().toLowerCase();
        const isTracked = (p) => p.trackStock === true || p.track_stock === true;
        return this.products.filter((p) => {
            if (this.category !== 'Semua' && p.category !== this.category) return false;
            if (this.status === 'active' && p.status !== 'Active') return false;
            if (this.status === 'inactive' && p.status !== 'Inactive') return false;
            if (this.status === 'outofstock' && p.status !== 'OutOfStock') return false;
            if (this.stockLevel === 'in' && (p.status === 'OutOfStock' || (isTracked(p) && p.stock <= 0))) return false;
            if (this.stockLevel === 'low' && (!isTracked(p) || !(p.stock > 0 && p.stock <= p.lowStockThreshold))) return false;
            if (this.stockLevel === 'out' && !(p.status === 'OutOfStock' || (isTracked(p) && p.stock <= 0))) return false;
            if (q) {
                const hay = `${p.name} ${p.sku} ${p.description ?? ''}`.toLowerCase();
                if (!hay.includes(q)) return false;
            }
            return true;
        });
    },

    get sorted() {
        const arr = [...this.filtered];
        switch (this.sortBy) {
            case 'name-asc': arr.sort((a, b) => a.name.localeCompare(b.name) || a.id.localeCompare(b.id)); break;
            case 'name-desc': arr.sort((a, b) => b.name.localeCompare(a.name) || a.id.localeCompare(b.id)); break;
            case 'price-asc': arr.sort((a, b) => a.price - b.price || a.id.localeCompare(b.id)); break;
            case 'price-desc': arr.sort((a, b) => b.price - a.price || a.id.localeCompare(b.id)); break;
            case 'stock-asc': arr.sort((a, b) => a.stock - b.stock || a.id.localeCompare(b.id)); break;
            default: break; // urutan katalog
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
        const cur = this.page;
        return [...new Set([1, n, cur - 1, cur, cur + 1])].filter((x) => x >= 1 && x <= n).sort((a, b) => a - b);
    },

    get paged() {
        if (this.page > this.totalPages) this.page = this.totalPages;
        return this.sorted.slice((this.page - 1) * this.pageSize, this.page * this.pageSize);
    },

    resetPage() {
        this.page = 1;
        this.selected = [];
    },

    clearFilters() {
        this.query = '';
        this.category = 'Semua';
        this.status = 'all';
        this.stockLevel = 'all';
        this.sortBy = 'catalog';
        this.page = 1;
        this.selected = [];
    },

    /* ---------------- seleksi & bulk ---------------- */
    toggleSelect(id) {
        const i = this.selected.indexOf(id);
        if (i > -1) this.selected.splice(i, 1);
        else this.selected.push(id);
    },

    get allPageSelected() {
        return this.paged.length > 0 && this.paged.every((p) => this.selected.includes(p.id));
    },

    get somePageSelected() {
        return this.paged.some((p) => this.selected.includes(p.id)) && !this.allPageSelected;
    },

    toggleSelectAll() {
        if (this.allPageSelected) {
            this.selected = this.selected.filter((id) => !this.paged.some((p) => p.id === id));
        } else {
            this.paged.forEach((p) => {
                if (!this.selected.includes(p.id)) this.selected.push(p.id);
            });
        }
    },

    bulkReviewOpen: false,
    bulkTarget: 'Inactive',

    bulkApply(target) {
        this.bulkTarget = target;
        this.bulkReviewOpen = true;
    },

    bulkConfirm() {
        this.selected.forEach((id) => this.$store.posy.setProductStatus(id, this.bulkTarget));
        this.$store.posy.toast(this.bulkTarget === 'Active' ? 'Produk diaktifkan' : 'Produk dinonaktifkan');
        this.selected = [];
        this.bulkReviewOpen = false;
    },

    /* ---------------- form ---------------- */
    openCreate() {
        if (!this.$store.posy.can('manageProducts')) {
            this.$store.posy.toast('Peran demo Anda tidak dapat mengelola produk.', { type: 'warning' });
            return;
        }
        this.editingId = null;
        this.form = emptyForm();
        this.form.lowStockThreshold = this.$store.posy.settings.defaultLowStockThreshold;
        this.formErrors = {};
        this.formOpen = true;
        this.$nextTick(() => PM.lockBody(true));
    },

    openEdit(id) {
        if (!this.$store.posy.can('manageProducts')) return;
        const p = this.$store.posy.product(id);
        if (!p) return;
        this.editingId = id;
        this.form = {
            name: p.name,
            sku: p.sku,
            category: p.category,
            price: p.price,
            trackStock: p.trackStock !== false,
            stock: p.stock ?? 0,
            lowStockThreshold: p.lowStockThreshold ?? 8,
            status: p.status ?? 'Active',
            description: p.description ?? '',
            image: p.image ?? null,
        };
        this.formErrors = {};
        this.formOpen = true;
        this.detailId = null;
        this.$nextTick(() => PM.lockBody(true));
    },

    get formDirty() {
        if (this.editingId) {
            const p = this.$store.posy.product(this.editingId);
            if (!p) return false;
            return (
                this.form.name !== p.name || this.form.sku !== p.sku || this.form.category !== p.category ||
                String(this.form.price) !== String(p.price) || Boolean(this.form.trackStock) !== (p.trackStock !== false) ||
                this.form.stock !== p.stock || this.form.lowStockThreshold !== p.lowStockThreshold ||
                this.form.status !== p.status || this.form.description !== (p.description ?? '') ||
                this.form.image !== (p.image ?? null)
            );
        }
        return Object.values(this.form).some((v) => v !== '' && v !== null && v !== 0 && v !== 8 && v !== false);
    },

    closeForm(force = false) {
        if (!force && this.formDirty) {
            this.discardOpen = true;
            return;
        }
        this.formOpen = false;
        this.$nextTick(() => PM.lockBody(false));
    },

    validateForm() {
        const e = {};
        const name = this.form.name.trim();
        if (name.length < 2 || name.length > 80) e.name = 'Masukkan nama produk (2–80 karakter).';
        const sku = this.form.sku.trim().toUpperCase();
        if (!/^[A-Za-z0-9_-]{3,32}$/.test(sku)) e.sku = 'SKU 3–32 karakter alfanumerik, tanda hubung, atau garis bawah.';
        else if (this.products.some((p) => p.sku.toUpperCase() === sku && p.id !== this.editingId)) e.sku = 'SKU ini sudah digunakan.';
        if (!this.form.category) e.category = 'Pilih kategori.';
        const price = Number(this.form.price);
        if (!Number.isFinite(price) || price <= 0 || price > 999999999) e.price = 'Masukkan harga lebih dari 0.';
        else if (Math.round(price) !== price) e.price = 'Harga dalam Rupiah bulat (tanpa sen).';
        if (this.form.trackStock) {
            const stock = Number(this.form.stock);
            if (!Number.isInteger(stock) || stock < 0 || stock > 99999) e.stock = 'Masukkan jumlah unit bulat.';
            const thr = Number(this.form.lowStockThreshold);
            if (!Number.isInteger(thr) || thr < 0 || thr > 99999) e.lowStockThreshold = 'Masukkan ambang batas bulat.';
        }
        if ((this.form.description ?? '').length > 500) e.description = 'Maksimum 500 karakter.';
        this.formErrors = e;
        return Object.keys(e).length === 0;
    },

    async saveForm() {
        if (!this.validateForm()) {
            const first = Object.keys(this.formErrors)[0];
            this.$nextTick(() => document.getElementById(`fld-${first}`)?.focus());
            return;
        }
        this.saving = true;
        await new Promise((r) => setTimeout(r, 500));
        this.$store.posy.saveProduct(
            {
                ...this.form,
                name: this.form.name.trim(),
                sku: this.form.sku.trim(),
                price: Number(this.form.price),
                trackStock: Boolean(this.form.trackStock),
                stock: this.form.trackStock ? Number(this.form.stock || 0) : 0,
                lowStockThreshold: this.form.trackStock ? Number(this.form.lowStockThreshold || 0) : 0,
                status: this.form.status,
            },
            this.editingId
        );
        this.saving = false;
        this.formOpen = false;
        this.$store.posy.toast(this.editingId ? 'Produk diperbarui' : 'Produk ditambahkan');
        if (this.totalRows < this.products.length && this.editingId === null) {
            // produk baru mungkin tersembunyi oleh filter aktif
            const visible = this.sorted.some((p) => p.sku === this.form.sku.trim().toUpperCase());
            if (!visible) {
                this.$store.posy.toast('Produk disimpan. Hapus filter untuk melihatnya.', {
                    type: 'info',
                    actionLabel: 'Hapus Filter',
                    onAction: () => this.clearFilters(),
                });
            }
        }
        this.$nextTick(() => PM.lockBody(false));
    },

    onImageChange(event) {
        const file = event.target.files?.[0];
        if (!file) return;
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            this.formErrors.image = 'Pilih gambar yang didukung di bawah 5 MB.';
            event.target.value = '';
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            this.formErrors.image = 'Pilih gambar yang didukung di bawah 5 MB.';
            event.target.value = '';
            return;
        }
        const reader = new FileReader();
        reader.onload = () => {
            this.form.image = reader.result;
            delete this.formErrors.image;
        };
        reader.readAsDataURL(file);
    },

    removeImage() {
        this.form.image = null;
    },

    /* ---------------- detail ---------------- */
    get detailProduct() {
        return this.detailId ? this.$store.posy.product(this.detailId) : null;
    },

    get detailActivity() {
        return this.$store.posy.stockActivity.filter((a) => a.productId === this.detailId).slice().reverse();
    },

    openDetail(id) {
        this.detailId = id;
        this.$nextTick(() => PM.lockBody(true));
    },

    closeDetail() {
        this.detailId = null;
        this.$nextTick(() => PM.lockBody(false));
    },

    stockBadge(p) {
        if (p.status === 'OutOfStock') {
            return { label: 'Habis', cls: 'badge-danger', isOutOfStock: true, text: 'Habis' };
        }
        const isTracked = p.trackStock === true || p.track_stock === true;
        if (!isTracked) {
            return { label: 'Tersedia', cls: 'badge-neutral', isNoTrack: true, text: 'Cek Lapangan' };
        }
        if (p.stock === 0) return { label: 'Stok Habis', cls: 'badge-danger', isOutOfStock: true, text: 'Stok Habis' };
        if (p.stock <= p.lowStockThreshold) return { label: 'Stok Menipis', cls: 'badge-amber', isLow: true, text: 'Stok Menipis' };
        return { label: 'Tersedia', cls: 'badge-neutral', text: 'Tersedia' };
    },

    toggleAvailability(id) {
        if (!this.$store.posy.can('manageProducts')) return;
        const p = this.$store.posy.product(id);
        if (!p) return;
        const newStatus = p.status === 'OutOfStock' ? 'Active' : 'OutOfStock';
        this.$store.posy.setProductStatus(id, newStatus);
        this.$store.posy.toast(
            newStatus === 'OutOfStock'
                ? `Produk ${p.name} ditandai Habis`
                : `Produk ${p.name} ditandai Tersedia`,
            { type: newStatus === 'OutOfStock' ? 'warning' : 'success' }
        );
    },

    /* ---------------- penyesuaian stok ---------------- */
    openAdjust(id) {
        if (!this.$store.posy.can('manageProducts')) return;
        this.adjustTarget = this.$store.posy.product(id);
        this.adjustForm = { type: 'add', quantity: 1, reason: '' };
        this.adjustError = '';
        this.adjustOpen = true;
        this.detailId = null;
    },

    get adjustResult() {
        if (!this.adjustTarget) return null;
        const q = Number(this.adjustForm.quantity);
        const before = this.adjustTarget.stock;
        if (!Number.isFinite(q)) return null;
        if (this.adjustForm.type === 'add') return before + q;
        if (this.adjustForm.type === 'remove') return before - q;
        return q;
    },

    async saveAdjust() {
        const q = Number(this.adjustForm.quantity);
        const reason = this.adjustForm.reason.trim();
        if (!Number.isInteger(q) || q < (this.adjustForm.type === 'set' ? 0 : 1) || q > 99999) {
            this.adjustError = 'Masukkan jumlah yang valid.';
            return;
        }
        if (this.adjustForm.type === 'remove' && q > this.adjustTarget.stock) {
            this.adjustError = 'Penyesuaian tidak dapat mengurangi stok di bawah nol.';
            return;
        }
        if (reason.length < 3 || reason.length > 200) {
            this.adjustError = 'Alasan wajib 3–200 karakter.';
            return;
        }
        this.adjustError = '';
        const res = await this.$store.posy.adjustStock(this.adjustTarget.id, this.adjustForm.type, q, reason);
        if (res.ok) {
            this.adjustOpen = false;
            this.$store.posy.toast(`Stok ${this.adjustTarget.name} diperbarui`);
        } else {
            this.adjustError = res.reason;
        }
    },

    /* ---------------- status ---------------- */
    openStatus(id, to) {
        if (!this.$store.posy.can('manageProducts')) return;
        this.statusTarget = this.$store.posy.product(id);
        this.statusTo = to;
        this.statusOpen = true;
        this.detailId = null;
    },

    confirmStatus() {
        this.$store.posy.setProductStatus(this.statusTarget.id, this.statusTo);
        this.$store.posy.toast(this.statusTo === 'Active' ? 'Produk diaktifkan' : 'Produk dinonaktifkan');
        this.statusOpen = false;
    },

    exportCsv() {
        const headers = ['id', 'sku', 'name', 'category', 'price', 'lacak_stok', 'stock', 'low_stock_threshold', 'status'];
        const rows = this.sorted.map((p) => [
            p.id, p.sku, p.name, p.category, moneyPlain(p.price),
            p.trackStock !== false ? 'Ya' : 'Tidak (Cek Lapangan)',
            p.trackStock !== false ? p.stock : '-',
            p.trackStock !== false ? p.lowStockThreshold : '-',
            p.status === 'Active' ? 'Active' : p.status === 'OutOfStock' ? 'OutOfStock' : 'Inactive'
        ]);
        downloadFile('posymart-products.csv', toCsv(headers, rows));
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
