/**
 * PosyMart — Halaman Pelanggan Terdaftar.
 * Terintegrasi dengan backend API /api/v1/customers dan real-time guest QR registration.
 */

import Alpine from 'alpinejs';
import { moneyPlain, roundHalfUp } from '../utils/money.js';
import { toCsv, downloadFile } from '../utils/files.js';

const emptyForm = () => ({ name: '', email: '', phone: '', address: '', status: 'Active', notes: '' });

Alpine.data('customersPage', () => ({
    query: '',
    status: 'all',
    source: 'all', // 'all' | 'qr' | 'manual'
    activity: 'all',
    sortBy: 'newest',
    page: 1,
    pageSize: 10,
    selected: [],
    loading: false,
    customersList: [],

    // form state
    formOpen: false,
    editingId: null,
    form: emptyForm(),
    formErrors: {},
    saving: false,
    discardOpen: false,

    // profil & riwayat drawer
    profileId: null,
    profileCustomerData: null,
    profileLoading: false,
    profileTab: 'overview',
    notesEditing: false,
    notesDraft: '',
    bulkReviewOpen: false,
    bulkTarget: 'Inactive',
    statusOpen: false,
    drawerNotFound: false,
    loaded: false,

    init() {
        this.$nextTick(() => (this.loaded = true));
        this.loadCustomers();
        const params = new URLSearchParams(window.location.search);
        if (params.get('customer')) {
            const id = params.get('customer');
            setTimeout(() => {
                this.openProfile(id);
            }, 250);
        }
    },

    async loadCustomers() {
        this.loading = true;
        try {
            const res = await fetch('/api/v1/customers?limit=250');
            if (res.ok) {
                const resData = await res.json();
                const items = resData.data || [];
                this.customersList = items;

                // Sinkronkan ke Alpine store jika tersedia
                if (this.$store?.posy?.customers) {
                    items.forEach((bc) => {
                        const idx = this.$store.posy.customers.findIndex((x) => x.id === bc.id);
                        const mapped = {
                            id: bc.id,
                            name: bc.name,
                            email: bc.email || '',
                            phone: bc.phone || '',
                            address: bc.address || '',
                            status: bc.status || 'Active',
                            notes: bc.notes || '',
                            joinedAt: bc.joined_at || bc.created_at,
                            ordersCount: bc.orders_count || 0,
                            totalSpent: Number(bc.total_spent_cents || 0),
                        };
                        if (idx >= 0) {
                            this.$store.posy.customers[idx] = { ...this.$store.posy.customers[idx], ...mapped };
                        } else {
                            this.$store.posy.customers.push(mapped);
                        }
                    });
                }
            }
        } catch (e) {
            console.error('Gagal memuat data pelanggan:', e);
        } finally {
            this.loading = false;
        }
    },

    get customers() {
        if (this.customersList && this.customersList.length > 0) {
            return this.customersList;
        }
        return this.$store?.posy?.customers || [];
    },

    /* Metrik per pelanggan */
    metricsFor(c) {
        let orders = c.orders_count;
        let spent = c.total_spent_cents !== undefined ? Number(c.total_spent_cents) : undefined;

        // Fallback jika tidak ada agregasi backend
        if (orders === undefined && this.$store?.posy?.sales) {
            const sales = this.$store.posy.sales.filter((s) => s.customerId === c.id && s.status === 'Completed');
            orders = sales.length;
            spent = sales.reduce((a, s) => a + s.total, 0);
        }

        orders = orders || 0;
        spent = spent || 0;

        return {
            orders,
            spent,
            avg: orders ? roundHalfUp(spent / orders) : 0,
            last: c.last_order_at || null,
        };
    },

    get stats() {
        const list = this.customers;
        const total = list.length;
        const active = list.filter((c) => (c.status || 'Active') === 'Active').length;
        const qrCount = list.filter((c) => (c.notes || '').toLowerCase().includes('qr')).length;

        let totalOrders = 0;
        let totalSpent = 0;

        for (const c of list) {
            const m = this.metricsFor(c);
            totalOrders += m.orders;
            totalSpent += m.spent;
        }

        return {
            total,
            active,
            qrCount,
            totalOrders,
            totalSpent,
        };
    },

    normalizePhone(p) {
        return (p || '').replace(/\D/g, '');
    },

    waLink(phone) {
        if (!phone) return '#';
        let clean = phone.replace(/[^0-9]/g, '');
        if (clean.startsWith('0')) clean = '62' + clean.slice(1);
        return 'https://wa.me/' + clean;
    },

    fmtDate(d) {
        if (!d) return '—';
        const date = new Date(d);
        if (isNaN(date.getTime())) return d;
        return date.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    },

    initials(name) {
        if (!name) return 'PL';
        return name
            .split(' ')
            .map((w) => w[0])
            .slice(0, 2)
            .join('')
            .toUpperCase();
    },

    get filtered() {
        const q = this.query.trim().toLowerCase();
        return this.customers.filter((c) => {
            if (this.status === 'active' && (c.status || 'Active') !== 'Active') return false;
            if (this.status === 'inactive' && (c.status || 'Active') !== 'Inactive') return false;

            const isQr = (c.notes || '').toLowerCase().includes('qr');
            if (this.source === 'qr' && !isQr) return false;
            if (this.source === 'manual' && isQr) return false;

            const m = this.metricsFor(c);
            if (this.activity === 'has' && m.orders === 0) return false;
            if (this.activity === 'none' && m.orders > 0) return false;

            if (q) {
                const hay = `${c.name || ''} ${c.email || ''} ${c.phone || ''} ${c.id || ''}`.toLowerCase();
                const digits = this.normalizePhone(q);
                const phoneHit = digits.length >= 3 && this.normalizePhone(c.phone).includes(digits);
                if (!hay.includes(q) && !phoneHit) return false;
            }
            return true;
        });
    },

    get sorted() {
        const arr = [...this.filtered];
        switch (this.sortBy) {
            case 'name-desc':
                arr.sort((a, b) => (b.name || '').localeCompare(a.name || ''));
                break;
            case 'most_orders':
                arr.sort((a, b) => this.metricsFor(b).orders - this.metricsFor(a).orders);
                break;
            case 'spent-desc':
            case 'spent_desc':
                arr.sort((a, b) => this.metricsFor(b).spent - this.metricsFor(a).spent);
                break;
            case 'newest':
                arr.sort((a, b) => new Date(b.joined_at || b.joinedAt || b.created_at || 0) - new Date(a.joined_at || a.joinedAt || a.created_at || 0));
                break;
            case 'name-asc':
            default:
                arr.sort((a, b) => (a.name || '').localeCompare(b.name || ''));
                break;
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
        this.selected = [];
    },

    clearFilters() {
        this.query = '';
        this.status = 'all';
        this.source = 'all';
        this.activity = 'all';
        this.sortBy = 'newest';
        this.page = 1;
        this.selected = [];
    },

    /* Seleksi bulk */
    toggleSelect(id) {
        const i = this.selected.indexOf(id);
        i > -1 ? this.selected.splice(i, 1) : this.selected.push(id);
    },

    get allPageSelected() {
        return this.paged.length > 0 && this.paged.every((c) => this.selected.includes(c.id));
    },

    toggleSelectAll() {
        if (this.allPageSelected) this.selected = this.selected.filter((id) => !this.paged.some((c) => c.id === id));
        else this.paged.forEach((c) => !this.selected.includes(c.id) && this.selected.push(c.id));
    },

    bulkApply(target) {
        this.bulkTarget = target;
        this.bulkReviewOpen = true;
    },

    async bulkConfirm() {
        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
        for (const id of this.selected) {
            try {
                await fetch(`/api/v1/customers/${id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                    body: JSON.stringify({ status: this.bulkTarget }),
                });
            } catch (e) {
                console.error(e);
            }
        }
        await this.loadCustomers();
        this.$store?.posy?.toast(this.bulkTarget === 'Active' ? 'Pelanggan terpilih diaktifkan' : 'Pelanggan terpilih dinonaktifkan');
        this.selected = [];
        this.bulkReviewOpen = false;
    },

    /* Form Tambah & Ubah */
    openCreate() {
        this.editingId = null;
        this.form = emptyForm();
        this.formErrors = {};
        this.formOpen = true;
        this.$nextTick(() => window.PM?.lockBody?.(true));
    },

    openEdit(id) {
        const c = this.customers.find((x) => x.id === id) || (this.$store?.posy?.customer ? this.$store.posy.customer(id) : null);
        if (!c) return;
        this.editingId = id;
        this.form = {
            name: c.name || '',
            email: c.email || '',
            phone: c.phone || '',
            address: c.address || '',
            status: c.status || 'Active',
            notes: c.notes || '',
        };
        this.formErrors = {};
        this.formOpen = true;
        this.profileId = null;
        this.$nextTick(() => window.PM?.lockBody?.(true));
    },

    get formDirty() {
        if (this.editingId) {
            const c = this.customers.find((x) => x.id === this.editingId);
            return (
                c &&
                (this.form.name !== (c.name || '') ||
                    this.form.email !== (c.email || '') ||
                    this.form.phone !== (c.phone || '') ||
                    this.form.address !== (c.address || '') ||
                    this.form.status !== (c.status || 'Active') ||
                    this.form.notes !== (c.notes || ''))
            );
        }
        return Object.values(this.form).some((v) => v !== '' && v !== 'Active');
    },

    closeForm(force = false) {
        if (!force && this.formDirty) {
            this.discardOpen = true;
            return;
        }
        this.formOpen = false;
        this.$nextTick(() => window.PM?.lockBody?.(false));
    },

    validateForm() {
        const e = {};
        if (!this.form.name || this.form.name.trim().length < 2) {
            e.name = 'Masukkan nama lengkap minimal 2 karakter.';
        }
        const email = (this.form.email || '').trim();
        if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            e.email = 'Format email tidak valid.';
        }
        const phone = (this.form.phone || '').trim();
        if (phone) {
            const digits = this.normalizePhone(phone);
            if (digits.length < 7 || digits.length > 20) {
                e.phone = 'Nomor telepon harus antara 7–20 digit.';
            }
        }
        if (!email && !phone) {
            e.phone = 'Minimal isi salah satu kontak: nomor telepon/WhatsApp atau email.';
        }
        this.formErrors = e;
        return Object.keys(e).length === 0;
    },

    async saveForm() {
        if (!this.validateForm()) {
            this.$nextTick(() => document.getElementById('cf-name')?.focus());
            return;
        }

        this.saving = true;
        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const url = this.editingId ? `/api/v1/customers/${this.editingId}` : '/api/v1/customers';
        const method = this.editingId ? 'PUT' : 'POST';

        try {
            const payload = {
                name: this.form.name.trim(),
                email: this.form.email.trim() || null,
                phone: this.form.phone.trim() || null,
                address: this.form.address.trim() || null,
                status: this.form.status || 'Active',
                notes: this.form.notes.trim() || null,
            };

            const res = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                },
                body: JSON.stringify(payload),
            });

            if (res.ok) {
                await this.loadCustomers();
                this.formOpen = false;
                this.$store?.posy?.toast(this.editingId ? 'Data pelanggan diperbarui' : 'Pelanggan berhasil didaftarkan');
                window.PM?.lockBody?.(false);
            } else {
                const errData = await res.json();
                if (errData.errors) {
                    this.formErrors = errData.errors;
                } else if (errData.error?.message) {
                    this.$store?.posy?.toast(errData.error.message, { type: 'danger' });
                }
            }
        } catch (e) {
            console.error('Gagal menyimpan pelanggan:', e);
            this.$store?.posy?.toast('Terjadi kesalahan saat menyimpan data.', { type: 'danger' });
        } finally {
            this.saving = false;
        }
    },

    /* Drawer Profil & Riwayat Belanja */
    async openProfile(id) {
        this.profileId = id;
        this.profileTab = 'overview';
        this.drawerNotFound = false;
        this.notesEditing = false;
        this.profileLoading = true;
        this.profileCustomerData = null;

        const url = new URL(window.location);
        url.searchParams.set('customer', id);
        window.history.replaceState({}, '', url);
        window.PM?.lockBody?.(true);

        try {
            const res = await fetch(`/api/v1/customers/${id}`);
            if (res.ok) {
                this.profileCustomerData = await res.json();
            } else {
                this.profileCustomerData = this.customers.find((c) => c.id === id) || null;
            }
        } catch (e) {
            this.profileCustomerData = this.customers.find((c) => c.id === id) || null;
        } finally {
            this.profileLoading = false;
        }
    },

    closeProfile() {
        this.profileId = null;
        this.profileCustomerData = null;
        this.drawerNotFound = false;
        const url = new URL(window.location);
        url.searchParams.delete('customer');
        window.history.replaceState({}, '', url);
        window.PM?.lockBody?.(false);
    },

    get profileCustomer() {
        return this.profileCustomerData || this.customers.find((c) => c.id === this.profileId) || null;
    },

    get profileMetrics() {
        return this.profileCustomer ? this.metricsFor(this.profileCustomer) : null;
    },

    startEditNotes() {
        this.notesDraft = this.profileCustomer?.notes || '';
        this.notesEditing = true;
    },

    async saveNotes() {
        if (!this.profileCustomer) return;
        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
        try {
            await fetch(`/api/v1/customers/${this.profileCustomer.id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify({ notes: this.notesDraft.trim() }),
            });
            this.profileCustomer.notes = this.notesDraft.trim();
            this.notesEditing = false;
            this.$store?.posy?.toast('Catatan pelanggan berhasil disimpan');
            await this.loadCustomers();
        } catch (e) {
            console.error(e);
        }
    },

    openStatus(to) {
        this.bulkTarget = to;
        this.statusOpen = true;
    },

    async confirmStatus() {
        if (!this.profileCustomer) return;
        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
        try {
            await fetch(`/api/v1/customers/${this.profileCustomer.id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify({ status: this.bulkTarget }),
            });
            this.profileCustomer.status = this.bulkTarget;
            this.statusOpen = false;
            this.$store?.posy?.toast(this.bulkTarget === 'Active' ? 'Akun pelanggan diaktifkan' : 'Akun pelanggan dinonaktifkan');
            await this.loadCustomers();
        } catch (e) {
            console.error(e);
        }
    },

    copy(text, label) {
        window.PM?.copyText?.(text)?.then((ok) => {
            this.$store?.posy?.toast(ok ? `${label} berhasil disalin` : `Gagal menyalin ${label}`, { type: ok ? 'success' : 'info' });
        });
    },

    exportCsv() {
        const headers = ['ID Pelanggan', 'Nama Lengkap', 'Nomor Telepon/WA', 'Email', 'Sumber Registrasi', 'Waktu Mendaftar', 'Total Pesanan', 'Total Belanja', 'Status'];
        const rows = this.sorted.map((c) => {
            const m = this.metricsFor(c);
            const isQr = (c.notes || '').toLowerCase().includes('qr');
            return [
                c.id,
                c.name,
                c.phone || '',
                c.email || '',
                isQr ? 'Scan QR Meja' : 'Registrasi Akun',
                this.fmtDate(c.joined_at || c.joinedAt || c.created_at),
                m.orders,
                moneyPlain(m.spent),
                c.status || 'Active',
            ];
        });
        downloadFile('posymart-pelanggan-terdaftar.csv', toCsv(headers, rows));
        this.$store?.posy?.toast('Data pelanggan berhasil diekspor ke CSV');
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
