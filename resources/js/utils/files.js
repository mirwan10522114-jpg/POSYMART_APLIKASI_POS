/**
 * PosyMart — utilitas CSV & unduhan berkas (sepenuhnya lokal).
 */

/** Escape sel CSV; sanitasi formula spreadsheet (=, +, -, @) sesuai design.md §16.6. */
const escapeCell = (value) => {
    let s = value === null || value === undefined ? '' : String(value);
    if (/^[=+\-@\t\r]/.test(s)) s = `'${s}`;
    if (/[",\n\r]/.test(s)) s = `"${s.replace(/"/g, '""')}"`;
    return s;
};

export const toCsv = (headers, rows) => {
    const lines = [headers.map(escapeCell).join(',')];
    for (const row of rows) lines.push(row.map(escapeCell).join(','));
    return '\ufeff' + lines.join('\r\n');
};

export const downloadFile = (filename, content, mime = 'text/csv;charset=utf-8') => {
    const blob = new Blob([content], { type: mime });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(url), 4000);
};

/** Format Rupiah helper untuk struk */
const formatRpReceipt = (centsOrNominal) => {
    const n = Math.round(Number(centsOrNominal) || 0);
    return 'Rp' + n.toLocaleString('id-ID');
};

/** Format Tanggal & Jam untuk struk */
const formatDateTimeReceipt = (isoOrDate) => {
    if (!isoOrDate) return '-';
    try {
        const d = new Date(isoOrDate);
        return d.toLocaleString('id-ID', {
            timeZone: 'Asia/Jakarta',
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        }).replace(/\./g, ':') + ' WIB';
    } catch {
        return String(isoOrDate);
    }
};

/**
 * Pembuat HTML Struk Lebar 80 mm (Standar Profesional / Desktop).
 */
export const generate80mmReceiptHtml = (data = {}) => {
    const {
        storeName = 'PosyMart Coffee & Eatery',
        storeAddress = 'Jalan Pasar No. 123, Jakarta',
        storePhone = '+62 21 5550 0100',
        receiptHeader = 'PosyMart',
        receiptFooter = 'Terima kasih telah berbelanja bersama kami!',
        saleId = 'TX-1000',
        createdAt = new Date(),
        orderType = 'dine_in',
        tableNumber = '',
        customerName = 'Pelanggan Umum',
        cashierName = 'Kasir PosyMart',
        items = [],
        subtotal = 0,
        discount = 0,
        tax = 0,
        taxName = 'Pajak PB1',
        taxRatePercent = 8,
        serviceFee = 0,
        total = 0,
        paymentMethod = 'Tunai',
        tenderedAmount = null,
        changeAmount = null,
        status = 'Completed',
        note = '',
    } = data;

    const typeLabel = orderType === 'takeaway' || orderType === 'Takeaway'
        ? 'Takeaway (Bawa Pulang)'
        : ('Dine In (Makan di Tempat)' + (tableNumber ? ' • ' + (String(tableNumber).toLowerCase().includes('meja') ? tableNumber : 'Meja ' + tableNumber) : ''));

    const itemsHtml = (items || []).map((it) => {
        const qty = it.quantity || 1;
        const price = it.unitPrice || it.unit_price || it.price || it.unit_price_cents || 0;
        const lineTotal = qty * price;
        return `
            <div class="item-block">
                <div class="item-name">${it.name || 'Produk'}</div>
                <div class="item-math">
                    <span>${qty} × ${formatRpReceipt(price)}</span>
                    <span class="item-total">${formatRpReceipt(lineTotal)}</span>
                </div>
            </div>
        `;
    }).join('');

    return `
        <div class="store-header">${receiptHeader || storeName}</div>
        ${storeAddress ? `<div class="store-sub">${storeAddress}</div>` : ''}
        ${storePhone ? `<div class="store-sub">Telp: ${storePhone}</div>` : ''}
        
        <div class="receipt-title">STRUK PEMBAYARAN · 80 MM</div>

        <div class="row"><span>No. Struk</span><span class="font-bold">${saleId}</span></div>
        <div class="row"><span>Waktu</span><span>${formatDateTimeReceipt(createdAt)}</span></div>
        <div class="row"><span>Layanan</span><span class="font-bold">${typeLabel}</span></div>
        ${customerName ? `<div class="row"><span>Pelanggan</span><span>${customerName}</span></div>` : ''}
        ${cashierName ? `<div class="row"><span>Kasir</span><span>${cashierName}</span></div>` : ''}

        <div class="line"></div>
        <div class="item-header">
            <span>PRODUK &amp; JUMLAH</span>
            <span>SUBTOTAL</span>
        </div>
        <div class="line"></div>

        <div class="items-list">
            ${itemsHtml || '<div class="center muted py-2">Tidak ada item</div>'}
        </div>

        <div class="line"></div>
        <div class="row"><span>Subtotal Produk</span><span>${formatRpReceipt(subtotal)}</span></div>
        ${discount > 0 ? `<div class="row"><span>Diskon Potongan</span><span class="text-danger">-${formatRpReceipt(discount)}</span></div>` : ''}
        ${serviceFee > 0 ? `<div class="row"><span>Biaya Antar Meja</span><span>${formatRpReceipt(serviceFee)}</span></div>` : ''}
        ${tax > 0 ? `<div class="row"><span>${taxName} (${taxRatePercent}%)</span><span>${formatRpReceipt(tax)}</span></div>` : ''}

        <div class="line-double"></div>
        <div class="total-row">
            <span>TOTAL TAGIHAN</span>
            <span>${formatRpReceipt(total)}</span>
        </div>
        <div class="line-double"></div>

        <div class="row"><span>Metode Pembayaran</span><span class="font-bold">${paymentMethod}</span></div>
        ${tenderedAmount !== null && tenderedAmount !== undefined && tenderedAmount > 0 ? `
            <div class="row"><span>Diterima (Bayar)</span><span>${formatRpReceipt(tenderedAmount)}</span></div>
        ` : ''}
        ${changeAmount !== null && changeAmount !== undefined && changeAmount >= 0 ? `
            <div class="row"><span>Kembalian</span><span class="font-bold">${formatRpReceipt(changeAmount)}</span></div>
        ` : ''}
        <div class="row" style="margin-top: 4px;">
            <span>Status Transaksi</span>
            <span class="status-badge">${status === 'Cancelled' ? 'DIBATALKAN' : 'LUNAS / BERHASIL'}</span>
        </div>

        ${note ? `
            <div class="line"></div>
            <div class="row"><span>Catatan:</span><span>${note}</span></div>
        ` : ''}

        <div class="line"></div>
        <div class="center store-footer">${receiptFooter}</div>
        <div class="center muted" style="font-size: 10px; margin-top: 4px;">Simpan struk ini sebagai bukti transaksi resmi Anda.</div>
        <div class="center muted" style="font-size: 9.5px; margin-top: 8px; letter-spacing: 1px;">*** POSYMART COFFEE SHOP ***</div>
    `;
};

/** Buka jendela cetak untuk struk HTML (Standar 80 mm Profesional / Desktop). */
export const printHtml = (title, bodyHtml, options = {}) => {
    const is58 = options.width === '58 mm' || options.width === '58mm';
    const paperWidth = is58 ? '58mm' : '80mm';
    const bodyWidth = is58 ? '52mm' : '72mm';
    const fontSize = is58 ? '11.5px' : '13px';

    const win = window.open('', '_blank', 'width=520,height=740');
    if (!win) return false;

    win.document.write(`<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>${title}</title>
<style>
  @page {
    size: ${paperWidth} 297mm;
    margin: 0mm;
  }
  * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
  }
  html {
    margin: 0;
    padding: 0;
  }
  body {
    font-family: 'Courier New', Courier, 'Lucida Console', Monaco, monospace;
    font-size: ${fontSize};
    line-height: 1.45;
    color: #000;
    background: #fff;
    width: ${paperWidth};
    max-width: ${paperWidth};
    margin: 0 auto;
    padding: 4mm 5mm;
  }
  @media screen {
    html {
      background: #334155;
      padding: 24px 0;
      min-height: 100vh;
    }
    body {
      box-shadow: 0 16px 36px rgba(0,0,0,0.25);
      border-radius: 6px;
    }
  }
  @media print {
    @page {
      size: ${paperWidth} 297mm;
      margin: 0mm;
    }
    html {
      width: ${paperWidth} !important;
      max-width: ${paperWidth} !important;
      margin: 0 !important;
      padding: 0 !important;
      background: #fff !important;
    }
    body {
      width: ${paperWidth} !important;
      max-width: ${paperWidth} !important;
      min-width: ${paperWidth} !important;
      padding: 2mm 3mm !important;
      margin: 0 !important;
      background: #fff !important;
      color: #000 !important;
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }
    .no-print {
      display: none !important;
    }
  }
  .no-print {
    text-align: center;
    margin-bottom: 14px;
    padding-bottom: 10px;
    border-bottom: 1px dashed #94a3b8;
  }
  .btn-print {
    background: #0f766e;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 7px 18px;
    font-family: system-ui, -apple-system, sans-serif;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }
  .btn-print:hover {
    background: #115e59;
  }
  .store-header {
    font-size: 17px;
    font-weight: 800;
    text-align: center;
    letter-spacing: 0.5px;
    margin-bottom: 2px;
  }
  .store-sub {
    text-align: center;
    font-size: 11.5px;
    color: #222;
    line-height: 1.35;
  }
  .store-footer {
    font-size: 12px;
    font-weight: 600;
    margin-top: 8px;
  }
  .receipt-title {
    text-align: center;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 1.5px;
    margin: 8px 0 6px;
    padding: 3px 0;
    border-top: 1px dashed #333;
    border-bottom: 1px dashed #333;
  }
  .line {
    border-top: 1px dashed #333;
    margin: 6px 0;
  }
  .line-double {
    border-top: 2px dashed #000;
    margin: 8px 0;
  }
  .row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 8px;
    padding: 2px 0;
    font-size: 12px;
  }
  .font-bold {
    font-weight: 700;
  }
  .item-header {
    display: flex;
    justify-content: space-between;
    font-size: 11.5px;
    font-weight: 700;
    padding: 2px 0;
  }
  .item-block {
    margin: 5px 0;
  }
  .item-name {
    font-weight: 700;
    font-size: 12.5px;
    word-break: break-word;
  }
  .item-math {
    display: flex;
    justify-content: space-between;
    padding-left: 8px;
    font-size: 12px;
    color: #111;
  }
  .item-total {
    font-weight: 700;
  }
  .total-row {
    display: flex;
    justify-content: space-between;
    font-size: 15.5px;
    font-weight: 900;
    padding: 4px 0;
    letter-spacing: 0.5px;
  }
  .center {
    text-align: center;
  }
  .muted {
    color: #444;
    font-size: 11px;
  }
  .text-danger {
    color: #b91c1c;
  }
  .status-badge {
    display: inline-block;
    font-weight: 800;
    font-size: 11px;
    padding: 2px 6px;
    border: 1px solid #000;
    border-radius: 3px;
  }
</style>
</head>
<body>
  <div class="no-print">
    <button type="button" class="btn-print" onclick="window.print()">🖨️ Cetak Struk (80 mm)</button>
  </div>
  ${bodyHtml}
</body>
</html>`);

    win.document.close();
    win.focus();
    setTimeout(() => {
        try { win.print(); } catch (e) {}
    }, 400);
    return true;
};

