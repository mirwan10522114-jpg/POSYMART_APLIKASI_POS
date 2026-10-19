/**
 * PosyMart — seed data deterministik (design.md §26).
 * Semua nominal integer rupiah (1 sen USD acuan ≈ Rp100).
 * Jam demo: 10 Okt 2026, 14.00 WIB. Zona toko: Asia/Jakarta.
 */

import { makeIso } from '../utils/date.js';
import { computeTotals, allocateToItems, allocateUnits } from '../utils/money.js';

const DAY = (y, m, d) => ({ y, m, d });

/* ============================================================
 * PRODUK — 12 fixture (design.md §18)
 * ============================================================ */
export const PRODUCTS = [
    { id: 'p-cappuccino',     sku: 'POS-001', name: 'Cappuccino',     category: 'Kopi',    price: 25000, trackStock: false, stock: 999, lowStockThreshold: 0, status: 'Active', description: 'Espresso dengan susu hangat dan foam lembut.', image: '/images/products/p-cappuccino.jpg' },
    { id: 'p-latte',          sku: 'POS-002', name: 'Latte',          category: 'Kopi',    price: 38000, trackStock: false, stock: 999, lowStockThreshold: 0, status: 'Active', description: 'Espresso lembut dengan susu hangat.', image: '/images/products/p-latte.jpg' },
    { id: 'p-americano',      sku: 'POS-003', name: 'Americano',      category: 'Kopi',    price: 29000, trackStock: false, stock: 999, lowStockThreshold: 0, status: 'Active', description: 'Espresso dengan tambahan air panas.', image: '/images/products/p-americano.jpg' },
    { id: 'p-mocha',          sku: 'POS-004', name: 'Mocha',          category: 'Kopi',    price: 42000, trackStock: false, stock: 999, lowStockThreshold: 0, status: 'Active', description: 'Espresso, susu, dan cokelat.', image: '/images/products/p-mocha.jpg' },
    { id: 'p-bagel',          sku: 'POS-005', name: 'Bagel',          category: 'Makanan', price: 18000, trackStock: true,  stock: 24, lowStockThreshold: 8, status: 'Active', description: 'Bagel panggang segar dengan kulit keemasan.', image: '/images/products/p-bagel.jpg' },
    { id: 'p-sandwich',       sku: 'POS-006', name: 'Sandwich',       category: 'Makanan', price: 55000, trackStock: true,  stock: 12, lowStockThreshold: 4, status: 'Active', description: 'Sandwich roti gandum dengan sayuran segar.', image: '/images/products/p-sandwich.jpg' },
    { id: 'p-caesar-salad',   sku: 'POS-007', name: 'Caesar Salad',   category: 'Makanan', price: 65000, trackStock: true,  stock: 8,  lowStockThreshold: 3, status: 'Active', description: 'Sayuran segar dengan dressing Caesar dan topping renyah.', image: '/images/products/p-caesar-salad.jpg' },
    { id: 'p-muffin',         sku: 'POS-008', name: 'Muffin',         category: 'Dessert', price: 26000, trackStock: true,  stock: 6,  lowStockThreshold: 2, status: 'Active', description: 'Muffin lembut dengan potongan cokelat.', image: '/images/products/p-muffin.jpg' },
    { id: 'p-orange-juice',   sku: 'POS-009', name: 'Orange Juice',   category: 'Minuman', price: 29000, trackStock: false, stock: 999, lowStockThreshold: 0, status: 'Active', description: 'Jus jeruk segar disajikan dingin.', image: '/images/products/p-orange-juice.jpg' },
    { id: 'p-smoothie',       sku: 'POS-010', name: 'Smoothie',       category: 'Minuman', price: 39000, trackStock: false, stock: 999, lowStockThreshold: 0, status: 'Active', description: 'Smoothie buah creamy disajikan dingin.', image: '/images/products/p-smoothie.jpg' },
    { id: 'p-chocolate-cake', sku: 'POS-011', name: 'Chocolate Cake', category: 'Dessert', price: 45000, trackStock: true,  stock: 5,  lowStockThreshold: 2, status: 'Active', description: 'Kue lapis cokelat kaya rasa.', image: '/images/products/p-chocolate-cake.jpg' },
    { id: 'p-cookie',         sku: 'POS-012', name: 'Cookie',         category: 'Dessert', price: 18000, trackStock: true,  stock: 28, lowStockThreshold: 6, status: 'Active', description: 'Cookie klasik dengan chocolate chips.', image: '/images/products/p-cookie.jpg' },
];

