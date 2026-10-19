/**
 * PosyMart — store global (Alpine) + persistensi LocalStorage.
 * Satu sumber data untuk seluruh halaman (design.md §16.6, §26).
 */

import { buildSeed, DISCOUNTS } from '../data/seed.js';
import { computeTotals, computeDiscountCents, formatRp, roundHalfUp } from '../utils/money.js';
import { DEMO_NOW_ISO, zonedDateKey, toDate } from '../utils/date.js';

const STORAGE_KEY = 'posymart-data-v6';

const canPersist = () => {
    try {
        window.localStorage.removeItem('posymart-demo-v1');
        window.localStorage.removeItem('posymart-data-v2');
        window.localStorage.removeItem('posymart-data-v3');
        window.localStorage.removeItem('posymart-data-v4');
        window.localStorage.removeItem('posymart-data-v5');
        window.localStorage.setItem('__posy_test__', '1');
        window.localStorage.removeItem('__posy_test__');
        return true;
    } catch {
        return false;
    }
};

const load = () => {
    if (!canPersist()) return null;
    try {
        const raw = window.localStorage.getItem(STORAGE_KEY);
        if (!raw) return null;
        const data = JSON.parse(raw);
        if (!data || !Array.isArray(data.products)) return null;
        if (data.version && data.version !== 6) return null;
        return data;
    } catch {
        return null;
    }
};

let saveTimer = null;

