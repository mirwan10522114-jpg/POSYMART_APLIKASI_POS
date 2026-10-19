{{-- ================= HEADER GLOBAL ================= --}}
<header class="h-[64px] bg-white border-b border-line px-[30px] max-[480px]:px-4 flex items-center justify-between gap-4 sticky top-0 z-30 shadow-[0_1px_3px_rgba(30,41,59,0.02)]">
    <div class="flex items-center gap-3 min-w-0">
        <button type="button"
                class="btn btn-secondary btn-icon xl:hidden"
                @click="sidebarOpen = true" aria-label="Buka navigasi">
            <x-icon name="menu" class="size-5"/>
        </button>

        @if (request()->is('dashboard') || request()->is('/'))
            <div class="flex items-center gap-2.5 min-w-0">
                <span class="size-2 rounded-full bg-emerald-500 animate-pulse shrink-0"></span>
                <div class="min-w-0">
                    <p class="text-[13px] font-semibold text-ink truncate leading-tight">PosyMart Store #01</p>
                    <p class="text-[11.5px] text-ink-muted hidden sm:block leading-tight">Kasir &amp; Pemesanan Meja Aktif</p>
                </div>
            </div>
        @else
            <nav class="flex items-center gap-2 text-[13px] text-ink-muted" aria-label="Breadcrumb">
                <a href="/dashboard" class="hover:text-primary transition-colors flex items-center gap-1.5 font-medium">
                    <x-icon name="shopping-bag" class="size-4 text-primary"/>
                    <span>PosyMart</span>
                </a>
                <span class="text-ink-muted/40 font-normal">/</span>
                <span class="font-semibold text-ink truncate max-w-[200px] sm:max-w-none">@yield('title')</span>
            </nav>
        @endif
    </div>

    {{-- Pencarian Global --}}
    <div class="relative w-[340px] max-w-[36vw] hidden md:block">
        <x-icon name="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-ink-muted pointer-events-none"/>
        <input id="global-search" type="search" placeholder="Cari apa saja... (Ctrl+K)"
               class="input pl-10 pr-12 text-[13px] h-9.5 rounded-xl bg-surface-muted/60 border-line hover:border-primary/40 focus:bg-white transition-colors" autocomplete="off"
               aria-label="Cari produk"
               x-ref="globalSearch"
               @input="$store.posy.catalogSearch = $event.target.value"
               @keydown.window.ctrl.k.prevent="$refs.globalSearch?.focus()"
               @keydown.window.meta.k.prevent="$refs.globalSearch?.focus()">
        <span class="absolute right-2.5 top-1/2 -translate-y-1/2 hidden lg:inline-flex items-center h-5 px-1.5 rounded-md bg-white border border-line text-[10.5px] font-medium text-ink-muted select-none shadow-xs">⌘ K</span>
    </div>

    {{-- Kanan: Aksi & Notifikasi & Keranjang --}}
    <div class="flex items-center gap-2.5 shrink-0">
        <a href="/orders" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-primary-soft text-focus text-xs font-semibold hover:bg-primary-soft/80 transition-colors" title="Buka Antrean Pesanan Meja">
            <span class="size-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span>Pesanan Meja</span>
        </a>

        {{-- Notifikasi --}}
        <div class="relative">
            <button type="button" class="btn btn-secondary btn-icon relative" @click="notifOpen = !notifOpen; cartOpen = false; profileOpen = false"
                    :aria-expanded="notifOpen" aria-label="Buka notifikasi">
                <x-icon name="bell" class="size-5"/>
                <template x-if="unread > 0">
                    <span class="absolute -top-1 -right-1 flex h-4.5 min-w-[18px] items-center justify-center">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-primary opacity-60"></span>
                        <span class="relative inline-flex items-center justify-center h-4.5 min-w-[18px] px-1 rounded-full bg-primary text-white text-[10px] font-bold border-2 border-white tabular-nums shadow-xs"
                              x-text="unread"></span>
                    </span>
                </template>
            </button>
            <div x-show="notifOpen" x-cloak @click.outside="notifOpen = false" @keydown.escape="notifOpen = false"
                 x-transition:enter="pop-enter" x-transition:leave="pop-leave"
                 class="absolute right-0 top-[calc(100%+10px)] w-[410px] max-w-[calc(100vw-32px)] rounded-2xl bg-white border border-line shadow-[0_16px_48px_rgba(30,41,59,0.16)] z-[60] overflow-hidden"
                 role="dialog" aria-label="Notifikasi">
                <div class="flex items-center justify-between px-4 py-3 border-b border-divider bg-surface-muted/30">
                    <div class="flex items-center gap-2">
                        <p class="text-[14.5px] font-semibold text-ink">Notifikasi</p>
                        <span x-show="unread > 0" class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-primary-soft text-focus tabular-nums" x-text="`${unread} Baru`"></span>
                    </div>
                    <button type="button" class="text-[12px] font-medium text-primary hover:text-primary-hover hover:underline transition-colors disabled:opacity-50"
                            :disabled="unread === 0"
                            @click="markAllRead()">Tandai semua dibaca</button>
                </div>

                {{-- State Kosong --}}
                <div x-show="!$store.posy.notifications || $store.posy.notifications.length === 0" class="px-6 py-10 text-center flex flex-col items-center justify-center">
                    <div class="size-12 rounded-2xl bg-surface-muted grid place-items-center mb-3 text-ink-muted">
                        <x-icon name="bell" class="size-6 text-ink-muted/50"/>
                    </div>
                    <p class="text-[14px] font-semibold text-ink">Belum Ada Notifikasi</p>
                    <p class="text-[12px] text-ink-muted max-w-[260px] mt-1 leading-relaxed">
                        Pesanan baru dari scan barcode meja atau kasir akan muncul secara langsung di sini.
                    </p>
                </div>

                {{-- Daftar Notifikasi --}}
                <div x-show="$store.posy.notifications && $store.posy.notifications.length > 0" class="max-h-[380px] overflow-y-auto nice-scroll divide-y divide-divider">
                    <template x-for="n in [...$store.posy.notifications].sort((a,b)=> (b.at || '').localeCompare(a.at || ''))" :key="n.id">
                        <div class="p-3.5 flex gap-3 cursor-pointer hover:bg-surface-muted/60 transition-colors group"
                             :class="!n.read ? 'bg-primary-soft/30' : 'bg-white'"
                             @click="openNotification(n)">
                            <span class="grid size-9 shrink-0 place-items-center rounded-xl mt-0.5 transition-transform group-hover:scale-105"
                                  :class="{
                                      'bg-emerald-100 text-emerald-700': n.type === 'payment',
                                      'bg-sky-100 text-sky-700': n.type === 'order' || n.type === 'qr_order',
                                      'bg-amber-100 text-amber-700': n.type === 'stock',
                                      'bg-rose-100 text-rose-700': n.type === 'return',
                                      'bg-purple-100 text-purple-700': n.type === 'discount',
                                  }">
                                <x-icon name="clipboard-list" class="size-4.5" x-show="n.type === 'order' || n.type === 'qr_order'"/>
                                <x-icon name="check-circle" class="size-4.5" x-show="n.type === 'payment'"/>
                                <x-icon name="package" class="size-4.5" x-show="n.type === 'stock'"/>
                                <x-icon name="rotate-ccw" class="size-4.5" x-show="n.type === 'return'"/>
                                <x-icon name="tag" class="size-4.5" x-show="n.type === 'discount'"/>
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-1.5">
                                    <p class="text-[13px] font-semibold text-ink leading-snug line-clamp-1" x-text="n.title"></p>
                                    <span class="size-2 rounded-full bg-primary shrink-0 mt-1" x-show="!n.read" aria-hidden="true" title="Belum dibaca"></span>
                                </div>
                                <p class="text-[12px] text-ink-secondary mt-0.5 leading-relaxed line-clamp-2" x-text="n.body"></p>
                                <div class="flex items-center justify-between mt-2 pt-1 border-t border-line/30 text-[11px] text-ink-muted">
                                    <span class="tabular-nums" x-text="$rpfmt.dateTime(n.at)"></span>
                                    <span class="font-medium text-primary flex items-center gap-0.5 group-hover:underline">
                                        <span>Buka</span>
                                        <x-icon name="arrow-right" class="size-3"/>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Footer Notifikasi --}}
                <div class="px-4 py-2.5 border-t border-divider bg-surface-muted/40 flex items-center justify-between">
                    <div class="flex items-center gap-1.5 text-[11.5px] text-ink-muted font-medium">
                        <span class="size-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Sinkronisasi Otomatis</span>
                    </div>
                    <a href="/orders" class="text-[11.5px] font-semibold text-primary hover:text-primary-hover flex items-center gap-1 hover:underline" @click="notifOpen = false">
                        <span>Antrean Pesanan</span>
                        <x-icon name="arrow-right" class="size-3"/>
                    </a>
                </div>
            </div>
        </div>

        {{-- Ringkasan keranjang POS --}}
        <div class="relative">
            <button type="button" class="btn btn-secondary btn-icon relative" @click="cartOpen = !cartOpen; notifOpen = false; profileOpen = false"
                    :aria-expanded="cartOpen" aria-label="Ringkasan keranjang POS">
                <x-icon name="cart" class="size-5"/>
                <span x-show="cartBadge" x-cloak
                      class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-primary text-white text-[10.5px] font-semibold grid place-items-center border-2 border-white tabular-nums"
                      x-text="cartBadge"></span>
            </button>
            <div x-show="cartOpen" x-cloak @click.outside="cartOpen = false" @keydown.escape="cartOpen = false"
                 x-transition:enter="pop-enter" x-transition:leave="pop-leave"
                 class="absolute right-0 top-[calc(100%+10px)] w-[400px] max-w-[calc(100vw-32px)] rounded-2xl bg-white border border-line shadow-[0_16px_48px_rgba(30,41,59,0.16)] z-[60] overflow-hidden"
                 role="dialog" aria-label="Ringkasan penjualan berjalan">
                <div class="px-4 py-3 border-b border-divider">
                    <p class="text-[15px]/[22px] font-semibold">Penjualan Berjalan</p>
                    <p class="text-[12.5px]/[17px] text-ink-secondary" x-text="cartCount > 0 ? `${cartCount} unit dalam keranjang` : 'Keranjang POS kosong'"></p>
                </div>
                <div class="max-h-[300px] overflow-y-auto nice-scroll" x-show="cartCount > 0">
                    <template x-for="it in $store.posy.draftSale.items" :key="it.productId">
                        <div class="px-4 py-2.5 border-b border-divider last:border-0 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-[13.5px]/[18px] font-medium truncate" x-text="$store.posy.product(it.productId)?.name"></p>
                                <p class="text-[12px]/[16px] text-ink-secondary tabular-nums" x-text="`${it.quantity} × ${$rp(it.unitPrice)}`"></p>
                            </div>
                            <p class="text-[13px] font-medium tabular-nums" x-text="$rp(it.unitPrice * it.quantity)"></p>
                        </div>
                    </template>
                </div>
                <div class="px-4 py-3 border-t border-divider flex items-center justify-between">
                    <a href="/dashboard" class="btn btn-primary btn-sm" @click="cartOpen = false">Buka Penjualan Saat Ini</a>
                    <p class="text-[13px] text-ink-muted tabular-nums" x-text="cartCount > 0 ? $rp($store.posy.draftTotals().total) : ''"></p>
                </div>
            </div>
        </div>
    </div>
</header>