export const CATEGORIES = ['Kopi', 'Makanan', 'Minuman', 'Dessert'];

/* ============================================================
 * PELANGGAN — 5 fixture (design.md §19)
 * ============================================================ */
export const CUSTOMERS = [];

/* ============================================================
 * PENGGUNA RESMI POSYMART — 1 Owner, 1 Admin, 3 Kasir
 * ============================================================ */
export const USERS = [
    { id: 'user-owner',        name: 'Owner PosyMart', email: 'owner@posymart.com',     role: 'Owner',   status: 'Active',  lastActiveAt: null, addedAt: makeIso(2026, 10, 1, 8, 0),  note: 'Pemilik Toko & Super Admin', current: true },
    { id: 'user-admin',        name: 'Administrator', email: 'admin@posymart.com',     role: 'Admin',   status: 'Active',  lastActiveAt: null, addedAt: makeIso(2026, 10, 1, 8, 0),  note: 'Admin Sistem & Operasional' },
    { id: 'user-kasir-1',      name: 'Kasir 1',        email: 'kasir1@posymart.com',    role: 'Cashier', status: 'Active',  lastActiveAt: null, addedAt: makeIso(2026, 10, 1, 8, 0),  note: 'Shift Pagi' },
    { id: 'user-kasir-2',      name: 'Kasir 2',        email: 'kasir2@posymart.com',    role: 'Cashier', status: 'Active',  lastActiveAt: null, addedAt: makeIso(2026, 10, 1, 8, 0),  note: 'Shift Siang' },
    { id: 'user-kasir-3',      name: 'Kasir 3',        email: 'kasir3@posymart.com',    role: 'Cashier', status: 'Active',  lastActiveAt: null, addedAt: makeIso(2026, 10, 1, 8, 0),  note: 'Shift Malam' },
];

export const ROLE_PERMISSIONS = {
    Owner: { manageProducts: true, manageUsers: true, manageSettings: true, approveReturns: true, voidSales: true, viewReports: true, manageDiscounts: true, cancelOrders: true },
    Admin: { manageProducts: true, manageUsers: true, manageSettings: true, approveReturns: true, voidSales: true, viewReports: true, manageDiscounts: true, cancelOrders: true },
    Manager: { manageProducts: true, manageUsers: false, manageSettings: false, approveReturns: true, voidSales: true, viewReports: true, manageDiscounts: true, cancelOrders: true },
    Cashier: { manageProducts: false, manageUsers: false, manageSettings: false, approveReturns: false, voidSales: false, viewReports: false, manageDiscounts: false, cancelOrders: false },
};

/* ============================================================
 * DISKON — 7 fixture (design.md §23)
 * ============================================================ */
