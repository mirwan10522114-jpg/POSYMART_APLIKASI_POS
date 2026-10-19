/**
 * PosyMart — utilitas uang (integer rupiah).
 * Semua nominal disimpan sebagai integer rupiah (tanpa pecahan).
 * Konversi acuan dari fixture desain: 1 sen USD ≈ Rp100.
 */

export const roundHalfUp = (value) => {
    if (value >= 0) return Math.floor(value + 0.5);
    return -Math.floor(-value + 0.5);
};

export const formatRp = (amount) => {
    const n = Math.round(Number(amount) || 0);
    const sign = n < 0 ? '-' : '';
    return sign + 'Rp' + Math.abs(n).toLocaleString('id-ID');
};

/** Untuk export CSV: angka polos tanpa simbol. */
export const moneyPlain = (amount) => String(Math.round(Number(amount) || 0));

export const parseRpInput = (value) => {
    if (value === null || value === undefined || String(value).trim() === '') return NaN;
    const cleaned = String(value).replace(/[^\d,-]/g, '').replace(/\.(?=\d{3}\b)/g, '').replace(',', '.');
    const n = Number(cleaned);
    return Number.isFinite(n) ? n : NaN;
};

/**
 * Perhitungan total penjualan sesuai design.md §26.4:
 * 1. subtotal bruto = Σ harga satuan × qty
 * 2. satu diskon eligible dibatasi subtotal eligible (tanpa stacking)
 * 3. pajak = roundHalfUp(netSubtotal × rateBps / 10000) bila pajak aktif
 * 4. total = netSubtotal + pajak
 */
export const computeTotals = ({ items, discountCents = 0, taxEnabled = true, taxRateBps = 800 }) => {
    const subtotal = items.reduce((sum, it) => sum + it.unitPrice * it.quantity, 0);
    const discount = Math.max(0, Math.min(discountCents, subtotal));
    const netSubtotal = subtotal - discount;
    const tax = taxEnabled ? roundHalfUp((netSubtotal * taxRateBps) / 10000) : 0;
    return { subtotal, discount, netSubtotal, tax, total: netSubtotal + tax };
};

/**
 * Alokasi diskon + pajak ke item (largest remainder, tie-break ID item ascending).
 * Diskon hanya dialokasikan pada item eligible, proporsional bruto item eligible.
 * Pajak dialokasikan menurut nilai net tiap item setelah diskon.
 * Mengembalikan array sejajar `items`: { allocatedDiscount, allocatedTax }
 */
export const allocateToItems = (items, discountTotal, taxTotal, eligibleIds = null) => {
    const eligible = eligibleIds ? new Set(eligibleIds) : null;
    const isEligible = (it) => !eligible || eligible.has(it.productId);

    const discountAlloc = allocateByValue(
        discountTotal,
        items.map((it) => (isEligible(it) ? it.unitPrice * it.quantity : 0))
    );
    const netValues = items.map((it, i) => it.unitPrice * it.quantity - discountAlloc[i]);
    const taxAlloc = allocateByValue(taxTotal, netValues);

    return items.map((_, i) => ({ allocatedDiscount: discountAlloc[i], allocatedTax: taxAlloc[i] }));
};

/** Alokasi proporsional atas daftar nilai (largest remainder, tie-break index ascending). */
export const allocateByValue = (pool, values) => {
    const total = values.reduce((a, b) => a + b, 0);
    const result = values.map(() => 0);
    if (total <= 0 || pool <= 0) return result;
    const floors = values.map((v) => Math.floor((pool * v) / total));
    let remainder = pool - floors.reduce((a, b) => a + b, 0);
    const order = values
        .map((_, i) => i)
        .filter((i) => values[i] > 0)
        .sort((a, b) => {
            const fa = (pool * values[a]) / total - floors[a];
            const fb = (pool * values[b]) / total - floors[b];
            if (fb !== fa) return fb - fa;
            return a - b;
        });
    for (const i of order) {
        if (remainder <= 0) break;
        result[i] += 1;
        remainder -= 1;
    }
    values.forEach((v, i) => {
        result[i] += floors[i];
    });
    return result;
};

/**
 * Alokasi nilai item (net dan tax) ke unit individual untuk refund parsial.
 * Tie-break pecahan sama: indeks unit DESCENDING (sen sisa masuk unit terakhir dahulu).
 */
export const allocateUnits = (total, unitCount) => {
    if (unitCount <= 0) return [];
    if (total <= 0) return Array.from({ length: unitCount }, () => 0);
    const base = Math.floor(total / unitCount);
    let remainder = total - base * unitCount;
    const units = Array.from({ length: unitCount }, () => base);
    // Distribusikan sisa dari unit terakhir ke pertama (indeks descending).
    for (let i = unitCount - 1; i >= 0 && remainder > 0; i--) {
        units[i] += 1;
        remainder -= 1;
    }
    return units;
};

/** Hitung diskon (rupiah) dari definisi promo terhadap eligible subtotal. */
export const computeDiscountCents = (discount, eligibleSubtotal) => {
    if (!discount || eligibleSubtotal <= 0) return 0;
    if (discount.type === 'percentage') {
        return Math.min(eligibleSubtotal, roundHalfUp((eligibleSubtotal * discount.value) / 100));
    }
    return Math.min(eligibleSubtotal, discount.value);
};
