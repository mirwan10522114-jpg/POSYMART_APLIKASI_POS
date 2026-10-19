<?php

use App\Http\Controllers\GuestWebController;
use App\Http\Controllers\WebAuthController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PosyMart — Aplikasi POS & Sistem Pemesanan Meja
|--------------------------------------------------------------------------
*/

// --- Autentikasi Admin & Staf ---
Route::get('/login', [WebAuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [WebAuthController::class, 'login'])->name('login.post');
Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');

// Root redirect: Arahkan ke dashboard jika sudah login, atau ke login jika belum
Route::get('/', function () {
    return Auth::check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// --- Halaman Terproteksi Admin & Staf ---
$pages = [
    'dashboard' => 'dashboard',
    'sales' => 'sales',
    'products' => 'products',
    'customers' => 'customers',
    'orders' => 'orders',
    'payments' => 'payments',
    'refunds' => 'refunds',
    'reports' => 'reports',
    'returns' => 'returns',
    'discounts' => 'discounts',
    'users' => 'users',
    'settings' => 'settings',
    'tables' => 'tables',
];

Route::middleware('auth')->group(function () use ($pages) {

    foreach ($pages as $uri => $view) {
        Route::get("/{$uri}", fn () => view("pages.{$view}"))->name($view);
    }
});

// --- Guest Mobile Ordering Portal (Single QR Barcode / Universal Portal) ---
Route::get('/order', [GuestWebController::class, 'portal'])->name('guest.portal');
Route::get('/pesan', [GuestWebController::class, 'portal']);
Route::get('/m', [GuestWebController::class, 'portal']);

// --- Guest Mobile Table Ordering (Spesifik QR Meja atau Token) ---
Route::get('/m/{token}', [GuestWebController::class, 'index'])->name('guest.table');
Route::get('/m/{token}/order/{orderId}', [GuestWebController::class, 'order'])->name('guest.order');

Route::fallback(fn () => view('pages.notfound'));