export const DISCOUNTS = [
    { id: 'd-welcome10',  name: 'Welcome Offer',   code: 'WELCOME10',  type: 'percentage', value: 10,    appliesTo: 'all',        categories: [], products: [], customers: [], minSpend: 100000, usageLimit: 100,       usedCount: 32, startDate: makeIso(2026, 10, 1, 0, 0),  endDate: makeIso(2026, 11, 1, 0, 0),  enabled: true,  published: true,  createdAt: makeIso(2026, 9, 28, 9, 0),  updatedAt: makeIso(2026, 10, 1, 8, 0) },
    { id: 'd-coffee1',    name: 'Coffee Break',    code: 'COFFEE1',    type: 'fixed',      value: 10000, appliesTo: 'categories', categories: ['Kopi'], products: [], customers: [], minSpend: 0,      usageLimit: null,      usedCount: 18, startDate: makeIso(2026, 10, 1, 0, 0),  endDate: makeIso(2026, 11, 1, 0, 0),  enabled: true,  published: true,  createdAt: makeIso(2026, 9, 28, 9, 30), updatedAt: makeIso(2026, 10, 1, 8, 5) },
    { id: 'd-weekend15',  name: 'Weekend Treat',   code: 'WEEKEND15',  type: 'percentage', value: 15,    appliesTo: 'categories', categories: ['Dessert'], products: [], customers: [], minSpend: 0, usageLimit: 50,        usedCount: 0,  startDate: makeIso(2026, 10, 17, 0, 0), endDate: makeIso(2026, 10, 19, 0, 0), enabled: true,  published: true,  createdAt: makeIso(2026, 10, 5, 14, 0), updatedAt: makeIso(2026, 10, 5, 14, 0) },
    { id: 'd-lunch2',     name: 'Lunch Combo',     code: 'LUNCH2',     type: 'fixed',      value: 20000, appliesTo: 'categories', categories: ['Makanan'], products: [], customers: [], minSpend: 0, usageLimit: 100, usedCount: 45, startDate: makeIso(2026, 9, 1, 0, 0),   endDate: makeIso(2026, 10, 1, 0, 0),  enabled: true,  published: true,  createdAt: makeIso(2026, 8, 28, 10, 0), updatedAt: makeIso(2026, 9, 1, 7, 0) },
    { id: 'd-member5',    name: 'Member Reward',   code: 'MEMBER5',    type: 'percentage', value: 5,     appliesTo: 'all',        categories: [], products: [], customers: ['CUS-003'], minSpend: 0, usageLimit: null, usedCount: 20, startDate: makeIso(2026, 10, 1, 0, 0), endDate: makeIso(2026, 11, 1, 0, 0), enabled: false, published: true, createdAt: makeIso(2026, 9, 29, 11, 0), updatedAt: makeIso(2026, 10, 3, 16, 0) },
    { id: 'd-first25',    name: 'First 25 Orders', code: 'FIRST25',    type: 'fixed',      value: 15000, appliesTo: 'all',        categories: [], products: [], customers: [], minSpend: 0, usageLimit: 25, usedCount: 25, startDate: makeIso(2026, 10, 1, 0, 0),  endDate: makeIso(2026, 11, 1, 0, 0), enabled: true,  published: true,  createdAt: makeIso(2026, 9, 30, 12, 0), updatedAt: makeIso(2026, 10, 2, 18, 30) },
    { id: 'd-autumn20',   name: 'Autumn Special',  code: 'AUTUMN20',   type: 'percentage', value: 20,    appliesTo: 'all',        categories: [], products: [], customers: [], minSpend: 0, usageLimit: null, usedCount: 0, startDate: null, endDate: null, enabled: true, published: false, createdAt: makeIso(2026, 10, 8, 15, 0), updatedAt: makeIso(2026, 10, 8, 15, 0) },
];

/* ============================================================
 * SALE BUILDER
 * ============================================================ */
let itemSeq = 0;
const nextItemId = () => `si-${++itemSeq}`;

/**
 * Bangun SaleRecord lengkap dengan alokasi diskon/pajak per item dan per unit.
 */
