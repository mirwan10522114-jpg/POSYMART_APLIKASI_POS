<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#4CB0AE">
    <title>@yield('title', 'PosyMart') · PosyMart</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%234CB0AE' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z'/%3E%3Cpath d='M3 6h18'/%3E%3Cpath d='M16 10a4 4 0 0 1-8 0'/%3E%3C/svg%3E">
</head>
<body class="bg-canvas text-ink antialiased">
<div class="app-shell" x-data="shell" x-cloak>

    {{-- ================= SIDEBAR (desktop) ================= --}}
    <aside class="sidebar hidden xl:flex" aria-label="Navigasi utama">
        <div class="px-4 pt-7 pb-5">
            <a href="/dashboard" class="flex items-center gap-3 px-2" aria-label="PosyMart — ke Dasbor">
                <span class="grid size-9 place-items-center rounded-[10px] bg-white/15">
                    <x-icon name="shopping-bag" class="size-5 text-white"/>
                </span>
                <span class="text-[24px]/[30px] font-medium text-white">PosyMart</span>
            </a>
        </div>

        <nav class="flex-1 px-2 space-y-1" aria-label="Menu utama">
            @php
                $nav = [
                    ['dashboard', 'Dasbor', 'layout-dashboard'],
                    ['orders', 'Pesanan Meja', 'clipboard-list'],
                    ['payments', 'Pembayaran', 'credit-card'],
                    ['refunds', 'Refunds', 'rotate-ccw'],
                    ['sales', 'Kasir POS', 'bar-chart'],
                    ['products', 'Produk', 'package'],
                    ['discounts', 'Diskon', 'badge-percent'],
                    ['reports', 'Laporan', 'line-chart'],
                    ['customers', 'Pelanggan Terdaftar', 'users'],
                    ['users', 'Staf & Tim Toko', 'shield-check'],
                    ['settings', 'Pengaturan', 'settings'],
                ];
            @endphp
            @foreach ($nav as [$route, $label, $icon])
                @if ($route === 'reports')
                    <div x-data="{ reportsOpen: {{ request()->is('reports*') ? 'true' : 'false' }} }" class="space-y-1">
                        <div class="flex items-center gap-1">
                            <a href="/reports"
                               class="nav-item flex-1"
                               @if (request()->is('reports') && !request('period')) aria-current="page" @endif>
                                <x-icon name="{{ $icon }}" class="size-[21px] shrink-0"/>
                                <span class="truncate">{{ $label }}</span>
                            </a>
                            <button type="button" 
                                    @click="reportsOpen = !reportsOpen"
                                    class="size-9 grid place-items-center rounded-lg text-white/70 hover:text-white hover:bg-white/10 transition-colors shrink-0"
                                    :aria-expanded="reportsOpen"
                                    title="Tampilkan periode rekap laporan"
                                    aria-label="Toggle sub-menu laporan">
                                <x-icon name="chevron-down" class="size-4 transition-transform duration-200" x-bind:class="reportsOpen && 'rotate-180'"/>
                            </button>
                        </div>
                        <div x-show="reportsOpen" x-cloak class="pl-8 pr-1 py-1 space-y-1">
                            <a href="/reports?period=today"
                               class="flex items-center gap-2 px-3 py-1.5 text-[13px] rounded-lg text-white/80 hover:text-white hover:bg-white/10 transition-colors {{ request()->is('reports*') && request('period') === 'today' ? 'bg-white/20 text-white font-medium' : '' }}">
                                <span class="size-1.5 rounded-full {{ request()->is('reports*') && request('period') === 'today' ? 'bg-amber' : 'bg-white/40' }}"></span>
                                <span>Rekap Harian</span>
                            </a>
                            <a href="/reports?period=week"
                               class="flex items-center gap-2 px-3 py-1.5 text-[13px] rounded-lg text-white/80 hover:text-white hover:bg-white/10 transition-colors {{ request()->is('reports*') && request('period') === 'week' ? 'bg-white/20 text-white font-medium' : '' }}">
                                <span class="size-1.5 rounded-full {{ request()->is('reports*') && request('period') === 'week' ? 'bg-amber' : 'bg-white/40' }}"></span>
                                <span>Rekap Mingguan</span>
                            </a>
                            <a href="/reports?period=month"
                               class="flex items-center gap-2 px-3 py-1.5 text-[13px] rounded-lg text-white/80 hover:text-white hover:bg-white/10 transition-colors {{ request()->is('reports*') && request('period') === 'month' ? 'bg-white/20 text-white font-medium' : '' }}">
                                <span class="size-1.5 rounded-full {{ request()->is('reports*') && request('period') === 'month' ? 'bg-amber' : 'bg-white/40' }}"></span>
                                <span>Rekap Bulanan</span>
                            </a>
                            <a href="/reports?period=year"
                               class="flex items-center gap-2 px-3 py-1.5 text-[13px] rounded-lg text-white/80 hover:text-white hover:bg-white/10 transition-colors {{ request()->is('reports*') && request('period') === 'year' ? 'bg-white/20 text-white font-medium' : '' }}">
                                <span class="size-1.5 rounded-full {{ request()->is('reports*') && request('period') === 'year' ? 'bg-amber' : 'bg-white/40' }}"></span>
                                <span>Rekap Tahunan</span>
                            </a>
                        </div>
                    </div>
                @else
                    <a href="/{{ $route }}"
                       class="nav-item"
                       @if (request()->is($route) || request()->is($route.'/*')) aria-current="page" @endif>
                        <x-icon name="{{ $icon }}" class="size-[21px] shrink-0"/>
                        <span class="truncate">{{ $label }}</span>
                    </a>
                @endif
            @endforeach
        </nav>

        <div class="px-2 pb-7 pt-3 space-y-2 mt-auto">
            <div class="rounded-xl border border-white/20 px-4 py-3.5">
                <div class="flex items-center gap-2.5">
                    <x-icon name="headphones" class="size-5 text-white/90"/>
                    <div class="min-w-0">
                        <p class="text-[14px]/[19px] font-medium text-white truncate">Butuh Bantuan?</p>
                        <button type="button" @click="helpOpen = true"
                                class="text-[12px]/[16px] text-white/78 hover:text-white underline-offset-2 hover:underline"
                                aria-label="Hubungi dukungan">Hubungi dukungan</button>
                    </div>
                </div>
            </div>
            <div class="relative">
                <button type="button" @click="profileOpen = !profileOpen; notifOpen = false"
                        class="w-full rounded-xl border border-white/20 px-3.5 py-2.5 flex items-center gap-2.5 hover:bg-white/10 transition-colors"
                        :aria-expanded="profileOpen" aria-haspopup="menu"
                        aria-label="Menu akun {{ auth()->user()?->name ?? 'Admin' }}">
                    <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-white/25 text-[12px] font-semibold text-white">
                        {{ strtoupper(substr(auth()->user()?->name ?? 'AD', 0, 2)) }}
                    </span>
                    <span class="min-w-0 flex-1 text-left">
                        <span class="block text-[13px]/[17px] font-medium text-white truncate">{{ auth()->user()?->name ?? 'Administrator' }}</span>
                        <span class="block text-[12px]/[15px] text-white/78">{{ auth()->user()?->role ?? 'Admin' }}</span>
                    </span>
                    <x-icon name="chevron-down" class="size-4 text-white/70 shrink-0 transition-transform" x-bind:class="profileOpen && 'rotate-180'"/>
                </button>
                <div x-show="profileOpen" x-cloak @click.outside="profileOpen = false"
                     @keydown.escape="profileOpen = false"
                     x-transition:enter="pop-enter" x-transition:leave="pop-leave"
                     class="absolute bottom-full left-0 right-0 mb-2 rounded-xl bg-white p-1.5 shadow-[0_12px_40px_rgba(30,41,59,0.18)] z-50"
                     role="menu" aria-label="Menu akun">
                    <div class="px-3 py-2 border-b border-divider mb-1">
                        <p class="text-[13px] font-medium text-slate-800">{{ auth()->user()?->name ?? 'Administrator' }}</p>
                        <p class="text-[11.5px] text-ink-muted truncate">{{ auth()->user()?->email ?? 'admin@posymart.com' }}</p>
                    </div>
                    <a href="/users" class="w-full block text-left px-3 py-2 rounded-lg text-[13px] hover:bg-surface-muted" role="menuitem">
                        Kelola Pengguna
                    </a>
                    <a href="/settings" class="w-full block text-left px-3 py-2 rounded-lg text-[13px] hover:bg-surface-muted" role="menuitem">
                        Pengaturan Sistem
                    </a>
                    <div class="border-t border-divider mt-1 pt-1">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-3 py-2 rounded-lg text-[13px] text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium" role="menuitem">
                                <x-icon name="log-out" class="size-4"/>
                                <span>Keluar (Logout)</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </aside>

    {{-- ================= DRAWER SIDEBAR (mobile/tablet) ================= --}}
    <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-[70] xl:hidden" role="dialog" aria-modal="true" aria-label="Navigasi">
        <div class="absolute inset-0 bg-[#1c2b2b]/45 backdrop-blur-[2px]" @click="sidebarOpen = false"
             x-transition:enter="fade-enter" x-transition:leave="fade-leave"></div>
        <div class="absolute inset-y-0 left-0 w-[280px] max-w-[85vw] bg-sidebar flex flex-col"
             x-ref="mobileNav" @keydown.tab="PM.trap($event, $refs.mobileNav)"
             x-transition:enter="slide-enter" x-transition:leave="slide-leave"
             x-transition:enter.start="-translate-x-full" x-transition:leave.end="-translate-x-full">
            <div class="px-4 pt-6 pb-4 flex items-center justify-between">
                <a href="/dashboard" class="flex items-center gap-3" @click="sidebarOpen = false">
                    <span class="grid size-9 place-items-center rounded-[10px] bg-white/15">
                        <x-icon name="shopping-bag" class="size-5 text-white"/>
                    </span>
                    <span class="text-[22px]/[28px] font-medium text-white">PosyMart</span>
                </a>
                <button type="button" class="grid size-11 place-items-center rounded-lg text-white/90 hover:bg-white/15"
                        @click="sidebarOpen = false" aria-label="Tutup navigasi">
                    <x-icon name="x" class="size-5"/>
                </button>
            </div>
            <nav class="flex-1 px-2 space-y-1 overflow-y-auto" aria-label="Menu utama">
                @foreach ($nav as [$route, $label, $icon])
                    @if ($route === 'reports')
                        <div x-data="{ reportsOpen: {{ request()->is('reports*') ? 'true' : 'false' }} }" class="space-y-1">
                            <div class="flex items-center gap-1">
                                <a href="/reports"
                                   class="nav-item flex-1"
                                   @if (request()->is('reports') && !request('period')) aria-current="page" @endif
                                   @click="sidebarOpen = false">
                                    <x-icon name="{{ $icon }}" class="size-[21px] shrink-0"/>
                                    <span class="truncate">{{ $label }}</span>
                                </a>
                                <button type="button" 
                                        @click="reportsOpen = !reportsOpen"
                                        class="size-9 grid place-items-center rounded-lg text-white/70 hover:text-white hover:bg-white/10 transition-colors shrink-0"
                                        :aria-expanded="reportsOpen"
                                        title="Tampilkan periode rekap laporan"
                                        aria-label="Toggle sub-menu laporan">
                                    <x-icon name="chevron-down" class="size-4 transition-transform duration-200" x-bind:class="reportsOpen && 'rotate-180'"/>
                                </button>
                            </div>
                            <div x-show="reportsOpen" x-cloak class="pl-8 pr-1 py-1 space-y-1">
                                <a href="/reports?period=today"
                                   @click="sidebarOpen = false"
                                   class="flex items-center gap-2 px-3 py-1.5 text-[13px] rounded-lg text-white/80 hover:text-white hover:bg-white/10 transition-colors {{ request()->is('reports*') && request('period') === 'today' ? 'bg-white/20 text-white font-medium' : '' }}">
                                    <span class="size-1.5 rounded-full {{ request()->is('reports*') && request('period') === 'today' ? 'bg-amber' : 'bg-white/40' }}"></span>
                                    <span>Rekap Harian</span>
                                </a>
                                <a href="/reports?period=week"
                                   @click="sidebarOpen = false"
                                   class="flex items-center gap-2 px-3 py-1.5 text-[13px] rounded-lg text-white/80 hover:text-white hover:bg-white/10 transition-colors {{ request()->is('reports*') && request('period') === 'week' ? 'bg-white/20 text-white font-medium' : '' }}">
                                    <span class="size-1.5 rounded-full {{ request()->is('reports*') && request('period') === 'week' ? 'bg-amber' : 'bg-white/40' }}"></span>
                                    <span>Rekap Mingguan</span>
                                </a>
                                <a href="/reports?period=month"
                                   @click="sidebarOpen = false"
                                   class="flex items-center gap-2 px-3 py-1.5 text-[13px] rounded-lg text-white/80 hover:text-white hover:bg-white/10 transition-colors {{ request()->is('reports*') && request('period') === 'month' ? 'bg-white/20 text-white font-medium' : '' }}">
                                    <span class="size-1.5 rounded-full {{ request()->is('reports*') && request('period') === 'month' ? 'bg-amber' : 'bg-white/40' }}"></span>
                                    <span>Rekap Bulanan</span>
                                </a>
                                <a href="/reports?period=year"
                                   @click="sidebarOpen = false"
                                   class="flex items-center gap-2 px-3 py-1.5 text-[13px] rounded-lg text-white/80 hover:text-white hover:bg-white/10 transition-colors {{ request()->is('reports*') && request('period') === 'year' ? 'bg-white/20 text-white font-medium' : '' }}">
                                    <span class="size-1.5 rounded-full {{ request()->is('reports*') && request('period') === 'year' ? 'bg-amber' : 'bg-white/40' }}"></span>
                                    <span>Rekap Tahunan</span>
                                </a>
                            </div>
                        </div>
                    @else
                        <a href="/{{ $route }}" class="nav-item"
                           @if (request()->is($route) || request()->is($route.'/*')) aria-current="page" @endif
                           @click="sidebarOpen = false">
                            <x-icon name="{{ $icon }}" class="size-[21px] shrink-0"/>
                            <span class="truncate">{{ $label }}</span>
                        </a>
                    @endif
                @endforeach
            </nav>
            <div class="px-4 pb-6 pt-3">
                <div class="rounded-xl border border-white/20 px-4 py-3.5 flex items-center gap-2.5">
                    <x-icon name="headphones" class="size-5 text-white/90"/>
                    <div class="min-w-0">
                        <p class="text-[13px]/[18px] font-medium text-white truncate">Butuh Bantuan?</p>
                        <button type="button" class="text-[11.5px]/[15px] text-white/78 hover:text-white" @click="sidebarOpen = false; helpOpen = true">Hubungi dukungan</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= AREA UTAMA ================= --}}
    <div class="min-w-0 flex flex-col bg-canvas min-h-screen">
        @include('layouts.partials.header')
        <main id="konten-utama" class="flex-1 min-w-0 px-[30px] max-[480px]:px-4 pt-6 max-[768px]:pt-5 pb-12 focus:outline-none" tabindex="-1">
            @yield('content')
        </main>
    </div>

    {{-- ================= PANEL BANTUAN ================= --}}
    <div x-show="helpOpen" x-cloak class="fixed inset-0 z-[80]" role="dialog" aria-modal="true" aria-label="Bantuan dan dukungan">
        <div class="absolute inset-0 bg-[#1c2b2b]/40" @click="helpOpen = false"
             x-transition:enter="fade-enter" x-transition:leave="fade-leave"></div>
        <div class="absolute inset-y-0 right-0 w-[480px] max-w-[92vw] bg-white flex flex-col"
             x-ref="helpPanel" @keydown.tab="PM.trap($event, $refs.helpPanel)"
             x-transition:enter="slide-enter" x-transition:leave="slide-leave">
            <div class="flex items-center justify-between px-6 py-5 border-b border-divider">
                <div class="flex items-center gap-3">
                    <span class="grid size-10 place-items-center rounded-full bg-primary-soft text-focus">
                        <x-icon name="headphones" class="size-5"/>
                    </span>
                    <div>
                        <h2 class="text-[18px]/[24px] font-semibold">Bantuan &amp; Dukungan</h2>
                        <p class="text-[13px]/[18px] text-ink-secondary">Panduan singkat aplikasi demo PosyMart</p>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary btn-sm btn-icon" @click="helpOpen = false" aria-label="Tutup panel bantuan">
                    <x-icon name="x" class="size-4.5"/>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5 nice-scroll">
                <div class="space-y-2">
                    <h3 class="text-[15px]/[22px] font-semibold">Memulai</h3>
                    <p class="text-[13.5px]/[21px] text-ink-secondary">PosyMart adalah prototipe kasir (POS) berbasis demo. Seluruh data — produk, pelanggan, transaksi, retur, hingga pengaturan — tersimpan lokal di peramban Anda (LocalStorage). Tidak ada pembayaran, pengiriman email, atau pencetakan nyata yang diproses.</p>
                </div>
                <div class="space-y-2">
                    <h3 class="text-[15px]/[22px] font-semibold">Membuat penjualan</h3>
                    <p class="text-[13.5px]/[21px] text-ink-secondary">Buka <span class="font-medium text-ink">Dasbor</span>, klik tombol <span class="font-medium text-ink">+</span> pada katalog untuk menambah produk ke Penjualan Saat Ini, atur jumlah, lalu klik <span class="font-medium text-ink">Bayar</span> untuk menjalankan simulasi pembayaran. Transaksi baru akan muncul di halaman Penjualan.</p>
                </div>
                <div class="space-y-2">
                    <h3 class="text-[15px]/[22px] font-semibold">Mengelola data</h3>
                    <p class="text-[13.5px]/[21px] text-ink-secondary">Halaman Produk, Pelanggan, Pesanan, Retur, Diskon, dan Pengguna mendukung tambah, ubah, dan aksi lanjutan dengan validasi lengkap. Semua perubahan saling terhubung antarhalaman — misalnya menyelesaikan pesanan otomatis membuat satu transaksi penjualan baru.</p>
                </div>
                <div class="space-y-2">
                    <h3 class="text-[15px]/[22px] font-semibold">Mengembalikan demo ke kondisi awal</h3>
                    <p class="text-[13.5px]/[21px] text-ink-secondary">Buka <span class="font-medium text-ink">Pengaturan → Tampilan</span> lalu gunakan <span class="font-medium text-ink">Reset data demo</span> untuk memulihkan seluruh data seed secara konsisten.</p>
                </div>
                <div class="rounded-xl bg-surface-muted border border-line p-4">
                    <p class="text-[12.5px]/[19px] text-ink-secondary"><span class="font-medium text-ink">Catatan:</span> pajak 8% pada demo hanyalah konfigurasi contoh, bukan ketentuan pajak resmi. Simulasi berjalan pada jam demo 10 Okt 2026, 14.00 WIB.</p>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-divider flex items-center justify-between gap-3">
                <p class="text-[12px]/[17px] text-ink-muted">PosyMart Demo v1</p>
                <button type="button" class="btn btn-secondary btn-sm" @click="helpOpen = false">Tutup</button>
            </div>
        </div>
    </div>

    {{-- ================= TOASTS ================= --}}
    <div class="fixed bottom-5 right-5 z-[90] flex flex-col gap-2.5 w-[min(360px,calc(100vw-24px))] max-[520px]:bottom-4 max-[520px]:left-1/2 max-[520px]:-translate-x-1/2 max-[520px]:right-auto" aria-live="polite" aria-label="Notifikasi aksi">
        <template x-for="t in $store.posy.toasts" :key="t.id">
            <div class="toast-enter flex items-start gap-3 rounded-xl bg-white border border-line shadow-[0_10px_36px_rgba(30,41,59,0.16)] px-4 py-3"
                 :class="t.type === 'warning' && 'border-amber/40', t.type === 'error' && 'border-danger/40'">
                <span class="grid size-7 shrink-0 place-items-center rounded-full mt-px"
                      :class="t.type === 'success' ? 'bg-[#e7f4f0] text-[#2f7d68]' : t.type === 'warning' ? 'bg-amber-soft text-amber' : t.type === 'error' ? 'bg-danger-soft text-danger' : 'bg-info-soft text-info'">
                    <x-icon name="check-circle" class="size-4" x-show="t.type === 'success'"/>
                    <x-icon name="alert-circle" class="size-4" x-show="t.type === 'warning' || t.type === 'error'"/>
                    <x-icon name="info" class="size-4" x-show="t.type === 'info'"/>
                </span>
                <p class="flex-1 text-[13px]/[19px] text-ink pt-1" x-text="t.message"></p>
                <button type="button" x-show="t.actionLabel" @click="t.onAction?.(); $store.posy.dismissToast(t.id)"
                        class="text-[13px] font-medium text-primary hover:text-primary-hover pt-1 shrink-0" x-text="t.actionLabel"></button>
                <button type="button" class="grid size-7 place-items-center rounded-md text-ink-muted hover:bg-surface-muted shrink-0" aria-label="Tutup notifikasi"
                        @click="$store.posy.dismissToast(t.id)">
                    <x-icon name="x" class="size-4"/>
                </button>
            </div>
        </template>
    </div>

    {{-- Live region sopan untuk pembaca layar --}}
    <div class="sr-only" role="status" aria-live="polite" x-text="$store.posy.liveMessage"></div>

    {{-- Peringatan storage tidak tersedia --}}
    <div x-show="$store.posy.ready && !$store.posy.storageAvailable" x-cloak
         class="fixed bottom-5 left-1/2 -translate-x-1/2 z-[89] rounded-full bg-ink text-white text-[12px] px-4 py-2 shadow-lg">
        Perubahan hanya disimpan untuk sesi ini.
    </div>
</div>
</body>
</html>
