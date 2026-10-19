/**
 * PosyMart — utilitas tanggal/waktu (zona Asia/Jakarta).
 * Jam demo dibekukan: Sabtu, 10 Okt 2026, 14.00 WIB (2026-10-10T14:00:00+07:00).
 * Semua fixture memakai jam ini, bukan jam perangkat.
 */

export const DEMO_NOW_ISO = '2026-10-10T14:00:00+07:00';
export const STORE_TZ = 'Asia/Jakarta';

const MONTHS_ID = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
const MONTHS_ID_FULL = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
];
const DAYS_ID = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

/** Parse ISO string menjadi Date (aman terhadap offset +07:00). */
export const toDate = (iso) => (iso instanceof Date ? iso : new Date(iso));

/** Bagian tanggal lokal sesuai zona toko (Asia/Jakarta, UTC+7). */
export const zonedParts = (iso) => {
    const d = toDate(iso);
    // Ambil bagian sesuai timezone Asia/Jakarta via formatter
    const fmt = new Intl.DateTimeFormat('en-GB', {
        timeZone: STORE_TZ,
        year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit', hour12: false,
    });
    const parts = {};
    for (const p of fmt.formatToParts(d)) parts[p.type] = p.value;
    return {
        year: Number(parts.year),
        month: Number(parts.month),
        day: Number(parts.day),
        hour: Number(parts.hour === '24' ? '0' : parts.hour),
        minute: Number(parts.minute),
    };
};

/** "2026-10-10" (tanggal toko dari ISO) */
export const zonedDateKey = (iso) => {
    const p = zonedParts(iso);
    return `${p.year}-${String(p.month).padStart(2, '0')}-${String(p.day).padStart(2, '0')}`;
};

/** "10 Okt 2026" */
export const fmtDate = (iso) => {
    const p = zonedParts(iso);
    return `${p.day} ${MONTHS_ID[p.month - 1]} ${p.year}`;
};

/** "10 Oktober 2026" */
export const fmtDateFull = (iso) => {
    const p = zonedParts(iso);
    return `${p.day} ${MONTHS_ID_FULL[p.month - 1]} ${p.year}`;
};

/** "10.32" (24 jam, pemisah titik gaya Indonesia) */
export const fmtTime = (iso) => {
    const p = zonedParts(iso);
    return `${String(p.hour).padStart(2, '0')}.${String(p.minute).padStart(2, '0')}`;
};

/** "10 Okt 2026, 10.32" */
export const fmtDateTime = (iso) => `${fmtDate(iso)}, ${fmtTime(iso)}`;

/** "Sab, 10 Okt 2026" */
export const fmtDateDow = (iso) => {
    const dow = toDate(iso).toLocaleDateString('en-GB', { timeZone: STORE_TZ, weekday: 'short' });
    const map = { Sun: 'Min', Mon: 'Sen', Tue: 'Sel', Wed: 'Rab', Thu: 'Kam', Fri: 'Jum', Sat: 'Sab' };
    return `${map[dow] || dow}, ${fmtDate(iso)}`;
};

/** Label bulan untuk sumbu chart: "Okt" */
export const monthShort = (m) => MONTHS_ID[m - 1];

/** Tanggal toko dari jam demo. */
export const demoNow = () => toDate(DEMO_NOW_ISO);

export const demoDateKey = () => zonedDateKey(DEMO_NOW_ISO);

/** Tanggal hari ini sesuai zona toko Asia/Jakarta ("YYYY-MM-DD") */
export const todayKey = () => zonedDateKey(new Date());

/** Tanggal n hari yang lalu sesuai zona toko Asia/Jakarta ("YYYY-MM-DD") */
export const daysAgoKey = (n) => {
    const d = new Date();
    d.setDate(d.getDate() - n);
    return zonedDateKey(d);
};

/** Tanggal awal bulan ini sesuai zona toko Asia/Jakarta ("YYYY-MM-01") */
export const startOfMonthKey = () => {
    const p = zonedParts(new Date());
    return `${p.year}-${String(p.month).padStart(2, '0')}-01`;
};

/** Buat ISO dengan offset +07:00 dari komponen tanggal-waktu toko. */
export const makeIso = (y, m, d, h = 0, min = 0) => {
    const pad = (n) => String(n).padStart(2, '0');
    return `${y}-${pad(m)}-${pad(d)}T${pad(h)}:${pad(min)}:00+07:00`;
};

/** Selisih hari (berdasarkan tanggal toko), a - b. */
export const daysBetween = (isoA, isoB) => {
    const a = zonedDateKey(isoA);
    const b = zonedDateKey(isoB);
    return Math.round((new Date(a) - new Date(b)) / 86400000);
};

/** Rentang tanggal toko inklusif [startIso, endIso] mengandung iso. */
export const inRangeInclusive = (iso, startIso, endIso) => {
    const k = zonedDateKey(iso);
    const s = zonedDateKey(startIso);
    const e = zonedDateKey(endIso);
    return k >= s && k <= e;
};

/** Daftar kunci tanggal antara dua ISO (inklusif). */
export const dateKeysBetween = (startIso, endIso) => {
    const keys = [];
    const s = new Date(zonedDateKey(startIso) + 'T00:00:00Z');
    const e = new Date(zonedDateKey(endIso) + 'T00:00:00Z');
    for (let t = s; t <= e; t = new Date(t.getTime() + 86400000)) {
        keys.push(t.toISOString().slice(0, 10));
    }
    return keys;
};

/** Tambah hari ke sebuah ISO (mempertahankan jam). */
export const addDays = (iso, days) => {
    const d = toDate(iso);
    return new Date(d.getTime() + days * 86400000).toISOString();
};

/** Nama hari untuk checkbox "Buka hari" di Settings. */
export const WEEKDAY_LABELS = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