export const buildSale = ({
    id, createdAt, customerId = null, customerName = 'Pelanggan Umum', cashierId = 'user-john-doe',
    cashierName = 'John Doe', status = 'Completed', paymentMethod = 'Cash', items, discountCents = 0,
    eligibleIds = null, taxEnabled = true, taxRateBps = 800, taxName = 'Pajak', note = '',
    sourceOrderId = null, voidReason = null, voidedAt = null, paidTotal = null,
}) => {
    const totals = computeTotals({ items, discountCents, taxEnabled, taxRateBps });
    const allocs = allocateToItems(items, discountCents, totals.tax, eligibleIds);
    const snapshots = items.map((it, i) => {
        const net = it.unitPrice * it.quantity - allocs[i].allocatedDiscount;
        const unitNets = allocateUnits(net, it.quantity);
        const unitTaxes = allocateUnits(allocs[i].allocatedTax, it.quantity);
        return {
            id: nextItemId(),
            productId: it.productId,
            name: it.name,
            quantity: it.quantity,
            unitPrice: it.unitPrice,
            allocatedDiscount: allocs[i].allocatedDiscount,
            allocatedTax: allocs[i].allocatedTax,
            unitAllocations: unitNets.map((netCents, u) => ({ unitIndex: u, netCents, taxCents: unitTaxes[u] })),
        };
    });
    return {
        id, createdAt, customerId, customerNameSnapshot: customerName, cashierId, cashierNameSnapshot: cashierName,
        status, paymentMethod, items: snapshots,
        subtotal: totals.subtotal, discount: totals.discount, tax: totals.tax,
        taxRateBpsSnapshot: taxRateBps, taxNameSnapshot: taxName, taxEnabledSnapshot: taxEnabled,
        total: paidTotal ?? totals.total, sourceOrderId, note, voidReason, voidedAt,
    };
};

const P = (productId, qty) => {
    const p = PRODUCTS.find((x) => x.id === productId);
    return { productId, name: p.name, unitPrice: p.price, quantity: qty };
};

/* ============================================================
 * SALES — 6 record hari ini (10 Okt) + 6 arsip + 44 historis
 * ============================================================ */
const sales = [];

// --- Hari ini: TX-1001..TX-1006 (design.md §17) ---
sales.push(buildSale({
    id: 'TX-1001', createdAt: makeIso(2026, 10, 10, 10, 32), paymentMethod: 'Cash', status: 'Completed',
    items: [P('p-cappuccino', 1), P('p-bagel', 2), P('p-caesar-salad', 1), P('p-orange-juice', 1)],
}));
sales.push(buildSale({
    id: 'TX-1002', createdAt: makeIso(2026, 10, 10, 11, 5), customerId: 'CUS-001', customerName: 'Ava Thompson',
    paymentMethod: 'Card', status: 'Completed', items: [P('p-cappuccino', 1), P('p-sandwich', 1)],
}));
sales.push(buildSale({
    id: 'TX-1003', createdAt: makeIso(2026, 10, 10, 11, 28), customerId: 'CUS-002', customerName: 'Liam Carter',
    paymentMethod: 'Card', status: 'Completed', items: [P('p-latte', 2), P('p-cookie', 2)],
}));
sales.push(buildSale({
    id: 'TX-1004', createdAt: makeIso(2026, 10, 10, 12, 10), customerId: 'CUS-003', customerName: 'Olivia Brooks',
    paymentMethod: 'Cash', status: 'Completed', items: [P('p-mocha', 1), P('p-muffin', 1)],
}));
sales.push(buildSale({
    id: 'TX-1005', createdAt: makeIso(2026, 10, 10, 12, 45), customerId: 'CUS-004', customerName: 'Ethan Miller',
    paymentMethod: 'Card', status: 'Completed', items: [P('p-americano', 2), P('p-caesar-salad', 1)],
}));
sales.push(buildSale({
    id: 'TX-1006', createdAt: makeIso(2026, 10, 10, 13, 15), customerId: 'CUS-005', customerName: 'Sofia Reed',
    paymentMethod: 'Cash', status: 'Voided', items: [P('p-smoothie', 1), P('p-chocolate-cake', 1)],
    voidReason: 'Penjualan demo duplikat', voidedAt: makeIso(2026, 10, 10, 13, 22),
}));

