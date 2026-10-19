<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Setting;
use App\Models\Table;
use App\Models\TableQr;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuestWebController extends Controller
{
    /**
     * Universal Guest mobile ordering portal (Single QR Barcode for the whole cafe).
     * Route: GET /order, GET /pesan, GET /m
     */
    public function portal(): View
    {
        $settings = Setting::instance();
        $tables = Table::where('is_active', true)
            ->orderByRaw("CAST(SUBSTR(table_code, 2) AS INTEGER) ASC")
            ->get();

        return view('pages.guest-order', [
            'isValid' => true,
            'isUniversalPortal' => true,
            'table' => null,
            'availableTables' => $tables,
            'qr' => null,
            'token' => 'portal',
            'settings' => $settings,
            'initialOrderId' => null,
        ]);
    }

    /**
     * Guest mobile landing & table order flow.
     * Route: GET /m/{token}
     */
    public function index(string $token): View
    {
        if ($token === 'portal' || $token === 'order' || $token === 'pesan') {
            return $this->portal();
        }

        $tables = Table::where('is_active', true)
            ->orderByRaw("CAST(SUBSTR(table_code, 2) AS INTEGER) ASC")
            ->get();

        $qr = TableQr::with('table')->where('public_token', $token)->first();

        // Support fallback by table_code directly if token not found (e.g. T017 or qr-meja-17)
        if (!$qr && str_starts_with(strtoupper($token), 'T')) {
            $table = Table::where('table_code', strtoupper($token))->first();
            $qr = $table?->activeQr;
        }

        $settings = Setting::instance();

        if (!$qr) {
            return view('pages.guest-order', [
                'isValid' => false,
                'isUniversalPortal' => false,
                'availableTables' => $tables,
                'errorCode' => 'QR_INVALID',
                'errorMessage' => 'Kode QR meja tidak dikenali atau telah kedaluwarsa. Silakan tanyakan kepada staf kafe.',
                'table' => null,
                'token' => $token,
                'settings' => $settings,
            ]);
        }

        if ($qr->status !== 'Active') {
            return view('pages.guest-order', [
                'isValid' => false,
                'isUniversalPortal' => false,
                'availableTables' => $tables,
                'errorCode' => 'QR_INACTIVE',
                'errorMessage' => "Meja {$qr->table->label} sedang tidak aktif menerima pesanan mandiri. Silakan hubungi staf kafe.",
                'table' => $qr->table,
                'token' => $token,
                'settings' => $settings,
            ]);
        }

        $table = $qr->table;
        if (!$table || !$table->is_active) {
            return view('pages.guest-order', [
                'isValid' => false,
                'isUniversalPortal' => false,
                'availableTables' => $tables,
                'errorCode' => 'TABLE_INACTIVE',
                'errorMessage' => "Meja {$table?->label} saat ini sedang dinonaktifkan. Silakan hubungi kasir/staf.",
                'table' => $table,
                'token' => $token,
                'settings' => $settings,
            ]);
        }

        // Increment scan count
        $qr->increment('scan_count');

        return view('pages.guest-order', [
            'isValid' => true,
            'isUniversalPortal' => false,
            'availableTables' => $tables,
            'table' => $table,
            'qr' => $qr,
            'token' => $qr->public_token,
            'settings' => $settings,
            'initialOrderId' => null,
        ]);
    }

    /**
     * Guest order tracking view.
     * Route: GET /m/{token}/order/{orderId}
     */
    public function order(string $token, string $orderId): View
    {
        $tables = Table::where('is_active', true)
            ->orderByRaw("CAST(SUBSTR(table_code, 2) AS INTEGER) ASC")
            ->get();

        $qr = TableQr::with('table')->where('public_token', $token)->first();
        $table = $qr?->table;
        $settings = Setting::instance();

        return view('pages.guest-order', [
            'isValid' => true,
            'isUniversalPortal' => false,
            'availableTables' => $tables,
            'table' => $table,
            'qr' => $qr,
            'token' => $token,
            'settings' => $settings,
            'initialOrderId' => $orderId,
        ]);
    }
}