export const createStore = () => {
    const seed = buildSeed();
    const persisted = load();

    // "state" sengaja memakai let: setelah Alpine.store() membungkus objek ini
    // dengan proxy reaktif, __bindProxy mengarahkan semua mutasi closure ke proxy
    // tersebut agar UI selalu diberi tahu (Vue reactivity).
    let state = {
        version: 6,
        storageAvailable: canPersist(),
        ready: false,
        loading: true,
        loadError: false,
        seededAt: persisted?.seededAt ?? seed.seededAt,
        // data utama
        products: persisted?.products ?? seed.products,
        customers: persisted?.customers ?? seed.customers,
        users: persisted?.users ?? seed.users,
        discounts: persisted?.discounts ?? seed.discounts,
        sales: persisted?.sales ?? seed.sales,
        orders: persisted?.orders ?? seed.orders,
        returns: persisted?.returns ?? seed.returns,
        settings: persisted?.settings ?? seed.settings,
        draftSale: persisted?.draftSale ?? seed.draftSale,
        notifications: persisted?.notifications ?? seed.notifications,
        stockActivity: persisted?.stockActivity ?? seed.stockActivity,
        idCounters: persisted?.idCounters ?? seed.idCounters,
        // UI global
        sidebarOpen: false,
        toasts: [],
        cartUndo: null,
        liveMessage: '',
        catalogSearch: '',
        cartTouched: false,
        previewRole: null,
    };

    const ROLE_PERMS = {
        Owner: { manageProducts: true, manageUsers: true, manageSettings: true, approveReturns: true, voidSales: true, viewReports: true, manageDiscounts: true, cancelOrders: true },
        Admin: { manageProducts: true, manageUsers: true, manageSettings: true, approveReturns: true, voidSales: true, viewReports: true, manageDiscounts: true, cancelOrders: true },
        Manager: { manageProducts: true, manageUsers: false, manageSettings: false, approveReturns: true, voidSales: true, viewReports: true, manageDiscounts: true, cancelOrders: true },
        Cashier: { manageProducts: false, manageUsers: false, manageSettings: false, approveReturns: false, voidSales: false, viewReports: false, manageDiscounts: false, cancelOrders: false },
    };

    const activeRole = () => state.previewRole || currentUser()?.role || 'Admin';
    const can = (permission) => ROLE_PERMS[activeRole()][permission] !== false;

    /* ---------------- persistensi ---------------- */
    const persistSync = () => {
        if (!state.storageAvailable) return;
        clearTimeout(saveTimer);
        try {
            const payload = {
                version: 6,
                seededAt: state.seededAt ?? seed.seededAt,
                products: state.products,
                customers: state.customers,
                users: state.users,
                discounts: state.discounts,
                sales: state.sales,
                orders: state.orders,
                returns: state.returns,
                settings: state.settings,
                draftSale: state.draftSale,
                notifications: state.notifications,
                stockActivity: state.stockActivity,
                idCounters: state.idCounters,
            };
            window.localStorage.setItem(STORAGE_KEY, JSON.stringify(payload));
        } catch (err) {
            console.error('Gagal menyimpan state PosyMart ke LocalStorage:', err);
            state.storageAvailable = false;
        }
    };

    const persist = () => {
        if (!state.storageAvailable) return;
        clearTimeout(saveTimer);
        saveTimer = setTimeout(persistSync, 120);
    };

    if (typeof window !== 'undefined') {
        window.addEventListener('beforeunload', persistSync);
    }

    /* ---------------- util ---------------- */
    const nextId = (kind, prefix) => {
        state.idCounters[kind] = (state.idCounters[kind] ?? 1) + 1;
        return `${prefix}${state.idCounters[kind]}`;
    };

    const product = (id) => state.products.find((p) => p.id === id);
    const customer = (id) => state.customers.find((c) => c.id === id);
    const sale = (id) => state.sales.find((s) => s.id === id);
    const order = (id) => state.orders.find((o) => o.id === id);
    const returnRec = (id) => state.returns.find((r) => r.id === id);
    const discount = (id) => state.discounts.find((d) => d.id === id);
    const user = (id) => state.users.find((u) => u.id === id);
    const currentUser = () => state.users.find((u) => u.current) || state.users[0];

    const taxSettings = () => ({
        taxEnabled: state.settings.taxEnabled,
        taxRateBps: Math.round(state.settings.taxRatePercent * 100),
        taxName: state.settings.taxName,
    });

    /* ---------------- toast & live region ---------------- */
    const toast = (message, { type = 'success', actionLabel = null, onAction = null, duration = 4000 } = {}) => {
        const id = 't-' + Date.now() + '-' + Math.random().toString(36).slice(2, 7);
        state.toasts.push({ id, message, type, actionLabel, onAction });
        setTimeout(() => dismissToast(id), duration);
    };

    const dismissToast = (id) => {
        const i = state.toasts.findIndex((t) => t.id === id);
        if (i > -1) state.toasts.splice(i, 1);
    };

    const announce = (msg) => {
        state.liveMessage = '';
        setTimeout(() => {
            state.liveMessage = msg;
        }, 60);
    };

    /* ============================================================
     * DRAFT SALE (keranjang POS)
     * ============================================================ */
    const draftLines = () => state.draftSale.items;

    const draftProducts = () =>
        state.draftSale.items
            .map((it) => ({ ...it, product: product(it.productId) }))
            .filter((it) => it.product);

    const eligibleProductIds = (disc) => {
        if (!disc) return null;
        if (disc.appliesTo === 'all') return null;
        if (disc.appliesTo === 'categories') {
            return state.products.filter((p) => disc.categories.includes(p.category)).map((p) => p.id);
        }
        return [...disc.products];
    };

    const draftDiscountCents = () => {
        const d = state.draftSale.discount;
        if (!d) return 0;
        const ids = eligibleProductIds(d);
        const eligible = state.draftSale.items.reduce((sum, it) => {
            return sum + (ids === null || ids.includes(it.productId) ? it.unitPrice * it.quantity : 0);
        }, 0);
        return computeDiscountCents(d, eligible);
    };

    const draftTotals = () => {
        const { taxEnabled, taxRateBps } = taxSettings();
        return computeTotals({
            items: state.draftSale.items,
            discountCents: draftDiscountCents(),
            taxEnabled,
            taxRateBps,
        });
    };

    const validateDraftDiscount = () => {
        const d = state.draftSale.discount;
        if (!d) return;
        const live = state.discounts.find((x) => x.code === d.code);
        const cust = state.draftSale.customerId;
        let valid = !!live && live.enabled && live.published;
        const now = toDate(DEMO_NOW_ISO).getTime();
        if (valid && live.startDate && toDate(live.startDate).getTime() > now) valid = false;
        if (valid && live.endDate && toDate(live.endDate).getTime() <= now) valid = false;
        if (valid && live.usageLimit !== null && live.usedCount >= live.usageLimit) valid = false;
        if (valid && live.appliesTo === 'customers' && (!cust || !(live.customers || []).includes(cust))) valid = false;
        if (valid) {
            const subtotal = state.draftSale.items.reduce((a, it) => a + it.unitPrice * it.quantity, 0);
            if (subtotal < (live.minSpend || 0)) valid = false;
        }
        if (!valid) {
            state.draftSale.discount = null;
            toast('Diskon tidak lagi memenuhi syarat', { type: 'warning' });
        }
    };

    const addToCart = (productId) => {
        const p = product(productId);
        if (!p || p.status !== 'Active') return;
        state.cartTouched = true;
        const existing = state.draftSale.items.find((it) => it.productId === productId);
        if (existing) {
            existing.quantity += 1;
        } else {
            state.draftSale.items.push({ productId, quantity: 1, unitPrice: p.price });
        }
        announce(`${p.name} ditambahkan ke penjualan`);
        validateDraftDiscount();
        persist();
    };

    const changeQty = (productId, delta) => {
        const it = state.draftSale.items.find((x) => x.productId === productId);
        if (!it) return;
        state.cartTouched = true;
        const next = it.quantity + delta;
        if (next < 1) return;
        it.quantity = next;
        validateDraftDiscount();
        persist();
    };

    const removeLine = (productId) => {
        const i = state.draftSale.items.findIndex((x) => x.productId === productId);
        if (i > -1) {
            state.cartTouched = true;
            announce('Item dihapus');
            state.draftSale.items.splice(i, 1);
            validateDraftDiscount();
            persist();
        }
    };

    const clearCart = () => {
        state.cartTouched = true;
        state.cartUndo = JSON.parse(JSON.stringify(state.draftSale));
        state.draftSale.items = [];
        state.draftSale.note = '';
        state.draftSale.discount = null;
        announce('Keranjang dikosongkan');
        persist();
    };

    const undoClearCart = () => {
        if (state.cartUndo) {
            state.draftSale = state.cartUndo;
            state.cartUndo = null;
            persist();
        }
    };

    const setCartNote = (note) => {
        state.draftSale.note = note;
        persist();
    };

    const setCartCustomer = (customerId, customerName = null) => {
        state.draftSale.customerId = customerId;
        if (customerName !== null) {
            state.draftSale.customerNameSnapshot = customerName;
        } else if (customerId) {
            const c = customer(customerId);
            state.draftSale.customerNameSnapshot = c?.name ?? '';
        } else {
            state.draftSale.customerNameSnapshot = '';
        }
        validateDraftDiscount();
        persist();
    };

    const setCartCustomerName = (name) => {
        state.draftSale.customerNameSnapshot = name;
        if (state.draftSale.customerId) {
            const c = customer(state.draftSale.customerId);
            if (c && c.name !== name) {
                state.draftSale.customerId = null;
            }
        }
        validateDraftDiscount();
        persist();
    };

    const setCartOrderType = (type) => {
        state.draftSale.orderType = type === 'takeaway' ? 'takeaway' : 'dine_in';
        persist();
    };

    const setCartTableNumber = (table) => {
        state.draftSale.tableNumber = table;
        persist();
    };

    const applyDiscount = (discountId) => {
        const d = state.discounts.find((x) => x.id === discountId);
        if (!d) return { ok: false, reason: 'Diskon tidak ditemukan' };
        const now = toDate(DEMO_NOW_ISO).getTime();
        if (state.draftSale.discount && state.draftSale.discount.code === d.code) return { ok: true };
        if (!d.published) return { ok: false, reason: 'Diskon masih berupa draf' };
        if (!d.enabled) return { ok: false, reason: 'Diskon sedang dinonaktifkan' };
        if (d.startDate && toDate(d.startDate).getTime() > now) return { ok: false, reason: 'Diskon belum dimulai' };
        if (d.endDate && toDate(d.endDate).getTime() <= now) return { ok: false, reason: 'Diskon sudah berakhir' };
        if (d.usageLimit !== null && d.usedCount >= d.usageLimit) return { ok: false, reason: 'Batas pemakaian diskon sudah tercapai' };
        if (d.appliesTo === 'customers') {
            const cust = state.draftSale.customerId;
            if (!cust || !(d.customers || []).includes(cust)) return { ok: false, reason: 'Diskon ini hanya untuk pelanggan tertentu' };
        }
        const subtotal = state.draftSale.items.reduce((a, it) => a + it.unitPrice * it.quantity, 0);
        if (subtotal < (d.minSpend || 0)) return { ok: false, reason: `Minimum belanja ${formatRp(d.minSpend)}` };
        state.draftSale.discount = {
            id: d.id, code: d.code, name: d.name, type: d.type, value: d.value,
            appliesTo: d.appliesTo, categories: [...(d.categories || [])], products: [...(d.products || [])], customers: [...(d.customers || [])],
            minSpend: d.minSpend || 0,
        };
        persist();
        return { ok: true };
    };

    const removeDiscount = () => {
        state.draftSale.discount = null;
        persist();
    };

    /* ---------------- pembayaran simulasi ---------------- */
    const paymentResult = { resolve: null };

    const simulatePayment = () =>
        new Promise((resolve) => {
            // Simulasi pembayaran kasir: selalu berhasil deterministik
            setTimeout(() => {
                resolve({ ok: true, ref: 'TXN-' + Date.now().toString(36).toUpperCase() });
            }, 300);
        });

    const nextSaleId = () => {
        let n = state.idCounters.sale ?? 1007;
        let id;
        do {
            id = `TX-${n}`;
            n++;
        } while (state.sales.some((s) => s.id === id));
        state.idCounters.sale = n;
        return id;
    };

    const buildSaleFromDraft = (paymentMethod) => {
        const { taxEnabled, taxRateBps, taxName } = taxSettings();
        const cust = state.draftSale.customerId ? customer(state.draftSale.customerId) : null;
        const d = state.draftSale.discount;
        const items = state.draftSale.items.map((it) => {
            const p = product(it.productId);
            return { productId: it.productId, name: p?.name ?? 'Produk', unitPrice: it.unitPrice, quantity: it.quantity };
        });
        const eligibleIds = eligibleProductIds(d);
        const eligibleSubtotal = items.reduce((sum, it) => {
            return sum + (eligibleIds === null || eligibleIds.includes(it.productId) ? it.unitPrice * it.quantity : 0);
        }, 0);
        const discountCents = d ? computeDiscountCents(d, eligibleSubtotal) : 0;
        return {
            id: nextSaleId(),
            createdAt: new Date().toISOString(),
            customerId: cust?.id ?? null,
            customerNameSnapshot: (state.draftSale.customerNameSnapshot || '').trim() || (cust?.name ?? 'Pelanggan Umum'),
            orderType: state.draftSale.orderType || 'dine_in',
            tableNumber: (state.draftSale.tableNumber || '').trim(),
            cashierId: currentUser().id,
            cashierNameSnapshot: currentUser().name,
            status: 'Completed',
            paymentMethod,
            items,
            discountCents,
            eligibleIds,
            taxEnabled,
            taxRateBps,
            taxName,
            note: state.draftSale.note,
            fromDraft: true,
        };
    };

    const completePayment = async (paymentMethod, cashReceived = null) => {
        if (state.draftSale.paymentStatus === 'processing') return { ok: false, reason: 'Sedang diproses' };
        // Validasi stok bila tracking aktif
        if (state.settings.trackInventory) {
            const problems = [];
            for (const it of state.draftSale.items) {
                const p = product(it.productId);
                if (!p) continue;
                if (p.status === 'OutOfStock') {
                    problems.push({ name: p.name, available: 0, requested: it.quantity });
                    continue;
                }
                const isTracked = p.trackStock === true || p.track_stock === true;
                if (isTracked) {
                    const inCart = state.draftSale.items.filter((x) => x.productId === it.productId).reduce((a, x) => a + x.quantity, 0);
                    if (p.stock < inCart) {
                        problems.push({ name: p.name, available: p.stock, requested: inCart });
                    }
                }
            }
            if (problems.length) return { ok: false, stockProblems: problems };
        }
        if (paymentMethod === 'Cash' && !state.settings.allowCashChange && cashReceived != null && cashReceived !== draftTotals().total) {
            return { ok: false, reason: 'Nominal tunai harus tepat sama dengan total (kembalian dinonaktifkan).' };
        }
        state.draftSale.paymentStatus = 'processing';
        try {
            const result = await simulatePayment();
            if (!result.ok) {
                state.draftSale.paymentStatus = 'error';
                return { ok: false, reason: 'Pembayaran simulasi gagal. Struk tidak dibuat.' };
            }
            const payload = buildSaleFromDraft(paymentMethod);
            const rec = finalizeSale(payload);
            // Kurangi stok sekali (hanya produk yang melacak stok)
            if (state.settings.trackInventory) {
                for (const it of payload.items) {
                    const p = product(it.productId);
                    const isTracked = p && (p.trackStock === true || p.track_stock === true);
                    if (p && isTracked) p.stock = Math.max(0, p.stock - it.quantity);
                }
            }
            // Tambah pemakaian diskon tepat satu
            if (state.draftSale.discount) {
                const live = state.discounts.find((x) => x.code === state.draftSale.discount.code);
                if (live) live.usedCount += 1;
            }
            state.draftSale = { items: [], note: '', customerId: null, customerNameSnapshot: '', orderType: 'dine_in', tableNumber: '', discount: null, paymentStatus: 'idle' };
            state.draftSale.paymentStatus = 'idle';
            state.cartTouched = true;
            persistSync();
            return { ok: true, saleId: rec.id, ref: result.ref };
        } finally {
            if (state.draftSale.paymentStatus === 'processing') state.draftSale.paymentStatus = 'idle';
        }
    };

    /** Ubah payload draft menjadi SaleRecord penuh dengan alokasi. */
    const finalizeSale = (payload) => {
        const totals = computeTotals({
            items: payload.items,
            discountCents: payload.discountCents,
            taxEnabled: payload.taxEnabled,
            taxRateBps: payload.taxRateBps,
        });
        // alokasi per item (design.md §26.4)
        const eligible = payload.eligibleIds ? new Set(payload.eligibleIds) : null;
        const isEl = (it) => !eligible || eligible.has(it.productId);
        const discAlloc = allocate_(totals.discount, payload.items.map((it) => (isEl(it) ? it.unitPrice * it.quantity : 0)));
        const netVals = payload.items.map((it, i) => it.unitPrice * it.quantity - discAlloc[i]);
        const taxAlloc = allocate_(totals.tax, netVals);
        let seq = 0;
        const items = payload.items.map((it, i) => {
            const net = it.unitPrice * it.quantity - discAlloc[i];
            const unitNets = allocUnits_(net, it.quantity);
            const unitTaxes = allocUnits_(taxAlloc[i], it.quantity);
            return {
                id: `si-${payload.id}-${++seq}`,
                productId: it.productId, name: it.name, quantity: it.quantity, unitPrice: it.unitPrice,
                allocatedDiscount: discAlloc[i], allocatedTax: taxAlloc[i],
                unitAllocations: unitNets.map((n, u) => ({ unitIndex: u, netCents: n, taxCents: unitTaxes[u] })),
            };
        });
        const rec = {
            id: payload.id, createdAt: payload.createdAt ?? new Date().toISOString(),
            customerId: payload.customerId, customerNameSnapshot: payload.customerNameSnapshot,
            orderType: payload.orderType || 'dine_in',
            tableNumber: payload.tableNumber || '',
            cashierId: payload.cashierId, cashierNameSnapshot: payload.cashierNameSnapshot,
            status: payload.status ?? 'Completed', paymentMethod: payload.paymentMethod,
            items, subtotal: totals.subtotal, discount: totals.discount, tax: totals.tax,
            taxRateBpsSnapshot: payload.taxRateBps, taxNameSnapshot: payload.taxName, taxEnabledSnapshot: payload.taxEnabled,
            total: totals.total, sourceOrderId: payload.sourceOrderId ?? null,
            note: payload.note ?? '', voidReason: null, voidedAt: null,
        };
        state.sales.unshift(rec);
        return rec;
    };

    // helper alokasi (largest remainder)
    const allocate_ = (pool, values) => {
        const total = values.reduce((a, b) => a + b, 0);
        const res = values.map(() => 0);
        if (total <= 0 || pool <= 0) return res;
        const floors = values.map((v) => Math.floor((pool * v) / total));
        let rem = pool - floors.reduce((a, b) => a + b, 0);
        const order = values.map((_, i) => i).filter((i) => values[i] > 0).sort((a, b) => {
            const fa = (pool * values[a]) / total - floors[a];
            const fb = (pool * values[b]) / total - floors[b];
            return fb !== fa ? fb - fa : a - b;
        });
        for (const i of order) {
            if (rem <= 0) break;
            res[i] += 1;
            rem -= 1;
        }
        values.forEach((v, i) => (res[i] += floors[i]));
        return res;
    };

    const allocUnits_ = (total, n) => {
        if (n <= 0) return [];
        if (total <= 0) return Array.from({ length: n }, () => 0);
        const base = Math.floor(total / n);
        let rem = total - base * n;
        const units = Array.from({ length: n }, () => base);
        for (let i = n - 1; i >= 0 && rem > 0; i--) {
            units[i] += 1;
            rem -= 1;
        }
        return units;
    };

    /* ---------------- void sale ---------------- */
    const voidSale = (saleId, reason) => {
        const s = sale(saleId);
        if (!s || s.status !== 'Completed') return false;
        const hasActiveReturn = state.returns.some((r) => r.saleId === saleId && (r.status === 'Pending' || r.status === 'Completed'));
        if (hasActiveReturn) return false;
        s.status = 'Voided';
        s.voidReason = reason;
        s.voidedAt = new Date().toISOString();
        // Pulihkan stok sekali bila sebelumnya mengurangi (rekam di movement)
        if (state.settings.trackInventory && s.stockReduced !== false) {
            for (const it of s.items) {
                const p = product(it.productId);
                if (p) p.stock += it.quantity;
            }
            s.stockReduced = false;
        }
        // Batalkan satu redemption promo bila ada
        if (s.discountCode) {
            const live = state.discounts.find((x) => x.code === s.discountCode);
            if (live && live.usedCount > 0) live.usedCount -= 1;
        }
        persist();
        return true;
    };

    /* ============================================================
     * PRODUK
     * ============================================================ */
    const saveProduct = (form, editingId = null) => {
        const trackStock = Boolean(form.trackStock);
        const data = {
            sku: form.sku.trim().toUpperCase(),
            name: form.name.trim(),
            category: form.category,
            price: Math.round(form.price),
            trackStock: trackStock,
            track_stock: trackStock,
            stock: trackStock ? Number(form.stock || 0) : 999,
            lowStockThreshold: trackStock ? Number(form.lowStockThreshold || 0) : 0,
            status: form.status,
            description: (form.description || '').trim(),
            image: form.image || (editingId ? `/images/products/${editingId}.jpg` : null),
        };
        if (editingId) {
            const p = product(editingId);
            Object.assign(p, data);
            p.updatedAt = new Date().toISOString();
            fetch(`/api/v1/products/${editingId}`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    name: data.name,
                    sku: data.sku,
                    category: data.category,
                    price_cents: data.price,
                    stock_qty: data.stock,
                    track_stock: data.trackStock,
                    status: data.status,
                    description: data.description,
                    image_url: data.image,
                }),
            }).catch(() => {});
        } else {
            const id = `p-${data.name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '')}-${state.idCounters.product++}`;
            if (!data.image) data.image = `/images/products/${id}.jpg`;
            state.products.push({
                id, ...data,
                createdAt: new Date().toISOString(), updatedAt: new Date().toISOString(),
            });
            fetch('/api/v1/products', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    id,
                    name: data.name,
                    sku: data.sku,
                    category: data.category,
                    price_cents: data.price,
                    stock_qty: data.stock,
                    track_stock: data.trackStock,
                    status: data.status,
                    description: data.description,
                    image_url: data.image,
                }),
            }).catch(() => {});
        }
        persist();
        return true;
    };

    const adjustStock = (productId, type, qty, reason) => {
        const p = product(productId);
        if (!p) return { ok: false, reason: 'Produk tidak ditemukan' };
        const before = p.stock;
        let after = before;
        if (type === 'add') after = before + qty;
        else if (type === 'remove') {
            if (qty > before) return { ok: false, reason: 'Penyesuaian tidak dapat mengurangi stok di bawah nol.' };
            after = before - qty;
        } else after = qty;
        p.stock = after;
        state.stockActivity.push({
            id: `adj-${Date.now()}`, productId, productName: p.name, type, quantity: qty,
            quantityBefore: before, quantityAfter: after, reason, by: currentUser().name, at: new Date().toISOString(),
        });
        persist();
        return { ok: true, before, after };
    };

    const setProductStatus = (productId, status) => {
        const p = product(productId);
        if (p) {
            p.status = status;
            p.updatedAt = new Date().toISOString();
            persist();
            fetch(`/api/v1/products/${productId}`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ status }),
            }).catch(() => {});
        }
    };

    /* ============================================================
     * PELANGGAN
     * ============================================================ */
    const saveCustomer = (form, editingId = null) => {
        if (editingId) {
            const c = customer(editingId);
            Object.assign(c, {
                name: form.name.trim(), email: (form.email || '').trim(), phone: (form.phone || '').trim(),
                address: (form.address || '').trim(), status: form.status, notes: (form.notes || '').trim(),
            });
            c.updatedAt = new Date().toISOString();
        } else {
            state.customers.push({
                id: nextId('customer', 'CUS-'), name: form.name.trim(), email: (form.email || '').trim(),
                phone: (form.phone || '').trim(), address: (form.address || '').trim(), status: form.status,
                notes: (form.notes || '').trim(), joinedAt: new Date().toISOString(),
            });
        }
        persist();
    };

    /* ============================================================
     * PESANAN
     * ============================================================ */
    const saveOrder = (form, editingId = null) => {
        const tax = taxSettings();
        const items = form.items.map((it) => {
            const p = product(it.productId);
            return { productId: it.productId, name: p?.name ?? 'Produk', unitPrice: it.unitPrice, quantity: it.quantity };
        });
        const totals = computeTotals({ items, taxEnabled: tax.taxEnabled, taxRateBps: tax.taxRateBps });
        if (editingId) {
            const o = order(editingId);
            Object.assign(o, {
                customerId: form.customerId, customerNameSnapshot: form.customerId ? customer(form.customerId)?.name ?? 'Pelanggan Umum' : 'Pelanggan Umum',
                items, fulfillment: form.fulfillment, pickupAt: form.fulfillment === 'Pickup' ? form.pickupAt : null,
                tableNumber: form.fulfillment === 'Dine-in' ? form.tableNumber : null, note: form.note ?? '',
                subtotal: totals.subtotal, tax: totals.tax, total: totals.total,
                taxRateBpsSnapshot: tax.taxRateBps, taxNameSnapshot: tax.taxName, taxEnabledSnapshot: tax.taxEnabled,
            });
            persist();
            return o;
        }
        const id = nextId('order', 'ORD-');
        const placedAt = new Date().toISOString();
        const rec = {
            id, customerId: form.customerId, customerNameSnapshot: form.customerId ? customer(form.customerId)?.name ?? 'Pelanggan Umum' : 'Pelanggan Umum',
            items, fulfillment: form.fulfillment, placedAt, pickupAt: form.fulfillment === 'Pickup' ? form.pickupAt : null,
            tableNumber: form.fulfillment === 'Dine-in' ? form.tableNumber : null, note: form.note ?? '',
            status: form.targetStatus, subtotal: totals.subtotal, tax: totals.tax, total: totals.total,
            taxRateBpsSnapshot: tax.taxRateBps, taxNameSnapshot: tax.taxName, taxEnabledSnapshot: tax.taxEnabled,
            timeline: [{ status: form.targetStatus, at: placedAt }], saleId: null, cancelReason: null,
            paymentMethod: null, paidAt: null, cashierId: currentUser().id,
        };
        state.orders.unshift(rec);
        persist();
        return rec;
    };

    const ORDER_FLOW = {
        Draft: { next: 'Pending', action: 'PlaceOrder' },
        Pending: { next: 'Preparing', action: 'StartPreparing' },
        Preparing: { next: 'Ready', action: 'MarkReady' },
    };

    const advanceOrder = (orderId) => {
        const o = order(orderId);
        if (!o) return false;
        const flow = ORDER_FLOW[o.status];
        if (!flow) return false;
        o.status = flow.next;
        o.timeline.push({ status: flow.next, at: new Date().toISOString() });
        persist();
        return true;
    };

    const cancelOrder = (orderId, reason) => {
        const o = order(orderId);
        if (!o || !['Draft', 'Pending', 'Preparing', 'Ready'].includes(o.status)) return false;
        o.status = 'Cancelled';
        o.cancelReason = reason;
        o.timeline.push({ status: 'Cancelled', at: new Date().toISOString(), reason });
        persist();
        return true;
    };

    const completeOrderPay = async (orderId, paymentMethod) => {
        const o = order(orderId);
        if (!o || o.status !== 'Ready' || o.saleId) return { ok: false, reason: 'Pesanan tidak bisa diselesaikan' };
        const result = await simulatePayment();
        if (!result.ok) return { ok: false, reason: 'Pembayaran simulasi gagal. Pesanan tetap Ready.' };
        // buat satu sale dari snapshot order
        const payload = {
            id: nextSaleId(), createdAt: new Date().toISOString(), customerId: o.customerId,
            customerNameSnapshot: o.customerNameSnapshot, cashierId: currentUser().id, cashierNameSnapshot: currentUser().name,
            status: 'Completed', paymentMethod, items: o.items.map((it) => ({ ...it })),
            discountCents: 0, eligibleIds: null, taxEnabled: o.taxEnabledSnapshot, taxRateBps: o.taxRateBpsSnapshot,
            taxName: o.taxNameSnapshot, note: o.note, sourceOrderId: o.id,
        };
        const rec = finalizeSale(payload);
        if (state.settings.trackInventory) {
            for (const it of o.items) {
                const p = product(it.productId);
                if (p) p.stock = Math.max(0, p.stock - it.quantity);
            }
        }
        o.status = 'Completed';
        o.saleId = rec.id;
        o.paymentMethod = paymentMethod;
        o.paidAt = new Date().toISOString();
        o.timeline.push({ status: 'Completed', at: o.paidAt });
        persistSync();
        return { ok: true, saleId: rec.id };
    };

    const duplicateOrderAsDraft = (orderId) => {
        const o = order(orderId);
        if (!o) return null;
        const id = nextId('order', 'ORD-');
        const rec = {
            ...JSON.parse(JSON.stringify(o)),
            id, status: 'Draft', saleId: null, paymentMethod: null, paidAt: null, cancelReason: null,
            placedAt: new Date().toISOString(), pickupAt: null,
            timeline: [{ status: 'Draft', at: new Date().toISOString() }],
        };
        state.orders.unshift(rec);
        persist();
        return rec;
    };

    /* ============================================================
     * RETUR
     * ============================================================ */
    const saleReturnedUnits = (saleId, productId) => {
        const s = sale(saleId);
        const si = s?.items.find((x) => x.productId === productId);
        if (!si) return [];
        const reserved = [];
        for (const r of state.returns) {
            if (r.saleId !== saleId || r.status === 'Rejected' || r.status === 'Draft') continue;
            const ri = r.items.find((x) => x.productId === productId);
            if (ri) reserved.push(...ri.refundedUnits);
        }
        return [...new Set(reserved)];
    };

    const availableUnits = (saleId, productId) => {
        const s = sale(saleId);
        const si = s?.items.find((x) => x.productId === productId);
        if (!si) return 0;
        return si.quantity - saleReturnedUnits(saleId, productId).length;
    };

    const saveReturnDraft = (form) => {
        const id = nextId('retur', 'R-');
        const rec = {
            id, saleId: form.saleId, customerName: form.customerName, requestedAt: new Date().toISOString(),
            status: 'Draft', items: form.items, reason: form.reason, notes: form.notes ?? '',
            refundNet: form.refundNet, refundTax: form.refundTax, refundTotal: form.refundNet + form.refundTax,
            unitQuantity: form.items.reduce((a, x) => a + x.quantity, 0),
            originalMethod: form.originalMethod, originalDate: form.originalDate,
            completedAt: null, reviewedAt: null, rejectedReason: null,
        };
        state.returns.unshift(rec);
        persist();
        return rec;
    };

    const submitReturn = (returnId) => {
        const r = returnRec(returnId);
        if (!r || r.status !== 'Draft') return false;
        r.status = 'Pending';
        r.submittedAt = new Date().toISOString();
        persist();
        return true;
    };

    const approveReturn = async (returnId) => {
        const r = returnRec(returnId);
        if (!r || r.status !== 'Pending') return { ok: false, reason: 'Retur tidak bisa disetujui' };
        const res = await simulatePayment();
        if (!res.ok) return { ok: false, reason: 'Simulasi refund gagal. Status tetap Menunggu.' };
        // validasi ulang ketersediaan
        for (const it of r.items) {
            if (availableUnits(r.saleId, it.productId) < it.quantity) {
                return { ok: false, reason: `Ketersediaan unit ${it.name} tidak mencukupi.` };
            }
        }
        r.status = 'Completed';
        r.completedAt = new Date().toISOString();
        r.reviewedAt = r.completedAt;
        r.reviewer = currentUser().name;
        r.receiptNo = `RR-${r.id}`;
        // tandai unit terpakai
        for (const it of r.items) {
            const si = sale(r.saleId)?.items.find((x) => x.productId === it.productId);
            if (si) si.returnedUnits = [...(si.returnedUnits ?? []), ...it.refundedUnits];
        }
        // restock bila kondisi memenuhi
        if (state.settings.trackInventory) {
            for (const it of r.items) {
                if (it.restock && it.condition === 'Unopened') {
                    const p = product(it.productId);
                    if (p) p.stock += it.quantity;
                }
            }
        }
        persist();
        return { ok: true };
    };

    const rejectReturn = (returnId, reason) => {
        const r = returnRec(returnId);
        if (!r || r.status !== 'Pending') return false;
        r.status = 'Rejected';
        r.rejectedReason = reason;
        r.reviewedAt = new Date().toISOString();
        persist();
        return true;
    };

    const deleteReturnDraft = (returnId) => {
        const r = returnRec(returnId);
        if (!r || r.status !== 'Draft') return false;
        state.returns.splice(state.returns.indexOf(r), 1);
        persist();
        return true;
    };

    const duplicateReturnAsDraft = (returnId) => {
        const r = returnRec(returnId);
        if (!r) return null;
        const id = nextId('retur', 'R-');
        const rec = { ...JSON.parse(JSON.stringify(r)), id, status: 'Draft', completedAt: null, reviewedAt: null, rejectedReason: null, requestedAt: new Date().toISOString() };
        state.returns.unshift(rec);
        persist();
        return rec;
    };

    /* ============================================================
     * DISKON
     * ============================================================ */
    const discountEffectiveStatus = (d) => {
        const now = toDate(DEMO_NOW_ISO).getTime();
        if (!d.published) return 'Draft';
        if (!d.enabled) return 'Nonaktif';
        if (d.endDate && toDate(d.endDate).getTime() <= now) return 'Kedaluwarsa';
        if (d.startDate && toDate(d.startDate).getTime() > now) return 'Terjadwal';
        if (d.usageLimit !== null && d.usedCount >= d.usageLimit) return 'Habis';
        return 'Aktif';
    };

    const saveDiscount = (form, editingId = null) => {
        const data = {
            name: form.name.trim(), code: form.code.trim().toUpperCase(), type: form.type, value: form.value,
            appliesTo: form.appliesTo, categories: [...form.categories], products: [...form.products],
            customers: [...form.customers], minSpend: form.minSpend || 0,
            usageLimit: form.unlimited ? null : form.usageLimit,
            startDate: form.startDate || null, endDate: form.endDate || null,
            enabled: form.enabled, published: form.published,
            description: (form.description || '').trim(),
        };
        if (editingId) {
            const d = discount(editingId);
            Object.assign(d, data);
            d.updatedAt = new Date().toISOString();
        } else {
            state.discounts.unshift({
                id: `d-${data.code.toLowerCase()}`, ...data, usedCount: 0,
                createdAt: new Date().toISOString(), updatedAt: new Date().toISOString(),
            });
        }
        persist();
    };

    const setDiscountEnabled = (id, enabled) => {
        const d = discount(id);
        if (d) {
            d.enabled = enabled;
            d.updatedAt = new Date().toISOString();
            persist();
        }
    };

    const deleteDiscount = (id) => {
        const d = discount(id);
        if (!d) return false;
        const effective = discountEffectiveStatus(d);
        if (!(d.published === false || d.usedCount === 0)) return false;
        state.discounts.splice(state.discounts.indexOf(d), 1);
        persist();
        return true;
    };

    const duplicateDiscount = (id) => {
        const d = discount(id);
        if (!d) return null;
        const copy = JSON.parse(JSON.stringify(d));
        copy.id = `d-copy-${Date.now().toString(36)}`;
        copy.code = '';
        copy.name = `${d.name} (Salinan)`;
        copy.published = false;
        copy.usedCount = 0;
        copy.createdAt = copy.updatedAt = new Date().toISOString();
        state.discounts.unshift(copy);
        persist();
        return copy;
    };

    /* ============================================================
     * PENGGUNA
     * ============================================================ */
    const saveUser = (form, editingId = null) => {
        if (editingId) {
            const u = user(editingId);
            u.name = form.name.trim();
            if (u.status !== 'Active') u.email = form.email.trim().toLowerCase();
            if (!u.current) u.role = form.role;
            u.note = (form.note || '').trim();
            u.updatedAt = new Date().toISOString();
        } else {
            state.users.push({
                id: `user-${form.name.trim().toLowerCase().replace(/[^a-z0-9]+/g, '-')}-${Date.now().toString(36).slice(-4)}`,
                name: form.name.trim(), email: form.email.trim().toLowerCase(), role: form.role,
                status: 'Invited', lastActiveAt: null, addedAt: new Date().toISOString(),
                note: (form.note || '').trim(), current: false,
            });
        }
        persist();
    };

    const setUserStatus = (id, status) => {
        const u = user(id);
        if (!u || u.current) return false;
        if (u.role === 'Admin' && status !== 'Active') {
            const otherAdmins = state.users.filter((x) => x.role === 'Admin' && x.status === 'Active' && x.id !== id);
            if (otherAdmins.length === 0) return false;
        }
        u.status = status;
        persist();
        return true;
    };

    const acceptInvitation = (id) => {
        const u = user(id);
        if (!u || u.status !== 'Invited') return false;
        u.status = 'Active';
        u.lastActiveAt = new Date().toISOString();
        persist();
        return true;
    };

    const cancelInvitation = (id) => {
        const u = user(id);
        if (!u || u.status !== 'Invited') return false;
        state.users.splice(state.users.indexOf(u), 1);
        persist();
        return true;
    };

    /* ============================================================
     * PENGATURAN
     * ============================================================ */
    const saveSettings = (draft) => {
        state.settings = JSON.parse(JSON.stringify(draft));
        persist();
    };

    const resetDemoData = () => {
        const fresh = buildSeed();
        Object.assign(state, {
            products: fresh.products, customers: fresh.customers, users: fresh.users,
            discounts: fresh.discounts, sales: fresh.sales, orders: fresh.orders,
            returns: fresh.returns, settings: fresh.settings, draftSale: fresh.draftSale,
            notifications: fresh.notifications, stockActivity: fresh.stockActivity,
            idCounters: fresh.idCounters,
        });
        persist();
    };

    /* ---------------- audio notifikasi (Web Audio API) ---------------- */
    const playNotificationChime = () => {
        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            const ctx = new AudioCtx();
            if (ctx.state === 'suspended') {
                ctx.resume().catch(() => {});
            }
            const now = ctx.currentTime;
            // Nada 1: D5 (587.33Hz) -> A5 (880Hz)
            const osc1 = ctx.createOscillator();
            const gain1 = ctx.createGain();
            osc1.type = 'sine';
            osc1.frequency.setValueAtTime(587.33, now);
            osc1.frequency.exponentialRampToValueAtTime(880, now + 0.12);
            gain1.gain.setValueAtTime(0.25, now);
            gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.5);
            osc1.connect(gain1);
            gain1.connect(ctx.destination);
            osc1.start(now);
            osc1.stop(now + 0.5);

            // Nada 2: D6 (1174.66Hz)
            const osc2 = ctx.createOscillator();
            const gain2 = ctx.createGain();
            osc2.type = 'sine';
            osc2.frequency.setValueAtTime(1174.66, now + 0.12);
            gain2.gain.setValueAtTime(0.22, now + 0.12);
            gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.7);
            osc2.connect(gain2);
            gain2.connect(ctx.destination);
            osc2.start(now + 0.12);
            osc2.stop(now + 0.7);
        } catch {
            // Abaikan jika browser membatasi audio autoplay
        }
    };

    /* ---------------- notifikasi ---------------- */
    const unreadCount = () => (state.notifications || []).filter((n) => !n.read).length;

    const markAllRead = () => {
        (state.notifications || []).forEach((n) => (n.read = true));
        persist();
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        fetch('/api/v1/admin/notifications/read-all', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
            },
        }).catch(() => {});
    };

    const markRead = (id) => {
        const n = (state.notifications || []).find((x) => x.id === id);
        if (n) {
            n.read = true;
            persist();
        }
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        fetch(`/api/v1/admin/notifications/${id}/read`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
            },
        }).catch(() => {});
    };

    const syncNotificationsWithApi = async (silent = false) => {
        try {
            const res = await fetch('/api/v1/admin/notifications', {
                headers: { 'Accept': 'application/json' },
            });
            if (!res.ok) return;
            const json = await res.json();
            const list = Array.isArray(json.notifications) ? json.notifications : [];

            const prevIds = new Set((state.notifications || []).map((x) => x.id));
            const mapped = list.map((n) => {
                const isPaid = n.type === 'payment' || n.type === 'OrderPaid';
                const isOrder = n.type === 'order' || n.type === 'qr_order' || n.type === 'ReviewRequired';
                const type = isPaid ? 'payment' : isOrder ? 'order' : (n.type || 'order');
                return {
                    id: n.id,
                    type,
                    title: n.title,
                    body: n.message,
                    at: n.created_at || new Date().toISOString(),
                    read: Boolean(n.is_read),
                    orderId: n.order_id,
                    url: n.data?.url || (n.order_id ? '/orders' : null),
                    data: n.data || {},
                };
            });

            // Deteksi notifikasi belum dibaca yang baru tiba untuk audio chime & toast
            const newUnread = mapped.filter((x) => !x.read && !prevIds.has(x.id));
            if (newUnread.length > 0 && !silent) {
                playNotificationChime();
                const latest = newUnread[0];
                toast(latest.title, { type: 'info' });
            }

            state.notifications = mapped;
            persist();
        } catch {
            // offline atau unauthenticated
        }
    };

    /* ---------------- sinkronisasi produk dengan server ---------------- */
    const syncProductsWithApi = async () => {
        try {
            const res = await fetch('/api/v1/products?limit=100', {
                headers: { 'Accept': 'application/json' },
            });
            if (!res.ok) return;
            const json = await res.json();
            const serverProducts = json.data || (Array.isArray(json) ? json : null);
            if (Array.isArray(serverProducts) && serverProducts.length > 0) {
                serverProducts.forEach((sp) => {
                    const existing = state.products.find((p) => p.id === sp.id);
                    const imgUrl = sp.image_url || `/images/products/${sp.id}.jpg`;
                    const price = sp.price_cents ?? sp.price ?? 0;
                    const trackStockBool = sp.track_stock === true || sp.track_stock === 1;
                    const stockVal = trackStockBool ? (sp.stock_qty ?? 0) : 999;
                    if (existing) {
                        existing.name = sp.name;
                        existing.price = price;
                        existing.category = sp.category ?? existing.category;
                        existing.description = sp.description ?? existing.description;
                        existing.image = imgUrl;
                        existing.trackStock = trackStockBool;
                        existing.track_stock = trackStockBool;
                        existing.stock = stockVal;
                        if (trackStockBool && sp.low_stock_threshold !== undefined) existing.lowStockThreshold = sp.low_stock_threshold;
                        if (sp.status) existing.status = sp.status;
                    } else {
                        state.products.push({
                            id: sp.id,
                            sku: sp.sku || '',
                            name: sp.name,
                            category: sp.category || 'Kopi',
                            price: price,
                            trackStock: trackStockBool,
                            track_stock: trackStockBool,
                            stock: stockVal,
                            lowStockThreshold: trackStockBool ? (sp.low_stock_threshold ?? 8) : 0,
                            status: sp.status || 'Active',
                            description: sp.description || '',
                            image: imgUrl,
                        });
                    }
                });
                persist();
            }
        } catch (e) {
            // offline or fallback
        }
    };

    /* ---------------- sinkronisasi pelanggan dengan server ---------------- */
    const syncCustomersWithApi = async () => {
        try {
            const res = await fetch('/api/v1/customers?limit=100', {
                headers: { 'Accept': 'application/json' },
            });
            if (!res.ok) return;
            const json = await res.json();
            const serverCustomers = json.data || (Array.isArray(json) ? json : null);
            if (Array.isArray(serverCustomers)) {
                if (serverCustomers.length === 0) {
                    state.customers = [];
                } else {
                    state.customers = serverCustomers.map((sc) => ({
                        id: sc.id,
                        name: sc.name,
                        email: sc.email || '',
                        phone: sc.phone || '',
                        status: sc.status || 'Active',
                        joinedAt: sc.joined_at || sc.created_at || new Date().toISOString(),
                        address: sc.address || '',
                        notes: sc.notes || '',
                    }));
                }
                persist();
            }
        } catch (e) {
            // offline or fallback
        }
    };

    /* ---------------- sinkronisasi penjualan dengan server ---------------- */
    const syncSalesWithApi = async () => {
        try {
            const res = await fetch('/api/v1/sales?limit=250', {
                headers: { 'Accept': 'application/json' },
            });
            if (!res.ok) return;
            const json = await res.json();
            const serverSales = json.data || (Array.isArray(json) ? json : null);
            if (Array.isArray(serverSales)) {
                if (serverSales.length === 0) {
                    state.sales = [];
                } else {
                    state.sales = serverSales.map((ss) => {
                        const sid = ss.id || ss.tx_code;
                        const orderType = ss.source_order?.order_type || (ss.order_type || 'dine_in');
                        const tableNumber = ss.source_order?.table_number || (ss.table_number || '');
                        return {
                            id: sid,
                            createdAt: ss.created_at || new Date().toISOString(),
                            customerId: ss.customer_id ?? null,
                            customerNameSnapshot: ss.customer_name_snapshot || ss.customer?.name || 'Pelanggan Walk-in',
                            orderType: orderType,
                            tableNumber: tableNumber,
                            cashierId: ss.cashier_id || 'u-admin',
                            cashierNameSnapshot: ss.cashier_name_snapshot || 'Admin',
                            status: ss.status || 'Completed',
                            paymentMethod: ss.payment_method === 'Digital Wallet' ? 'Digital Wallet' : (ss.payment_method === 'Card' ? 'Card' : (ss.payment_method || 'Cash')),
                            items: (ss.items || []).map((it, idx) => ({
                                id: it.id || `si-${sid}-${idx + 1}`,
                                productId: it.product_id,
                                name: it.name,
                                quantity: it.quantity,
                                unitPrice: it.unit_price_cents ?? it.unit_price ?? 0,
                                total: it.total_cents ?? ((it.unit_price_cents ?? it.unit_price ?? 0) * (it.quantity || 1)),
                                allocatedDiscount: it.allocated_discount_cents ?? 0,
                                allocatedTax: it.allocated_tax_cents ?? 0,
                            })),
                            lines: (ss.items || []).map((it, idx) => ({
                                productId: it.product_id,
                                name: it.name,
                                quantity: it.quantity,
                                unitPrice: it.unit_price_cents ?? it.unit_price ?? 0,
                                total: it.total_cents ?? ((it.unit_price_cents ?? it.unit_price ?? 0) * (it.quantity || 1)),
                            })),
                            subtotal: ss.subtotal_cents ?? ss.subtotal ?? 0,
                            discount: ss.discount_cents ?? ss.discount ?? 0,
                            tax: ss.tax_cents ?? ss.tax ?? 0,
                            total: ss.total_cents ?? ss.total ?? 0,
                            paidAmount: ss.paid_cents ?? ss.total_cents ?? 0,
                            changeAmount: ss.change_cents ?? 0,
                            sourceOrderId: ss.source_order_id ?? null,
                            note: ss.note ?? '',
                        };
                    });
                }
                persist();
            }
        } catch (e) {
            // offline or fallback
        }
    };

    /* ---------------- init ---------------- */
    const init = () => {
        state.loading = false;
        state.ready = true;
        syncProductsWithApi();
        syncCustomersWithApi();
        syncSalesWithApi();
        syncNotificationsWithApi(true);
    };

    return {
        ...state,
        persist,
        init,
        syncProductsWithApi,
        syncCustomersWithApi,
        syncSalesWithApi,
        syncNotificationsWithApi,
        playNotificationChime,
        /** Ikat referensi internal ke proxy reaktif Alpine. */
        __bindProxy(proxy) {
            state = proxy;
        },
        // pencari
        product, customer, sale, order, returnRec, discount, user, currentUser,
        // toast
        toast, dismissToast, announce,
        // izin demo
        can, activeRole,
        // cart
        draftLines, draftProducts, draftTotals, draftDiscountCents, addToCart, changeQty, removeLine,
        clearCart, undoClearCart, setCartNote, setCartCustomer, setCartCustomerName, setCartOrderType, setCartTableNumber, applyDiscount, removeDiscount, validateDraftDiscount,
        // pembayaran
        completePayment, voidSale, simulatePayment,
        // produk
        saveProduct, adjustStock, setProductStatus,
        // pelanggan
        saveCustomer,
        // pesanan
        saveOrder, advanceOrder, cancelOrder, completeOrderPay, duplicateOrderAsDraft,
        // retur
        saleReturnedUnits, availableUnits, saveReturnDraft, submitReturn, approveReturn, rejectReturn,
        deleteReturnDraft, duplicateReturnAsDraft,
        // diskon
        discountEffectiveStatus, saveDiscount, setDiscountEnabled, deleteDiscount, duplicateDiscount,
        // pengguna
        saveUser, setUserStatus, acceptInvitation, cancelInvitation,
        // pengaturan
        saveSettings, resetDemoData, taxSettings,
        // notifikasi
        unreadCount, markAllRead, markRead,
    };
};