// --- Arsip untuk Orders/Returns (di luar rentang Laporan 04–10 Okt) ---
sales.push(buildSale({ id: 'TX-0988', createdAt: makeIso(2026, 10, 3, 11, 10), paymentMethod: 'Card', status: 'Completed', items: [P('p-smoothie', 1), P('p-chocolate-cake', 1)] }));
sales.push(buildSale({ id: 'TX-0990', createdAt: makeIso(2026, 9, 28, 9, 40),  paymentMethod: 'Cash', status: 'Completed', items: [P('p-cappuccino', 1)] }));
sales.push(buildSale({ id: 'TX-0992', createdAt: makeIso(2026, 10, 1, 10, 15), paymentMethod: 'Cash', status: 'Completed', items: [P('p-bagel', 1)] }));
sales.push(buildSale({ id: 'TX-0993', createdAt: makeIso(2026, 10, 2, 11, 30), paymentMethod: 'Card', status: 'Completed', items: [P('p-cookie', 2)] }));
sales.push(buildSale({ id: 'TX-0994', createdAt: makeIso(2026, 10, 3, 9, 50),  paymentMethod: 'Card', status: 'Completed', items: [P('p-latte', 1)] }));
sales.push(buildSale({ id: 'TX-0995', createdAt: makeIso(2026, 10, 3, 10, 5),  paymentMethod: 'Cash', status: 'Completed', items: [P('p-smoothie', 1)] }));

// --- 44 transaksi historis deterministik (04–09 Okt, design.md §26.2) ---
const HIST = [
    { day: DAY(2026, 10, 4),  rows: [['Cash', 300000, 2], ['Card', 450000, 2], ['Digital Wallet', 150000, 2]] },
    { day: DAY(2026, 10, 5),  rows: [['Cash', 250000, 2], ['Card', 300000, 4], ['Digital Wallet', 150000, 2]] },
    { day: DAY(2026, 10, 6),  rows: [['Cash', 250000, 3], ['Card', 250000, 5], ['Digital Wallet', 250000, 1]] },
    { day: DAY(2026, 10, 7),  rows: [['Cash', 250000, 2], ['Card', 300000, 4], ['Digital Wallet', 350000, 1]] },
    { day: DAY(2026, 10, 8),  rows: [['Cash', 250000, 3], ['Card', 250000, 6], ['Digital Wallet', 250000, 1]] },
    { day: DAY(2026, 10, 9),  rows: [['Cash', 150000, 2], ['Card', 500000, 1], ['Digital Wallet', 200000, 1]] },
];
const TEMPLATES = {
    150000: () => [P('p-cappuccino', 3), P('p-chocolate-cake', 1)],
    200000: () => [P('p-sandwich', 2), P('p-chocolate-cake', 2)],
    250000: () => [P('p-cappuccino', 2), P('p-chocolate-cake', 4)],
    300000: () => [P('p-cappuccino', 6), P('p-chocolate-cake', 2)],
    350000: () => [P('p-cappuccino', 10)],
    450000: () => [P('p-chocolate-cake', 10)],
    500000: () => [P('p-cappuccino', 4), P('p-chocolate-cake', 8)],
};

let histId = 1100;
let histMin = 0;
for (const { day, rows } of HIST) {
    for (const [method, net, count] of rows) {
        for (let i = 0; i < count; i++) {
            const h = 9 + Math.floor(histMin / 60);
            const m = histMin % 60;
            sales.push(buildSale({
                id: `TX-${histId}`, createdAt: makeIso(day.y, day.m, day.d, h, m),
                paymentMethod: method, status: 'Completed', items: TEMPLATES[net](),
            }));
            histId++;
            histMin += 10;
        }
    }
    histMin = 0;
}

export const SALES = sales;

/* ============================================================
 * ORDERS — 7 fixture (design.md §20.3)
 * ============================================================ */
const orderTotals = (items) => computeTotals({ items });

const mkOrder = ({ id, customerId, customerName, items, fulfillment, placed, pickup, table, note, status, timeline, saleId, cancelReason, paymentMethod }) => {
    const t = orderTotals(items);
    return {
        id, customerId: customerId ?? null, customerNameSnapshot: customerName ?? 'Pelanggan Umum',
        items: items.map((it) => ({ ...it })),
        fulfillment, placedAt: placed, pickupAt: pickup ?? null, tableNumber: table ?? null, note: note ?? '',
        status, subtotal: t.subtotal, tax: t.tax, taxRateBpsSnapshot: 800, taxNameSnapshot: 'Pajak', taxEnabledSnapshot: true,
        total: t.total, timeline, saleId: saleId ?? null, cancelReason: cancelReason ?? null, paymentMethod: paymentMethod ?? null,
        paidAt: null, cashierId: 'user-john-doe',
    };
};

