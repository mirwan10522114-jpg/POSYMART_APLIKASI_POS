/**
 * PosyMart — Halaman Laporan Pendapatan Kafe.
 * Rekap pendapatan harian, mingguan, bulanan, tahunan, rekonsiliasi kasir,
 * tipe pesanan (Dine In vs Take Away), menu terlaris, dan cetak struk closing shift.
 */

import Alpine from 'alpinejs';
import { roundHalfUp, moneyPlain, formatRp } from '../utils/money.js';
import {
    fmtDate,
    fmtDateFull,
    fmtTime,
    fmtDateTime,
    zonedDateKey,
    zonedParts,
    dateKeysBetween,
    todayKey,
    daysAgoKey,
    startOfMonthKey
} from '../utils/date.js';
import { toCsv, downloadFile, printHtml } from '../utils/files.js';

// Registry chart di level modul — tahan terhadap re-inisialisasi Alpine
const chartRegistry = {};

Alpine.data('reportsPage', () => ({
    // Filter Periode Kafe
    period: 'today', // 'today' | 'week' | 'month' | 'year' | 'custom'
    selectedDate: todayKey(),
    selectedMonth: `${new Date().getFullYear()}-${String(new Date().getMonth() + 1).padStart(2, '0')}`,
    selectedYear: String(new Date().getFullYear()),
    customStart: daysAgoKey(6),
    customEnd: todayKey(),
    appliedStart: todayKey(),
    appliedEnd: todayKey(),
    rangeError: '',
    applying: false,

    // Tabel Rincian
    tableQuery: '',
    tableSortKey: 'key',
    tableSortDir: 'asc',
    page: 1,
    pageSize: 10,

    // Modal & Dialog
    exportOpen: false,
    receiptModalOpen: false,
    metricModalOpen: false,
    activeMetric: 'netSales', // 'netSales' | 'gross' | 'tax' | 'orders' | 'itemsSold' | 'aov'
    loaded: false,
    charts: {},

    async init() {
        // Baca parameter URL ?period= jika ada
        const urlParams = new URLSearchParams(window.location.search);
        const p = urlParams.get('period');
        if (p && ['today', 'week', 'month', 'year', 'custom'].includes(p)) {
            this.period = p;
        }

        this.computeDateRange();

        // Sinkronisasi transaksi dari backend server jika ada data yang belum ter-cache
        await this.syncSalesFromApi();

        this.$nextTick(() => {
            this.loaded = true;
            this.renderCharts();
        });

        // Watchers
        this.$watch('period', () => {
            this.computeDateRange();
            this.updateUrlParam();
            this.page = 1;
            this.renderCharts();
        });
        this.$watch('selectedDate', () => {
            if (this.period === 'today') {
                this.computeDateRange();
                this.renderCharts();
            }
        });
        this.$watch('selectedMonth', () => {
            if (this.period === 'month') {
                this.computeDateRange();
                this.renderCharts();
            }
        });
        this.$watch('selectedYear', () => {
            if (this.period === 'year') {
                this.computeDateRange();
                this.renderCharts();
            }
        });

        // Tangkap event popstate dari navigasi browser / sidebar
        window.addEventListener('popstate', () => {
            const currentParams = new URLSearchParams(window.location.search);
            const currentPeriod = currentParams.get('period') || 'today';
            if (currentPeriod !== this.period) {
                this.period = currentPeriod;
            }
        });
    },

    destroy() {
        Object.values(chartRegistry).forEach((c) => c?.destroy?.());
    },

    updateUrlParam() {
        const url = new URL(window.location.href);
        url.searchParams.set('period', this.period);
        window.history.replaceState({}, '', url.toString());
    },

    setPeriod(newPeriod) {
        this.period = newPeriod;
    },

    /** Hitung rentang tanggal awal & akhir berdasarkan pilihan periode */
    computeDateRange() {
        const now = new Date();
        const y = now.getFullYear();
        const m = now.getMonth() + 1;

        if (this.period === 'today') {
            this.appliedStart = this.selectedDate || todayKey();
            this.appliedEnd = this.selectedDate || todayKey();
        } else if (this.period === 'week') {
            // 7 Hari Terakhir
            this.appliedStart = daysAgoKey(6);
            this.appliedEnd = todayKey();
        } else if (this.period === 'month') {
            // Bulan Berjalan atau Bulan Terpilih (1 s/d akhir bulan)
            const [selY, selM] = (this.selectedMonth || `${y}-${String(m).padStart(2, '0')}`).split('-');
            const lastDay = new Date(Number(selY), Number(selM), 0).getDate();
            this.appliedStart = `${selY}-${selM}-01`;
            this.appliedEnd = `${selY}-${selM}-${String(lastDay).padStart(2, '0')}`;
        } else if (this.period === 'year') {
            // 1 Tahun Penuh (1 Jan s/d 31 Des)
            const selY = this.selectedYear || String(y);
            this.appliedStart = `${selY}-01-01`;
            this.appliedEnd = `${selY}-12-31`;
        } else if (this.period === 'custom') {
            this.appliedStart = this.customStart || daysAgoKey(6);
            this.appliedEnd = this.customEnd || todayKey();
        }
    },

    applyCustomRange() {
        if (!this.customStart || !this.customEnd) {
            this.rangeError = 'Tanggal mulai dan akhir wajib diisi.';
            return;
        }
        if (this.customStart > this.customEnd) {
            this.rangeError = 'Tanggal akhir harus sama atau setelah tanggal mulai.';
            return;
        }
        this.rangeError = '';
        this.applying = true;
        setTimeout(() => {
            this.appliedStart = this.customStart;
            this.appliedEnd = this.customEnd;
            this.page = 1;
            this.applying = false;
            this.renderCharts();
        }, 150);
    },

    get rangeLabel() {
        if (this.period === 'today') {
            return `Hari Ini (${fmtDate(this.appliedStart + 'T00:00:00+07:00')})`;
        }
        if (this.period === 'week') {
            return `Minggu Ini (${fmtDate(this.appliedStart + 'T00:00:00+07:00')} – ${fmtDate(this.appliedEnd + 'T00:00:00+07:00')})`;
        }
        if (this.period === 'month') {
            const [y, m] = this.appliedStart.split('-');
            const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            return `Bulan ${monthNames[Number(m) - 1]} ${y}`;
        }
        if (this.period === 'year') {
            return `Tahun ${this.appliedStart.slice(0, 4)}`;
        }
        return `${fmtDate(this.appliedStart + 'T00:00:00+07:00')} – ${fmtDate(this.appliedEnd + 'T00:00:00+07:00')}`;
    },

    /* ---------------- Sinkronisasi dengan Database API ---------------- */
    async syncSalesFromApi() {
        try {
            const res = await fetch('/api/v1/sales?limit=250', {
                headers: { 'Accept': 'application/json' },
            });
            if (!res.ok) return;
            const json = await res.json();
            const serverSales = json.data || (Array.isArray(json) ? json : null);
            if (Array.isArray(serverSales) && serverSales.length > 0) {
                const localSales = this.$store.posy.sales;
                serverSales.forEach((ss) => {
                    const existing = localSales.find((ls) => ls.id === ss.id || ls.receiptNo === ss.tx_code);
                    const mappedItems = Array.isArray(ss.items) ? ss.items.map((it) => ({
                        productId: it.product_id,
                        name: it.name,
                        quantity: it.quantity,
                        unitPrice: it.unit_price_cents ?? it.unit_price ?? 0,
                        total: it.total_cents ?? it.total ?? ((it.unit_price_cents ?? 0) * (it.quantity || 1)),
                    })) : [];

                    if (!existing && ss.status === 'Completed') {
                        localSales.push({
                            id: ss.id,
                            receiptNo: ss.tx_code,
                            createdAt: ss.created_at,
                            completedAt: ss.created_at,
                            cashierId: ss.cashier_id,
                            cashierName: ss.cashier_name_snapshot || 'Kasir',
                            customerId: ss.customer_id,
                            customerName: ss.customer_name_snapshot || 'Umum',
                            paymentMethod: ss.payment_method === 'Digital Wallet' ? 'Digital Wallet' : (ss.payment_method === 'Card' ? 'Card' : 'Cash'),
                            subtotal: ss.subtotal_cents ?? 0,
                            discount: ss.discount_cents ?? 0,
                            tax: ss.tax_cents ?? 0,
                            total: ss.total_cents ?? 0,
                            paid: ss.paid_cents ?? ss.total_cents ?? 0,
                            change: ss.change_cents ?? 0,
                            status: 'Completed',
                            orderType: ss.note && ss.note.toLowerCase().includes('takeaway') ? 'takeaway' : 'dine_in',
                            items: mappedItems,
                            lines: mappedItems,
                        });
                    } else if (existing && (!existing.items || existing.items.length === 0) && mappedItems.length > 0) {
                        existing.items = mappedItems;
                        existing.lines = mappedItems;
                    }
                });
                this.$store.posy.persist();
            }
        } catch (e) {
            // offline fallback
        }
    },

    /* ---------------- Penjualan Aktif dalam Rentang Terpilih ---------------- */
    get activeSales() {
        const sales = this.$store.posy.sales || [];
        return sales.filter((s) => {
            if (s.status !== 'Completed') return false;
            const k = zonedDateKey(s.completedAt || s.createdAt || new Date().toISOString());
            return k >= this.appliedStart && k <= this.appliedEnd;
        });
    },

    get activeRefunds() {
        const returns = this.$store.posy.returns || [];
        return returns.filter((r) => {
            if (r.status !== 'Completed') return false;
            const k = zonedDateKey(r.completedAt || r.requestedAt || new Date().toISOString());
            return k >= this.appliedStart && k <= this.appliedEnd;
        });
    },

    /* ---------------- Daftar Transaksi dengan Pajak ---------------- */
    get taxSales() {
        return this.activeSales.filter((s) => (s.tax || 0) > 0);
    },

    /* ---------------- Ringkasan Keuangan Utama Kafe ---------------- */
    get totals() {
        const sales = this.activeSales;
        const refunds = this.activeRefunds;

        const subtotal = sales.reduce((a, s) => a + (s.subtotal || 0), 0);
        const discounts = sales.reduce((a, s) => a + (s.discount || 0), 0);
        const tax = sales.reduce((a, s) => a + (s.tax || 0), 0);
        const total = sales.reduce((a, s) => a + (s.total || 0), 0);

        const refundTotal = refunds.reduce((a, r) => a + (r.refundTotal || 0), 0);
        const refundNet = refunds.reduce((a, r) => a + (r.refundNet || 0), 0);
        const refundTax = refunds.reduce((a, r) => a + (r.refundTax || 0), 0);

        // 1. Penjualan Bersih (Net Sales): Omzet murni menu setelah diskon & retur (DI LUAR PAJAK)
        const netSales = Math.max(0, subtotal - discounts - refundNet);
        // 2. Pajak Bersih (Net Tax): Titipan pajak terkumpul setelah dikurangi refund pajak
        const netTax = Math.max(0, tax - refundTax);
        // 3. Total Penerimaan Kotor Masuk (Gross Collected / Uang Masuk Bruto termasuk Pajak)
        const grossCollected = Math.max(0, total - refundTotal);

        const orders = sales.length;
        const aov = orders > 0 ? roundHalfUp(grossCollected / orders) : 0;

        // Porsi / cups terjual
        let itemsSold = 0;
        sales.forEach((s) => {
            const list = Array.isArray(s.items) && s.items.length > 0 ? s.items : (Array.isArray(s.lines) ? s.lines : []);
            itemsSold += list.reduce((acc, it) => acc + Number(it.quantity || 1), 0);
        });

        // Rekonsiliasi Metode Pembayaran
        const cash = sales.filter((s) => s.paymentMethod === 'Cash').reduce((a, s) => a + (s.total || 0), 0);
        const card = sales.filter((s) => s.paymentMethod === 'Card').reduce((a, s) => a + (s.total || 0), 0);
        const wallet = sales.filter((s) => s.paymentMethod === 'Digital Wallet' || s.paymentMethod === 'QRIS' || s.paymentMethod === 'E-Wallet').reduce((a, s) => a + (s.total || 0), 0);

        // Rekonsiliasi Tipe Pesanan (Dine In vs Take Away)
        const dineInSales = sales.filter((s) => s.orderType !== 'takeaway').reduce((a, s) => a + (s.total || 0), 0);
        const dineInOrders = sales.filter((s) => s.orderType !== 'takeaway').length;

        const takeawaySales = sales.filter((s) => s.orderType === 'takeaway').reduce((a, s) => a + (s.total || 0), 0);
        const takeawayOrders = sales.filter((s) => s.orderType === 'takeaway').length;

        return {
            orders,
            itemsSold,
            subtotal,
            discounts,
            tax,
            gross: grossCollected,
            grossCollected,
            refundTotal,
            refundNet,
            refundTax,
            netSales,
            netTax,
            netCollected: grossCollected,
            aov,
            cash,
            card,
            wallet,
            dineInSales,
            dineInOrders,
            takeawaySales,
            takeawayOrders,
            completedRefunds: refunds.length,
        };
    },

    /* ---------------- Seluruh Menu & Porsi Terjual (Rincian Closing) ---------------- */
    get soldProducts() {
        const itemMap = {};
        this.activeSales.forEach((s) => {
            const list = Array.isArray(s.items) && s.items.length > 0 ? s.items : (Array.isArray(s.lines) ? s.lines : []);
            list.forEach((it) => {
                const id = it.productId || it.product_id || it.name || 'item';
                const name = it.name || (this.$store.posy.product ? this.$store.posy.product(id)?.name : null) || 'Menu Kafe';
                const qty = Number(it.quantity || 1);
                const price = Number(it.unitPrice ?? it.unit_price ?? it.unit_price_cents ?? 0);
                const total = (it.total != null && it.total > 0) ? Number(it.total) : (price * qty);

                if (!itemMap[id]) {
                    itemMap[id] = {
                        id,
                        name,
                        category: it.category || 'Menu Kafe',
                        quantity: 0,
                        unitPrice: price,
                        revenue: 0,
                    };
                }
                itemMap[id].quantity += qty;
                itemMap[id].revenue += total;
                if (!itemMap[id].unitPrice && price) {
                    itemMap[id].unitPrice = price;
                }
            });
        });

        return Object.values(itemMap).sort((a, b) => b.quantity - a.quantity || b.revenue - a.revenue);
    },

    /* ---------------- Top Menu Kafe Terlaris (Dashboard Top 5) ---------------- */
    get topProducts() {
        return this.soldProducts.slice(0, 5);
    },

    /* ---------------- Analisis Jam Sibuk Kafe (Peak Hours) ---------------- */
    get hourlyAnalysis() {
        const hours = [];
        // Rentang jam buka kafe: 07:00 s/d 23:00
        for (let h = 7; h <= 23; h++) {
            const hStr = String(h).padStart(2, '0') + ':00';
            const nextHStr = String(h + 1).padStart(2, '0') + ':00';
            const matched = this.activeSales.filter((s) => {
                const parts = zonedParts(s.completedAt || s.createdAt);
                return parts.hour === h;
            });
            const orders = matched.length;
            const gross = matched.reduce((a, s) => a + (s.total || 0), 0);
            hours.push({
                hour: h,
                label: `${hStr} – ${nextHStr}`,
                shortLabel: hStr,
                orders,
                gross,
                netSales: gross,
            });
        }
        return hours;
    },

    /* ---------------- Baris Tabel Rekap Sesuai Periode ---------------- */
    get breakdownRows() {
        if (this.period === 'today') {
            // Harian: baris per jam sibuk
            return this.hourlyAnalysis.filter((h) => h.orders > 0 || (h.hour >= 8 && h.hour <= 22)).map((h) => ({
                key: `hour-${h.hour}`,
                label: h.label,
                sublabel: 'Jam operasional kafe',
                orders: h.orders,
                dineInOrders: this.activeSales.filter((s) => zonedParts(s.completedAt || s.createdAt).hour === h.hour && s.orderType !== 'takeaway').length,
                takeawayOrders: this.activeSales.filter((s) => zonedParts(s.completedAt || s.createdAt).hour === h.hour && s.orderType === 'takeaway').length,
                gross: h.gross,
                discounts: 0,
                refundTotal: 0,
                netSales: h.gross,
                cash: this.activeSales.filter((s) => zonedParts(s.completedAt || s.createdAt).hour === h.hour && s.paymentMethod === 'Cash').reduce((a, s) => a + (s.total || 0), 0),
                cashless: this.activeSales.filter((s) => zonedParts(s.completedAt || s.createdAt).hour === h.hour && s.paymentMethod !== 'Cash').reduce((a, s) => a + (s.total || 0), 0),
            }));
        }

        if (this.period === 'year') {
            // Tahunan: baris per 12 bulan
            const selY = this.appliedStart.slice(0, 4);
            const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            return months.map((mName, idx) => {
                const mPrefix = `${selY}-${String(idx + 1).padStart(2, '0')}`;
                const mSales = this.activeSales.filter((s) => zonedDateKey(s.completedAt || s.createdAt).startsWith(mPrefix));
                const mRefunds = this.activeRefunds.filter((r) => zonedDateKey(r.completedAt || r.requestedAt).startsWith(mPrefix));

                const gross = mSales.reduce((a, s) => a + (s.total || 0), 0);
                const discounts = mSales.reduce((a, s) => a + (s.discount || 0), 0);
                const refundTotal = mRefunds.reduce((a, r) => a + (r.refundTotal || 0), 0);
                const netSales = Math.max(0, gross - refundTotal);

                const cash = mSales.filter((s) => s.paymentMethod === 'Cash').reduce((a, s) => a + (s.total || 0), 0);
                const cashless = mSales.filter((s) => s.paymentMethod !== 'Cash').reduce((a, s) => a + (s.total || 0), 0);

                return {
                    key: mPrefix,
                    label: `${mName} ${selY}`,
                    sublabel: 'Rekap bulanan',
                    orders: mSales.length,
                    dineInOrders: mSales.filter((s) => s.orderType !== 'takeaway').length,
                    takeawayOrders: mSales.filter((s) => s.orderType === 'takeaway').length,
                    gross,
                    discounts,
                    refundTotal,
                    netSales,
                    cash,
                    cashless,
                };
            });
        }

        // Mingguan / Bulanan / Kustom: baris per tanggal
        const keys = dateKeysBetween(this.appliedStart + 'T00:00:00+07:00', this.appliedEnd + 'T00:00:00+07:00');
        return keys.map((key) => {
            const daySales = this.activeSales.filter((s) => zonedDateKey(s.completedAt || s.createdAt) === key);
            const dayRefunds = this.activeRefunds.filter((r) => zonedDateKey(r.completedAt || r.requestedAt) === key);

            const [y, m, d] = key.split('-');
            const dateObj = new Date(`${key}T00:00:00+07:00`);
            const daysShort = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
            const monthsShort = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            const dayName = daysShort[dateObj.getDay()] || '';
            const label = `${dayName}, ${Number(d)} ${monthsShort[Number(m) - 1]} ${y}`;

            const gross = daySales.reduce((a, s) => a + (s.total || 0), 0);
            const discounts = daySales.reduce((a, s) => a + (s.discount || 0), 0);
            const refundTotal = dayRefunds.reduce((a, r) => a + (r.refundTotal || 0), 0);
            const netSales = Math.max(0, gross - refundTotal);

            const cash = daySales.filter((s) => s.paymentMethod === 'Cash').reduce((a, s) => a + (s.total || 0), 0);
            const cashless = daySales.filter((s) => s.paymentMethod !== 'Cash').reduce((a, s) => a + (s.total || 0), 0);

            return {
                key,
                label,
                sublabel: `${daySales.length} transaksi`,
                orders: daySales.length,
                dineInOrders: daySales.filter((s) => s.orderType !== 'takeaway').length,
                takeawayOrders: daySales.filter((s) => s.orderType === 'takeaway').length,
                gross,
                discounts,
                refundTotal,
                netSales,
                cash,
                cashless,
            };
        });
    },

    get tableFiltered() {
        const q = this.tableQuery.trim().toLowerCase();
        if (!q) return this.breakdownRows;
        return this.breakdownRows.filter((r) => r.label.toLowerCase().includes(q) || r.key.includes(q));
    },

    get tableSorted() {
        const arr = [...this.tableFiltered];
        const dir = this.tableSortDir === 'asc' ? 1 : -1;
        arr.sort((a, b) => {
            if (this.tableSortKey === 'key') return a.key.localeCompare(b.key) * dir;
            if (this.tableSortKey === 'orders') return (a.orders - b.orders) * dir;
            if (this.tableSortKey === 'netSales') return (a.netSales - b.netSales) * dir;
            if (this.tableSortKey === 'gross') return (a.gross - b.gross) * dir;
            return 0;
        });
        return arr;
    },

    setTableSort(key) {
        if (this.tableSortKey === key) this.tableSortDir = this.tableSortDir === 'asc' ? 'desc' : 'asc';
        else {
            this.tableSortKey = key;
            this.tableSortDir = 'asc';
        }
    },

    get totalRows() {
        return this.tableSorted.length;
    },

    get totalPages() {
        return Math.max(1, Math.ceil(this.totalRows / this.pageSize));
    },

    get paged() {
        if (this.page > this.totalPages) this.page = this.totalPages;
        return this.tableSorted.slice((this.page - 1) * this.pageSize, this.page * this.pageSize);
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

    /* ---------------- Chart.js Renderings ---------------- */
    async renderCharts() {
        await this.$nextTick();
        const { default: Chart } = await import('chart.js/auto');
        Chart.defaults.font.family = "'Inter', ui-sans-serif, system-ui, sans-serif";
        Chart.defaults.font.size = 12;
        Chart.defaults.color = '#6F7478';
        Chart.defaults.animation = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? false : { duration: 200 };

        const q = (name) => document.querySelector(`canvas[data-chart="${name}"]`);
        const rows = this.breakdownRows;
        const labels = rows.map((r) => r.label.split(',')[0]); // ringkas
        const totals = this.totals;

        // 1. Chart Tren Pendapatan
        try {
            chartRegistry.trend?.destroy();
            const trendEl = q('trend');
            if (trendEl) {
                const maxNet = Math.max(1000, ...rows.map((r) => r.netSales));
                const niceMax = Math.ceil(maxNet / 4) * 4 || 1000;
                chartRegistry.trend = new Chart(trendEl, {
                    type: this.period === 'today' || this.period === 'year' ? 'bar' : 'line',
                    data: {
                        labels: this.period === 'today' ? rows.map((r) => r.label.slice(0, 5)) : labels,
                        datasets: [{
                            label: 'Penjualan Bersih',
                            data: rows.map((r) => r.netSales),
                            borderColor: '#49ADAB',
                            backgroundColor: this.period === 'today' || this.period === 'year' ? '#49ADAB' : 'rgba(73,173,171,0.10)',
                            borderWidth: 2.5,
                            borderRadius: 4,
                            fill: true,
                            tension: 0.3,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                suggestedMax: niceMax,
                                grid: { color: '#F0F1F3' },
                                border: { display: false },
                                ticks: {
                                    callback: (val) => val >= 1000000 ? (val / 1000000) + 'jt' : (val >= 1000 ? (val / 1000) + 'rb' : val),
                                },
                            },
                            x: { grid: { display: false }, border: { display: false } },
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#25272A',
                                padding: 10,
                                cornerRadius: 8,
                                callbacks: {
                                    label: (ctx) => `Penjualan Bersih: Rp${ctx.raw.toLocaleString('id-ID')}`,
                                },
                            },
                        },
                    },
                });
            }
        } catch (e) {
            console.error('[PosyMart] chart tren gagal:', e);
        }

        // 2. Chart Metode Pembayaran (Doughnut)
        try {
            chartRegistry.payment?.destroy();
            const payEl = q('payment');
            if (payEl) {
                const totalPay = totals.cash + totals.card + totals.wallet;
                chartRegistry.payment = new Chart(payEl, {
                    type: 'doughnut',
                    data: {
                        labels: ['Tunai', 'Kartu / EDC', 'QRIS / E-Wallet'],
                        datasets: [{
                            data: totalPay > 0 ? [totals.cash, totals.card, totals.wallet] : [1, 0, 0],
                            backgroundColor: totalPay > 0 ? ['#49ADAB', '#7898AB', '#E89858'] : ['#E2E8F0', '#CBD5E1', '#F1F5F9'],
                            borderWidth: 3,
                            borderColor: '#fff',
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '72%',
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#25272A',
                                padding: 10,
                                cornerRadius: 8,
                                callbacks: {
                                    label: (ctx) => {
                                        if (totalPay === 0) return 'Belum ada transaksi';
                                        const pct = totalPay > 0 ? ((ctx.raw / totalPay) * 100).toFixed(1) : 0;
                                        return `${ctx.label}: Rp${ctx.raw.toLocaleString('id-ID')} (${pct}%)`;
                                    },
                                },
                            },
                        },
                    },
                });
            }
        } catch (e) {
            console.error('[PosyMart] chart pembayaran gagal:', e);
        }

        // 3. Chart Tipe Pesanan Kafe (Dine In vs Take Away)
        try {
            chartRegistry.dining?.destroy();
            const diningEl = q('dining');
            if (diningEl) {
                const totalDine = totals.dineInSales + totals.takeawaySales;
                chartRegistry.dining = new Chart(diningEl, {
                    type: 'doughnut',
                    data: {
                        labels: ['Dine In (Makan di Tempat)', 'Take Away (Bungkus)'],
                        datasets: [{
                            data: totalDine > 0 ? [totals.dineInSales, totals.takeawaySales] : [1, 0],
                            backgroundColor: totalDine > 0 ? ['#2A7270', '#8DC8B4'] : ['#E2E8F0', '#CBD5E1'],
                            borderWidth: 3,
                            borderColor: '#fff',
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '72%',
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#25272A',
                                padding: 10,
                                cornerRadius: 8,
                                callbacks: {
                                    label: (ctx) => {
                                        if (totalDine === 0) return 'Belum ada transaksi';
                                        const pct = totalDine > 0 ? ((ctx.raw / totalDine) * 100).toFixed(1) : 0;
                                        return `${ctx.label}: Rp${ctx.raw.toLocaleString('id-ID')} (${pct}%)`;
                                    },
                                },
                            },
                        },
                    },
                });
            }
        } catch (e) {
            console.error('[PosyMart] chart dining gagal:', e);
        }
    },

    /* ---------------- Modal Detail Metrik Keuangan ---------------- */
    openMetricModal(metricKey) {
        this.activeMetric = metricKey || 'netSales';
        this.metricModalOpen = true;
    },

    closeMetricModal() {
        this.metricModalOpen = false;
    },

    /* ---------------- Cetak Struk Closing Kasir (Thermal Receipt) ---------------- */
    openReceiptModal() {
        this.receiptModalOpen = true;
    },

    closeReceiptModal() {
        this.receiptModalOpen = false;
    },

    printClosingReceipt() {
        const t = this.totals;
        const nowStr = fmtDateTime(new Date().toISOString());
        const user = this.$store.posy.currentUser?.()?.name || 'Kasir';
        const role = this.$store.posy.currentUser?.()?.role || 'Staff';
        const items = this.soldProducts;

        const itemsHtml = items.length > 0
            ? items.map((it) => `
                <div style="margin: 4px 0; line-height: 1.4;">
                    <div style="font-weight:bold; font-size:11.5px; word-break:break-word;">${it.name}</div>
                    <div style="display:flex; justify-content:space-between; font-size:10.5px; color:#333;">
                        <span>${it.quantity} Porsi ${it.unitPrice ? '× ' + formatRp(it.unitPrice) : ''}</span>
                        <span style="font-weight:bold; color:#000;">${formatRp(it.revenue)}</span>
                    </div>
                </div>
            `).join('')
            : '<div style="font-style:italic; color:#888; font-size:10.5px; padding:4px 0;">Tidak ada menu terjual</div>';

        const receiptBody = `
            <div style="text-align:center; margin-bottom:12px;">
                <div style="font-size:16px; font-weight:bold; letter-spacing:1px;">POSYMART CAFE</div>
                <div style="font-size:11px; color:#555;">REKAP CLOSING KASIR</div>
                <div style="font-size:10px; color:#777;">Waktu Cetak: ${nowStr}</div>
                <div style="font-size:10px; color:#777;">Kasir: ${user} (${role})</div>
                <div style="font-size:10px; color:#777;">Periode: ${this.rangeLabel}</div>
            </div>
            <div style="border-top:1px dashed #333; margin:8px 0;"></div>
            <table style="width:100%; font-size:11px; line-height:1.6;">
                <tr><td>Total Transaksi</td><td style="text-align:right; font-weight:bold;">${t.orders} struk</td></tr>
                <tr><td>Total Menu Terjual</td><td style="text-align:right; font-weight:bold;">${t.itemsSold} porsi</td></tr>
                <tr><td>Rata-rata Order (AOV)</td><td style="text-align:right;">${formatRp(t.aov)}</td></tr>
            </table>
            <div style="border-top:1px dashed #333; margin:8px 0;"></div>
            <table style="width:100%; font-size:11px; line-height:1.6;">
                <tr><td>Penjualan Kotor</td><td style="text-align:right;">${formatRp(t.gross)}</td></tr>
                <tr><td>Total Diskon</td><td style="text-align:right; color:#c00;">-${formatRp(t.discounts)}</td></tr>
                <tr><td>Total Refund</td><td style="text-align:right; color:#c00;">-${formatRp(t.refundTotal)}</td></tr>
                <tr style="font-weight:bold; font-size:12px;">
                    <td>PENJUALAN BERSIH</td><td style="text-align:right;">${formatRp(t.netSales)}</td>
                </tr>
            </table>
            <div style="border-top:1px dashed #333; margin:8px 0;"></div>
            <div style="font-size:11px; font-weight:bold; margin-bottom:4px; display:flex; justify-content:space-between;">
                <span>RINCIAN MENU TERJUAL:</span>
                <span style="font-size:10px; font-weight:normal; color:#555;">${items.length} Menu (${t.itemsSold} Porsi)</span>
            </div>
            <div style="margin: 4px 0 8px;">
                ${itemsHtml}
            </div>
            <div style="border-top:1px dashed #333; margin:8px 0;"></div>
            <div style="font-size:11px; font-weight:bold; margin-bottom:4px;">REKONSILIASI PEMBAYARAN:</div>
            <table style="width:100%; font-size:11px; line-height:1.6;">
                <tr><td>Uang Tunai di Laci (Cash)</td><td style="text-align:right; font-weight:bold;">${formatRp(t.cash)}</td></tr>
                <tr><td>QRIS / E-Wallet</td><td style="text-align:right;">${formatRp(t.wallet)}</td></tr>
                <tr><td>Kartu / EDC / Transfer</td><td style="text-align:right;">${formatRp(t.card)}</td></tr>
            </table>
            <div style="border-top:1px dashed #333; margin:8px 0;"></div>
            <div style="font-size:11px; font-weight:bold; margin-bottom:4px;">TIPE PESANAN:</div>
            <table style="width:100%; font-size:11px; line-height:1.6;">
                <tr><td>Dine In (${t.dineInOrders} order)</td><td style="text-align:right;">${formatRp(t.dineInSales)}</td></tr>
                <tr><td>Take Away (${t.takeawayOrders} order)</td><td style="text-align:right;">${formatRp(t.takeawaySales)}</td></tr>
            </table>
            <div style="border-top:1px dashed #333; margin:16px 0 10px;"></div>
            <div style="display:flex; justify-content:space-between; text-align:center; font-size:10px; margin-top:16px;">
                <div style="width:45%;">Kasir<br><br><br>(${user})</div>
                <div style="width:45%;">Supervisor<br><br><br>( .................... )</div>
            </div>
            <div style="text-align:center; font-size:9px; color:#888; margin-top:16px;">
                *** POSYMART COFFEE SHOP RECAP ***
            </div>
        `;

        printHtml(`Rekap Closing - ${nowStr}`, receiptBody);
        this.receiptModalOpen = false;
        this.$store.posy.toast('Struk rekap closing kasir siap dicetak');
    },

    /* ---------------- Ekspor CSV ---------------- */
    exportSummaryCsv() {
        const headers = ['periode', 'transaksi', 'dine_in_orders', 'takeaway_orders', 'gross_sales', 'discounts', 'refund_total', 'net_sales', 'cash_paid', 'cashless_paid'];
        const rows = this.breakdownRows.map((r) => [
            r.label,
            r.orders,
            r.dineInOrders,
            r.takeawayOrders,
            moneyPlain(r.gross),
            moneyPlain(r.discounts),
            moneyPlain(r.refundTotal),
            moneyPlain(r.netSales),
            moneyPlain(r.cash),
            moneyPlain(r.cashless),
        ]);
        downloadFile(`posymart-rekap-${this.period}-${this.appliedStart}-to-${this.appliedEnd}.csv`, toCsv(headers, rows));
        this.exportOpen = false;
        this.$store.posy.toast('CSV Ringkasan Pendapatan diunduh');
    },

    exportTransactionsCsv() {
        const headers = ['struk', 'tanggal_waktu', 'kasir', 'pelanggan', 'tipe_pesanan', 'metode_pembayaran', 'subtotal', 'diskon', 'pajak', 'total_bersih'];
        const rows = this.activeSales.map((s) => [
            s.receiptNo || s.id,
            fmtDateTime(s.completedAt || s.createdAt),
            s.cashierName || 'Kasir',
            s.customerName || 'Umum',
            s.orderType === 'takeaway' ? 'Take Away' : 'Dine In',
            s.paymentMethod || 'Cash',
            moneyPlain(s.subtotal),
            moneyPlain(s.discount),
            moneyPlain(s.tax),
            moneyPlain(s.total),
        ]);
        downloadFile(`posymart-transaksi-${this.appliedStart}-to-${this.appliedEnd}.csv`, toCsv(headers, rows));
        this.exportOpen = false;
        this.$store.posy.toast('CSV Rincian Transaksi diunduh');
    },

    exportMenuCsv() {
        const headers = ['nama_menu', 'kategori', 'porsi_terjual', 'total_omzet'];
        const rows = this.topProducts.map((p) => [
            p.name,
            p.category,
            p.quantity,
            moneyPlain(p.revenue),
        ]);
        downloadFile(`posymart-menu-terlaris-${this.appliedStart}-to-${this.appliedEnd}.csv`, toCsv(headers, rows));
        this.exportOpen = false;
        this.$store.posy.toast('CSV Menu Terlaris diunduh');
    },

    printReport() {
        window.print();
    },
}));
