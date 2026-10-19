/**
 * PosyMart — Halaman Pengguna (design.md §24).
 * Tim, simulasi izin, undangan, proteksi akun current.
 */

import Alpine from 'alpinejs';
import { ROLE_PERMISSIONS } from '../data/seed.js';

const emptyForm = () => ({ name: '', email: '', role: 'Cashier', note: '' });

Alpine.data('usersPage', () => ({
    query: '',
    role: 'all',
    status: 'all',
    sortBy: 'name-asc',
    page: 1,
    pageSize: 10,
    // form
    formOpen: false,
    editingId: null,
    form: emptyForm(),
    formErrors: {},
    saving: false,
    discardOpen: false,
    // detail
    detailId: null,
    // permissions dialog
    permsOpen: false,
    previewRole: 'Admin',
    // dialog status
    statusOpen: false,
    statusTarget: null,
    statusTo: 'Disabled',
    cancelInviteOpen: false,
    cancelTarget: null,
    loaded: false,

    init() {
        this.$nextTick(() => (this.loaded = true));
        const params = new URLSearchParams(window.location.search);
        if (params.get('invite')) {
            const u = this.$store.posy.users.find((x) => x.id === params.get('invite'));
            if (u) {
                this.detailId = u.id;
                this.$store.posy.toast('Tautan undangan demo dibuka.', { type: 'info' });
            }
        }
    },

    get allowed() {
        return this.$store.posy.can('manageUsers');
    },

    get users() {
        return this.$store.posy.users;
    },

    get stats() {
        return {
            total: this.users.length,
            active: this.users.filter((u) => u.status === 'Active').length,
            invited: this.users.filter((u) => u.status === 'Invited').length,
        };
    },

    rolePerms(role) {
        return ROLE_PERMISSIONS[role] ?? {};
    },

    permRows() {
        return [
            ['Melihat Dasbor', true, true, true],
            ['Membuat penjualan & menerapkan diskon', true, true, true],
            ['Melihat katalog produk', true, true, true],
            ['Membuat/mengubah produk dan stok', true, true, false],
            ['Melihat/mengubah data pelanggan', true, true, true],
            ['Melihat pesanan', true, true, true],
            ['Membatalkan pesanan / menyetujui retur', true, true, false],
            ['Membatalkan penjualan / memproses refund', true, true, false],
            ['Membuat draf retur', true, true, true],
            ['Melihat/mengekspor laporan', true, true, false],
            ['Mengelola diskon', true, true, false],
            ['Melihat/mengundang/mengubah tim', true, false, false],
            ['Melihat/mengubah pengaturan toko', true, false, false],
        ];
    },

    get filtered() {
        const q = this.query.trim().toLowerCase();
        return this.users.filter((u) => {
            if (this.role !== 'all' && u.role !== this.role) return false;
            if (this.status !== 'all' && u.status !== this.status) return false;
            if (q && !`${u.name} ${u.email}`.toLowerCase().includes(q)) return false;
            return true;
        });
    },

    get sorted() {
        const arr = [...this.filtered];
        switch (this.sortBy) {
            case 'name-desc': arr.sort((a, b) => b.name.localeCompare(a.name)); break;
            case 'last-desc': arr.sort((a, b) => (b.lastActiveAt ?? '').localeCompare(a.lastActiveAt ?? '')); break;
            case 'added-desc': arr.sort((a, b) => (b.addedAt ?? '').localeCompare(a.addedAt ?? '')); break;
            default: arr.sort((a, b) => a.name.localeCompare(b.name));
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
        this.role = 'all';
        this.status = 'all';
        this.sortBy = 'name-asc';
        this.page = 1;
    },

    initials(name) {
        return name.split(' ').map((w) => w[0]).slice(0, 2).join('').toUpperCase();
    },

    statusBadge(u) {
        return { Active: 'badge-positive', Invited: 'badge-amber', Disabled: 'badge-neutral' }[u.status] ?? 'badge-neutral';
    },

    statusLabel(u) {
        return { Active: 'Aktif', Invited: 'Diundang', Disabled: 'Nonaktif' }[u.status] ?? u.status;
    },

    /* ---------------- form ---------------- */
    openCreate() {
        this.editingId = null;
        this.form = emptyForm();
        this.formErrors = {};
        this.formOpen = true;
        this.$nextTick(() => PM.lockBody(true));
    },

    openEdit(id) {
        const u = this.$store.posy.user(id);
        if (!u) return;
        this.editingId = id;
        this.form = { name: u.name, email: u.email, role: u.role, note: u.note ?? '' };
        this.formErrors = {};
        this.formOpen = true;
        this.detailId = null;
        this.$nextTick(() => PM.lockBody(true));
    },

    get formDirty() {
        if (this.editingId) {
            const u = this.$store.posy.user(this.editingId);
            return u && (this.form.name !== u.name || this.form.email !== u.email || this.form.role !== u.role || this.form.note !== (u.note ?? ''));
        }
        return Object.values(this.form).some((v) => v !== '' && v !== 'Cashier');
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
        if (this.form.name.trim().length < 2 || this.form.name.trim().length > 80) e.name = 'Masukkan nama (2–80 karakter).';
        const email = this.form.email.trim().toLowerCase();
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) e.email = 'Format email tidak valid.';
        else if (this.users.some((u) => u.email.toLowerCase() === email && u.id !== this.editingId)) e.email = 'Email ini sudah digunakan.';
        if ((this.form.note ?? '').length > 240) e.note = 'Maksimum 240 karakter.';
        this.formErrors = e;
        return Object.keys(e).length === 0;
    },

    async saveForm() {
        if (!this.validateForm()) return;
        this.saving = true;
        await new Promise((r) => setTimeout(r, 450));
        this.$store.posy.saveUser({ ...this.form, name: this.form.name.trim(), email: this.form.email.trim() }, this.editingId);
        this.saving = false;
        this.formOpen = false;
        this.$store.posy.toast(this.editingId ? 'Pengguna diperbarui' : 'Undangan demo dibuat');
        this.$nextTick(() => PM.lockBody(false));
    },

    /* ---------------- detail ---------------- */
    get detailUser() {
        return this.detailId ? this.$store.posy.user(this.detailId) : null;
    },

    openDetail(id) {
        this.detailId = id;
        this.$nextTick(() => PM.lockBody(true));
    },

    closeDetail() {
        this.detailId = null;
        this.$nextTick(() => PM.lockBody(false));
    },

    /* ---------------- aksi status ---------------- */
    openStatus(id, to) {
        this.statusTarget = this.$store.posy.user(id);
        this.statusTo = to;
        this.statusOpen = true;
        this.detailId = null;
    },

    get statusBlockedReason() {
        const u = this.statusTarget;
        if (!u) return '';
        if (u.current) return 'Anda tidak dapat menonaktifkan, menghapus, atau mengubah peran akun Anda sendiri.';
        if (u.role === 'Admin' && this.statusTo !== 'Active') {
            const others = this.users.filter((x) => x.role === 'Admin' && x.status === 'Active' && x.id !== u.id);
            if (others.length === 0) return 'Pertahankan minimal satu Admin aktif.';
        }
        return '';
    },

    confirmStatus() {
        const ok = this.$store.posy.setUserStatus(this.statusTarget.id, this.statusTo);
        if (ok) {
            this.statusOpen = false;
            this.$store.posy.toast(this.statusTo === 'Active' ? 'Pengguna diaktifkan' : 'Pengguna dinonaktifkan');
        } else {
            this.statusOpen = false;
            this.$store.posy.toast(this.statusBlockedReason || 'Aksi ditolak.', { type: 'warning' });
        }
    },

    simulateAccept(id) {
        if (this.$store.posy.acceptInvitation(id)) {
            this.$store.posy.toast('Undangan diterima dalam demo');
        }
    },

    copyInvite(u) {
        const link = `${window.location.origin}/users?invite=${u.id}`;
        PM.copyText(link).then((ok) => {
            this.$store.posy.toast(ok ? 'Tautan undangan disalin' : `Tautan: ${link}`, { type: ok ? 'success' : 'info' });
        });
    },

    openCancelInvite(id) {
        this.cancelTarget = this.$store.posy.user(id);
        this.cancelInviteOpen = true;
        this.detailId = null;
    },

    confirmCancelInvite() {
        if (this.$store.posy.cancelInvitation(this.cancelTarget.id)) {
            this.cancelInviteOpen = false;
            this.$store.posy.toast('Undangan dibatalkan');
        }
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