export const ORDERS = [
    mkOrder({
        id: 'ORD-2007', customerId: 'CUS-001', customerName: 'Ava Thompson',
        items: [P('p-bagel', 1), P('p-orange-juice', 1)], fulfillment: 'Pickup',
        placed: makeIso(2026, 10, 10, 9, 43), pickup: makeIso(2026, 10, 10, 10, 0), note: 'Tanpa es, tolong.',
        status: 'Pending', timeline: [{ status: 'Pending', at: makeIso(2026, 10, 10, 9, 43) }],
    }),
    mkOrder({
        id: 'ORD-2001', customerId: 'CUS-001', customerName: 'Ava Thompson',
        items: [P('p-cappuccino', 2), P('p-bagel', 2)], fulfillment: 'Pickup',
        placed: makeIso(2026, 10, 10, 9, 40), pickup: makeIso(2026, 10, 10, 9, 55), note: 'Bagel dipanggang sebentar.',
        status: 'Pending', timeline: [{ status: 'Pending', at: makeIso(2026, 10, 10, 9, 40) }],
    }),
    mkOrder({
        id: 'ORD-2002', customerId: 'CUS-002', customerName: 'Liam Carter',
        items: [P('p-latte', 1), P('p-sandwich', 1)], fulfillment: 'Dine-in',
        placed: makeIso(2026, 10, 10, 9, 35), table: '04',
        status: 'Preparing', timeline: [
            { status: 'Pending', at: makeIso(2026, 10, 10, 9, 35) },
            { status: 'Preparing', at: makeIso(2026, 10, 10, 9, 41) },
        ],
    }),
    mkOrder({
        id: 'ORD-2003', customerId: 'CUS-003', customerName: 'Olivia Brooks',
        items: [P('p-americano', 2), P('p-muffin', 2)], fulfillment: 'Pickup',
        placed: makeIso(2026, 10, 10, 9, 28), pickup: makeIso(2026, 10, 10, 9, 45), note: 'Dikemas terpisah.',
        status: 'Ready', timeline: [
            { status: 'Pending', at: makeIso(2026, 10, 10, 9, 28) },
            { status: 'Preparing', at: makeIso(2026, 10, 10, 9, 33) },
            { status: 'Ready', at: makeIso(2026, 10, 10, 9, 38) },
        ],
    }),
    mkOrder({
        id: 'ORD-2004', items: [P('p-caesar-salad', 1), P('p-orange-juice', 1)], fulfillment: 'Pickup',
        placed: makeIso(2026, 10, 10, 9, 20), pickup: makeIso(2026, 10, 10, 10, 10),
        status: 'Draft', timeline: [{ status: 'Draft', at: makeIso(2026, 10, 10, 9, 20) }],
    }),
    mkOrder({
        id: 'ORD-2006', customerId: 'CUS-005', customerName: 'Sofia Reed',
        items: [P('p-cappuccino', 1), P('p-cookie', 2)], fulfillment: 'Pickup',
        placed: makeIso(2026, 10, 8, 8, 40), pickup: makeIso(2026, 10, 8, 9, 0),
        status: 'Cancelled', cancelReason: 'Pelanggan berubah pikiran.',
        timeline: [
            { status: 'Pending', at: makeIso(2026, 10, 8, 8, 40) },
            { status: 'Cancelled', at: makeIso(2026, 10, 8, 8, 52), reason: 'Pelanggan berubah pikiran.' },
        ],
    }),
    mkOrder({
        id: 'ORD-2005', items: [P('p-smoothie', 1), P('p-chocolate-cake', 1)], fulfillment: 'Dine-in',
        placed: makeIso(2026, 10, 3, 11, 10), table: '12',
        status: 'Completed', saleId: 'TX-0988', paymentMethod: 'Card',
        timeline: [
            { status: 'Pending', at: makeIso(2026, 10, 3, 11, 10) },
            { status: 'Preparing', at: makeIso(2026, 10, 3, 11, 14) },
            { status: 'Ready', at: makeIso(2026, 10, 3, 11, 22) },
            { status: 'Completed', at: makeIso(2026, 10, 3, 11, 26) },
        ],
    }),
];

