<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#ffffff">
    <title>{{ $table ? $table->label . ' · ' : '' }}Beranda | {{ $settings->store_name ?? 'PosyMart' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400..700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%230A5A42' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z'/%3E%3Cpath d='M3 6h18'/%3E%3Cpath d='M16 10a4 4 0 0 1-8 0'/%3E%3C/svg%3E">
    <style>
        [x-cloak] { display: none !important; }
        .tap-highlight-transparent { -webkit-tap-highlight-color: transparent; }

        :root {
            --brand: #0A5A42;
            --ink: #1D2521;
            --ink-2: #5C6661;
            --line: #E3E7E4;
            --fill: #F1F3F0;
            --bg: #FFFFFF;
            --page: #E8EBE8;
            --open: #1F9D6B;
            --font: "Figtree", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }

        *, *::before, *::after { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; }
        body {
            margin: 0;
            background: var(--page);
            color: var(--ink);
            font: 400 15px/1.45 var(--font);
            -webkit-font-smoothing: antialiased;
        }
        a { color: inherit; text-decoration: none; -webkit-tap-highlight-color: transparent; }
        ul { list-style: none; margin: 0; padding: 0; }
        h1, h2, h3, p { margin: 0; }
        svg { display: block; flex: none; }
        :focus-visible { outline: 2px solid var(--brand); outline-offset: 2px; }

        .ico {
            width: 22px; height: 22px;
            fill: none; stroke: currentColor;
            stroke-width: 1.75; stroke-linecap: round; stroke-linejoin: round;
        }

        /* Kolom aplikasi */
        .app { max-width: 430px; min-height: 100vh; margin: 0 auto; background: var(--bg); position: relative; }
        @media (min-width: 431px) {
            .app { border-left: 1px solid var(--line); border-right: 1px solid var(--line); }
        }

        /* Header */
        .top { padding: calc(16px + env(safe-area-inset-top)) 16px 0; }
        .top-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .hello { font-size: 14px; color: var(--ink-2); }
        .name { font-size: 24px; line-height: 1.2; font-weight: 700; letter-spacing: -0.02em; }
        .icon-btn {
            width: 44px; height: 44px;
            display: grid; place-items: center;
            border: 1px solid var(--line); border-radius: 12px;
            background: var(--bg); color: var(--ink); cursor: pointer;
        }

        .status-row { display: flex; align-items: center; justify-content: space-between; margin-top: 10px; }
        .status { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--ink-2); }
        .dot { width: 7px; height: 7px; border-radius: 50%; background: var(--open); }
        .text-link { padding: 6px 0; font-size: 14px; font-weight: 600; color: var(--brand); cursor: pointer; }

        .search { position: relative; margin-top: 12px; }
        .search .ico {
            position: absolute; left: 14px; top: 50%; margin-top: -10px;
            width: 20px; height: 20px; color: var(--ink-2); pointer-events: none;
        }
        .search input {
            width: 100%; height: 48px; padding: 0 16px 0 44px;
            border: 1px solid transparent; border-radius: 12px;
            background: var(--fill); color: var(--ink); font: inherit;
            -webkit-appearance: none; appearance: none;
        }
        .search input::placeholder { color: #67716C; }
        .search input:focus { outline: none; border-color: var(--brand); background: var(--bg); }

        /* Konten Beranda */
        main.home-main {
            display: flex; flex-direction: column; gap: 28px;
            padding: 24px 16px calc(96px + env(safe-area-inset-bottom));
        }
        .block > h2 { margin-bottom: 12px; }
        .block h2 { font-size: 17px; line-height: 1.3; font-weight: 600; letter-spacing: -0.01em; }
        .block-head { display: flex; align-items: baseline; justify-content: space-between; margin-bottom: 12px; }

        /* Pilihan pesan */
        .panel { border: 1px solid var(--line); border-radius: 14px; overflow: hidden; list-style: none; margin: 0; padding: 0; }
        .panel li + li { border-top: 1px solid var(--line); }
        .row-action { display: flex; align-items: center; gap: 12px; padding: 14px; min-height: 72px; cursor: pointer; text-decoration: none; color: inherit; }
        .row-action:active { background: var(--fill); }
        @media (hover: hover) { .row-action:hover { background: var(--fill); } }
        .row-icon {
            width: 44px; height: 44px; display: grid; place-items: center;
            border-radius: 10px; background: var(--fill); color: var(--brand);
        }
        .row-text { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 2px; }
        .row-title { font-size: 16px; font-weight: 600; }
        .row-sub { font-size: 13px; color: var(--ink-2); }
        .chev { width: 18px; height: 18px; color: #8A948F; }

        /* Voucher: satu-satunya blok berwarna di halaman */
        .voucher { padding: 18px 18px 0; border-radius: 14px; background: var(--brand); color: #fff; cursor: pointer; }
        .v-head { display: flex; align-items: center; justify-content: space-between; font-size: 13px; color: rgba(255,255,255,.78); }
        .v-status { display: inline-flex; align-items: center; gap: 6px; font-weight: 600; color: #fff; }
        .v-status .ico { width: 16px; height: 16px; stroke-width: 2.25; }
        .v-title { margin-top: 12px; font-size: 22px; line-height: 1.2; font-weight: 700; letter-spacing: -0.02em; }
        .v-desc { margin-top: 6px; font-size: 13.5px; color: rgba(255,255,255,.8); }
        .v-more {
            display: flex; align-items: center; justify-content: space-between;
            margin-top: 16px; padding: 14px 0;
            border-top: 1px solid rgba(255,255,255,.2); font-size: 13.5px;
            color: inherit; text-decoration: none; cursor: pointer;
        }
        .v-more span { color: rgba(255,255,255,.8); }
        .v-more span:last-child { display: inline-flex; align-items: center; gap: 2px; font-weight: 600; color: #fff; }
        .v-more .ico { width: 16px; height: 16px; }

        /* Kategori */
        .chips { display: flex; flex-wrap: wrap; gap: 8px; list-style: none; margin: 0; padding: 0; }
        .chip {
            display: inline-flex; align-items: center; height: 40px; padding: 0 16px;
            border: 1px solid var(--line); border-radius: 999px;
            font-size: 14px; font-weight: 500; cursor: pointer; color: inherit; text-decoration: none;
            background: transparent;
        }
        .chip:active { background: var(--fill); }
        @media (hover: hover) { .chip:hover { background: var(--fill); } }

        /* Promo */
        .promos {
            display: flex; gap: 12px; margin: 0 -16px; padding: 0 16px;
            overflow-x: auto; scroll-snap-type: x mandatory; scroll-padding: 0 16px;
            scrollbar-width: none; list-style: none;
        }
        .promos::-webkit-scrollbar { display: none; }
        .promos li { flex: 0 0 78%; scroll-snap-align: start; }
        .promo {
            display: flex; flex-direction: column; gap: 4px;
            padding: 12px;
            border-radius: 16px; background: var(--fill); cursor: pointer;
            color: inherit; text-decoration: none; border: 1px solid var(--line);
            transition: transform 0.15s ease;
        }
        .promo:active { transform: scale(0.98); }
        .promo-img {
            width: 100%; height: 110px; border-radius: 12px; overflow: hidden; margin-bottom: 8px;
            background: #E3E7E4;
        }
        .promo-img img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .promo h3 { font-size: 15px; font-weight: 700; letter-spacing: -0.01em; color: var(--ink); }
        .promo p { font-size: 12.5px; color: var(--ink-2); line-height: 1.35; margin-top: 2px; }

        /* Navigasi bawah */
        .tabbar {
            position: fixed; left: 50%; bottom: 0; transform: translateX(-50%);
            width: 100%; max-width: 430px;
            display: grid; grid-template-columns: repeat(4, 1fr);
            padding-bottom: env(safe-area-inset-bottom);
            background: var(--bg); border-top: 1px solid var(--line);
            z-index: 40;
        }
        .tab {
            display: flex; flex-direction: column; align-items: center; gap: 4px;
            padding: 10px 0 12px; font-size: 11.5px; font-weight: 500; color: var(--ink-2);
            cursor: pointer; background: transparent; border: none; text-decoration: none;
        }
        .tab .ico { width: 24px; height: 24px; }
        .tab[aria-current="page"] { color: var(--brand); font-weight: 600; }
        .tab[aria-current="page"] .ico { stroke-width: 2.1; }
    </style>
</head>
<body class="selection:bg-[#0A5A42]/15">

<div x-data="guestTableOrder({
        token: '{{ $token }}',
        isValid: {{ $isValid ? 'true' : 'false' }},
        isUniversalPortal: {{ ($isUniversalPortal ?? false) ? 'true' : 'false' }},
        errorCode: '{{ $errorCode ?? '' }}',
        errorMessage: '{{ $errorMessage ?? '' }}',
        table: {{ Js::from($table) }},
        availableTables: {{ Js::from($availableTables ?? []) }},
        initialOrderId: '{{ $initialOrderId ?? '' }}',
        storeName: '{{ $settings->store_name ?? 'PosyMart Cafe' }}'
    })"
    x-cloak
    class="app min-h-screen flex flex-col relative overflow-x-hidden">

    {{-- ================= TOAST NOTIFICATION ================= --}}
    <div x-show="toast.show" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-4"
         class="fixed top-4 left-1/2 -translate-x-1/2 z-50 w-[90%] max-w-sm rounded-xl px-4 py-3 shadow-lg flex items-center gap-3 text-white text-sm font-medium"
         :class="toast.type === 'error' ? 'bg-rose-600' : (toast.type === 'info' ? 'bg-slate-800' : 'bg-[#2E8B89]')">
        <span x-text="toast.message" class="flex-1"></span>
        <button type="button" @click="toast.show = false" class="text-white/80 hover:text-white">&times;</button>
    </div>

    {{-- ================= 1. ERROR STATE ================= --}}
    <template x-if="!isValid">
        <div class="flex-1 flex flex-col items-center justify-center p-8 text-center min-h-[80vh]">
            <div class="size-20 rounded-full bg-rose-100 text-rose-600 grid place-items-center mb-5">
                <svg class="size-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <h2 class="text-xl font-bold text-slate-800 mb-2" x-text="errorCode === 'QR_INACTIVE' ? 'Meja Tidak Aktif' : 'Kode QR Tidak Dikenali'"></h2>
            <p class="text-slate-600 text-sm mb-6 leading-relaxed" x-text="errorMessage"></p>
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-xs text-amber-800 text-left w-full mb-6">
                <p class="font-semibold mb-1">Petunjuk Layanan:</p>
                <p>1. Silakan panggil pelayan atau kasir kafe.</p>
                <p>2. Pastikan Anda memindai stiker QR yang terpasang di meja Anda atau gunakan portal pemesanan.</p>
            </div>
            <a href="/order" class="text-xs text-[#2E8B89] font-bold hover:underline mb-2">Buka Portal Pemesanan Kafe</a>
            <a href="/sales" class="text-xs text-slate-400 hover:text-slate-600">Akses Staff / Dashboard Kasir</a>
        </div>
    </template>

    {{-- ================= VALID FLOW ================= --}}
    <template x-if="isValid">
        <div class="flex-1 flex flex-col">            {{-- TOP HEADER BAR (Only visible on non-landing screens) --}}
            <header x-show="currentScreen !== 'landing'" class="sticky top-0 z-30 bg-white/95 backdrop-blur border-b border-slate-100 px-4 py-3 flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <template x-if="currentScreen === 'menu'">
                        <button type="button" @click="currentScreen = 'landing'" class="p-1 -ml-1 rounded-lg text-slate-500 hover:bg-slate-100 transition mr-0.5" title="Kembali ke Beranda">
                            <svg class="size-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                    </template>
                    <span class="size-8 rounded-lg bg-[#014D38]/10 text-[#014D38] grid place-items-center font-bold text-sm">
                        PM
                    </span>
                    <div>
                        <h1 class="text-xs font-semibold text-slate-500 uppercase tracking-wider" x-text="storeName"></h1>
                        <div class="flex items-center gap-1.5">
                            <span class="font-bold text-slate-900 text-base" x-text="displayTableTitle"></span>
                        </div>
                    </div>
                </div>

                {{-- Current State Badges / Actions --}}
                <div class="flex items-center gap-2">
                    <template x-if="isRegistered && customerName">
                        <button type="button" @click="openMyOrders()"
                                class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl border border-slate-200/80 bg-slate-50 hover:bg-[#014D38]/10 hover:border-[#014D38]/40 text-slate-700 hover:text-[#014D38] text-xs font-bold transition shadow-2xs active:scale-95"
                                title="Lacak Pesanan Saya">
                            <svg class="size-3.5 text-[#014D38]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                            <span>Pesanan Saya</span>
                            <template x-if="hasActiveOrders">
                                <span class="size-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            </template>
                        </button>
                    </template>

                    <template x-if="isRegistered && customerName && currentScreen === 'menu'">
                        <div class="text-right pr-1">
                            <span class="text-xs font-bold text-slate-800 block truncate max-w-[90px]" x-text="customerName"></span>
                            <span class="text-[10px] text-emerald-600 font-semibold block leading-tight">Terdaftar</span>
                        </div>
                    </template>
                    <template x-if="currentScreen === 'menu' && cartCount > 0">
                        <button type="button" @click="currentScreen = 'cart'" class="relative p-2 rounded-xl bg-slate-100 text-slate-700 hover:bg-[#014D38]/10 hover:text-[#014D38] transition">
                            <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                            <span class="absolute -top-1 -right-1 size-5 rounded-full bg-[#014D38] text-white text-[11px] font-bold grid place-items-center" x-text="cartCount"></span>
                        </button>
                    </template>
                    <template x-if="currentScreen === 'order_tracking'">
                        <button type="button" @click="currentScreen = 'menu'"
                                class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition flex items-center gap-1">
                            <span>← Menu</span>
                        </button>
                    </template>
                </div>
            </header>

            {{-- ================= SCREEN: 1. HOME / LANDING ================= --}}
            <section x-show="currentScreen === 'landing'" class="flex-1 flex flex-col bg-white">
                <header class="top">
                    <div class="top-row">
                        <div>
                            <p class="hello" x-text="timeGreeting">Selamat malam,</p>
                            <h1 class="name" x-text="customerName || 'Pelanggan Setia'">Mirwan Kholid</h1>
                        </div>
                        <button class="icon-btn" type="button" @click="handleNotifClick()" aria-label="Notifikasi">
                            <svg class="ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                        </button>
                    </div>

                    <div class="status-row">
                        <p class="status"><span class="dot" aria-hidden="true"></span>Kafe sedang buka</p>
                        <a class="text-link" href="#" @click.prevent="openTablePicker()" x-text="displayTableTitle || 'Pilih meja'">Pilih meja</a>
                    </div>

                    <form class="search" role="search" @submit.prevent="currentScreen = 'menu'">
                        <svg class="ico" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                        <input type="search" x-model="searchQuery" @focus="currentScreen = 'menu'" placeholder="Cari kopi, buttercream, atau camilan" aria-label="Cari menu">
                    </form>
                </header>

                <main class="home-main">

                    {{-- Hero Promo Banner Carousel / Slider --}}
                    <section class="banner-carousel">
                        <div class="relative w-full aspect-[2.1/1] overflow-hidden rounded-2xl bg-[var(--fill)] shadow-xs cursor-pointer border border-[var(--line)]"
                             @click="handleBannerClick(activeBannerIdx)">
                            <template x-for="(b, idx) in promoBanners" :key="idx">
                                <div x-show="activeBannerIdx === idx"
                                     x-transition:enter="transition ease-out duration-300"
                                     x-transition:enter-start="opacity-0 scale-98"
                                     x-transition:enter-end="opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-200 absolute inset-0"
                                     x-transition:leave-start="opacity-100 scale-100"
                                     x-transition:leave-end="opacity-0 scale-98"
                                     class="w-full h-full">
                                    <img :src="b.image" :alt="b.title" class="w-full h-full object-cover">
                                </div>
                            </template>
                        </div>

                        {{-- Dots Indicator --}}
                        <div class="flex items-center justify-center gap-1.5 mt-2.5">
                            <template x-for="(b, idx) in promoBanners" :key="idx">
                                <button type="button" @click="activeBannerIdx = idx"
                                        class="h-1.5 rounded-full transition-all duration-300 cursor-pointer border-none p-0"
                                        :class="activeBannerIdx === idx ? 'w-6 bg-[var(--brand)]' : 'w-1.5 bg-[#CBD5E1] hover:bg-slate-400'"
                                        :aria-label="'Slide ' + (idx + 1)">
                                </button>
                            </template>
                        </div>
                    </section>

                    <section class="block">
                        <h2>Pesan sekarang</h2>
                        <ul class="panel">
                            <li>
                                <a class="row-action" href="#" @click.prevent="selectPickUp()">
                                    <span class="row-icon"><svg class="ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg></span>
                                    <span class="row-text">
                                        <span class="row-title">Pick Up</span>
                                        <span class="row-sub">Tanpa antre, siap 5–10 menit</span>
                                    </span>
                                    <svg class="ico chev" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                                </a>
                            </li>
                            <li>
                                <a class="row-action" href="#" @click.prevent="selectDelivery()">
                                    <span class="row-icon"><svg class="ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/></svg></span>
                                    <span class="row-text">
                                        <span class="row-title">Delivery</span>
                                        <span class="row-sub" x-text="table?.label ? ('Diantar ke ' + table.label) : (manualTableNumber ? ('Diantar ke Meja ' + manualTableNumber) : 'Diantar ke meja Anda')">Diantar ke meja Anda</span>
                                    </span>
                                    <svg class="ico chev" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                                </a>
                            </li>
                        </ul>
                    </section>

                    <section class="voucher" aria-labelledby="v-title" @click="applyVoucher('NEW_USER_20K'); openLoyaltyModal()">
                        <div class="v-head">
                            <span>Voucher pengguna baru</span>
                            <span class="v-status"><svg class="ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Aktif</span>
                        </div>
                        <h2 class="v-title" id="v-title">Semua menu jadi Rp20.000</h2>
                        <p class="v-desc">Khusus pengguna baru, berlaku otomatis untuk kopi dan minuman pilihan.</p>
                        <a class="v-more" href="#" @click.prevent.stop="openLoyaltyModal()">
                            <span>Voucher pengguna pertama</span>
                            <span>Lihat voucher<svg class="ico" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg></span>
                        </a>
                    </section>

                    <section class="block">
                        <div class="block-head">
                            <h2>Kategori</h2>
                            <a class="text-link" href="#" @click.prevent="selectedCategory = 'all'; searchQuery = ''; currentScreen = 'menu'">Semua menu</a>
                        </div>
                        <ul class="chips">
                            <li><button type="button" class="chip" @click="selectedCategory = 'all'; searchQuery = 'kopi'; currentScreen = 'menu'">Kopi Susu</button></li>
                            <li><button type="button" class="chip" @click="selectedCategory = 'all'; searchQuery = 'buttercream'; currentScreen = 'menu'">Buttercream</button></li>
                            <li><button type="button" class="chip" @click="selectedCategory = 'all'; searchQuery = 'pastry'; currentScreen = 'menu'">Pastry</button></li>
                            <template x-for="c in categories.slice(0, 4)" :key="c.id">
                                <li><button type="button" class="chip" @click="selectedCategory = c.id; searchQuery = ''; currentScreen = 'menu'" x-text="c.name"></button></li>
                            </template>
                        </ul>
                    </section>

                    <section class="block">
                        <h2>Promo & Pilihan Spesial</h2>
                        <ul class="promos">
                            <li>
                                <a class="promo" href="#" @click.prevent="searchQuery = 'buttercream'; currentScreen = 'menu'">
                                    <div class="promo-img">
                                        <img src="/images/banners/promo_buttercream.jpg" alt="Coba buttercream">
                                    </div>
                                    <h3>Coba buttercream</h3>
                                    <p>Kopi susu dengan topping buttercream lembut.</p>
                                </a>
                            </li>
                            <li>
                                <a class="promo" href="#" @click.prevent="selectedCategory = 'all'; currentScreen = 'menu'">
                                    <div class="promo-img">
                                        <img src="/images/banners/promo_package.jpg" alt="Teman minum kopi">
                                    </div>
                                    <h3>Teman minum kopi</h3>
                                    <p>Pilih pastry untuk dipesan bersama minumanmu.</p>
                                </a>
                            </li>
                        </ul>
                    </section>

                </main>
            </section>

            {{-- ================= SCREEN: 2. MENU CATALOG ================= --}}
            <section x-show="currentScreen === 'menu'" class="flex-1 flex flex-col">
                {{-- Search & Categories --}}
                <div class="sticky top-[57px] z-20 bg-white border-b border-slate-100 px-4 pt-3 pb-2 space-y-2.5">
                    <div class="relative">
                        <input type="text" x-model="searchQuery" placeholder="Cari kopi, makanan, camilan..."
                               class="w-full pl-9 pr-4 py-2.5 bg-slate-100 border-none rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-[#4CB0AE] transition">
                        <svg class="size-4 text-slate-400 absolute left-3 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>

                    {{-- Category Pills --}}
                    <div class="flex items-center gap-2 overflow-x-auto no-scrollbar py-1 text-xs">
                        <button type="button" @click="selectedCategory = 'all'"
                                :class="selectedCategory === 'all' ? 'bg-[#4CB0AE] text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-600 font-medium hover:bg-slate-200'"
                                class="px-3.5 py-1.5 rounded-full whitespace-nowrap transition">
                            Semua Menu
                        </button>
                        <template x-for="cat in categories" :key="cat.id">
                            <button type="button" @click="selectedCategory = cat.id"
                                    :class="selectedCategory === cat.id ? 'bg-[#4CB0AE] text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-600 font-medium hover:bg-slate-200'"
                                    class="px-3.5 py-1.5 rounded-full whitespace-nowrap transition"
                                    x-text="cat.name">
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Product List --}}
                <div class="p-4 space-y-3.5 flex-1">
                    {{-- Notice to login if not logged in --}}
                    <template x-if="!isRegistered || !customerName">
                        <div @click="accountDrawerOpen = true; authMode = 'login'"
                             class="p-3.5 rounded-2xl border border-[var(--line)] bg-[var(--fill)] hover:bg-[#E3E7E4] transition cursor-pointer flex items-center justify-between shadow-2xs">
                            <div class="flex items-center gap-3">
                                <span class="size-8 rounded-xl text-white grid place-items-center text-sm font-bold shrink-0" style="background: var(--brand);">👤</span>
                                <div>
                                    <h4 class="text-xs font-bold text-[var(--ink)] leading-snug">Masuk Akun untuk Memesan</h4>
                                    <p class="text-[11px] text-[var(--ink-2)] mt-0.5">Silakan login atau daftar untuk menambahkan menu ke pesanan.</p>
                                </div>
                            </div>
                            <span class="text-xs font-bold px-2.5 py-1 rounded-lg bg-white border border-[var(--line)] shrink-0" style="color: var(--brand);">
                                Masuk ›
                            </span>
                        </div>
                    </template>

                    <template x-for="product in filteredProducts" :key="product.id">
                        <div class="bg-white border border-slate-200/70 rounded-2xl p-3.5 shadow-2xs hover:shadow-sm transition flex gap-3.5 items-center cursor-pointer"
                             @click="openCustomizer(product)">
                            {{-- Product Thumbnail / Real Food Photo --}}
                            <div class="size-20 rounded-2xl bg-slate-100 overflow-hidden shrink-0 relative border border-slate-200/70 shadow-2xs">
                                <img :src="product.image_url || ('/images/products/' + product.id + '.jpg')"
                                     :alt="product.name"
                                     class="size-full object-cover transition-transform duration-300 hover:scale-105"
                                     loading="lazy"
                                     onerror="this.src='/images/products/p-cappuccino.jpg'">
                                <template x-if="product.status === 'OutOfStock' || (product.track_stock && product.stock_qty <= 0)">
                                    <span class="absolute top-1.5 left-1.5 bg-rose-600 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-md shadow-xs">Habis</span>
                                </template>
                            </div>

                            {{-- Product Info --}}
                            <div class="flex-1 min-w-0">
                                <h3 class="font-bold text-slate-900 text-sm truncate" x-text="product.name"></h3>
                                <p class="text-xs text-slate-500 line-clamp-1 mt-0.5" x-text="product.description || 'Pilihan lezat PosyMart Cafe'"></p>
                                <div class="mt-2 flex items-center justify-between">
                                    <span class="font-bold text-slate-900 text-sm" x-text="formatRp(product.price_cents)"></span>
                                    
                                    {{-- Add Button or Qty Stepper or Habis Badge --}}
                                    <div @click.stop>
                                        <template x-if="product.status === 'OutOfStock' || (product.track_stock && product.stock_qty <= 0)">
                                            <span class="px-2.5 py-1 rounded-xl bg-rose-50 text-rose-600 border border-rose-200 text-xs font-bold">
                                                Habis
                                            </span>
                                        </template>
                                        <template x-if="product.status !== 'OutOfStock' && (!product.track_stock || product.stock_qty > 0)">
                                            <div>
                                                <template x-if="!getItemInCart(product.id)">
                                                    <button type="button" @click.stop="openCustomizer(product)"
                                                            class="px-3.5 py-1.5 rounded-xl text-white text-xs font-bold shadow-xs active:scale-95 transition"
                                                            style="background: var(--brand);">
                                                        + Tambah
                                                    </button>
                                                </template>
                                                <template x-if="getItemInCart(product.id)">
                                                    <div class="flex items-center gap-1.5 bg-slate-100 rounded-xl p-1">
                                                        <button type="button" @click.stop="decrementCartItem(product.id)" class="size-6 rounded-lg bg-white shadow-2xs font-bold text-slate-700 grid place-items-center active:scale-95 text-xs">-</button>
                                                        <span class="px-1 text-xs font-bold text-slate-900" x-text="getItemInCart(product.id).quantity"></span>
                                                        <button type="button" @click.stop="incrementCartItem(product.id)" class="size-6 rounded-lg text-white shadow-2xs font-bold grid place-items-center active:scale-95 text-xs" style="background: var(--brand);">+</button>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </section>

            {{-- ================= SCREEN: 3. CART ================= --}}
            <section x-show="currentScreen === 'cart'" class="flex-1 flex flex-col p-4">
                <div class="flex items-center gap-2 mb-4">
                    <button type="button" @click="currentScreen = 'menu'" class="p-2 -ml-2 rounded-xl text-slate-600 hover:bg-slate-100">
                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <h2 class="text-lg font-bold text-slate-900">Pesanan Anda</h2>
                </div>

                {{-- Table context reminder --}}
                <div class="bg-[#4CB0AE]/10 border border-[#4CB0AE]/20 rounded-2xl p-3 flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="size-7 rounded-lg bg-[#4CB0AE] text-white grid place-items-center text-xs font-bold">✓</span>
                        <span class="text-xs font-semibold text-slate-700"
                              x-text="deliveryType === 'counter' ? 'Penyajian: Ambil di Kasir / Bar' : (manualTableNumber ? ('Diantar ke: Meja ' + manualTableNumber) : (table ? ('Duduk di: ' + table.label) : 'Portal Pesan Mandiri'))"></span>
                    </div>
                    <span class="text-[11px] text-[#2E8B89] font-medium"
                          x-text="deliveryType === 'counter' ? 'Tanpa Meja' : (selectedTable?.area || table?.area || 'Bebas Pilih Meja')"></span>
                </div>

                {{-- Cart Items --}}
                <div class="flex-1 space-y-3">
                    <template x-for="item in cart" :key="item.product_id">
                        <div class="bg-white border border-slate-200/70 rounded-2xl p-3.5 shadow-2xs">
                            <div class="flex items-start gap-3">
                                <img :src="'/images/products/' + item.product_id + '.jpg'" :alt="item.name"
                                     class="size-14 rounded-xl object-cover shrink-0 border border-slate-100 shadow-2xs"
                                     onerror="this.src='/images/products/p-cappuccino.jpg'">
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-bold text-slate-900 text-sm truncate" x-text="item.name"></h4>
                                    <span class="text-xs text-slate-500" x-text="formatRp(item.unit_price_cents)"></span>
                                    <template x-if="item.notes">
                                        <p class="text-[11px] text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md mt-1 italic truncate" x-text="'Catatan: ' + item.notes"></p>
                                    </template>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <button type="button" @click="decrementCartItem(item.product_id)" class="size-7 rounded-lg bg-slate-100 font-bold text-slate-700 grid place-items-center active:scale-95 text-xs">-</button>
                                    <span class="w-5 text-center text-xs font-bold text-slate-900" x-text="item.quantity"></span>
                                    <button type="button" @click="incrementCartItem(item.product_id)" class="size-7 rounded-lg bg-[#4CB0AE] text-white font-bold grid place-items-center active:scale-95 text-xs">+</button>
                                </div>
                            </div>
                            <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                                <button type="button" @click="removeCartItem(item.product_id)" class="text-rose-500 hover:text-rose-700 font-medium">Hapus</button>
                                <span class="font-bold text-slate-900" x-text="formatRp(item.unit_price_cents * item.quantity)"></span>
                            </div>
                        </div>
                    </template>

                    <template x-if="cart.length === 0">
                        <div class="p-12 text-center text-slate-400">
                            <p class="text-sm">Keranjang Anda masih kosong.</p>
                            <button type="button" @click="currentScreen = 'menu'" class="mt-3 px-4 py-2 rounded-xl bg-slate-100 text-slate-700 text-xs font-bold">Kembali ke Menu</button>
                        </div>
                    </template>
                </div>

                {{-- Opsi Penyajian: Ambil Sendiri di Kasir vs Diantar ke Meja --}}
                <div x-show="cart.length > 0" class="mt-4 bg-white border border-slate-200/80 rounded-2xl p-4 shadow-2xs space-y-3">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="size-6 rounded-lg bg-[#4CB0AE]/15 text-[#2E8B89] grid place-items-center text-xs font-bold">✓</span>
                            <h4 class="font-bold text-slate-900 text-xs uppercase tracking-wider">Metode Penerimaan</h4>
                        </div>
                        <span class="text-[11px] font-semibold"
                              :class="deliveryType === 'table' ? 'text-[#2E8B89]' : 'text-amber-700'"
                              x-text="deliveryType === 'table' ? 'Diantar ke Meja' : 'Ambil di Kasir'"></span>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        {{-- Opsi 1: Diantar ke Meja --}}
                        <button type="button" @click="deliveryType = 'table'"
                                class="p-3 rounded-xl border-2 text-left transition flex flex-col justify-between gap-2.5 active:scale-[0.98]"
                                :class="deliveryType === 'table' ? 'border-[#4CB0AE] bg-[#4CB0AE]/5 ring-1 ring-[#4CB0AE]/30' : 'border-slate-200 bg-white hover:border-slate-300'">
                            <div class="flex items-center justify-between">
                                <div class="size-8 rounded-lg grid place-items-center"
                                     :class="deliveryType === 'table' ? 'bg-[#4CB0AE] text-white' : 'bg-slate-100 text-slate-500'">
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                </div>
                                <span class="text-[10px] font-extrabold px-1.5 py-0.5 rounded-md"
                                      :class="deliveryType === 'table' ? 'bg-[#4CB0AE]/20 text-[#2E8B89]' : 'bg-slate-100 text-slate-600'"
                                      x-text="tableDeliveryFee > 0 ? ('+' + formatRp(tableDeliveryFee)) : 'Gratis'"></span>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-900 leading-tight">Diantar ke Meja</p>
                                <p class="text-[10.5px] text-slate-500 mt-0.5 leading-snug">Diantar pelayan ke meja</p>
                            </div>
                        </button>

                        {{-- Opsi 2: Ambil Sendiri di Kasir --}}
                        <button type="button" @click="deliveryType = 'counter'"
                                class="p-3 rounded-xl border-2 text-left transition flex flex-col justify-between gap-2.5 active:scale-[0.98]"
                                :class="deliveryType === 'counter' ? 'border-[#4CB0AE] bg-[#4CB0AE]/5 ring-1 ring-[#4CB0AE]/30' : 'border-slate-200 bg-white hover:border-slate-300'">
                            <div class="flex items-center justify-between">
                                <div class="size-8 rounded-lg grid place-items-center"
                                     :class="deliveryType === 'counter' ? 'bg-[#4CB0AE] text-white' : 'bg-slate-100 text-slate-500'">
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                </div>
                                <span class="text-[10px] font-extrabold px-1.5 py-0.5 rounded-md bg-emerald-100 text-emerald-800">
                                    Gratis
                                </span>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-900 leading-tight">Ambil di Kasir</p>
                                <p class="text-[10.5px] text-slate-500 mt-0.5 leading-snug">Ambil sendiri saat siap</p>
                            </div>
                        </button>
                    </div>

                    {{-- Form Input Nomor Meja Khusus Diantar ke Meja (Wajib Diisi) --}}
                    <div x-show="deliveryType === 'table'"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="p-3.5 bg-gradient-to-br from-emerald-50/90 to-teal-50/60 border border-emerald-200/90 rounded-xl space-y-2.5">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold text-slate-800" for="cart-table-number-input">
                                Nomor Meja Anda <span class="text-rose-500">*</span>
                            </label>
                            <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full border shadow-2xs"
                                  :class="(manualTableNumber || table?.label) ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                  x-text="(manualTableNumber || table?.label) ? ('Meja ' + (manualTableNumber || table?.label).replace(/^Meja\s*/i, '')) : 'Syarat Wajib Diisi'"></span>
                        </div>

                        <div class="flex items-center gap-2">
                            <div class="relative flex-1">
                                <input id="cart-table-number-input" type="text" x-model="manualTableNumber" @input="onManualTableInput($event.target.value)"
                                       placeholder="Ketik no. meja, misal: 12"
                                       class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-900 placeholder:font-normal placeholder:text-slate-400 focus:ring-2 focus:ring-[#4CB0AE] transition">
                                <svg class="size-4 text-slate-400 absolute left-2.5 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            </div>

                            <select x-model="selectedTableSelect" @change="onTableSelectChange($event.target.value)"
                                    class="w-36 py-2.5 px-2 bg-white border border-slate-300 rounded-xl text-xs font-medium text-slate-700 focus:ring-2 focus:ring-[#4CB0AE]">
                                <option value="">-- Pilih Meja --</option>
                                <template x-for="t in availableTables" :key="t.id">
                                    <option :value="t.id" x-text="t.label + (t.area ? ' (' + t.area + ')' : '')"></option>
                                </template>
                            </select>
                        </div>
                        <p class="text-[11px] text-slate-500">
                            Wajib mengisi nomor meja agar pesanan dapat diantar tepat ke tempat duduk Anda.
                        </p>
                    </div>

                    {{-- Info Ambil di Kasir --}}
                    <div x-show="deliveryType === 'counter'"
                         x-transition:enter="transition ease-out duration-200"
                         class="p-2.5 bg-amber-50/80 border border-amber-200/80 rounded-xl text-left flex items-start gap-2">
                        <svg class="size-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-[11px] text-amber-800 leading-snug">
                            Pesanan dapat langsung diambil di kasir/bar saat status pesanan <strong>Ready</strong> (tanpa biaya pengantaran).
                        </p>
                    </div>
                </div>

                {{-- Price Breakdown & Checkout Action --}}
                <div x-show="cart.length > 0" class="mt-4 pt-4 border-t border-slate-200/80 space-y-2.5">
                    {{-- Voucher Selection in Cart --}}
                    <div class="p-3 rounded-2xl border-2 border-dashed transition cursor-pointer flex items-center justify-between"
                         :class="appliedVoucherCode ? 'border-amber-300 bg-amber-50/60' : 'border-slate-300 bg-slate-50 hover:bg-slate-100/80'"
                         @click="openLoyaltyModal()">
                        <div class="flex items-center gap-2.5">
                            <span class="size-7 rounded-xl grid place-items-center text-xs font-bold shrink-0"
                                  :class="appliedVoucherCode ? 'bg-amber-500 text-white' : 'bg-slate-200 text-slate-600'">🎟️</span>
                            <div class="text-left">
                                <span class="text-xs font-black block"
                                      :class="appliedVoucherCode ? 'text-amber-950' : 'text-slate-800'"
                                      x-text="appliedVoucherLabel || 'Gunakan Voucher / Promo Hemat'"></span>
                                <span class="text-[10.5px] block leading-tight"
                                      :class="appliedVoucherCode ? 'text-amber-800 font-bold' : 'text-slate-500'"
                                      x-text="appliedVoucherCode ? ('Hemat ' + formatRp(voucherDiscount) + ' dengan voucher ini!') : 'Voucher 20RB khusus pengguna pertama'"></span>
                            </div>
                        </div>
                        <template x-if="appliedVoucherCode">
                            <button type="button" @click.stop="removeVoucher()" class="text-xs font-black text-rose-600 hover:underline px-2 py-1">
                                Lepas
                            </button>
                        </template>
                        <template x-if="!appliedVoucherCode">
                            <span class="text-xs font-bold text-[#014D38] flex items-center gap-0.5">Pilih ›</span>
                        </template>
                    </div>

                    <div class="flex items-center justify-between text-xs text-slate-600 pt-1">
                        <span>Subtotal</span>
                        <span class="font-semibold text-slate-800" x-text="formatRp(cartSubtotal)"></span>
                    </div>

                    {{-- Potongan Voucher --}}
                    <template x-if="voucherDiscount > 0">
                        <div class="flex items-center justify-between text-xs font-bold text-emerald-700">
                            <span class="flex items-center gap-1">
                                <span>Potongan Voucher</span>
                                <span class="text-[9.5px] uppercase bg-emerald-100 text-emerald-800 px-1.5 py-0.2 rounded" x-text="appliedVoucherCode"></span>
                            </span>
                            <span x-text="'- ' + formatRp(voucherDiscount)"></span>
                        </div>
                    </template>

                    {{-- Biaya Tambahan Pengantaran --}}
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-600 flex items-center gap-1.5">
                            <span>Biaya Pengantaran</span>
                            <span class="text-[10px] px-1.5 py-0.2 rounded font-semibold"
                                  :class="deliveryType === 'table' ? 'bg-teal-50 text-[#2E8B89]' : 'bg-slate-100 text-slate-500'"
                                  x-text="deliveryType === 'table' ? 'Diantar ke Meja' : 'Ambil di Kasir'"></span>
                        </span>
                        <span class="font-semibold"
                              :class="deliveryType === 'table' && tableDeliveryFee > 0 ? 'text-slate-800' : 'text-emerald-600'"
                              x-text="deliveryType === 'table' ? (tableDeliveryFee > 0 ? formatRp(tableDeliveryFee) : 'Gratis') : 'Gratis (Rp 0)'"></span>
                    </div>

                    <div class="flex items-center justify-between text-xs text-slate-600">
                        <span x-text="taxLabel"></span>
                        <span class="font-semibold text-slate-800" x-text="formatRp(cartTax)"></span>
                    </div>
                    <div class="flex items-center justify-between text-base font-black text-slate-900 pt-2 border-t border-slate-100">
                        <span>Total Pembayaran</span>
                        <span class="text-[#014D38]" x-text="formatRp(cartTotal)"></span>
                    </div>

                    <button type="button" @click="proceedToCheckout()"
                            class="w-full mt-4 py-3.5 rounded-2xl bg-gradient-to-r from-[#4CB0AE] to-[#3BA09E] text-white font-bold text-sm shadow-md shadow-[#4CB0AE]/20 hover:opacity-95 active:scale-[0.99] transition flex items-center justify-center gap-2">
                        <span>Lanjut ke Konfirmasi</span>
                        <svg class="size-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </div>
            </section>

            {{-- ================= SCREEN: 4. CHECKOUT ================= --}}
            <section x-show="currentScreen === 'checkout'" class="flex-1 flex flex-col p-4">
                <div class="flex items-center gap-2 mb-4">
                    <button type="button" @click="currentScreen = 'cart'" class="p-2 -ml-2 rounded-xl text-slate-600 hover:bg-slate-100">
                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <h2 class="text-lg font-bold text-slate-900">Konfirmasi Pemesanan</h2>
                </div>

                <div class="space-y-4 flex-1">
                    {{-- Titik Penerimaan & Nama Pemesan --}}
                    <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-2xs space-y-3">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <div>
                                <span class="text-[11px] font-semibold text-slate-400 uppercase">Titik Penerimaan</span>
                                <h3 class="font-extrabold text-slate-900 text-base"
                                    x-text="deliveryType === 'counter' ? 'Ambil di Kasir / Bar' : (manualTableNumber ? ('Meja ' + manualTableNumber) : (table?.label || 'Pilih / Ketik Nomor Meja'))"></h3>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold"
                                  :class="deliveryType === 'counter' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-50 text-emerald-700'"
                                  x-show="deliveryType === 'counter' || (selectedTable?.area || table?.area)"
                                  x-text="deliveryType === 'counter' ? 'Kasir / Bar' : (selectedTable?.area || table?.area)"></span>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">
                                Nama Pemesan <span class="text-slate-400 font-normal">(Misal: Rani)</span>
                            </label>
                            <input type="text" x-model="customerName" placeholder="Tulis nama panggilan Anda..."
                                   class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-[#4CB0AE] transition">
                            <p class="text-[11px] text-slate-400 mt-1">Nama pemesan akan dicantumkan pada tiket pesanan.</p>
                        </div>
                    </div>

                    {{-- Opsi Pengantaran Pesanan --}}
                    <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-2xs space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                            <h4 class="font-bold text-slate-900 text-xs uppercase tracking-wider">Metode Pengantaran</h4>
                            <span class="text-[11px] text-slate-500 font-medium">Pilih cara penyajian</span>
                        </div>

                        <div class="space-y-2.5">
                            {{-- Pilihan 1: Diantar ke Meja --}}
                            <button type="button" @click="deliveryType = 'table'"
                                    class="w-full p-3.5 rounded-2xl border-2 text-left transition flex items-center justify-between gap-3 active:scale-[0.99]"
                                    :class="deliveryType === 'table' ? 'border-[#4CB0AE] bg-[#4CB0AE]/5 shadow-2xs' : 'border-slate-200 bg-white hover:border-slate-300'">
                                <div class="flex items-center gap-3">
                                    <div class="size-10 rounded-xl grid place-items-center shrink-0"
                                         :class="deliveryType === 'table' ? 'bg-[#4CB0AE] text-white' : 'bg-slate-100 text-slate-600'">
                                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <p class="text-xs font-bold text-slate-900">Diantar ke Meja</p>
                                            <template x-if="manualTableNumber || table?.label">
                                                <span class="text-[10px] px-2 py-0.5 rounded-full font-bold bg-[#4CB0AE]/15 text-[#2E8B89]"
                                                      x-text="manualTableNumber ? ('Meja ' + manualTableNumber) : table?.label"></span>
                                            </template>
                                        </div>
                                        <p class="text-[11px] text-slate-500 mt-0.5">Pelayan mengantar langsung ke nomor meja Anda</p>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="text-xs font-bold"
                                          :class="deliveryType === 'table' ? 'text-[#2E8B89]' : 'text-slate-500'"
                                          x-text="tableDeliveryFee > 0 ? ('+' + formatRp(tableDeliveryFee)) : 'Gratis'"></span>
                                </div>
                            </button>

                            {{-- Form Input Nomor Meja Khusus Diantar ke Meja --}}
                            <div x-show="deliveryType === 'table'"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 -translate-y-2"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 class="p-3.5 bg-gradient-to-br from-emerald-50/80 to-teal-50/60 border border-emerald-200/90 rounded-2xl space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <label class="block text-xs font-black text-slate-800">
                                        Nomor Meja Anda <span class="text-rose-500">*</span>
                                    </label>
                                    <span class="text-[11px] font-bold text-emerald-800 bg-white px-2.5 py-0.5 rounded-full border border-emerald-200 shadow-2xs"
                                          x-text="manualTableNumber ? ('Meja ' + manualTableNumber) : 'Wajib diisi'"></span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <div class="relative flex-1">
                                        <input type="text" x-model="manualTableNumber" @input="onManualTableInput($event.target.value)"
                                               placeholder="Ketik nomor meja, misal: 17"
                                               class="w-full pl-9 pr-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-900 placeholder:font-normal placeholder:text-slate-400 focus:ring-2 focus:ring-[#4CB0AE] transition">
                                        <svg class="size-4 text-slate-400 absolute left-3 top-3 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    </div>

                                    {{-- Quick Selector Dropdown --}}
                                    <select x-model="selectedTableSelect" @change="onTableSelectChange($event.target.value)"
                                            class="w-36 py-2.5 px-2 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-700 focus:ring-2 focus:ring-[#4CB0AE]">
                                        <option value="">-- Pilih List --</option>
                                        <template x-for="t in availableTables" :key="t.id">
                                            <option :value="t.id" x-text="t.label + (t.area ? ' (' + t.area + ')' : '')"></option>
                                        </template>
                                    </select>
                                </div>
                                <p class="text-[11px] text-slate-500">
                                    Ketik nomor meja atau pilih dari daftar meja yang tersedia di kafe.
                                </p>
                            </div>

                            {{-- Pilihan 2: Ambil Sendiri di Kasir --}}
                            <button type="button" @click="deliveryType = 'counter'"
                                    class="w-full p-3.5 rounded-2xl border-2 text-left transition flex items-center justify-between gap-3 active:scale-[0.99]"
                                    :class="deliveryType === 'counter' ? 'border-[#4CB0AE] bg-[#4CB0AE]/5 shadow-2xs' : 'border-slate-200 bg-white hover:border-slate-300'">
                                <div class="flex items-center gap-3">
                                    <div class="size-10 rounded-xl grid place-items-center shrink-0"
                                         :class="deliveryType === 'counter' ? 'bg-[#4CB0AE] text-white' : 'bg-slate-100 text-slate-600'">
                                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-slate-900">Ambil Sendiri di Kasir</p>
                                        <p class="text-[11px] text-slate-500 mt-0.5">Ambil di bar/kasir saat status pesanan siap (Tanpa nomor meja)</p>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="text-xs font-bold text-emerald-600">Gratis (Rp 0)</span>
                                </div>
                            </button>
                        </div>
                    </div>

                    {{-- Ringkasan Item --}}
                    <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-2xs">
                        <h4 class="font-bold text-slate-900 text-xs uppercase tracking-wider mb-3">Ringkasan Item</h4>
                        <div class="space-y-2.5">
                            <template x-for="item in cart" :key="item.product_id">
                                <div class="flex items-center gap-2.5 text-xs py-1">
                                    <img :src="'/images/products/' + item.product_id + '.jpg'" :alt="item.name"
                                         class="size-10 rounded-lg object-cover shrink-0 border border-slate-100 shadow-2xs"
                                         onerror="this.src='/images/products/p-cappuccino.jpg'">
                                    <div class="flex-1 min-w-0 pr-1">
                                        <p class="font-semibold text-slate-800 truncate"><span x-text="item.quantity + 'x '"></span><span x-text="item.name"></span></p>
                                        <template x-if="item.notes">
                                            <p class="text-[11px] text-slate-400 italic truncate" x-text="item.notes"></p>
                                        </template>
                                    </div>
                                    <span class="font-bold text-slate-900 shrink-0" x-text="formatRp(item.unit_price_cents * item.quantity)"></span>
                                </div>
                            </template>
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-100 space-y-1.5 text-xs">
                            <div class="flex justify-between text-slate-600">
                                <span>Subtotal Menu</span>
                                <span class="font-semibold text-slate-800" x-text="formatRp(cartSubtotal)"></span>
                            </div>
                            <template x-if="voucherDiscount > 0">
                                <div class="flex justify-between text-emerald-600 font-bold">
                                    <span class="flex items-center gap-1">
                                        <span>Potongan Voucher</span>
                                        <span class="text-[9.5px] uppercase bg-emerald-100 text-emerald-800 px-1.5 py-0.2 rounded" x-text="appliedVoucherCode"></span>
                                    </span>
                                    <span x-text="'- ' + formatRp(voucherDiscount)"></span>
                                </div>
                            </template>
                            <template x-if="deliveryType === 'table'">
                                <div class="flex justify-between text-slate-600">
                                    <span class="flex items-center gap-1.5">
                                        <span>Biaya Antar ke Meja</span>
                                        <span class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-medium"
                                              x-text="manualTableNumber ? ('Meja ' + manualTableNumber) : (table?.label || '')"></span>
                                    </span>
                                    <span class="font-semibold text-slate-800" x-text="tableDeliveryFee > 0 ? formatRp(tableDeliveryFee) : 'Gratis'"></span>
                                </div>
                            </template>
                            <template x-if="deliveryType === 'counter'">
                                <div class="flex justify-between text-slate-600">
                                    <span>Layanan Ambil di Kasir</span>
                                    <span class="font-semibold text-emerald-600">Gratis (Rp 0)</span>
                                </div>
                            </template>
                            <div class="flex justify-between text-slate-600">
                                <span x-text="taxLabel"></span>
                                <span class="font-semibold text-slate-800" x-text="formatRp(cartTax)"></span>
                            </div>
                            <div class="flex justify-between text-sm font-extrabold text-slate-900 pt-2 border-t border-slate-100">
                                <span>Total Tagihan</span>
                                <span class="text-[#014D38] font-black" x-text="formatRp(cartTotal)"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Metode Pembayaran --}}
                    <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-2xs">
                        <h4 class="font-bold text-slate-900 text-xs uppercase tracking-wider mb-3">Metode Pembayaran</h4>
                        <div class="flex items-center gap-3 p-3 rounded-xl border-2 border-[#4CB0AE] bg-[#4CB0AE]/5">
                            <span class="size-8 rounded-lg bg-[#4CB0AE] text-white grid place-items-center font-bold text-xs">QRIS</span>
                            <div class="flex-1">
                                <p class="text-xs font-bold text-slate-900">QRIS Dinamis</p>
                                <p class="text-[11px] text-slate-500">GoPay, OVO, Dana, BCA, Livin, ShopeePay</p>
                            </div>
                            <span class="size-5 rounded-full bg-[#4CB0AE] text-white grid place-items-center text-xs">✓</span>
                        </div>
                    </div>
                </div>

                {{-- Action Button --}}
                <div class="mt-6 pt-3">
                    <button type="button" @click="executeCheckout()" :disabled="isSubmitting"
                            class="w-full py-4 rounded-2xl bg-gradient-to-r from-[#4CB0AE] to-[#3BA09E] text-white font-bold text-base shadow-lg shadow-[#4CB0AE]/25 hover:opacity-95 active:scale-[0.99] transition flex items-center justify-center gap-2 disabled:opacity-50">
                        <template x-if="!isSubmitting">
                            <span class="flex items-center gap-2">
                                <span>Pesan &amp; Bayar (<span x-text="formatRp(cartTotal)"></span>)</span>
                                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            </span>
                        </template>
                        <template x-if="isSubmitting">
                            <span class="flex items-center gap-2">
                                <svg class="animate-spin size-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>Menyiapkan Tagihan QRIS...</span>
                            </span>
                        </template>
                    </button>
                </div>
            </section>

            {{-- ================= SCREEN: 5. QRIS PAYMENT ================= --}}
            <section x-show="currentScreen === 'payment'" class="flex-1 flex flex-col p-4 text-center">
                <div class="pt-2">
                    <span class="inline-block px-3 py-1 rounded-full bg-amber-100 text-amber-800 text-xs font-bold uppercase mb-2">
                        Menunggu Pembayaran
                    </span>
                    <h2 class="text-xl font-black text-slate-900" x-text="formatRp(activeOrder?.total_cents || 0)"></h2>
                    <p class="text-xs text-slate-500 mt-0.5">Order ID: <strong class="text-slate-800" x-text="activeOrder?.order_code"></strong> · <span x-text="activeOrder?.table_label_snapshot || table?.label || (manualTableNumber ? 'Meja ' + manualTableNumber : 'Kasir')"></span></p>

                    {{-- 15-Minute Countdown Timer --}}
                    <div class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-100 text-xs font-semibold text-slate-700">
                        <svg class="size-4 text-amber-500 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"/><path d="M12 6v6l4 2" stroke-width="2"/></svg>
                        <span>Sisa waktu: <strong x-text="countdownFormatted">15:00</strong></span>
                    </div>

                    {{-- QR Code Display Box --}}
                    <div class="mt-5 p-6 bg-white border-2 border-slate-200 rounded-3xl shadow-sm max-w-[280px] mx-auto text-center relative">
                        <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100">
                            <span class="font-extrabold text-sm text-slate-800">QRIS</span>
                            <span class="text-[10px] font-semibold text-slate-400">GPN / BI</span>
                        </div>

                        {{-- Stylized SVG QRIS Payload --}}
                        <div class="size-48 mx-auto bg-slate-50 p-2 rounded-2xl border border-slate-200 flex flex-col items-center justify-center relative">
                            <svg class="size-44 text-slate-800" viewBox="0 0 100 100" fill="currentColor">
                                {{-- QR Pattern Simulator --}}
                                <rect x="5" y="5" width="28" height="28" rx="4" fill="none" stroke="currentColor" stroke-width="4"/>
                                <rect x="11" y="11" width="16" height="16" rx="2"/>
                                <rect x="67" y="5" width="28" height="28" rx="4" fill="none" stroke="currentColor" stroke-width="4"/>
                                <rect x="73" y="11" width="16" height="16" rx="2"/>
                                <rect x="5" y="67" width="28" height="28" rx="4" fill="none" stroke="currentColor" stroke-width="4"/>
                                <rect x="11" y="73" width="16" height="16" rx="2"/>
                                <circle cx="50" cy="50" r="10" fill="#4CB0AE"/>
                                <path d="M40 10h20M10 40v20M40 70h10M70 40v20M50 20v10M80 70h10M65 85h15M85 65v15" stroke="currentColor" stroke-width="3"/>
                            </svg>
                            <span class="absolute text-[10px] font-extrabold text-white bg-[#4CB0AE] px-2 py-0.5 rounded-full shadow-xs">
                                SCAN ME
                            </span>
                        </div>

                        <p class="text-[11px] text-slate-400 mt-3">NMID: ID1020261011041<br>PosyMart Table Order</p>
                    </div>

                    {{-- Instructions for single-phone usage (PRD Section 8.5) --}}
                    <div class="mt-4 p-3 bg-slate-50 rounded-xl text-left text-xs text-slate-600 max-w-[320px] mx-auto space-y-1">
                        <p class="font-bold text-slate-800">Petunjuk Pembayaran Satu Ponsel:</p>
                        <p>1. Screenshot layar QRIS di atas.</p>
                        <p>2. Buka aplikasi m-Banking atau E-Wallet (BCA/GoPay/OVO/Dana).</p>
                        <p>3. Pilih menu QRIS &gt; Upload Foto dari Galeri.</p>
                    </div>
                </div>

                {{-- Action Verification Buttons --}}
                <div class="mt-6 space-y-2.5 max-w-[320px] mx-auto w-full">
                    {{-- Check Server Status --}}
                    <button type="button" @click="checkPaymentStatus()" :disabled="isChecking"
                            class="w-full py-3.5 rounded-2xl bg-[#4CB0AE] text-white font-bold text-sm shadow-sm hover:bg-[#3BA09E] active:scale-95 transition flex items-center justify-center gap-2">
                        <svg class="size-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Periksa Status Pembayaran</span>
                    </button>

                    {{-- Sandbox Simulated Pay Button (Per PRD section 8.1 & 19) --}}
                    <button type="button" @click="simulatePay()" :disabled="isSimulating"
                            class="w-full py-2.5 rounded-xl border border-dashed border-[#4CB0AE] text-[#2E8B89] bg-[#4CB0AE]/5 hover:bg-[#4CB0AE]/10 text-xs font-semibold active:scale-95 transition flex items-center justify-center gap-1.5">
                        <span>⚡ Simulasikan Bayar QRIS (Sandbox Test)</span>
                    </button>
                </div>
            </section>

            {{-- ================= SCREEN: 6. ORDER TRACKING & STEPPER ================= --}}
            <section x-show="currentScreen === 'order_tracking'" class="flex-1 flex flex-col p-4">
                {{-- Dynamic Fulfillment Status Banner --}}
                <div class="rounded-2xl p-4 text-white shadow-md text-center mb-4 transition-all duration-300"
                     :class="activeOrder?.fulfillment_status === 'Served' ? 'bg-gradient-to-r from-emerald-600 to-teal-700' :
                            (activeOrder?.fulfillment_status === 'Ready' ? 'bg-gradient-to-r from-emerald-500 to-teal-600 ring-4 ring-emerald-200' :
                            (activeOrder?.fulfillment_status === 'Preparing' ? 'bg-gradient-to-r from-teal-600 to-cyan-700' : 'bg-gradient-to-r from-emerald-500 to-teal-600'))">
                    <span class="size-11 rounded-full bg-white/20 grid place-items-center mx-auto mb-2 font-bold text-xl shadow-xs"
                          :class="activeOrder?.fulfillment_status === 'Ready' ? 'animate-bounce' : ''">
                        <template x-if="activeOrder?.fulfillment_status === 'Served'">
                            <span>🎉</span>
                        </template>
                        <template x-if="activeOrder?.fulfillment_status === 'Ready'">
                            <span>🔔</span>
                        </template>
                        <template x-if="activeOrder?.fulfillment_status === 'Preparing'">
                            <span>☕</span>
                        </template>
                        <template x-if="!['Served', 'Ready', 'Preparing'].includes(activeOrder?.fulfillment_status)">
                            <span>✓</span>
                        </template>
                    </span>
                    <h2 class="font-black text-lg tracking-tight"
                        x-text="activeOrder?.fulfillment_status === 'Served' ? 'Pesanan Selesai Disajikan!' :
                               (activeOrder?.fulfillment_status === 'Ready' ? (activeOrder?.delivery_type === 'counter' ? 'Pesanan Siap Diambil di Kasir!' : 'Pesanan Sedang Diantar ke Meja!') :
                               (activeOrder?.fulfillment_status === 'Preparing' ? 'Pesanan Sedang Disiapkan...' :
                               (activeOrder?.fulfillment_status === 'Accepted' ? 'Pesanan Diterima Staf' : 'Pembayaran Berhasil!')))">
                    </h2>
                    <p class="text-xs text-white/90 mt-0.5"
                       x-text="activeOrder?.fulfillment_status === 'Served' ? ('Hidangan telah selesai disajikan ke ' + (activeOrder?.table_label_snapshot || 'Meja Anda') + '. Selamat menikmati!') :
                              (activeOrder?.fulfillment_status === 'Ready' ? (activeOrder?.delivery_type === 'counter' ? 'Silakan ambil hidangan Anda di meja kasir/bar.' : ('Pelayan sedang membawa hidangan ke ' + (activeOrder?.table_label_snapshot || 'Meja Anda') + '.')) :
                              (activeOrder?.fulfillment_status === 'Preparing' ? 'Barista dan staf dapur sedang meracik pesanan Anda.' :
                              'Pesanan Anda telah diteruskan ke dapur & barista kafe.'))">
                    </p>
                    <div class="mt-3 pt-2.5 border-t border-white/20 flex items-center justify-between text-xs font-medium">
                        <span>No. Pesanan: <strong class="text-white" x-text="activeOrder?.order_code || activeOrder?.id"></strong></span>
                        <span class="bg-white/20 px-2.5 py-0.5 rounded-full font-bold" x-text="activeOrder?.table_label_snapshot || table?.label || 'Ambil di Kasir'"></span>
                    </div>
                </div>

                {{-- Delivery Mode Banner --}}
                <div class="bg-slate-50 border border-slate-200/90 rounded-2xl p-3.5 mb-4 flex items-center justify-between shadow-2xs">
                    <div class="flex items-center gap-3">
                        <div class="size-9 rounded-xl grid place-items-center shrink-0"
                             :class="activeOrder?.delivery_type === 'counter' ? 'bg-amber-100 text-amber-700' : 'bg-[#4CB0AE]/15 text-[#2E8B89]'">
                            <template x-if="activeOrder?.delivery_type === 'counter'">
                                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                            </template>
                            <template x-if="activeOrder?.delivery_type !== 'counter'">
                                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            </template>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-800"
                               x-text="activeOrder?.delivery_type === 'counter' ? 'Ambil Sendiri di Kasir' : 'Diantar Langsung ke Meja'"></p>
                            <p class="text-[11px] text-slate-500"
                               x-text="activeOrder?.delivery_type === 'counter' ? 'Silakan ambil di meja kasir/bar saat status Siap' : 'Pelayan akan mengantar ke ' + (activeOrder?.table_label_snapshot || table?.label || 'tempat duduk Anda')"></p>
                        </div>
                    </div>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full shrink-0"
                          :class="activeOrder?.delivery_type === 'counter' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'"
                          x-text="activeOrder?.delivery_type === 'counter' ? 'Pick-up' : 'Antar Meja'">
                    </span>
                </div>

                {{-- Live Fulfillment Stepper --}}
                <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-2xs mb-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-slate-900 text-xs uppercase tracking-wider">Status Pemesanan</h3>
                        <span class="flex items-center gap-1.5 text-xs font-bold text-[#2E8B89]">
                            <span class="size-2 rounded-full bg-[#4CB0AE] animate-ping"></span>
                            <span x-text="getFulfillmentLabel(activeOrder?.fulfillment_status)"></span>
                        </span>
                    </div>

                    {{-- Stepper Progress --}}
                    <div class="relative pl-6 space-y-6 before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
                        {{-- 1. Paid --}}
                        <div class="relative">
                            <span class="absolute -left-6 top-0.5 size-5 rounded-full bg-emerald-500 text-white text-[10px] font-bold grid place-items-center">✓</span>
                            <p class="text-xs font-bold text-slate-800">Pembayaran Terverifikasi (Paid)</p>
                            <p class="text-[11px] text-slate-400">Pembayaran QRIS sah telah diterima server.</p>
                        </div>

                        {{-- 2. New / Diterima --}}
                        <div class="relative">
                            <span class="absolute -left-6 top-0.5 size-5 rounded-full grid place-items-center text-[10px] font-bold"
                                  :class="isStepReached('Accepted') || isStepReached('Preparing') || isStepReached('Ready') || isStepReached('Served') ? 'bg-emerald-500 text-white' : (activeOrder?.fulfillment_status === 'New' ? 'bg-[#4CB0AE] text-white animate-pulse' : 'bg-slate-200 text-slate-400')">
                                <span x-text="isStepReached('Accepted') ? '✓' : '2'"></span>
                            </span>
                            <p class="text-xs font-bold" :class="isStepReached('Accepted') ? 'text-slate-800' : 'text-[#2E8B89]'">Pesanan Diterima Staf (New)</p>
                            <p class="text-[11px] text-slate-400">Barista telah menerima pesanan Meja Anda.</p>
                        </div>

                        {{-- 3. Preparing --}}
                        <div class="relative">
                            <span class="absolute -left-6 top-0.5 size-5 rounded-full grid place-items-center text-[10px] font-bold"
                                  :class="isStepReached('Ready') || isStepReached('Served') ? 'bg-emerald-500 text-white' : (activeOrder?.fulfillment_status === 'Preparing' ? 'bg-[#4CB0AE] text-white animate-pulse' : 'bg-slate-200 text-slate-400')">
                                <span x-text="isStepReached('Ready') ? '✓' : '3'"></span>
                            </span>
                            <p class="text-xs font-bold" :class="isStepReached('Preparing') ? 'text-[#2E8B89]' : 'text-slate-700'">Sedang Disiapkan (Preparing)</p>
                            <p class="text-[11px] text-slate-400">Minuman &amp; makanan sedang diracik oleh staf dapur.</p>
                        </div>

                        {{-- 4. Ready --}}
                        <div class="relative">
                            <span class="absolute -left-6 top-0.5 size-5 rounded-full grid place-items-center text-[10px] font-bold"
                                  :class="isStepReached('Served') ? 'bg-emerald-500 text-white' : (activeOrder?.fulfillment_status === 'Ready' ? 'bg-emerald-600 text-white animate-bounce ring-4 ring-emerald-200' : 'bg-slate-200 text-slate-400')">
                                <span x-text="isStepReached('Served') ? '✓' : '4'"></span>
                            </span>
                            <p class="text-xs font-bold" :class="isStepReached('Ready') ? 'text-emerald-700 font-extrabold' : 'text-slate-700'"
                               x-text="activeOrder?.delivery_type === 'counter' ? 'Pesanan Siap Diambil di Kasir' : ('Pesanan Siap Diantar ke ' + (activeOrder?.table_label_snapshot || 'Meja'))"></p>
                            <p class="text-[11px] text-slate-400"
                               x-text="activeOrder?.delivery_type === 'counter' ? 'Silakan menuju kasir/bar untuk mengambil pesanan Anda.' : ('Pelayan sedang membawa hidangan ke ' + (activeOrder?.table_label_snapshot || 'Meja Anda') + '.')"></p>
                        </div>

                        {{-- 5. Served --}}
                        <div class="relative">
                            <span class="absolute -left-6 top-0.5 size-5 rounded-full grid place-items-center text-[10px] font-bold"
                                  :class="activeOrder?.fulfillment_status === 'Served' ? 'bg-emerald-600 text-white ring-4 ring-emerald-200' : 'bg-slate-200 text-slate-400'">
                                <span x-text="activeOrder?.fulfillment_status === 'Served' ? '✓' : '5'"></span>
                            </span>
                            <p class="text-xs font-bold" :class="activeOrder?.fulfillment_status === 'Served' ? 'text-emerald-700 font-black' : 'text-slate-700'"
                               x-text="'Selesai Disajikan (Served) ' + (activeOrder?.fulfillment_status === 'Served' ? '🎉' : '')"></p>
                            <p class="text-[11px] text-slate-400"
                               x-text="activeOrder?.fulfillment_status === 'Served' ? ('Semua menu telah lengkap disajikan di ' + (activeOrder?.table_label_snapshot || 'Meja Anda') + '. Selamat menikmati!') : 'Pesanan telah selesai diantar/disajikan.'"></p>
                        </div>
                    </div>
                </div>

                {{-- Digital Receipt Summary --}}
                <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-2xs space-y-3 mb-4">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                        <span class="text-xs font-bold text-slate-800">Rincian Pembelian</span>
                        <span class="text-[11px] text-emerald-700 font-bold bg-emerald-50 px-2 py-0.5 rounded-md">Lunas · QRIS</span>
                    </div>

                    <div class="space-y-2.5 text-xs">
                        <template x-for="it in (activeOrder?.items || cart)" :key="it.id || it.product_id">
                            <div class="flex items-center justify-between text-slate-700">
                                <div class="flex items-center gap-2 min-w-0 pr-2">
                                    <img :src="'/images/products/' + (it.product_id || it.id) + '.jpg'" :alt="it.name"
                                         class="size-8 rounded-lg object-cover shrink-0 border border-slate-100 shadow-2xs"
                                         onerror="this.src='/images/products/p-cappuccino.jpg'">
                                    <span class="truncate"><span x-text="it.quantity + 'x '" class="font-bold"></span><span x-text="it.name"></span></span>
                                </div>
                                <span class="font-semibold text-slate-900 shrink-0" x-text="formatRp(it.unit_price_cents * it.quantity)"></span>
                            </div>
                        </template>
                    </div>

                    <div class="pt-2 border-t border-slate-100 flex justify-between text-xs text-slate-600">
                        <span>Subtotal Menu</span>
                        <span class="font-semibold text-slate-800" x-text="formatRp(activeOrder?.subtotal_cents || cartSubtotal)"></span>
                    </div>
                    <template x-if="activeOrder?.discount_cents > 0">
                        <div class="flex justify-between text-xs text-emerald-600 font-medium">
                            <span class="flex items-center gap-1">
                                <span>🏷️ Potongan Voucher</span>
                                <span class="text-[10px] bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold" x-text="activeOrder?.voucher_code || 'PROMO'"></span>
                            </span>
                            <span class="font-bold" x-text="'- ' + formatRp(activeOrder.discount_cents)"></span>
                        </div>
                    </template>
                    <template x-if="activeOrder?.service_fee_cents > 0">
                        <div class="flex justify-between text-xs text-slate-600">
                            <span>Biaya Antar ke Meja</span>
                            <span class="font-semibold text-slate-800" x-text="formatRp(activeOrder.service_fee_cents)"></span>
                        </div>
                    </template>
                    <template x-if="activeOrder?.tax_cents > 0">
                        <div class="flex justify-between text-xs text-slate-600">
                            <span x-text="taxLabel"></span>
                            <span class="font-semibold text-slate-800" x-text="formatRp(activeOrder.tax_cents)"></span>
                        </div>
                    </template>

                    <div class="pt-2 border-t border-slate-100 flex justify-between text-sm font-extrabold text-slate-900">
                        <span>Total Bayar</span>
                        <span class="text-[#2E8B89]" x-text="formatRp(activeOrder?.total_cents || 0)"></span>
                    </div>
                </div>

                {{-- Bottom Actions --}}
                <div class="mt-auto pt-2 space-y-2.5">
                    <button type="button" @click="printCustomerReceipt()"
                            class="w-full py-3 px-4 rounded-2xl bg-white border border-slate-200 text-slate-800 font-bold text-xs shadow-2xs hover:bg-slate-50 active:scale-[0.98] transition flex items-center justify-center gap-2">
                        <svg class="size-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        <span>Cetak Struk Pembayaran (80 mm)</span>
                    </button>
                    <button type="button" @click="resetForNewOrder()"
                            class="w-full py-3.5 rounded-2xl bg-slate-900 text-white font-bold text-sm shadow-sm hover:bg-slate-800 active:scale-95 transition">
                        + Pesan Menu Tambahan di Meja Ini
                    </button>
                    <p class="text-[11px] text-center text-slate-400">Pesanan tambahan akan membentuk tagihan baru tanpa mengubah pesanan sebelumnya.</p>
                </div>
            </section>

            {{-- ================= FLOATING CART BAR (WHEN IN MENU) ================= --}}
            <div x-show="currentScreen === 'menu' && cartCount > 0"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="translate-y-full"
                 x-transition:enter-end="translate-y-0"
                 class="fixed bottom-16 left-0 right-0 max-w-md mx-auto p-4 z-40 bg-gradient-to-t from-white/95 via-white/80 to-transparent pointer-events-none">
                <button type="button" @click="currentScreen = 'cart'"
                        class="w-full p-4 rounded-2xl bg-gradient-to-r from-[#014D38] to-[#04684B] text-white shadow-xl shadow-[#014D38]/30 flex items-center justify-between active:scale-[0.99] transition pointer-events-auto">
                    <div class="flex items-center gap-3">
                        <span class="size-8 rounded-xl bg-white/20 font-extrabold text-xs grid place-items-center" x-text="cartCount"></span>
                        <div class="text-left">
                            <p class="text-[11px] text-emerald-100 font-medium">Total Pesanan</p>
                            <p class="text-base font-extrabold" x-text="formatRp(cartTotal)"></p>
                        </div>
                    </div>
                    <span class="text-xs font-bold flex items-center gap-1.5 bg-white/20 px-3 py-1.5 rounded-xl">
                        <span>Lihat Keranjang</span>
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </span>
                </button>
            </div>

            {{-- ================= PESANAN SAYA (MY ORDERS DRAWER / MODAL) ================= --}}
            <div x-show="myOrdersOpen && isRegistered && customerName" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 backdrop-blur-2xs"
                 @click.self="myOrdersOpen = false">
                <div x-show="myOrdersOpen && isRegistered && customerName"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="translate-y-full sm:scale-95 sm:opacity-0"
                     x-transition:enter-end="translate-y-0 sm:scale-100 sm:opacity-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="translate-y-0 sm:scale-100 sm:opacity-100"
                     x-transition:leave-end="translate-y-full sm:scale-95 sm:opacity-0"
                     class="w-full max-w-md bg-white rounded-t-3xl sm:rounded-3xl p-5 shadow-2xl max-h-[88vh] flex flex-col">
                    
                    {{-- Header --}}
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2.5">
                            <div class="size-9 rounded-xl bg-[#4CB0AE]/15 text-[#2E8B89] grid place-items-center">
                                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-black text-slate-900">Pesanan &amp; Riwayat Saya</h3>
                                <p class="text-[11px] text-slate-500">Lacak proses pesanan meja &amp; riwayat belanja Anda</p>
                            </div>
                        </div>
                        <button type="button" @click="myOrdersOpen = false" class="size-8 rounded-full bg-slate-100 text-slate-500 hover:text-slate-800 grid place-items-center font-bold text-base">&times;</button>
                    </div>

                    {{-- Drawer Tab Switcher --}}
                    <div class="flex items-center p-1 rounded-xl bg-slate-100 mt-3 text-xs font-bold">
                        <button type="button" @click="orderDrawerTab = 'active'"
                                :class="orderDrawerTab === 'active' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                                class="flex-1 py-1.5 rounded-lg transition text-center flex items-center justify-center gap-1.5">
                            <span>Pesanan Meja Ini</span>
                            <span class="text-[10px] px-1.5 py-0.2 rounded-full"
                                  :class="orderDrawerTab === 'active' ? 'bg-[#4CB0AE]/15 text-[#2E8B89]' : 'bg-slate-200 text-slate-600'"
                                  x-text="myOrdersList.length"></span>
                        </button>
                        <button type="button" @click="orderDrawerTab = 'history'"
                                :class="orderDrawerTab === 'history' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                                class="flex-1 py-1.5 rounded-lg transition text-center flex items-center justify-center gap-1.5">
                            <span>Riwayat Akun</span>
                            <span class="text-[10px] px-1.5 py-0.2 rounded-full"
                                  :class="orderDrawerTab === 'history' ? 'bg-[#4CB0AE]/15 text-[#2E8B89]' : 'bg-slate-200 text-slate-600'"
                                  x-text="accountOrdersList.length"></span>
                        </button>
                    </div>

                    {{-- Body Content --}}
                    <div class="flex-1 overflow-y-auto py-3 space-y-3">
                        <template x-if="loadingMyOrders || loadingAccountOrders">
                            <div class="py-12 text-center text-slate-400">
                                <svg class="animate-spin size-7 text-[#4CB0AE] mx-auto mb-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <p class="text-xs font-medium">Memuat data pesanan...</p>
                            </div>
                        </template>

                        {{-- ============ TAB 1: PESANAN MEJA INI (ACTIVE SESSION) ============ --}}
                        <template x-if="!loadingMyOrders && !loadingAccountOrders && orderDrawerTab === 'active'">
                            <div>
                                <template x-if="myOrdersList.length === 0">
                                    <div class="py-10 text-center text-slate-400 px-4">
                                        <div class="size-16 rounded-2xl bg-slate-100 text-slate-400 grid place-items-center mx-auto mb-3">
                                            <svg class="size-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                        </div>
                                        <p class="text-sm font-bold text-slate-700">Belum Ada Pesanan di Meja Ini</p>
                                        <p class="text-xs text-slate-500 mt-1">Anda belum memesan menu di meja ini pada sesi saat ini.</p>
                                        <button type="button" @click="myOrdersOpen = false; currentScreen = 'menu'" class="mt-3 px-4 py-2 rounded-xl bg-[#4CB0AE] text-white text-xs font-bold shadow-xs">
                                            Pilih Menu Sekarang
                                        </button>
                                    </div>
                                </template>

                                <template x-if="myOrdersList.length > 0">
                                    <div class="space-y-3">
                                        <template x-for="ord in myOrdersList" :key="ord.id">
                                            <div class="border rounded-2xl p-3.5 transition shadow-2xs hover:shadow-xs"
                                                 :class="['New', 'Accepted', 'Preparing', 'Ready'].includes(ord.fulfillment_status) ? 'border-[#4CB0AE]/40 bg-[#4CB0AE]/5' : 'border-slate-200 bg-white'">
                                                
                                                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                                                    <div>
                                                        <div class="flex items-center gap-1.5">
                                                            <span class="font-extrabold text-xs text-slate-900" x-text="ord.order_code || ('#' + ord.id.slice(0, 8))"></span>
                                                            <span class="text-[10px] px-2 py-0.5 rounded-full font-bold"
                                                                  :class="ord.delivery_type === 'counter' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'"
                                                                  x-text="ord.delivery_type === 'counter' ? 'Ambil di Kasir' : 'Diantar ke Meja'">
                                                            </span>
                                                        </div>
                                                        <p class="text-[10px] text-slate-400 mt-0.5" x-text="formatDate(ord.created_at)"></p>
                                                    </div>
                                                    <div class="text-right">
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold"
                                                              :class="getFulfillmentBadgeClass(ord.fulfillment_status)">
                                                            <span class="size-1.5 rounded-full"
                                                                  :class="['New', 'Accepted', 'Preparing', 'Ready'].includes(ord.fulfillment_status) ? 'bg-current animate-pulse' : 'bg-current'"></span>
                                                            <span x-text="getFulfillmentShortLabel(ord.fulfillment_status)"></span>
                                                        </span>
                                                    </div>
                                                </div>

                                                <div class="py-2 text-xs text-slate-600 space-y-1">
                                                    <template x-for="item in (ord.items || [])" :key="item.id">
                                                        <div class="flex justify-between items-center">
                                                            <span class="truncate pr-2"><span x-text="item.quantity + 'x '" class="font-bold"></span><span x-text="item.name"></span></span>
                                                            <span class="shrink-0 font-medium text-slate-800" x-text="formatRp(item.unit_price_cents * item.quantity)"></span>
                                                        </div>
                                                    </template>
                                                </div>

                                                <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                                                    <div>
                                                        <span class="text-[10px] text-slate-400 block">Total Pembayaran</span>
                                                        <span class="text-xs font-black text-[#2E8B89]" x-text="formatRp(ord.total_cents)"></span>
                                                    </div>
                                                    <button type="button" @click="trackSelectedOrder(ord)"
                                                            class="px-3.5 py-1.5 rounded-xl bg-[#4CB0AE] hover:bg-[#3BA09E] text-white text-xs font-bold active:scale-95 transition flex items-center gap-1 shadow-2xs">
                                                        <span>Lacak Status</span>
                                                        <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>

                        {{-- ============ TAB 2: RIWAYAT SEMUA PESANAN AKUN (ACCOUNT HISTORY) ============ --}}
                        <template x-if="!loadingMyOrders && !loadingAccountOrders && orderDrawerTab === 'history'">
                            <div>
                                <template x-if="!isRegistered || !customerName">
                                    <div class="p-6 text-center bg-slate-50 rounded-2xl border border-slate-200/80">
                                        <div class="size-12 rounded-xl bg-amber-100 text-amber-700 grid place-items-center mx-auto mb-2.5">
                                            <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                        </div>
                                        <h4 class="text-xs font-bold text-slate-800">Masuk untuk Melihat Riwayat</h4>
                                        <p class="text-[11px] text-slate-500 mt-1 mb-3">Seluruh riwayat pesanan terdahulu Anda akan tampil setelah masuk dengan nomor HP terdaftar.</p>
                                        <button type="button" @click="myOrdersOpen = false; currentScreen = 'landing'; authMode = 'login'"
                                                class="px-4 py-2 rounded-xl bg-[#4CB0AE] text-white font-bold text-xs shadow-xs">
                                            Masuk ke Akun Saya
                                        </button>
                                    </div>
                                </template>

                                <template x-if="isRegistered && customerName && accountOrdersList.length === 0">
                                    <div class="py-10 text-center text-slate-400 px-4">
                                        <div class="size-16 rounded-2xl bg-slate-100 text-slate-400 grid place-items-center mx-auto mb-3">
                                            <svg class="size-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </div>
                                        <p class="text-sm font-bold text-slate-700">Belum Ada Riwayat Pesanan</p>
                                        <p class="text-xs text-slate-500 mt-1">Akun <span class="font-semibold text-slate-700" x-text="customerName"></span> belum memiliki catatan transaksi pesanan sebelumnya.</p>
                                    </div>
                                </template>

                                <template x-if="isRegistered && customerName && accountOrdersList.length > 0">
                                    <div class="space-y-3">
                                        <template x-for="ord in accountOrdersList" :key="ord.id">
                                            <div class="border border-slate-200/90 bg-white rounded-2xl p-3.5 shadow-2xs hover:shadow-xs transition">
                                                
                                                <div class="flex items-start justify-between pb-2 border-b border-slate-100">
                                                    <div>
                                                        <div class="flex items-center gap-1.5 flex-wrap">
                                                            <span class="font-extrabold text-xs text-slate-900" x-text="ord.order_code || ('#' + ord.id.slice(0, 8))"></span>
                                                            <span class="text-[10px] px-1.5 py-0.2 rounded font-bold bg-slate-100 text-slate-700" x-text="ord.table_label_snapshot || 'Meja'"></span>
                                                            <span class="text-[10px] px-1.5 py-0.2 rounded font-bold"
                                                                  :class="ord.delivery_type === 'counter' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'"
                                                                  x-text="ord.delivery_type === 'counter' ? 'Ambil di Kasir' : 'Antar Meja'">
                                                            </span>
                                                        </div>
                                                        <p class="text-[10px] text-slate-400 mt-1" x-text="formatDate(ord.created_at)"></p>
                                                    </div>
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold shrink-0"
                                                          :class="getFulfillmentBadgeClass(ord.fulfillment_status)">
                                                        <span x-text="getFulfillmentShortLabel(ord.fulfillment_status)"></span>
                                                    </span>
                                                </div>

                                                <div class="py-2 text-xs text-slate-600 space-y-1">
                                                    <template x-for="item in (ord.items || [])" :key="item.id">
                                                        <div class="flex justify-between items-center text-[11.5px]">
                                                            <span class="truncate pr-2"><span x-text="item.quantity + 'x '" class="font-bold"></span><span x-text="item.name"></span></span>
                                                            <span class="shrink-0 font-medium text-slate-800" x-text="formatRp(item.unit_price_cents * item.quantity)"></span>
                                                        </div>
                                                    </template>
                                                </div>

                                                <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                                                    <div>
                                                        <span class="text-[10px] text-slate-400 block">Total Tagihan</span>
                                                        <span class="text-xs font-black text-[#2E8B89]" x-text="formatRp(ord.total_cents)"></span>
                                                    </div>
                                                    <button type="button" @click="trackSelectedOrder(ord)"
                                                            class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-[#4CB0AE] hover:text-white text-slate-700 text-xs font-bold active:scale-95 transition flex items-center gap-1">
                                                        <span>Lihat Struk / Lacak</span>
                                                        <svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                                    </button>
                                                </div>

                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    {{-- Footer --}}
                    <div class="pt-3 border-t border-slate-100">
                        <button type="button" @click="myOrdersOpen = false"
                                class="w-full py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>

            {{-- ================= PRODUCT CUSTOMIZER MODAL ================= --}}
            <div x-show="customizerOpen" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 backdrop-blur-2xs"
                 @click.self="customizerOpen = false">
                <div x-show="customizerOpen"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="translate-y-full"
                     x-transition:enter-end="translate-y-0"
                     class="w-full max-w-md bg-white rounded-t-3xl p-5 shadow-2xl space-y-4">
                    <div class="flex items-start gap-3.5">
                        <img :src="selectedProduct?.image_url || ('/images/products/' + selectedProduct?.id + '.jpg')"
                             :alt="selectedProduct?.name"
                             class="size-16 rounded-2xl object-cover shrink-0 border border-slate-100 shadow-2xs"
                             onerror="this.src='/images/products/p-cappuccino.jpg'">
                        <div class="flex-1 min-w-0">
                            <h3 class="text-base font-bold text-slate-900 truncate" x-text="selectedProduct?.name"></h3>
                            <p class="text-xs text-[#2E8B89] font-bold mt-0.5" x-text="formatRp(selectedProduct?.price_cents || 0)"></p>
                            <p class="text-[11px] text-slate-500 line-clamp-2 mt-0.5" x-text="selectedProduct?.description"></p>
                        </div>
                        <button type="button" @click="customizerOpen = false" class="size-8 rounded-full bg-slate-100 text-slate-500 grid place-items-center font-bold shrink-0">&times;</button>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1.5">Catatan Khusus (Opsional):</label>
                        <input type="text" x-model="customizerNotes" placeholder="Misal: Less sugar, es sedikit, pisahkan sambal..."
                               class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:ring-2 focus:ring-[#4CB0AE]">
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <span class="text-xs font-semibold text-slate-600">Jumlah:</span>
                        <div class="flex items-center gap-3">
                            <button type="button" @click="customizerQty = Math.max(1, customizerQty - 1)" class="size-8 rounded-xl bg-slate-100 font-bold text-slate-700 grid place-items-center active:scale-95 text-sm">-</button>
                            <span class="w-6 text-center text-sm font-bold text-slate-900" x-text="customizerQty"></span>
                            <button type="button" @click="customizerQty++" class="size-8 rounded-xl bg-[#4CB0AE] text-white font-bold grid place-items-center active:scale-95 text-sm">+</button>
                        </div>
                    </div>

                    <button type="button" @click="addCustomizedToCart()"
                            class="w-full py-3.5 rounded-2xl bg-[#014D38] text-white font-bold text-sm shadow-md hover:bg-[#04684B] active:scale-95 transition">
                        Tambahkan ke Pesanan · <span x-text="formatRp((selectedProduct?.price_cents || 0) * customizerQty)"></span>
                    </button>
                </div>
            </div>

            {{-- ================= VOUCHER & PROMO MODAL ================= --}}
            <div x-show="loyaltyModalOpen" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 backdrop-blur-2xs"
                 @click.self="loyaltyModalOpen = false">
                <div x-show="loyaltyModalOpen"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="translate-y-full sm:scale-95 sm:opacity-0"
                     x-transition:enter-end="translate-y-0 sm:scale-100 sm:opacity-100"
                     class="w-full max-w-[430px] bg-white rounded-t-3xl sm:rounded-3xl p-5 shadow-2xl max-h-[85vh] flex flex-col font-['Figtree',sans-serif]">
                    
                    {{-- Modal Header --}}
                    <div class="flex items-center justify-between pb-3 border-b border-[var(--line)]">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-[var(--fill)] text-[var(--brand)] grid place-items-center shrink-0">
                                <svg class="ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M13 5v2"/><path d="M13 17v2"/><path d="M13 11v2"/></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-[var(--ink)] leading-tight">Voucher & Promo Hemat</h3>
                                <p class="text-[12.5px] text-[var(--ink-2)] mt-0.5">Pilih voucher untuk potongan otomatis pada pesanan</p>
                            </div>
                        </div>
                        <button type="button" @click="loyaltyModalOpen = false" class="w-8 h-8 rounded-full bg-[var(--fill)] hover:bg-[#E3E7E4] text-[var(--ink-2)] hover:text-[var(--ink)] grid place-items-center text-lg font-bold transition" aria-label="Tutup modal">
                            &times;
                        </button>
                    </div>

                    {{-- Active Voucher Banner --}}
                    <template x-if="appliedVoucherCode">
                        <div class="my-3 rounded-2xl p-3.5 text-white shadow-xs flex items-center justify-between"
                             style="background: var(--brand);">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-8 h-8 rounded-xl bg-white/20 grid place-items-center text-sm font-black shrink-0">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <span class="text-[10px] text-white/80 uppercase font-bold tracking-wider block">VOUCHER TERPASANG</span>
                                    <span class="text-xs font-bold truncate max-w-[210px] block" x-text="appliedVoucherLabel"></span>
                                </div>
                            </div>
                            <button type="button" @click="removeVoucher()" class="shrink-0 text-xs font-bold bg-white/20 hover:bg-white/30 text-white px-3.5 py-1.5 rounded-full transition active:scale-95">
                                Lepas
                            </button>
                        </div>
                    </template>

                    {{-- Voucher List (HANYA SATU: VOUCHER 20RB PENGGUNA PERTAMA) --}}
                    <div class="flex-1 overflow-y-auto space-y-3 py-2 pr-0.5">
                        <div class="border-2 border-amber-300 bg-white rounded-2xl p-4 flex items-center justify-between gap-3 shadow-xs">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5 mb-1.5 flex-wrap">
                                    <span class="text-[10px] font-bold text-[#8C6200] bg-[#FDF0CE] px-2.5 py-1 rounded-lg inline-block tracking-wide">
                                        ⭐ UTAMA · PENGGUNA PERTAMA
                                    </span>
                                    <span class="text-[10px] font-bold text-[#0A5A42] bg-[#D7F5E7] px-2.5 py-1 rounded-lg inline-block tracking-wide">
                                        Diskon Otomatis
                                    </span>
                                </div>
                                <h4 class="text-[15px] font-bold text-[var(--ink)] leading-snug">Semua Produk Jadi Rp 20.000</h4>
                                <p class="text-[12px] text-[var(--ink-2)] mt-1 leading-snug">Khusus pesanan pertama. Semua minuman otomatis dihitung Rp 20rb per cup.</p>
                            </div>
                            <div class="shrink-0">
                                <template x-if="appliedVoucherCode === 'NEW_USER_20K'">
                                    <button type="button"
                                            @click="removeVoucher()"
                                            class="px-4 py-2 rounded-full text-xs font-bold transition active:scale-95 text-white flex items-center gap-1 shadow-xs"
                                            style="background: var(--brand);">
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                        <span>Dipakai</span>
                                    </button>
                                </template>
                                <template x-if="appliedVoucherCode !== 'NEW_USER_20K'">
                                    <button type="button"
                                            @click="applyVoucher('NEW_USER_20K')"
                                            class="px-4 py-2 rounded-full text-xs font-bold transition active:scale-95 text-white shadow-xs"
                                            style="background: var(--brand);">
                                        <span>Pakai</span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Close Button --}}
                    <div class="pt-3 border-t border-[var(--line)] mt-2">
                        <button type="button" @click="loyaltyModalOpen = false" class="w-full py-3 rounded-xl bg-[var(--fill)] hover:bg-[#E3E7E4] text-[var(--ink)] font-semibold text-sm transition">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>

            {{-- ================= ACCOUNT & AUTH MODAL / DRAWER ================= --}}
            <div x-show="accountDrawerOpen" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/60 backdrop-blur-2xs"
                 @click.self="accountDrawerOpen = false">
                <div x-show="accountDrawerOpen"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="translate-y-full sm:scale-95 sm:opacity-0"
                     x-transition:enter-end="translate-y-0 sm:scale-100 sm:opacity-100"
                     class="w-full max-w-md bg-white rounded-t-3xl sm:rounded-3xl p-5 shadow-2xl max-h-[90vh] flex flex-col">
                    
                    {{-- Header --}}
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2.5">
                            <div class="size-9 rounded-xl bg-[#014D38]/10 text-[#014D38] grid place-items-center font-bold">
                                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-black text-slate-900 leading-tight" x-text="isRegistered && customerName ? 'Profil Akun' : 'Masuk POSYFRIENDS'"></h3>
                                <p class="text-[11px] text-slate-400" x-text="isRegistered && customerName ? 'Kelola akun & riwayat pesanan Anda' : 'Login atau daftar akun member'"></p>
                            </div>
                        </div>
                        <button type="button" @click="accountDrawerOpen = false" class="size-8 rounded-full bg-slate-100 text-slate-500 hover:text-slate-800 grid place-items-center font-bold text-lg">&times;</button>
                    </div>

                    {{-- Body: If Logged In --}}
                    <template x-if="isRegistered && customerName">
                        <div class="py-4 space-y-4">
                            {{-- Member Card --}}
                            <div class="bg-gradient-to-br from-[#014D38] via-[#025E45] to-[#04684B] rounded-2xl p-4 text-white shadow-lg shadow-[#014D38]/20 relative overflow-hidden">
                                <div class="absolute -right-4 -bottom-4 size-24 rounded-full bg-white/5 pointer-events-none"></div>
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-[10px] font-black uppercase tracking-widest bg-white/20 px-2.5 py-0.5 rounded-full">POSYFRIENDS MEMBER</span>
                                    <span class="text-xs font-bold text-emerald-200">MEMBER AKTIF</span>
                                </div>
                                <h4 class="text-lg font-black tracking-tight" x-text="customerName"></h4>
                                <div class="mt-2 pt-2 border-t border-white/10 text-xs text-emerald-100/90 space-y-0.5">
                                    <p class="flex items-center gap-1.5">
                                        <svg class="size-3.5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                        <span x-text="customerPhone"></span>
                                    </p>
                                    <template x-if="customerEmail">
                                        <p class="flex items-center gap-1.5">
                                            <svg class="size-3.5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                            <span x-text="customerEmail"></span>
                                        </p>
                                    </template>
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="space-y-2">
                                <button type="button" @click="accountDrawerOpen = false; handlePesananTab()"
                                        class="w-full p-3 rounded-2xl border border-slate-200 bg-white hover:border-[#014D38] hover:bg-[#014D38]/5 transition flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="size-8 rounded-xl bg-slate-100 text-[#014D38] grid place-items-center">
                                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                        </div>
                                        <div class="text-left">
                                            <span class="text-xs font-bold text-slate-900 block">Riwayat Pesanan</span>
                                            <span class="text-[11px] text-slate-400">Lihat semua pesanan sebelumnya</span>
                                        </div>
                                    </div>
                                    <svg class="size-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>

                                <button type="button" @click="accountDrawerOpen = false; openLoyaltyModal()"
                                        class="w-full p-3 rounded-2xl border border-slate-200 bg-white hover:border-[#014D38] hover:bg-[#014D38]/5 transition flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="size-8 rounded-xl bg-amber-50 text-amber-700 grid place-items-center">
                                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                                        </div>
                                        <div class="text-left">
                                            <span class="text-xs font-bold text-slate-900 block">Voucher & Promo Saya</span>
                                            <span class="text-[11px] text-slate-400">Voucher 20RB pengguna pertama aktif</span>
                                        </div>
                                    </div>
                                    <svg class="size-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>

                            {{-- Logout Button --}}
                            <div class="pt-2">
                                <button type="button" @click="accountDrawerOpen = false; logoutCustomer()"
                                        class="w-full py-3 rounded-xl border border-rose-200 bg-rose-50/70 text-rose-600 font-bold text-xs hover:bg-rose-100 transition flex items-center justify-center gap-1.5">
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    <span>Keluar dari Akun</span>
                                </button>
                            </div>
                        </div>
                    </template>

                    {{-- Body: If Not Logged In (Auth Form) --}}
                    <template x-if="!isRegistered || !customerName">
                        <div class="flex-1 overflow-y-auto py-3 space-y-4">
                            {{-- Tab Switcher --}}
                            <div class="flex items-center p-1 rounded-xl bg-slate-100 text-xs font-bold">
                                <button type="button" @click="authMode = 'login'"
                                        :class="authMode === 'login' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                                        class="flex-1 py-2 rounded-lg transition text-center">
                                    Masuk (Login)
                                </button>
                                <button type="button" @click="authMode = 'register'"
                                        :class="authMode === 'register' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                                        class="flex-1 py-2 rounded-lg transition text-center">
                                    Daftar Baru
                                </button>
                            </div>

                            {{-- Subform: Login --}}
                            <div x-show="authMode === 'login'" class="space-y-3">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Nomor HP / WhatsApp</label>
                                    <input type="tel" x-model="loginPhone" placeholder="08xxxxxxxxxx"
                                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-[#014D38] focus:border-[#014D38] transition">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Kata Sandi</label>
                                    <div class="relative">
                                        <input :type="showLoginPassword ? 'text' : 'password'" x-model="loginPassword" placeholder="Masukkan kata sandi"
                                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-[#014D38] focus:border-[#014D38] transition pr-10">
                                        <button type="button" @click="showLoginPassword = !showLoginPassword" class="absolute right-3 top-2.5 text-slate-400 text-xs">
                                            <span x-text="showLoginPassword ? 'Sembunyi' : 'Lihat'"></span>
                                        </button>
                                    </div>
                                </div>
                                <button type="button" @click="submitLogin()" :disabled="isSubmitting"
                                        class="w-full py-3 rounded-xl bg-[#014D38] text-white font-bold text-xs shadow-md shadow-[#014D38]/20 hover:bg-[#04684B] active:scale-95 transition disabled:opacity-50">
                                    <span x-show="!isSubmitting">Masuk Sekarang</span>
                                    <span x-show="isSubmitting">Memproses...</span>
                                </button>
                            </div>

                            {{-- Subform: Register (Email is strictly mandatory) --}}
                            <div x-show="authMode === 'register'" class="space-y-3">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap</label>
                                    <input type="text" x-model="customerName" placeholder="Contoh: Rian Anggara"
                                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-[#014D38] focus:border-[#014D38] transition">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Nomor HP / WhatsApp</label>
                                    <input type="tel" x-model="customerPhone" placeholder="08xxxxxxxxxx"
                                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-[#014D38] focus:border-[#014D38] transition">
                                </div>
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-xs font-bold text-slate-700">Alamat Email</label>
                                        <span class="text-[10px] text-rose-500 font-bold">*Wajib diisi</span>
                                    </div>
                                    <input type="email" x-model="customerEmail" placeholder="nama@email.com" required
                                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-[#014D38] focus:border-[#014D38] transition">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Kata Sandi Baru</label>
                                    <div class="relative">
                                        <input :type="showRegisterPassword ? 'text' : 'password'" x-model="customerPassword" placeholder="Minimal 4 karakter"
                                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-[#014D38] focus:border-[#014D38] transition pr-10">
                                        <button type="button" @click="showRegisterPassword = !showRegisterPassword" class="absolute right-3 top-2.5 text-slate-400 text-xs">
                                            <span x-text="showRegisterPassword ? 'Sembunyi' : 'Lihat'"></span>
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Konfirmasi Kata Sandi</label>
                                    <input :type="showRegisterPassword ? 'text' : 'password'" x-model="customerPasswordConfirm" placeholder="Ketik ulang kata sandi"
                                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-[#014D38] focus:border-[#014D38] transition">
                                </div>
                                <button type="button" @click="submitRegistration()" :disabled="isSubmitting"
                                        class="w-full py-3 rounded-xl bg-[#014D38] text-white font-bold text-xs shadow-md shadow-[#014D38]/20 hover:bg-[#04684B] active:scale-95 transition disabled:opacity-50">
                                    <span x-show="!isSubmitting">Daftar Akun Member</span>
                                    <span x-show="isSubmitting">Memproses...</span>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- ================= TABLE PICKER MODAL (FOR DELIVERY OPTION) ================= --}}
            <div x-show="tablePickerModalOpen" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/60 backdrop-blur-2xs"
                 @click.self="tablePickerModalOpen = false">
                <div x-show="tablePickerModalOpen"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="translate-y-full sm:scale-95 sm:opacity-0"
                     x-transition:enter-end="translate-y-0 sm:scale-100 sm:opacity-100"
                     class="w-full max-w-md bg-white rounded-t-3xl sm:rounded-3xl p-5 shadow-2xl max-h-[85vh] flex flex-col">
                    
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2.5">
                            <div class="size-9 rounded-xl bg-orange-100 text-orange-700 grid place-items-center font-bold">
                                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-black text-slate-900 leading-tight">Pilih Meja untuk Diantar</h3>
                                <p class="text-[11px] text-slate-400">Pesanan akan diantarkan langsung ke meja Anda</p>
                            </div>
                        </div>
                        <button type="button" @click="tablePickerModalOpen = false" class="size-8 rounded-full bg-slate-100 text-slate-500 hover:text-slate-800 grid place-items-center font-bold text-lg">&times;</button>
                    </div>

                    <div class="flex-1 overflow-y-auto py-4 space-y-3.5">
                        <template x-if="availableTables.length > 0">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-2">Pilih dari Daftar Meja:</label>
                                <div class="grid grid-cols-4 gap-2">
                                    <template x-for="tbl in availableTables" :key="tbl.id">
                                        <button type="button" @click="confirmTablePicker(tbl)"
                                                :class="selectedTable?.id === tbl.id ? 'bg-[#014D38] text-white font-bold ring-2 ring-[#014D38]/30' : 'bg-slate-50 border border-slate-200 text-slate-700 hover:bg-slate-100'"
                                                class="py-2.5 px-2 rounded-xl text-xs text-center transition">
                                            <span x-text="tbl.label || ('Meja ' + tbl.number)"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Atau Masukkan Nomor Meja Anda:</label>
                            <div class="flex items-center gap-2">
                                <input type="text" x-model="manualTableNumber" placeholder="Contoh: 12"
                                       class="flex-1 px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-[#014D38] focus:border-[#014D38]">
                                <button type="button" @click="confirmTablePicker(null)"
                                        class="px-5 py-2.5 rounded-xl bg-[#014D38] text-white font-bold text-xs shadow-xs hover:bg-[#04684B] active:scale-95 transition">
                                    Konfirmasi
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-slate-100">
                        <button type="button" @click="tablePickerModalOpen = false" class="w-full py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition">
                            Batal
                        </button>
                    </div>
                </div>
            </div>

            {{-- Floating Cart Quick-Bar on Menu Screen --}}
            <div x-show="currentScreen === 'menu' && cart.length > 0"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="fixed bottom-[68px] left-1/2 -translate-x-1/2 z-30 w-[92%] max-w-[398px]">
                <button type="button" @click="currentScreen = 'cart'"
                        class="w-full py-3.5 px-4 rounded-2xl bg-[#0A5A42] text-white font-bold text-sm shadow-xl flex items-center justify-between active:scale-[0.99] transition">
                    <div class="flex items-center gap-2.5">
                        <span class="size-6 rounded-full bg-white/20 grid place-items-center text-xs font-black" x-text="cartCount"></span>
                        <span x-text="formatRp(cartSubtotal)"></span>
                    </div>
                    <span class="flex items-center gap-1 text-xs font-black">
                        <span>Lihat Pesanan</span>
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                    </span>
                </button>
            </div>

            {{-- ================= BOTTOM TAB BAR (NAVIGASI UTAMA) ================= --}}
            <nav class="tabbar" aria-label="Navigasi utama">
                <button type="button" class="tab" :aria-current="currentScreen === 'landing' ? 'page' : null" @click="currentScreen = 'landing'">
                    <svg class="ico" viewBox="0 0 24 24" aria-hidden="true"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/></svg>
                    Home
                </button>
                <button type="button" class="tab" @click="openLoyaltyModal()">
                    <svg class="ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M13 5v2"/><path d="M13 17v2"/><path d="M13 11v2"/></svg>
                    Voucher
                </button>
                <button type="button" class="tab" :aria-current="currentScreen === 'order_tracking' ? 'page' : null" @click="handlePesananTab()">
                    <svg class="ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M14 8H8"/><path d="M16 12H8"/><path d="M13 16H8"/></svg>
                    Pesanan
                </button>
                <button type="button" class="tab" @click="handleAkunTab()">
                    <svg class="ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Akun
                </button>
            </nav>

        </div>
    </template>

</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('guestTableOrder', (initData) => ({
        token: initData.token,
        isValid: initData.isValid,
        errorCode: initData.errorCode,
        errorMessage: initData.errorMessage,
        table: initData.table,
        storeName: initData.storeName,
        currentScreen: initData.initialOrderId ? 'order_tracking' : 'landing',

        isUniversalPortal: initData.isUniversalPortal || false,
        availableTables: initData.availableTables || [],
        selectedTable: initData.table || null,
        manualTableNumber: initData.table?.label ? initData.table.label.replace(/^Meja\s*/i, '').trim() : '',
        selectedTableSelect: initData.table?.id || '',

        products: [],
        categories: [],
        selectedCategory: 'all',
        searchQuery: '',

        cart: [],
        customerId: '',
        customerName: '',
        customerPhone: '',
        customerEmail: '',
        isRegistered: false,

        authMode: 'login',
        loginPhone: '',
        loginPassword: '',
        showLoginPassword: false,
        customerPassword: '',
        customerPasswordConfirm: '',
        showRegisterPassword: false,
        orderDrawerTab: 'active',
        accountOrdersList: [],
        loadingAccountOrders: false,

        activeBannerIdx: 0,
        promoBanners: [
            { image: '/images/banners/promo_new_user_20k.png', title: 'Spesial 20RB Pengguna Pertama' },
            { image: '/images/banners/promo_buttercream.jpg', title: 'Butter Cream Temptations' },
            { image: '/images/banners/promo_package.jpg', title: 'Coffee Package Diskon 50%' }
        ],

        handleBannerClick(idx) {
            if (idx === 0) {
                this.applyVoucher('NEW_USER_20K');
                this.openLoyaltyModal();
            } else if (idx === 1) {
                this.searchQuery = 'buttercream';
                this.currentScreen = 'menu';
            } else {
                this.selectedCategory = 'all';
                this.searchQuery = '';
                this.currentScreen = 'menu';
            }
        },
        loyaltyModalOpen: false,
        accountDrawerOpen: false,
        tablePickerModalOpen: false,

        // Voucher state (First voucher provided is 20k flat for new user)
        appliedVoucherCode: 'NEW_USER_20K',
        appliedVoucherLabel: 'Semua Produk Jadi 20RB (Pengguna Pertama)',

        applyVoucher(code = 'NEW_USER_20K') {
            this.appliedVoucherCode = 'NEW_USER_20K';
            this.appliedVoucherLabel = 'Semua Produk Jadi 20RB (Pengguna Pertama)';
            this.showToast('Voucher Semua Produk Jadi 20RB diterapkan!', 'success');
        },

        removeVoucher() {
            this.appliedVoucherCode = '';
            this.appliedVoucherLabel = '';
            this.showToast('Voucher telah dilepas.', 'info');
        },

        get greetingText() {
            const hour = new Date().getHours();
            if (hour >= 4 && hour < 11) return 'Selamat Pagi,';
            if (hour >= 11 && hour < 15) return 'Selamat Siang,';
            if (hour >= 15 && hour < 18) return 'Selamat Sore,';
            return 'Selamat Malam,';
        },

        nextBanner() {
            this.activeBannerIdx = (this.activeBannerIdx + 1) % this.promoBanners.length;
        },

        prevBanner() {
            this.activeBannerIdx = (this.activeBannerIdx - 1 + this.promoBanners.length) % this.promoBanners.length;
        },

        selectPickUp() {
            this.deliveryType = 'counter';
            this.currentScreen = 'menu';
        },

        selectDelivery() {
            this.deliveryType = 'table';
            if (this.isUniversalPortal && !this.selectedTable && !this.manualTableNumber) {
                this.tablePickerModalOpen = true;
            } else {
                this.currentScreen = 'menu';
            }
        },

        confirmTablePicker(tbl) {
            if (tbl) {
                this.selectedTable = tbl;
                this.selectedTableSelect = tbl.id;
                this.manualTableNumber = tbl.label ? tbl.label.replace(/^Meja\s*/i, '').trim() : '';
            }
            this.tablePickerModalOpen = false;
            this.currentScreen = 'menu';
        },

        openTablePicker() {
            this.tablePickerModalOpen = true;
        },

        get timeGreeting() {
            const h = new Date().getHours();
            const s = h < 4 ? 'malam' : h < 11 ? 'pagi' : h < 15 ? 'siang' : h < 18 ? 'sore' : 'malam';
            return 'Selamat ' + s + ',';
        },

        handleNotifClick() {
            this.accountDrawerOpen = true;
        },

        handlePesananTab() {
            if (this.activeOrder) {
                this.currentScreen = 'order_tracking';
                return;
            }
            if (this.isRegistered && this.customerName) {
                this.loadAccountOrders();
                this.loadRecentOrdersSummary();
                this.orderDrawerTab = 'active';
                this.myOrdersOpen = true;
            } else {
                this.authMode = 'login';
                this.accountDrawerOpen = true;
                this.showToast('Silakan login untuk melihat riwayat pesanan Anda.', 'info');
            }
        },

        handleAkunTab() {
            this.accountDrawerOpen = true;
        },

        openLoyaltyModal() {
            this.loyaltyModalOpen = true;
        },

        deliveryType: 'table',
        myOrdersOpen: false,
        myOrdersList: [],
        loadingMyOrders: false,
        settings: {
            table_service_fee_enabled: {{ ($settings->table_service_fee_enabled ?? true) ? 'true' : 'false' }},
            table_service_fee_cents: {{ (int)($settings->table_service_fee_cents ?? 2000) }},
            tax_enabled: {{ ($settings->tax_enabled ?? false) ? 'true' : 'false' }},
            tax_rate_bps: {{ (int)($settings->tax_rate_bps ?? 0) }},
            tax_name: '{{ addslashes($settings->tax_name ?? 'Pajak') }}'
        },

        customizerOpen: false,
        selectedProduct: null,
        pendingProduct: null,
        customizerQty: 1,
        customizerNotes: '',

        isSubmitting: false,
        isChecking: false,
        isSimulating: false,
        activeOrder: null,
        activeAttempt: null,

        countdownSeconds: 15 * 60,
        timerInterval: null,
        pollInterval: null,

        toast: { show: false, message: '', type: 'info' },

        showToast(msg, type = 'info') {
            this.toast = { show: true, message: msg, type };
            setTimeout(() => { this.toast.show = false; }, 3500);
        },

        init() {
            if (this.isValid) {
                this.loadSavedCustomer();
                this.loadMenu();
                this.loadRecentOrdersSummary();
                if (this.isRegistered) {
                    this.loadAccountOrders();
                }
                if (initData.initialOrderId) {
                    this.loadExistingOrder(initData.initialOrderId);
                }
                setInterval(() => {
                    if (this.currentScreen === 'landing') {
                        this.nextBanner();
                    }
                }, 4500);
            }
        },

        loadSavedCustomer() {
            const saved = localStorage.getItem('posymart_customer');
            if (saved) {
                try {
                    const parsed = JSON.parse(saved);
                    if (parsed.name) {
                        this.customerId = parsed.id || '';
                        this.customerName = parsed.name || '';
                        this.customerPhone = parsed.phone || '';
                        this.customerEmail = parsed.email || '';
                        this.loginPhone = parsed.phone || '';
                        this.isRegistered = true;
                    }
                } catch (e) {}
            }
        },

        logoutCustomer() {
            this.resetRegistration();
            this.accountOrdersList = [];
            this.authMode = 'login';
            this.loginPhone = '';
            this.loginPassword = '';
            this.customerPassword = '';
            this.customerPasswordConfirm = '';
            this.currentScreen = 'landing';
            this.showToast('Berhasil keluar akun. Silakan login atau daftar kembali.', 'info');
        },

        resetRegistration() {
            this.customerId = '';
            this.customerName = '';
            this.customerPhone = '';
            this.customerEmail = '';
            this.loginPassword = '';
            this.customerPassword = '';
            this.customerPasswordConfirm = '';
            this.isRegistered = false;
            localStorage.removeItem('posymart_customer');
        },

        async submitLogin() {
            const rawPhone = this.loginPhone.trim();
            if (!rawPhone || rawPhone.length < 8) {
                this.showToast('Silakan masukkan nomor HP/WhatsApp yang valid (minimal 8 digit)', 'error');
                return;
            }
            if (!this.loginPassword || this.loginPassword.length < 4) {
                this.showToast('Silakan masukkan kata sandi akun Anda (minimal 4 karakter)', 'error');
                return;
            }

            this.isSubmitting = true;
            try {
                const res = await fetch('/api/v1/guest/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        phone: rawPhone,
                        password: this.loginPassword
                    })
                });

                const data = await res.json();
                if (res.ok && data.customer) {
                    this.customerId = data.customer.id;
                    this.customerName = data.customer.name;
                    this.customerPhone = data.customer.phone;
                    this.customerEmail = data.customer.email || '';
                    this.isRegistered = true;
                    this.loginPassword = '';
                    this.accountDrawerOpen = false;

                    localStorage.setItem('posymart_customer', JSON.stringify(data.customer));
                    this.showToast(`Selamat datang kembali, ${this.customerName}!`, 'success');

                    this.loadAccountOrders();
                    this.loadRecentOrdersSummary();
                    this.currentScreen = 'menu';

                    if (this.pendingProduct) {
                        const prod = this.pendingProduct;
                        this.pendingProduct = null;
                        this.$nextTick(() => {
                            this.openCustomizer(prod);
                        });
                    }
                } else if (res.status === 401) {
                    this.showToast(data.message || 'Kata sandi / password salah. Silakan coba lagi.', 'error');
                } else if (res.status === 404) {
                    this.showToast(data.message || 'Nomor HP belum terdaftar. Silakan lengkapi pendaftaran.', 'info');
                    this.customerPhone = rawPhone;
                    this.customerPassword = this.loginPassword;
                    this.customerPasswordConfirm = this.loginPassword;
                    this.authMode = 'register';
                } else {
                    this.showToast(data.message || 'Gagal masuk akun. Periksa data Anda.', 'error');
                }
            } catch (err) {
                console.error('Login error:', err);
                this.showToast('Gagal menghubungi server. Periksa koneksi Anda.', 'error');
            } finally {
                this.isSubmitting = false;
            }
        },

        async submitRegistration() {
            if (!this.customerName.trim()) {
                this.showToast('Silakan isi nama Anda terlebih dahulu', 'error');
                return;
            }
            if (!this.customerPhone.trim() || this.customerPhone.trim().length < 8) {
                this.showToast('Silakan masukkan nomor HP/WhatsApp yang valid', 'error');
                return;
            }
            if (!this.customerPassword || this.customerPassword.length < 4) {
                this.showToast('Silakan buat kata sandi minimal 4 karakter', 'error');
                return;
            }
            if (this.customerPassword !== this.customerPasswordConfirm) {
                this.showToast('Konfirmasi kata sandi tidak cocok. Silakan periksa kembali.', 'error');
                return;
            }
            if (!this.customerEmail || !this.customerEmail.trim()) {
                this.showToast('Silakan masukkan alamat email Anda', 'error');
                return;
            }
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(this.customerEmail.trim())) {
                this.showToast('Format email tidak valid (contoh: rani@example.com)', 'error');
                return;
            }

            this.isSubmitting = true;
            try {
                const res = await fetch('/api/v1/guest/register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        name: this.customerName.trim(),
                        phone: this.customerPhone.trim(),
                        password: this.customerPassword,
                        email: this.customerEmail.trim().toLowerCase(),
                        table_id: this.table?.id || ''
                    })
                });

                const data = await res.json();
                if (res.ok && data.customer) {
                    this.customerId = data.customer.id;
                    this.customerName = data.customer.name;
                    this.customerPhone = data.customer.phone;
                    this.customerEmail = data.customer.email || '';
                    this.isRegistered = true;
                    this.customerPassword = '';
                    this.customerPasswordConfirm = '';
                    this.accountDrawerOpen = false;

                    localStorage.setItem('posymart_customer', JSON.stringify(data.customer));
                    this.showToast(`Pendaftaran berhasil! Selamat datang, ${this.customerName}`, 'success');
                    this.loadAccountOrders();
                    this.loadRecentOrdersSummary();
                    this.currentScreen = 'menu';

                    if (this.pendingProduct) {
                        const prod = this.pendingProduct;
                        this.pendingProduct = null;
                        this.$nextTick(() => {
                            this.openCustomizer(prod);
                        });
                    }
                } else {
                    const errorMsg = data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Gagal mendaftar');
                    this.showToast(errorMsg, 'error');
                }
            } catch (err) {
                this.customerId = 'CUS-' + Math.floor(Math.random() * 900 + 100);
                this.isRegistered = true;
                localStorage.setItem('posymart_customer', JSON.stringify({
                    id: this.customerId,
                    name: this.customerName,
                    phone: this.customerPhone,
                    email: this.customerEmail
                }));
                this.loadAccountOrders();
                this.loadRecentOrdersSummary();
                this.currentScreen = 'menu';
            } finally {
                this.isSubmitting = false;
            }
        },

        async loadMenu() {
            try {
                const res = await fetch('/api/v1/guest/menu');
                const data = await res.json();
                this.products = data.products || [];
                this.categories = data.categories || [];
                if (data.tables && data.tables.length > 0) {
                    this.availableTables = data.tables;
                }
                if (data.settings) {
                    this.settings = data.settings;
                }
            } catch (err) {
                console.error('Failed to load menu:', err);
            }
        },

        async loadExistingOrder(orderId) {
            try {
                const res = await fetch(`/api/v1/guest/orders/${orderId}`);
                if (res.ok) {
                    const data = await res.json();
                    this.activeOrder = data.order;
                    this.currentScreen = 'order_tracking';
                    this.startPolling(orderId);
                }
            } catch (err) {
                console.error('Failed to load existing order:', err);
            }
        },

        get filteredProducts() {
            let list = this.products;
            if (this.selectedCategory !== 'all') {
                list = list.filter(p => p.category_id === this.selectedCategory);
            }
            if (this.searchQuery.trim()) {
                const q = this.searchQuery.toLowerCase();
                list = list.filter(p => p.name.toLowerCase().includes(q) || (p.description && p.description.toLowerCase().includes(q)));
            }
            return list;
        },

        getItemInCart(productId) {
            return this.cart.find(it => it.product_id === productId);
        },

        openCustomizer(product) {
            if (!this.isRegistered || !this.customerName) {
                this.pendingProduct = product;
                this.authMode = 'login';
                this.accountDrawerOpen = true;
                this.showToast('Silakan masuk atau daftar terlebih dahulu untuk memesan menu.', 'info');
                return;
            }
            this.selectedProduct = product;
            this.customizerQty = 1;
            this.customizerNotes = '';
            this.customizerOpen = true;
        },

        addCustomizedToCart() {
            if (!this.isRegistered || !this.customerName) {
                this.customizerOpen = false;
                this.pendingProduct = this.selectedProduct;
                this.authMode = 'login';
                this.accountDrawerOpen = true;
                this.showToast('Silakan masuk atau daftar terlebih dahulu untuk memesan menu.', 'info');
                return;
            }
            const existing = this.getItemInCart(this.selectedProduct.id);
            if (existing) {
                existing.quantity += this.customizerQty;
                if (this.customizerNotes) existing.notes = this.customizerNotes;
            } else {
                this.cart.push({
                    product_id: this.selectedProduct.id,
                    name: this.selectedProduct.name,
                    unit_price_cents: this.selectedProduct.price_cents,
                    quantity: this.customizerQty,
                    notes: this.customizerNotes
                });
            }
            this.customizerOpen = false;
            this.showToast(`${this.selectedProduct.name} ditambahkan ke keranjang`, 'success');
        },

        incrementCartItem(productId) {
            if (!this.isRegistered || !this.customerName) {
                this.accountDrawerOpen = true;
                this.authMode = 'login';
                this.showToast('Silakan masuk atau daftar terlebih dahulu untuk memesan menu.', 'info');
                return;
            }
            const item = this.getItemInCart(productId);
            if (item) item.quantity++;
        },

        decrementCartItem(productId) {
            const item = this.getItemInCart(productId);
            if (item) {
                if (item.quantity > 1) {
                    item.quantity--;
                } else {
                    this.removeCartItem(productId);
                }
            }
        },

        removeCartItem(productId) {
            this.cart = this.cart.filter(it => it.product_id !== productId);
        },

        get cartCount() {
            return this.cart.reduce((sum, it) => sum + it.quantity, 0);
        },

        get cartSubtotal() {
            return this.cart.reduce((sum, it) => sum + (it.unit_price_cents * it.quantity), 0);
        },

        get tableDeliveryFee() {
            const isEnabled = this.settings?.table_service_fee_enabled === true ||
                              this.settings?.table_service_fee_enabled === 1 ||
                              this.settings?.table_service_fee_enabled === '1';
            if (this.deliveryType === 'table' && isEnabled) {
                return Number(this.settings?.table_service_fee_cents ?? 2000);
            }
            return 0;
        },

        get voucherDiscount() {
            if (!this.appliedVoucherCode) return 0;
            const code = this.appliedVoucherCode;
            if (code === 'NEW_USER_20K') {
                // Semua produk jadi 20rb: diskon untuk item yang harga normal > 20.000
                return this.cart.reduce((sum, it) => {
                    if (it.unit_price_cents > 20000) {
                        return sum + ((it.unit_price_cents - 20000) * it.quantity);
                    }
                    return sum;
                }, 0);
            }
            return 0;
        },

        get cartTax() {
            const isEnabled = this.settings?.tax_enabled === true ||
                              this.settings?.tax_enabled === 1 ||
                              this.settings?.tax_enabled === '1';
            const rateBps = Number(this.settings?.tax_rate_bps || 0);
            if (isEnabled && rateBps > 0) {
                const taxable = Math.max(0, this.cartSubtotal - this.voucherDiscount);
                return Math.round((taxable * rateBps) / 10000);
            }
            return 0;
        },

        get taxLabel() {
            const isEnabled = this.settings?.tax_enabled === true ||
                              this.settings?.tax_enabled === 1 ||
                              this.settings?.tax_enabled === '1';
            const rateBps = Number(this.settings?.tax_rate_bps || 0);
            const name = (this.settings?.tax_name || 'Pajak').trim();
            if (isEnabled && rateBps > 0) {
                const pct = (rateBps / 100).toLocaleString('id-ID', { maximumFractionDigits: 2 });
                return `${name} (${pct}%)`;
            }
            return `${name} (0%)`;
        },

        get cartTotal() {
            return Math.max(0, (this.cartSubtotal + this.tableDeliveryFee + this.cartTax) - this.voucherDiscount);
        },

        get hasActiveOrders() {
            return (this.myOrdersList || []).some(o => ['New', 'Accepted', 'Preparing', 'Ready'].includes(o.fulfillment_status));
        },

        formatRp(cents) {
            return 'Rp ' + Number(cents || 0).toLocaleString('id-ID');
        },

        get displayTableTitle() {
            if (this.deliveryType === 'counter') {
                return 'Ambil di Kasir';
            }
            if (this.manualTableNumber && this.manualTableNumber.trim()) {
                const num = this.manualTableNumber.trim();
                return num.toLowerCase().startsWith('meja') ? num : ('Meja ' + num);
            }
            if (this.selectedTable?.label) {
                return this.selectedTable.label;
            }
            if (this.table?.label) {
                return this.table.label;
            }
            return 'Pilih Meja';
        },

        get displayTableArea() {
            if (this.deliveryType === 'counter') {
                return 'Kasir / Bar';
            }
            if (this.selectedTable?.area) {
                return this.selectedTable.area;
            }
            if (this.table?.area) {
                return this.table.area;
            }
            return '';
        },

        onTableSelectChange(tableId) {
            if (!tableId) {
                this.selectedTable = null;
                return;
            }
            const found = (this.availableTables || []).find(t => String(t.id) === String(tableId));
            if (found) {
                this.selectedTable = found;
                this.manualTableNumber = found.label ? found.label.replace(/^Meja\s*/i, '').trim() : (found.table_code || '');
            }
        },

        onManualTableInput(val) {
            const cleanVal = (val || '').trim();
            if (!cleanVal) {
                this.selectedTable = null;
                this.selectedTableSelect = '';
                return;
            }
            const digits = cleanVal.replace(/[^0-9]/g, '');
            const match = (this.availableTables || []).find(t => {
                if (digits && (t.table_code === 'T' + digits.padStart(3, '0') || t.label.toLowerCase().includes(digits))) {
                    return true;
                }
                return t.label.toLowerCase() === cleanVal.toLowerCase() || t.table_code.toLowerCase() === cleanVal.toLowerCase();
            });

            if (match) {
                this.selectedTable = match;
                this.selectedTableSelect = match.id;
            } else {
                this.selectedTable = null;
                this.selectedTableSelect = '';
            }
        },

        get countdownFormatted() {
            const m = Math.floor(this.countdownSeconds / 60);
            const s = this.countdownSeconds % 60;
            return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
        },

        startCountdown() {
            if (this.timerInterval) clearInterval(this.timerInterval);
            if (this.paymentPollInterval) clearInterval(this.paymentPollInterval);
            this.countdownSeconds = 15 * 60;
            this.timerInterval = setInterval(() => {
                if (this.countdownSeconds > 0) {
                    this.countdownSeconds--;
                } else {
                    clearInterval(this.timerInterval);
                    if (this.paymentPollInterval) clearInterval(this.paymentPollInterval);
                }
            }, 1000);

            // Auto poll status pembayaran setiap 3.5 detik saat di halaman QRIS
            this.paymentPollInterval = setInterval(() => {
                if (this.currentScreen === 'payment' && this.activeOrder) {
                    this.checkPaymentStatus(true);
                } else {
                    clearInterval(this.paymentPollInterval);
                }
            }, 3500);
        },

        proceedToCheckout() {
            if (!this.isRegistered || !this.customerName) {
                this.accountDrawerOpen = true;
                this.authMode = 'login';
                this.showToast('Silakan masuk atau daftar terlebih dahulu untuk melanjutkan pesanan.', 'warning');
                return;
            }
            if (this.cart.length === 0) {
                this.showToast('Keranjang Anda masih kosong.', 'error');
                return;
            }
            if (this.deliveryType === 'table') {
                const tableVal = (this.manualTableNumber || this.selectedTable?.label || this.table?.label || '').trim();
                if (!tableVal) {
                    this.showToast('Silakan masukkan nomor meja Anda terlebih dahulu untuk opsi Diantar ke Meja.', 'error');
                    this.$nextTick(() => {
                        const input = document.getElementById('cart-table-number-input');
                        if (input) {
                            input.focus();
                            input.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    });
                    return;
                }
            }
            this.currentScreen = 'checkout';
        },

        async executeCheckout() {
            if (!this.isRegistered || !this.customerName) {
                this.accountDrawerOpen = true;
                this.authMode = 'login';
                this.showToast('Silakan masuk atau daftar terlebih dahulu untuk melanjutkan pesanan.', 'warning');
                return;
            }
            if (this.cart.length === 0) return;

            if (this.deliveryType === 'table') {
                const tableNum = (this.manualTableNumber || '').trim();
                if (!tableNum && !this.selectedTable?.id && !this.table?.id) {
                    this.showToast('Silakan masukkan nomor meja Anda terlebih dahulu', 'error');
                    return;
                }
            }

            this.isSubmitting = true;

            try {
                const payload = {
                    table_id: (this.deliveryType === 'table') 
                        ? (this.selectedTable?.id || this.table?.id || null) 
                        : null,
                    table_number: (this.deliveryType === 'table') 
                        ? (this.manualTableNumber || this.selectedTable?.label || this.table?.label || '') 
                        : null,
                    items: this.cart.map(it => ({
                        product_id: it.product_id,
                        quantity: it.quantity,
                        notes: it.notes
                    })),
                    customer_id: this.customerId || null,
                    customer_name: this.customerName || (this.deliveryType === 'table' ? ('Pelanggan Meja ' + (this.manualTableNumber || '')) : 'Pelanggan Kasir'),
                    customer_phone: this.customerPhone || null,
                    payment_method: 'QRIS',
                    delivery_type: this.deliveryType,
                    voucher_code: this.appliedVoucherCode || null
                };

                const res = await fetch('/api/v1/guest/checkouts', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();

                if (!res.ok) {
                    this.showToast(data.error?.message || 'Gagal memproses checkout', 'error');
                    this.isSubmitting = false;
                    return;
                }

                this.activeOrder = {
                    id: data.order_id,
                    order_code: data.order_code,
                    subtotal_cents: data.subtotal_cents || this.cartSubtotal,
                    discount_cents: data.discount_cents ?? (this.voucherDiscount || 0),
                    tax_cents: data.tax_cents ?? (this.cartTax || 0),
                    total_cents: data.total_cents,
                    payment_status: data.payment_status,
                    fulfillment_status: data.fulfillment_status,
                    table_label_snapshot: (this.deliveryType === 'table')
                        ? (this.manualTableNumber ? ('Meja ' + this.manualTableNumber) : (this.table?.label || 'Meja'))
                        : 'Ambil di Kasir',
                    guest_token: data.guest_token,
                    delivery_type: this.deliveryType,
                    service_fee_cents: this.tableDeliveryFee,
                    voucher_code: this.appliedVoucherCode || null,
                    items: [...this.cart]
                };
                this.activeAttempt = data.payment_attempt;

                // Save to stored orders
                this.saveOrderId(data.order_id);
                this.loadRecentOrdersSummary();
                this.loadAccountOrders();

                this.startCountdown();
                this.currentScreen = 'payment';
                this.showToast('Tagihan QRIS berhasil dibuat! Silakan bayar.', 'success');
            } catch (err) {
                console.error('Checkout error:', err);
                this.showToast('Terjadi gangguan jaringan.', 'error');
            } finally {
                this.isSubmitting = false;
            }
        },

        async checkPaymentStatus(silent = false) {
            if (!this.activeOrder) return;
            if (!silent) this.isChecking = true;

            try {
                const res = await fetch(`/api/v1/guest/orders/${this.activeOrder.id}/payment-status-check`, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();

                if (data.is_paid) {
                    if (this.paymentPollInterval) clearInterval(this.paymentPollInterval);
                    if (this.timerInterval) clearInterval(this.timerInterval);
                    this.activeOrder.payment_status = 'Paid';
                    this.activeOrder.fulfillment_status = data.fulfillment_status || 'New';
                    this.currentScreen = 'order_tracking';
                    this.startPolling(this.activeOrder.id);
                    this.loadRecentOrdersSummary();
                    this.loadAccountOrders();
                    this.showToast('Pembayaran berhasil diverifikasi! Pesanan masuk ke dapur.', 'success');
                } else if (!silent) {
                    this.showToast('Menunggu pembayaran dari bank/e-wallet Anda...', 'info');
                }
            } catch (err) {
                if (!silent) console.error(err);
            } finally {
                if (!silent) this.isChecking = false;
            }
        },

        async simulatePay() {
            if (!this.activeOrder) return;
            this.isSimulating = true;

            try {
                const res = await fetch(`/api/v1/guest/orders/${this.activeOrder.id}/simulate-pay`, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();

                if (data.success) {
                    if (this.paymentPollInterval) clearInterval(this.paymentPollInterval);
                    if (this.timerInterval) clearInterval(this.timerInterval);
                    this.activeOrder.payment_status = 'Paid';
                    this.activeOrder.fulfillment_status = data.order?.fulfillment_status || 'New';
                    this.currentScreen = 'order_tracking';
                    this.startPolling(this.activeOrder.id);
                    this.loadRecentOrdersSummary();
                    this.loadAccountOrders();
                    this.showToast('Simulasi QRIS Berhasil! Pesanan otomatis masuk ke antrean admin.', 'success');
                } else {
                    this.showToast('Simulasi gagal: ' + (data.message || 'error'), 'error');
                }
            } catch (err) {
                console.error(err);
                this.showToast('Gagal simulasi pembayaran', 'error');
            } finally {
                this.isSimulating = false;
            }
        },

        startPolling(orderId) {
            if (this.pollInterval) clearInterval(this.pollInterval);
            this.pollInterval = setInterval(async () => {
                try {
                    const res = await fetch(`/api/v1/guest/orders/${orderId}`);
                    if (res.ok) {
                        const data = await res.json();
                        if (data.order) {
                            const prevStatus = this.activeOrder?.fulfillment_status;
                            this.activeOrder.fulfillment_status = data.order.fulfillment_status;
                            this.activeOrder.payment_status = data.order.payment_status;

                            if (prevStatus && prevStatus !== data.order.fulfillment_status) {
                                if (data.order.fulfillment_status === 'Ready') {
                                    const msg = data.order.delivery_type === 'counter'
                                        ? '🔔 Pesanan Anda SIAP diambil di kasir!'
                                        : `🔔 Pesanan Anda SIAP diantar ke ${data.order.table_label_snapshot || 'Meja Anda'}!`;
                                    this.showToast(msg, 'success');
                                } else if (data.order.fulfillment_status === 'Served') {
                                    const msg = `🎉 Pesanan telah SELESAI disajikan ke ${data.order.table_label_snapshot || 'Meja Anda'}. Selamat menikmati!`;
                                    this.showToast(msg, 'success');
                                } else if (data.order.fulfillment_status === 'Preparing') {
                                    this.showToast('☕ Pesanan Anda sedang diracik oleh staf dapur...', 'info');
                                }
                                this.loadRecentOrdersSummary();
                                this.loadAccountOrders();
                            }
                        }
                    }
                } catch (err) {
                    console.error('Polling error', err);
                }
            }, 3500);
        },

        isStepReached(step) {
            const steps = ['New', 'Accepted', 'Preparing', 'Ready', 'Served'];
            const cur = this.activeOrder?.fulfillment_status;
            return steps.indexOf(cur) >= steps.indexOf(step);
        },

        getFulfillmentLabel(status) {
            const map = {
                'NotQueued': 'Belum Masuk Antrean',
                'New': 'Pesanan Baru (Menunggu Konfirmasi)',
                'Accepted': 'Diterima Staf',
                'Preparing': 'Sedang Disiapkan',
                'Ready': this.activeOrder?.delivery_type === 'counter' ? 'Siap Diambil di Kasir' : 'Siap Diantar ke Meja',
                'Served': 'Selesai Disajikan',
                'ReviewRequired': 'Perlu Pemeriksaan Admin'
            };
            return map[status] || status;
        },

        saveOrderId(id) {
            if (!id) return;
            try {
                let ids = this.getStoredOrderIds();
                if (!ids.includes(id)) {
                    ids.unshift(id);
                    if (ids.length > 20) ids = ids.slice(0, 20);
                    localStorage.setItem('posymart_order_ids', JSON.stringify(ids));
                }
            } catch (e) {}
        },

        getStoredOrderIds() {
            try {
                const stored = localStorage.getItem('posymart_order_ids');
                return stored ? JSON.parse(stored) : [];
            } catch (e) {
                return [];
            }
        },

        async openMyOrders(tab = 'active') {
            if (!this.isRegistered || !this.customerName) {
                this.showToast('Silakan masuk atau daftar akun terlebih dahulu untuk melihat riwayat pesanan.', 'info');
                this.currentScreen = 'landing';
                this.authMode = 'login';
                return;
            }
            this.orderDrawerTab = tab;
            this.myOrdersOpen = true;
            this.loadingMyOrders = true;
            try {
                const storedIds = this.getStoredOrderIds();
                const params = new URLSearchParams();
                const currentTableId = this.table?.id || this.selectedTable?.id;
                if (currentTableId) params.append('table_id', currentTableId);
                if (this.customerId) params.append('customer_id', this.customerId);
                if (this.customerPhone) params.append('customer_phone', this.customerPhone);
                if (storedIds.length > 0) params.append('order_ids', storedIds.join(','));

                const res = await fetch(`/api/v1/guest/orders?${params.toString()}`);
                if (res.ok) {
                    const data = await res.json();
                    this.myOrdersList = data.orders || [];
                }
            } catch (e) {
                console.error('Failed to load my orders:', e);
            } finally {
                this.loadingMyOrders = false;
            }

            if (this.isRegistered) {
                this.loadAccountOrders();
            }
        },

        async loadAccountOrders() {
            if (!this.customerId && !this.customerPhone) {
                this.accountOrdersList = [];
                return;
            }
            this.loadingAccountOrders = true;
            try {
                const params = new URLSearchParams();
                params.append('scope', 'account');
                if (this.customerId) params.append('customer_id', this.customerId);
                if (this.customerPhone) params.append('customer_phone', this.customerPhone);

                const res = await fetch(`/api/v1/guest/orders?${params.toString()}`);
                if (res.ok) {
                    const data = await res.json();
                    this.accountOrdersList = data.orders || [];
                }
            } catch (e) {
                console.error('Failed to load account orders:', e);
            } finally {
                this.loadingAccountOrders = false;
            }
        },

        async loadRecentOrdersSummary() {
            try {
                const storedIds = this.getStoredOrderIds();
                const params = new URLSearchParams();
                const currentTableId = this.table?.id || this.selectedTable?.id;
                if (currentTableId) params.append('table_id', currentTableId);
                if (this.customerId) params.append('customer_id', this.customerId);
                if (storedIds.length > 0) params.append('order_ids', storedIds.join(','));

                const res = await fetch(`/api/v1/guest/orders?${params.toString()}`);
                if (res.ok) {
                    const data = await res.json();
                    this.myOrdersList = data.orders || [];
                }
            } catch (e) {}
        },

        trackSelectedOrder(order) {
            this.activeOrder = order;
            this.currentScreen = 'order_tracking';
            this.myOrdersOpen = false;
            this.startPolling(order.id);
        },

        getFulfillmentShortLabel(status) {
            const map = {
                'NotQueued': 'Pending',
                'New': 'Diterima',
                'Accepted': 'Diterima Staf',
                'Preparing': 'Diproses',
                'Ready': 'Siap/Ready',
                'Served': 'Selesai',
                'ReviewRequired': 'Pemeriksaan'
            };
            return map[status] || status;
        },

        getFulfillmentBadgeClass(status) {
            const map = {
                'New': 'bg-blue-100 text-blue-800',
                'Accepted': 'bg-sky-100 text-sky-800',
                'Preparing': 'bg-amber-100 text-amber-800',
                'Ready': 'bg-emerald-100 text-emerald-800',
                'Served': 'bg-slate-100 text-slate-700',
            };
            return map[status] || 'bg-slate-100 text-slate-600';
        },

        formatDate(isoStr) {
            if (!isoStr) return '';
            try {
                const d = new Date(isoStr);
                return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' · ' +
                       d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
            } catch (e) {
                return isoStr;
            }
        },

        printCustomerReceipt() {
            const ord = this.activeOrder;
            if (!ord) return;
            const items = (ord.items || this.cart || []).map(it => ({
                name: it.name,
                quantity: it.quantity,
                unitPrice: it.unit_price_cents || it.unitPrice || 0,
            }));
            const tableLabel = ord.table_label_snapshot || this.table?.label || 'Meja';
            const storeTitle = this.storeName || 'PosyMart Coffee & Eatery';
            const subtotal = items.reduce((acc, it) => acc + (it.quantity * it.unitPrice), 0);
            const discount = ord.discount_cents || 0;
            const fee = ord.service_fee_cents || 0;
            const tax = ord.tax_cents || 0;
            const total = ord.total_cents || (subtotal - discount + fee + tax);
            const dateStr = this.formatDate(ord.created_at) || new Date().toLocaleString('id-ID');
            const code = ord.order_code || ('#' + ord.id.slice(0, 8));

            const itemsHtml = items.map(it => `
                <div style="margin: 5px 0;">
                    <div style="font-weight: 700;">${it.name}</div>
                    <div style="display: flex; justify-content: space-between; padding-left: 8px; font-size: 12px; color: #111;">
                        <span>${it.quantity} × Rp${Number(it.unitPrice).toLocaleString('id-ID')}</span>
                        <span style="font-weight: 700;">Rp${Number(it.quantity * it.unitPrice).toLocaleString('id-ID')}</span>
                    </div>
                </div>
            `).join('');

            const closeHead = '<' + '/head>';
            const closeBody = '<' + '/body>';
            const closeHtml = '<' + '/html>';

            const html = [
                '<!DOCTYPE html>',
                '<html lang="id">',
                '<head>',
                '<meta charset="utf-8">',
                '<meta name="viewport" content="width=device-width, initial-scale=1.0">',
                '<title>Struk Pembayaran Meja - ' + code + '</title>',
                '<style>',
                '  @page { size: 80mm auto; margin: 0; }',
                '  * { box-sizing: border-box; margin: 0; padding: 0; }',
                '  body { font-family: "Courier New", Courier, Monaco, monospace; font-size: 13px; line-height: 1.45; color: #000; background: #fff; width: 80mm; max-width: 80mm; margin: 0 auto; padding: 5mm 6mm; }',
                '  @media screen { html { background: #e2e8f0; padding: 24px 0; } body { box-shadow: 0 12px 28px rgba(0,0,0,0.18); border-radius: 4px; } }',
                '  @media print { body { width: 80mm !important; max-width: 80mm !important; padding: 3mm 4mm !important; margin: 0 !important; } .no-print { display: none !important; } }',
                '  .no-print { text-align: center; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px dashed #94a3b8; }',
                '  .btn-print { background: #0f766e; color: #fff; border: none; border-radius: 6px; padding: 7px 18px; font-family: sans-serif; font-size: 13px; font-weight: 700; cursor: pointer; }',
                '  .store-header { font-size: 17px; font-weight: 800; text-align: center; margin-bottom: 2px; }',
                '  .store-sub { text-align: center; font-size: 11.5px; color: #222; }',
                '  .receipt-title { text-align: center; font-size: 12px; font-weight: 800; letter-spacing: 1.5px; margin: 8px 0 6px; padding: 3px 0; border-top: 1px dashed #333; border-bottom: 1px dashed #333; }',
                '  .line { border-top: 1px dashed #333; margin: 6px 0; }',
                '  .line-double { border-top: 2px dashed #000; margin: 8px 0; }',
                '  .row { display: flex; justify-content: space-between; padding: 2px 0; font-size: 12px; }',
                '  .font-bold { font-weight: 700; }',
                '  .total-row { display: flex; justify-content: space-between; font-size: 15.5px; font-weight: 900; padding: 4px 0; }',
                '  .center { text-align: center; }',
                '  .muted { color: #444; font-size: 11px; }',
                '  .status-badge { display: inline-block; font-weight: 800; font-size: 11px; padding: 2px 6px; border: 1px solid #000; border-radius: 3px; }',
                '</style>',
                closeHead,
                '<body>',
                '  <div class="no-print">',
                '    <button type="button" class="btn-print" onclick="window.print()">🖨️ Cetak Struk (80 mm)</button>',
                '  </div>',
                '  <div class="store-header">' + storeTitle + '</div>',
                '  <div class="store-sub">Pesanan Meja Pelanggan (Self-Order)</div>',
                '  <div class="receipt-title">STRUK PEMBAYARAN · 80 MM</div>',
                '  <div class="row"><span>Kode Pesanan</span><span class="font-bold">' + code + '</span></div>',
                '  <div class="row"><span>Waktu Bayar</span><span>' + dateStr + '</span></div>',
                '  <div class="row"><span>Layanan</span><span class="font-bold">' + tableLabel + '</span></div>',
                '  <div class="row"><span>Pelanggan</span><span>' + (ord.customer_name || this.customerName || 'Tamu Meja') + '</span></div>',
                '  <div class="line"></div>',
                '  <div style="display:flex; justify-content:space-between; font-weight:bold; font-size:11.5px;">',
                '    <span>PRODUK &amp; QTY</span><span>SUBTOTAL</span>',
                '  </div>',
                '  <div class="line"></div>',
                itemsHtml,
                '  <div class="line"></div>',
                '  <div class="row"><span>Subtotal Menu</span><span>Rp' + Number(subtotal).toLocaleString('id-ID') + '</span></div>',
                discount > 0 ? ('  <div class="row" style="color: #0f766e; font-weight: bold;"><span>Diskon Voucher</span><span>- Rp' + Number(discount).toLocaleString('id-ID') + '</span></div>') : '',
                fee > 0 ? ('  <div class="row"><span>Biaya Antar Meja</span><span>Rp' + Number(fee).toLocaleString('id-ID') + '</span></div>') : '',
                tax > 0 ? ('  <div class="row"><span>' + this.taxLabel + '</span><span>Rp' + Number(tax).toLocaleString('id-ID') + '</span></div>') : '',
                '  <div class="line-double"></div>',
                '  <div class="total-row"><span>TOTAL BAYAR</span><span>Rp' + Number(total).toLocaleString('id-ID') + '</span></div>',
                '  <div class="line-double"></div>',
                '  <div class="row"><span>Metode Pembayaran</span><span class="font-bold">QRIS / Dompet Digital</span></div>',
                '  <div class="row" style="margin-top: 4px;"><span>Status Tagihan</span><span class="status-badge">LUNAS / VERIFIED</span></div>',
                '  <div class="line"></div>',
                '  <div class="center muted" style="margin-top: 8px;">Terima kasih atas pesanan Anda!</div>',
                '  <div class="center muted" style="font-size: 10px; margin-top: 4px;">Menu Anda sedang disiapkan dan akan diantar ke ' + tableLabel + '.</div>',
                '  <div class="center muted" style="font-size: 9.5px; margin-top: 8px; letter-spacing: 1px;">*** POSYMART COFFEE SHOP ***</div>',
                closeBody,
                closeHtml
            ].join('\n');

            const win = window.open('', '_blank', 'width=520,height=740');
            if (win) {
                win.document.write(html);
                win.document.close();
                win.focus();
                setTimeout(() => { try { win.print(); } catch (e) {} }, 400);
            }
        },

        resetForNewOrder() {
            if (this.pollInterval) clearInterval(this.pollInterval);
            this.cart = [];
            this.activeOrder = null;
            this.currentScreen = 'menu';
        }
    }));
});
</script>

</body>
</html>
