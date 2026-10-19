/**
 * PosyMart — entry point Alpine.js.
 */

import Alpine from 'alpinejs';
import { createStore } from './store/store.js';
import { formatRp } from './utils/money.js';
import { fmtDate, fmtDateTime, fmtTime } from './utils/date.js';

// Komponen halaman (register Alpine.data)
import './pages/dashboard.js';
import './pages/sales.js';
import './pages/products.js';
import './pages/customers.js';
import './pages/orders.js';
import './pages/reports.js';
import './pages/returns.js';
import './pages/discounts.js';
import './pages/users.js';
import './pages/settings.js';

window.Alpine = Alpine;

/* ---------------- Helper global untuk focus trap & aksesibilitas ---------------- */
window.PM = {
    /** Fallback gambar produk yang gagal dimuat (dipakai lewat atribut native onerror). */
    imgFallback(el) {
        if (el.dataset.fallbackApplied) return;
        el.dataset.fallbackApplied = '1';
        el.src = "data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 96 96'%3E%3Crect width='96' height='96' fill='%23F4F4F6'/%3E%3Cg fill='none' stroke='%2390979B' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='28' y='34' width='40' height='32' rx='6'/%3E%3Cpath d='M37 34v-6a11 11 0 0 1 22 0v6'/%3E%3Ccircle cx='48' cy='49' r='4'/%3E%3C/g%3E%3C/svg%3E";
        el.classList.add('object-contain');
    },
    trap(event, root) {
        if (!root) return;
        const focusables = Array.from(
            root.querySelectorAll(
                'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
            )
        ).filter((el) => el.offsetParent !== null || el === document.activeElement);
        if (!focusables.length) return;
        const first = focusables[0];
        const last = focusables[focusables.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    },
    lockBody(lock) {
        document.body.classList.toggle('overflow-hidden', lock);
    },
    copyText(text) {
        if (navigator.clipboard?.writeText) {
            return navigator.clipboard.writeText(text).then(() => true).catch(() => false);
        }
        return Promise.resolve(false);
    },
};

/* ---------------- Magic properties ---------------- */
Alpine.magic('rp', () => formatRp);
Alpine.magic('rpfmt', () => ({ date: fmtDate, dateTime: fmtDateTime, time: fmtTime }));

/* ---------------- Store global ---------------- */
const store = createStore();
Alpine.store('posy', store);
// Alihkan semua mutasi internal store ke proxy reaktif Alpine agar UI reaktif.
store.__bindProxy(Alpine.store('posy'));

/* ---------------- Shell aplikasi (sidebar, header, overlay global) ---------------- */
Alpine.data('shell', () => ({
    sidebarOpen: false,
    notifOpen: false,
    cartOpen: false,
    helpOpen: false,
    profileOpen: false,
    previewRole: null,

    init() {
        this.$store.posy.init();
        this.$watch('sidebarOpen', (v) => PM.lockBody(v));
        this.$watch('helpOpen', (v) => PM.lockBody(v));
        this.$nextTick(() => {
            if (window.location.pathname === '/dashboard') {
                const el = document.getElementById('global-search');
                if (el) el.focus({ preventScroll: true });
            }
        });
        // Polling sinkronisasi notifikasi pesanan realtime setiap 8 detik
        if (!window.__posyNotifInterval) {
            window.__posyNotifInterval = setInterval(() => {
                this.$store.posy.syncNotificationsWithApi(false);
            }, 8000);
        }
    },

    get unread() {
        return this.$store.posy.unreadCount();
    },

    get cartCount() {
        return this.$store.posy.draftSale.items.reduce((a, x) => a + x.quantity, 0);
    },

    get cartBadge() {
        return this.cartCount > 0 ? this.cartCount : null;
    },

    get role() {
        return this.$store.posy.activeRole();
    },

    can(permission) {
        return this.$store.posy.can(permission);
    },

    switchRole(role) {
        this.$store.posy.previewRole = role;
        this.profileOpen = false;
        this.$store.posy.toast(`Peran demo aktif: ${role}. Tampilan izin menyesuaikan.`, { type: 'info' });
    },

    markAllRead() {
        this.$store.posy.markAllRead();
    },

    openNotification(n) {
        this.$store.posy.markRead(n.id);
        this.notifOpen = false;
        if (n.url) {
            window.location.href = n.url;
        } else if (n.orderId) {
            window.location.href = '/orders';
        }
    },

    closeAll() {
        this.notifOpen = false;
        this.cartOpen = false;
        this.profileOpen = false;
    },
}));

Alpine.start();