// Tandai paidAt pada order completed
for (const o of ORDERS) {
    if (o.status === 'Completed' && o.saleId) o.paidAt = o.timeline.at(-1)?.at ?? o.placedAt;
}

/* ============================================================
 * RETURNS — 5 fixture (design.md §22)
 * ============================================================ */
const mkReturn = ({ id, saleId, requestedAt, status, picks, reason, notes = '', completedAt = null, reviewedAt = null, rejectedReason = null }) => {
    const sale = SALES.find((s) => s.id === saleId);
    let refundNet = 0, refundTax = 0, unitQty = 0;
    const items = picks.map(({ productId, quantity, condition = 'Unopened', restock = true }) => {
        const si = sale.items.find((x) => x.productId === productId);
        const units = si.unitAllocations.slice(0, quantity);
        const net = units.reduce((a, u) => a + u.netCents, 0);
        const tax = units.reduce((a, u) => a + u.taxCents, 0);
        refundNet += net; refundTax += tax; unitQty += quantity;
        return {
            saleItemId: si.id, productId, name: si.name, quantity,
            unitPrice: si.unitPrice, refundNet: net, refundTax: tax, refundTotal: net + tax,
            condition, restock, refundedUnits: units.map((u) => u.unitIndex),
        };
    });
    return {
        id, saleId, customerName: sale.customerNameSnapshot, requestedAt, status,
        items, reason, notes, refundNet, refundTax, refundTotal: refundNet + refundTax, unitQuantity: unitQty,
        originalMethod: sale.paymentMethod, originalDate: sale.createdAt,
        completedAt, reviewedAt, rejectedReason,
    };
};

export const RETURNS = [
    mkReturn({
        id: 'R-1001', saleId: 'TX-0990', requestedAt: makeIso(2026, 10, 6, 10, 30), status: 'Completed',
        picks: [{ productId: 'p-cappuccino', quantity: 1 }], reason: 'Barang salah',
        completedAt: makeIso(2026, 10, 6, 10, 36), reviewedAt: makeIso(2026, 10, 6, 10, 34),
    }),
    mkReturn({
        id: 'R-1002', saleId: 'TX-0992', requestedAt: makeIso(2026, 10, 10, 8, 55), status: 'Draft',
        picks: [{ productId: 'p-bagel', quantity: 1 }], reason: 'Masalah kualitas',
    }),
    mkReturn({
        id: 'R-1003', saleId: 'TX-0993', requestedAt: makeIso(2026, 10, 8, 11, 20), status: 'Completed',
        picks: [{ productId: 'p-cookie', quantity: 2 }], reason: 'Masalah kualitas',
        completedAt: makeIso(2026, 10, 8, 11, 28), reviewedAt: makeIso(2026, 10, 8, 11, 25),
    }),
    mkReturn({
        id: 'R-1004', saleId: 'TX-0994', requestedAt: makeIso(2026, 10, 10, 9, 5), status: 'Pending',
        picks: [{ productId: 'p-latte', quantity: 1 }], reason: 'Barang salah',
        reviewedAt: null,
    }),
    mkReturn({
        id: 'R-1005', saleId: 'TX-0995', requestedAt: makeIso(2026, 10, 10, 9, 15), status: 'Rejected',
        picks: [{ productId: 'p-smoothie', quantity: 1 }], reason: 'Berubah pikiran',
        rejectedReason: 'Permintaan tidak memenuhi kebijakan retur demo.',
        reviewedAt: makeIso(2026, 10, 10, 9, 22),
    }),
];

// Tandai unit yang sudah direfund pada sale sumber (untuk ketersediaan refund)
const markReturned = (saleId, productId, unitIndexes) => {
    const sale = SALES.find((s) => s.id === saleId);
    const si = sale.items.find((x) => x.productId === productId);
    si.returnedUnits = [...(si.returnedUnits ?? []), ...unitIndexes];
};
markReturned('TX-0990', 'p-cappuccino', [0]);
markReturned('TX-0993', 'p-cookie', [0, 1]);

/* ============================================================
 * PENGATURAN DEFAULT (design.md §25, label Indonesia)
 * ============================================================ */
export const DEFAULT_SETTINGS = {
    businessName: 'PosyMart',
    businessType: 'Kafe',
    currency: 'IDR — Rupiah Indonesia',
    timeZone: 'Asia/Jakarta (UTC+07:00)',
    dateFormat: 'DD MMM YYYY',
    timeFormat: '24 jam',
    storeName: 'PosyMart Downtown',
    storeEmail: 'store@example.com',
    storePhone: '+62 21 5550 0100',
    streetAddress: 'Jalan Pasar No. 123',
    city: 'Jakarta',
    postalCode: '10110',
    country: 'Indonesia',
    openingTime: '08:00',
    closingTime: '20:00',
    openDays: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
    trackInventory: true,
    defaultLowStockThreshold: 8,
    payments: { cash: true, card: true, wallet: true },
    defaultPaymentMethod: 'Cash',
    allowCashChange: true,
    taxEnabled: true,
    taxName: 'Pajak',
    taxRatePercent: 8.0,
    tableServiceFeeEnabled: true,
    tableServiceFeeCents: 2000,
    receiptHeader: 'PosyMart',
    receiptFooter: 'Terima kasih telah berbelanja bersama kami!',
    receiptShowAddress: true,
    receiptShowCashier: true,
    receiptShowCustomer: false,
    receiptShowTax: true,
    receiptWidth: '80 mm',
    notifLowStock: true,
    notifNewOrder: true,
    notifReturn: true,
    notifDiscountExpiry: true,
    discountExpiryDays: 3,
    soundAlerts: false,
    theme: 'Light',
    accentColor: 'PosyMart Teal',
    tableDensity: 'Nyaman',
    reduceMotion: false,
};

/* ============================================================
 * SNAPSHOT REFERENSI DASBOR (design.md §7, independen)
 * ============================================================ */
export const REFERENCE_STATS = {
    totalSales: 0,
    orders: 0,
    customers: 0,
    avgOrderValue: 0,
    growth: { totalSales: 0, orders: 0, customers: 0, avgOrderValue: 0 },
};

/* ============================================================
 * NOTIFIKASI
 * ============================================================ */
export const NOTIFICATIONS = [];

/* ============================================================
 * DRAFT SALE AWAL
 * ============================================================ */
export const INITIAL_DRAFT = {
    items: [],
    note: '',
    customerId: null,
    discount: null,
    paymentStatus: 'idle',
};

/* ============================================================
 * AKTIVITAS STOK PER PRODUK (fixture awal: belum ada)
 * ============================================================ */
export const INITIAL_STOCK_ACTIVITY = [];

export const buildSeed = () => ({
    version: 4,
    seededAt: '2026-10-11T18:00:00+07:00',
    products: JSON.parse(JSON.stringify(PRODUCTS)),
    customers: [],
    users: JSON.parse(JSON.stringify(USERS)),
    discounts: JSON.parse(JSON.stringify(DISCOUNTS)),
    sales: [],
    orders: [],
    returns: [],
    settings: JSON.parse(JSON.stringify(DEFAULT_SETTINGS)),
    draftSale: { items: [], note: '', customerId: null, discount: null, paymentStatus: 'idle' },
    notifications: [],
    stockActivity: [],
    idCounters: { sale: 1000, order: 2000, retur: 1000, customer: 1, product: 13, user: 8 },
});

export const STOCK_ACTIVITY_TYPES = [
    { value: 'add', label: 'Tambah Stok' },
    { value: 'remove', label: 'Kurangi Stok' },
    { value: 'set', label: 'Atur Jumlah' },
];

export const RETURN_REASONS = ['Barang salah', 'Masalah kualitas', 'Pembelian ganda', 'Berubah pikiran', 'Lainnya'];
export const RETURN_CONDITIONS = ['Unopened', 'Opened', 'Damaged'];
