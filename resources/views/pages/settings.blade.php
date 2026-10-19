@extends('layouts.app')

@section('title', 'Pengaturan')
@section('subtitle', 'Konfigurasikan toko dan kasir Anda.')

@section('content')
<div x-data="settingsPage" x-cloak>

    {{-- penjaga akses demo --}}
    <template x-if="!allowed">
        <div class="py-16">
            <div class="card p-12 text-center max-w-lg mx-auto">
                <span class="mx-auto mb-4 grid size-14 place-items-center rounded-full bg-danger-soft text-danger">
                    <x-icon name="shield-check" class="size-7"/>
                </span>
                <h1 class="text-[22px]/[30px] font-semibold">Anda tidak memiliki akses ke halaman ini</h1>
                <p class="text-[14px]/[21px] text-ink-secondary mt-2">Pengaturan toko hanya dapat diubah oleh Admin pada simulasi peran ini.</p>
                <p class="text-[12px]/[17px] text-ink-muted mt-3">Ini hanya simulasi izin frontend — bukan proteksi server.</p>
                <a href="/dashboard" class="btn btn-primary mt-6">Kembali ke Dasbor</a>
            </div>
        </div>
    </template>

    <template x-if="allowed">
        <div>
            <x-page-header title="Pengaturan" subtitle="Konfigurasikan toko dan kasir Anda.">
                <span class="badge badge-amber" x-show="dirty" x-cloak>Ada perubahan belum disimpan</span>
                <button type="button" class="btn btn-secondary" @click="cancel()" :disabled="!dirty && !saving">Batal</button>
                <button type="button" class="btn btn-primary" @click="save()" :disabled="!dirty || saving" x-text="saving ? 'Menyimpan…' : 'Simpan Perubahan'"></button>
            </x-page-header>

            <div class="mt-6 rounded-xl bg-primary-soft/50 border border-primary/25 px-4 py-3 flex items-start gap-2.5">
                <x-icon name="info" class="size-4.5 text-focus shrink-0 mt-0.5"/>
                <p class="text-[12.5px]/[18px] text-ink-secondary">Perubahan disimpan di perangkat ini untuk demo. Pengaturan memengaruhi penjualan baru — transaksi historis memakai snapshot.</p>
            </div>

            <div class="grid grid-cols-1 min-[880px]:grid-cols-[220px_minmax(0,1fr)] gap-8 mt-6 items-start">
                {{-- ================= TAB VERTIKAL ================= --}}
                <nav class="panel p-2 min-[880px]:sticky min-[880px]:top-6" aria-label="Bagian pengaturan">
                    <div class="flex min-[880px]:flex-col gap-1 overflow-x-auto no-scrollbar" role="tablist" aria-orientation="vertical">
                        @php
                            $settingTabs = [
                                ['general', 'Umum', 'settings'],
                                ['store', 'Toko', 'package'],
                                ['delivery', 'Layanan Meja', 'truck'],
                                ['payments', 'Pembayaran', 'wallet'],
                                ['taxes', 'Pajak', 'percent'],
                                ['receipt', 'Struk', 'receipt'],
                                ['notifications', 'Notifikasi', 'bell'],
                                ['appearance', 'Tampilan', 'sun'],
                            ];
                        @endphp
                        @foreach ($settingTabs as [$key, $label, $icon])
                            <button type="button" role="tab" :aria-selected="activeTab === '{{ $key }}'"
                                    class="flex items-center gap-2.5 px-3.5 h-11 rounded-lg text-[14px] whitespace-nowrap transition-colors text-left"
                                    :class="activeTab === '{{ $key }}' ? 'bg-primary-soft text-focus font-medium' : 'text-ink-secondary hover:bg-surface-muted'"
                                    @click="switchTab('{{ $key }}')">
                                <x-icon name="{{ $icon }}" class="size-5 shrink-0"/>
                                <span>{{ $label }}</span>
                            </button>
                        @endforeach
                    </div>
                </nav>

                {{-- ================= KONTEN TAB ================= --}}
                <div class="max-w-[760px]">
                    <div class="panel p-6 sm:p-7">
                        <div class="mb-6">
                            <h2 class="text-[18px]/[24px] font-semibold" x-text="tabMeta[0]"></h2>
                            <p class="text-[13.5px]/[19px] text-ink-secondary mt-0.5" x-text="tabMeta[1]"></p>
                        </div>

                        {{-- ============ UMUM ============ --}}
                        <div x-show="activeTab === 'general'" role="tabpanel" aria-label="Umum" class="space-y-6">
                            <div>
                                <p class="text-[16px]/[24px] font-medium mb-4">Bisnis</p>
                                <div class="grid grid-cols-1 min-[560px]:grid-cols-2 gap-4">
                                    <div>
                                        <label class="field-label" for="s-bname">Nama Bisnis <span class="text-danger">*</span></label>
                                        <input id="s-bname" type="text" class="input" x-model="draft.businessName" :aria-invalid="!!errorFor('businessName')">
                                        <p class="field-error" x-show="errorFor('businessName')" x-text="errorFor('businessName')"></p>
                                    </div>
                                    <div>
                                        <label class="field-label" for="s-btype">Jenis Bisnis</label>
                                        <select id="s-btype" class="select" x-model="draft.businessType">
                                            <option>Kafe</option><option>Ritel</option><option>Restoran</option><option>Lainnya</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="border-t border-divider pt-6">
                                <p class="text-[16px]/[24px] font-medium mb-4">Regional</p>
                                <div class="grid grid-cols-1 min-[560px]:grid-cols-2 gap-4">
                                    <div>
                                        <label class="field-label" for="s-currency">Mata Uang</label>
                                        <select id="s-currency" class="select" x-model="draft.currency">
                                            <option>IDR — Rupiah Indonesia</option>
                                        </select>
                                        <p class="field-help">Format tampilan: Rp1.234.567</p>
                                    </div>
                                    <div>
                                        <label class="field-label" for="s-tz">Zona Waktu</label>
                                        <input id="s-tz" type="text" class="input" value="Asia/Jakarta (UTC+07:00)" disabled>
                                        <p class="field-help">Terkunci untuk fixture demo.</p>
                                    </div>
                                    <div>
                                        <label class="field-label" for="s-df">Format Tanggal</label>
                                        <select id="s-df" class="select" x-model="draft.dateFormat">
                                            <option value="DD MMM YYYY">10 Okt 2026</option>
                                            <option value="MMM DD, YYYY">Okt 10, 2026</option>
                                            <option value="YYYY-MM-DD">2026-10-10</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="field-label" for="s-tf">Format Waktu</label>
                                        <select id="s-tf" class="select" x-model="draft.timeFormat">
                                            <option value="24 jam">24 jam (14.00)</option>
                                            <option value="12 jam">12 jam (02.00 PM)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ============ TOKO ============ --}}
                        <div x-show="activeTab === 'store'" x-cloak role="tabpanel" aria-label="Toko" class="space-y-6">
                            <div>
                                <p class="text-[16px]/[24px] font-medium mb-4">Kontak</p>
                                <div class="grid grid-cols-1 min-[560px]:grid-cols-2 gap-4">
                                    <div>
                                        <label class="field-label" for="s-sname">Nama Toko <span class="text-danger">*</span></label>
                                        <input id="s-sname" type="text" class="input" x-model="draft.storeName" :aria-invalid="!!errorFor('storeName')">
                                        <p class="field-error" x-show="errorFor('storeName')" x-text="errorFor('storeName')"></p>
                                    </div>
                                    <div>
                                        <label class="field-label" for="s-semail">Email Toko</label>
                                        <input id="s-semail" type="email" class="input" x-model="draft.storeEmail" :aria-invalid="!!errorFor('storeEmail')">
                                        <p class="field-error" x-show="errorFor('storeEmail')" x-text="errorFor('storeEmail')"></p>
                                    </div>
                                    <div class="min-[560px]:col-span-2">
                                        <label class="field-label" for="s-sphone">Telepon Toko</label>
                                        <input id="s-sphone" type="tel" class="input" x-model="draft.storePhone" :aria-invalid="!!errorFor('storePhone')">
                                        <p class="field-error" x-show="errorFor('storePhone')" x-text="errorFor('storePhone')"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="border-t border-divider pt-6">
                                <p class="text-[16px]/[24px] font-medium mb-4">Lokasi</p>
                                <div class="grid grid-cols-1 min-[560px]:grid-cols-2 gap-4">
                                    <div class="min-[560px]:col-span-2">
                                        <label class="field-label" for="s-addr">Alamat Jalan <span class="text-danger">*</span></label>
                                        <input id="s-addr" type="text" class="input" x-model="draft.streetAddress" :aria-invalid="!!errorFor('streetAddress')">
                                        <p class="field-error" x-show="errorFor('streetAddress')" x-text="errorFor('streetAddress')"></p>
                                    </div>
                                    <div>
                                        <label class="field-label" for="s-city">Kota <span class="text-danger">*</span></label>
                                        <input id="s-city" type="text" class="input" x-model="draft.city" :aria-invalid="!!errorFor('city')">
                                        <p class="field-error" x-show="errorFor('city')" x-text="errorFor('city')"></p>
                                    </div>
                                    <div>
                                        <label class="field-label" for="s-postal">Kode Pos</label>
                                        <input id="s-postal" type="text" class="input" maxlength="16" x-model="draft.postalCode" :aria-invalid="!!errorFor('postalCode')">
                                        <p class="field-error" x-show="errorFor('postalCode')" x-text="errorFor('postalCode')"></p>
                                    </div>
                                    <div>
                                        <label class="field-label" for="s-country">Negara <span class="text-danger">*</span></label>
                                        <select id="s-country" class="select" x-model="draft.country">
                                            <option>Indonesia</option><option>Singapura</option><option>Malaysia</option><option>Lainnya</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="border-t border-divider pt-6">
                                <p class="text-[16px]/[24px] font-medium mb-4">Jam Buka</p>
                                <div class="grid grid-cols-2 gap-4 max-w-md">
                                    <div>
                                        <label class="field-label" for="s-open">Jam Buka</label>
                                        <input id="s-open" type="time" class="input" x-model="draft.openingTime">
                                    </div>
                                    <div>
                                        <label class="field-label" for="s-close">Jam Tutup</label>
                                        <input id="s-close" type="time" class="input" x-model="draft.closingTime" :aria-invalid="!!errorFor('closingTime')">
                                        <p class="field-error" x-show="errorFor('closingTime')" x-text="errorFor('closingTime')"></p>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <span class="field-label">Hari Buka <span class="text-danger">*</span></span>
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="d in weekdays" :key="d">
                                            <button type="button" class="chip !h-10 !w-11 !px-0 justify-center" :aria-pressed="draft.openDays.includes(d)" @click="toggle(draft.openDays, d)" x-text="d"></button>
                                        </template>
                                    </div>
                                    <p class="field-error" x-show="errorFor('openDays')" x-text="errorFor('openDays')"></p>
                                    <p class="field-help">Jam semalam (tutup setelah tengah malam) tidak didukung demo.</p>
                                </div>
                            </div>
                            <div class="border-t border-divider pt-6">
                                <p class="text-[16px]/[24px] font-medium mb-4">Inventaris</p>
                                <div class="flex items-center justify-between rounded-xl border border-line px-4 py-3.5 max-w-lg">
                                    <div>
                                        <p class="text-[13.5px]/[19px] font-medium">Lacak inventaris</p>
                                        <p class="text-[12px]/[16px] text-ink-muted">Saat nonaktif, checkout tidak membatasi/mengurangi stok.</p>
                                    </div>
                                    <button type="button" role="switch" :aria-checked="draft.trackInventory" @click="draft.trackInventory = !draft.trackInventory"
                                            class="relative w-12 h-7 rounded-full transition-colors shrink-0" :class="draft.trackInventory ? 'bg-primary' : 'bg-[#d6dade]'">
                                        <span class="absolute top-1 size-5 rounded-full bg-white shadow transition-all" :class="draft.trackInventory ? 'left-6' : 'left-1'"></span>
                                    </button>
                                </div>
                                <div class="mt-4 max-w-xs">
                                    <label class="field-label" for="s-threshold">Ambang Stok Menipis Default</label>
                                    <input id="s-threshold" type="number" min="0" max="99999" class="input tabular-nums" x-model="draft.defaultLowStockThreshold" :aria-invalid="!!errorFor('defaultLowStockThreshold')">
                                    <p class="field-help">Default untuk produk baru; tidak mengubah produk yang ada.</p>
                                    <p class="field-error" x-show="errorFor('defaultLowStockThreshold')" x-text="errorFor('defaultLowStockThreshold')"></p>
                                </div>
                            </div>
                        </div>

                        {{-- ============ LAYANAN MEJA & ANTAR ============ --}}
                        <div x-show="activeTab === 'delivery'" x-cloak role="tabpanel" aria-label="Layanan Meja" class="space-y-6">
                            <div>
                                <p class="text-[16px]/[24px] font-medium mb-1">Biaya Layanan Antar ke Meja</p>
                                <p class="text-[13px]/[19px] text-ink-secondary mb-4">Atur apakah pelanggan dikenakan biaya tambahan saat memilih opsi pesanan diantar langsung ke meja.</p>
                                
                                <div class="rounded-xl border border-line p-4 space-y-4 bg-surface-muted/40">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <p class="text-[14px] font-medium text-ink">Aktifkan Biaya Antar ke Meja</p>
                                            <p class="text-[12.5px]/[18px] text-ink-secondary mt-0.5">Jika aktif, opsi "Diantar ke Meja" pada checkout pelanggan akan menambahkan biaya ini ke tagihan.</p>
                                        </div>
                                        <button type="button" role="switch" :aria-checked="draft.tableServiceFeeEnabled" @click="draft.tableServiceFeeEnabled = !draft.tableServiceFeeEnabled"
                                                class="relative w-12 h-7 rounded-full transition-colors shrink-0" :class="draft.tableServiceFeeEnabled ? 'bg-primary' : 'bg-[#d6dade]'">
                                            <span class="absolute top-1 size-5 rounded-full bg-white shadow transition-all" :class="draft.tableServiceFeeEnabled ? 'left-6' : 'left-1'"></span>
                                        </button>
                                    </div>

                                    <div x-show="draft.tableServiceFeeEnabled" class="pt-3 border-t border-line/60">
                                        <label class="field-label" for="s-delivery-fee">Nominal Biaya Antar ke Meja (Rp) <span class="text-danger">*</span></label>
                                        <div class="relative max-w-xs mt-1">
                                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-[13px] text-ink-muted font-medium">Rp</span>
                                            <input id="s-delivery-fee" type="number" min="0" step="500" class="input pl-10 tabular-nums"
                                                   x-model="draft.tableServiceFeeCents" placeholder="2000">
                                        </div>
                                        <p class="field-help">Contoh: Rp2.000 atau Rp5.000. Jika pelanggan memilih "Ambil Sendiri di Kasir", biaya ini gratis (Rp0).</p>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-divider pt-6">
                                <p class="text-[16px]/[24px] font-medium mb-1">Pratinjau di Layar Pelanggan</p>
                                <p class="text-[13px]/[19px] text-ink-secondary mb-3">Berikut adalah simulasi bagaimana opsi ini muncul saat pelanggan melakukan checkout:</p>
                                <div class="rounded-2xl border border-[#49ADAB]/30 bg-[#49ADAB]/5 p-4 max-w-md space-y-2.5">
                                    <div class="flex items-center justify-between p-3 rounded-xl bg-white border border-[#49ADAB] shadow-2xs">
                                        <div class="flex items-center gap-2.5">
                                            <span class="size-6 rounded-full bg-[#49ADAB] text-white text-xs grid place-items-center font-bold">✓</span>
                                            <div>
                                                <p class="text-xs font-bold text-slate-900">Diantar ke Meja</p>
                                                <p class="text-[11px] text-slate-500">Pelayan mengantar hidangan ke meja Anda</p>
                                            </div>
                                        </div>
                                        <span class="text-xs font-bold text-[#2E8B89]" x-text="draft.tableServiceFeeEnabled ? $rp(draft.tableServiceFeeCents || 2000) : 'Gratis'"></span>
                                    </div>
                                    <div class="flex items-center justify-between p-3 rounded-xl bg-white/70 border border-slate-200">
                                        <div class="flex items-center gap-2.5">
                                            <span class="size-6 rounded-full border border-slate-300 grid place-items-center"></span>
                                            <div>
                                                <p class="text-xs font-medium text-slate-700">Ambil Sendiri di Kasir</p>
                                                <p class="text-[11px] text-slate-400">Ambil di bar/kasir saat pesanan sudah Ready</p>
                                            </div>
                                        </div>
                                        <span class="text-xs font-medium text-slate-500">Gratis (Rp 0)</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ============ PEMBAYARAN ============ --}}
                        <div x-show="activeTab === 'payments'" x-cloak role="tabpanel" aria-label="Pembayaran" class="space-y-6">
                            <div>
                                <p class="text-[16px]/[24px] font-medium mb-4">Metode</p>
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between rounded-xl border border-line px-4 py-3.5">
                                        <div class="flex items-center gap-3">
                                            <span class="grid size-10 place-items-center rounded-full bg-primary-soft text-focus"><x-icon name="banknote" class="size-5"/></span>
                                            <div>
                                                <p class="text-[13.5px]/[19px] font-medium">Tunai</p>
                                                <p class="text-[12px]/[16px] text-ink-muted">Simulasi pembayaran tunai.</p>
                                            </div>
                                        </div>
                                        <button type="button" role="switch" :aria-checked="draft.payments.cash" @click="draft.payments.cash = !draft.payments.cash"
                                                class="relative w-12 h-7 rounded-full transition-colors shrink-0" :class="draft.payments.cash ? 'bg-primary' : 'bg-[#d6dade]'">
                                            <span class="absolute top-1 size-5 rounded-full bg-white shadow transition-all" :class="draft.payments.cash ? 'left-6' : 'left-1'"></span>
                                        </button>
                                    </div>
                                    <div class="flex items-center justify-between rounded-xl border border-line px-4 py-3.5">
                                        <div class="flex items-center gap-3">
                                            <span class="grid size-10 place-items-center rounded-full bg-primary-soft text-focus"><x-icon name="credit-card" class="size-5"/></span>
                                            <div>
                                                <p class="text-[13.5px]/[19px] font-medium">Kartu</p>
                                                <p class="text-[12px]/[16px] text-ink-muted">Simulasi — tanpa nomor kartu/CVV.</p>
                                            </div>
                                        </div>
                                        <button type="button" role="switch" :aria-checked="draft.payments.card" @click="draft.payments.card = !draft.payments.card"
                                                class="relative w-12 h-7 rounded-full transition-colors shrink-0" :class="draft.payments.card ? 'bg-primary' : 'bg-[#d6dade]'">
                                            <span class="absolute top-1 size-5 rounded-full bg-white shadow transition-all" :class="draft.payments.card ? 'left-6' : 'left-1'"></span>
                                        </button>
                                    </div>
                                    <div class="flex items-center justify-between rounded-xl border border-line px-4 py-3.5">
                                        <div class="flex items-center gap-3">
                                            <span class="grid size-10 place-items-center rounded-full bg-primary-soft text-focus"><x-icon name="wallet" class="size-5"/></span>
                                            <div>
                                                <p class="text-[13.5px]/[19px] font-medium">Dompet Digital</p>
                                                <p class="text-[12px]/[16px] text-ink-muted">Simulasi — tanpa akun penyedia.</p>
                                            </div>
                                        </div>
                                        <button type="button" role="switch" :aria-checked="draft.payments.wallet" @click="draft.payments.wallet = !draft.payments.wallet"
                                                class="relative w-12 h-7 rounded-full transition-colors shrink-0" :class="draft.payments.wallet ? 'bg-primary' : 'bg-[#d6dade]'">
                                            <span class="absolute top-1 size-5 rounded-full bg-white shadow transition-all" :class="draft.payments.wallet ? 'left-6' : 'left-1'"></span>
                                        </button>
                                    </div>
                                </div>
                                <p class="field-error" x-show="errorFor('payments')" x-text="errorFor('payments')"></p>
                                <p class="field-help mt-3">Metode pembayaran disimulasikan. Tidak ada dana yang dibebankan.</p>
                            </div>
                            <div class="border-t border-divider pt-6">
                                <p class="text-[16px]/[24px] font-medium mb-4">Default</p>
                                <div class="max-w-xs">
                                    <label class="field-label" for="s-defpay">Metode Pembayaran Default</label>
                                    <select id="s-defpay" class="select" x-model="draft.defaultPaymentMethod" :aria-invalid="!!errorFor('defaultPaymentMethod')">
                                        <option value="Cash" :disabled="!draft.payments.cash">Tunai</option>
                                        <option value="Card" :disabled="!draft.payments.card">Kartu</option>
                                        <option value="Digital Wallet" :disabled="!draft.payments.wallet">Dompet Digital</option>
                                    </select>
                                    <p class="field-error" x-show="errorFor('defaultPaymentMethod')" x-text="errorFor('defaultPaymentMethod')"></p>
                                </div>
                            </div>
                            <div class="border-t border-divider pt-6">
                                <p class="text-[16px]/[24px] font-medium mb-4">Tunai</p>
                                <div class="flex items-center justify-between rounded-xl border border-line px-4 py-3.5 max-w-lg">
                                    <div>
                                        <p class="text-[13.5px]/[19px] font-medium">Izinkan kembalian</p>
                                        <p class="text-[12px]/[16px] text-ink-muted">Jika nonaktif, uang diterima harus tepat sama dengan total.</p>
                                    </div>
                                    <button type="button" role="switch" :aria-checked="draft.allowCashChange" @click="draft.allowCashChange = !draft.allowCashChange"
                                            class="relative w-12 h-7 rounded-full transition-colors shrink-0" :class="draft.allowCashChange ? 'bg-primary' : 'bg-[#d6dade]'">
                                        <span class="absolute top-1 size-5 rounded-full bg-white shadow transition-all" :class="draft.allowCashChange ? 'left-6' : 'left-1'"></span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- ============ PAJAK ============ --}}
                        <div x-show="activeTab === 'taxes'" x-cloak role="tabpanel" aria-label="Pajak" class="space-y-6">
                            <div class="flex items-center justify-between rounded-xl border border-line px-4 py-3.5 max-w-lg">
                                <div>
                                    <p class="text-[13.5px]/[19px] font-medium">Aktifkan pajak</p>
                                    <p class="text-[12px]/[16px] text-ink-muted">Nonaktif membuat pajak 0 untuk penjualan baru.</p>
                                </div>
                                <button type="button" role="switch" :aria-checked="draft.taxEnabled" @click="draft.taxEnabled = !draft.taxEnabled"
                                        class="relative w-12 h-7 rounded-full transition-colors shrink-0" :class="draft.taxEnabled ? 'bg-primary' : 'bg-[#d6dade]'">
                                    <span class="absolute top-1 size-5 rounded-full bg-white shadow transition-all" :class="draft.taxEnabled ? 'left-6' : 'left-1'"></span>
                                </button>
                            </div>
                            <div class="grid grid-cols-1 min-[560px]:grid-cols-2 gap-4 max-w-xl">
                                <div>
                                    <label class="field-label" for="s-taxname">Nama Pajak <span class="text-danger" x-show="draft.taxEnabled">*</span></label>
                                    <input id="s-taxname" type="text" class="input" x-model="draft.taxName" maxlength="30" :aria-invalid="!!errorFor('taxName')">
                                    <p class="field-error" x-show="errorFor('taxName')" x-text="errorFor('taxName')"></p>
                                </div>
                                <div>
                                    <label class="field-label" for="s-taxrate">Tarif Pajak (%)</label>
                                    <input id="s-taxrate" type="number" min="0" max="25" step="0.01" class="input tabular-nums" x-model="draft.taxRatePercent" :aria-invalid="!!errorFor('taxRatePercent')">
                                    <p class="field-error" x-show="errorFor('taxRatePercent')" x-text="errorFor('taxRatePercent')"></p>
                                </div>
                            </div>
                            <div class="border-t border-divider pt-6 max-w-xl">
                                <p class="text-[16px]/[24px] font-medium mb-4">Perhitungan</p>
                                <div class="grid grid-cols-1 min-[560px]:grid-cols-2 gap-4">
                                    <div>
                                        <label class="field-label" for="s-taxcalc">Perhitungan Pajak</label>
                                        <input id="s-taxcalc" type="text" class="input" value="Ditambahkan setelah diskon" disabled>
                                    </div>
                                    <div class="grid place-items-end">
                                        <p class="field-help">Pajak dihitung sekali dari subtotal neto, pembulatan half-up.</p>
                                    </div>
                                </div>
                                <p class="field-help mt-3">Konfigurasi pajak demo — 8% adalah fixture contoh, bukan ketentuan pajak wilayah.</p>
                            </div>
                        </div>

                        {{-- ============ STRUK ============ --}}
                        <div x-show="activeTab === 'receipt'" x-cloak role="tabpanel" aria-label="Struk">
                            <div class="grid grid-cols-1 min-[880px]:grid-cols-[minmax(0,1fr)_280px] gap-6">
                                <div class="space-y-6">
                                    <div>
                                        <p class="text-[16px]/[24px] font-medium mb-4">Branding</p>
                                        <div>
                                            <label class="field-label" for="s-rhead">Header Struk <span class="text-danger">*</span></label>
                                            <input id="s-rhead" type="text" class="input" x-model="draft.receiptHeader" maxlength="80" :aria-invalid="!!errorFor('receiptHeader')">
                                            <p class="field-error" x-show="errorFor('receiptHeader')" x-text="errorFor('receiptHeader')"></p>
                                        </div>
                                    </div>
                                    <div class="border-t border-divider pt-6">
                                        <p class="text-[16px]/[24px] font-medium mb-4">Konten</p>
                                        <div class="max-w-md">
                                            <label class="field-label" for="s-rfoot">Pesan Footer</label>
                                            <textarea id="s-rfoot" class="textarea !min-h-[60px]" x-model="draft.receiptFooter" maxlength="160"></textarea>
                                        </div>
                                        <div class="mt-4 space-y-3 max-w-lg">
                                            <div class="flex items-center justify-between rounded-xl border border-line px-4 py-3">
                                                <p class="text-[13.5px]/[19px]">Tampilkan alamat toko</p>
                                                <button type="button" role="switch" :aria-checked="draft.receiptShowAddress" @click="draft.receiptShowAddress = !draft.receiptShowAddress"
                                                        class="relative w-12 h-7 rounded-full transition-colors shrink-0" :class="draft.receiptShowAddress ? 'bg-primary' : 'bg-[#d6dade]'">
                                                    <span class="absolute top-1 size-5 rounded-full bg-white shadow transition-all" :class="draft.receiptShowAddress ? 'left-6' : 'left-1'"></span>
                                                </button>
                                            </div>
                                            <div class="flex items-center justify-between rounded-xl border border-line px-4 py-3">
                                                <p class="text-[13.5px]/[19px]">Tampilkan nama kasir</p>
                                                <button type="button" role="switch" :aria-checked="draft.receiptShowCashier" @click="draft.receiptShowCashier = !draft.receiptShowCashier"
                                                        class="relative w-12 h-7 rounded-full transition-colors shrink-0" :class="draft.receiptShowCashier ? 'bg-primary' : 'bg-[#d6dade]'">
                                                    <span class="absolute top-1 size-5 rounded-full bg-white shadow transition-all" :class="draft.receiptShowCashier ? 'left-6' : 'left-1'"></span>
                                                </button>
                                            </div>
                                            <div class="flex items-center justify-between rounded-xl border border-line px-4 py-3">
                                                <p class="text-[13.5px]/[19px]">Tampilkan nama pelanggan</p>
                                                <button type="button" role="switch" :aria-checked="draft.receiptShowCustomer" @click="draft.receiptShowCustomer = !draft.receiptShowCustomer"
                                                        class="relative w-12 h-7 rounded-full transition-colors shrink-0" :class="draft.receiptShowCustomer ? 'bg-primary' : 'bg-[#d6dade]'">
                                                    <span class="absolute top-1 size-5 rounded-full bg-white shadow transition-all" :class="draft.receiptShowCustomer ? 'left-6' : 'left-1'"></span>
                                                </button>
                                            </div>
                                            <div class="flex items-center justify-between rounded-xl border border-line px-4 py-3">
                                                <p class="text-[13.5px]/[19px]">Tampilkan rincian pajak</p>
                                                <button type="button" role="switch" :aria-checked="draft.receiptShowTax" @click="draft.receiptShowTax = !draft.receiptShowTax"
                                                        class="relative w-12 h-7 rounded-full transition-colors shrink-0" :class="draft.receiptShowTax ? 'bg-primary' : 'bg-[#d6dade]'">
                                                    <span class="absolute top-1 size-5 rounded-full bg-white shadow transition-all" :class="draft.receiptShowTax ? 'left-6' : 'left-1'"></span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="border-t border-divider pt-6">
                                        <p class="text-[16px]/[24px] font-medium mb-4">Tata Letak</p>
                                        <div class="max-w-xs">
                                            <label class="field-label" for="s-rwidth">Lebar Struk</label>
                                            <select id="s-rwidth" class="select" x-model="draft.receiptWidth">
                                                <option value="80 mm">Lebar 80 mm (Standar Profesional / Desktop)</option>
                                                <option value="58 mm">Lebar 58 mm (Standar Mobile / Portable Bluetooth)</option>
                                            </select>
                                            <p class="field-help">Format ukuran struk setelah checkout dan pembayaran (Epson/Xprinter/Thermal 80mm).</p>
                                        </div>
                                    </div>
                                </div>
                                {{-- pratinjau --}}
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <p class="text-[12.5px]/[17px] font-medium text-ink-secondary">Pratinjau Struk</p>
                                        <span class="badge badge-amber" x-show="dirty">Draf</span>
                                    </div>
                                    <div class="rounded-xl bg-surface-muted border border-dashed border-[#d6dade] p-3">
                                        <div class="bg-white rounded-lg px-3.5 py-4 font-mono text-[11px]/[16px] text-ink shadow-sm" :style="draft.receiptWidth === '58 mm' ? 'max-width: 220px; margin: 0 auto;' : 'max-width: 320px; margin: 0 auto;'">
                                            <p class="text-center font-bold text-[12.5px]" x-text="draft.receiptHeader"></p>
                                            <p class="text-center text-ink-muted">Struk demo — sampel</p>
                                            <p class="text-center" x-show="draft.receiptShowAddress">
                                                <span x-text="draft.streetAddress"></span><br>
                                                <span x-text="`${draft.city} ${draft.postalCode}`"></span>
                                            </p>
                                            <div class="border-t border-dashed border-[#c9ced4] my-2"></div>
                                            <template x-for="it in previewItems" :key="it.name">
                                                <div class="flex justify-between gap-2">
                                                    <span x-text="`${it.name} ×${it.quantity}`"></span>
                                                    <span x-text="(it.unitPrice * it.quantity).toLocaleString('id-ID')"></span>
                                                </div>
                                            </template>
                                            <div class="border-t border-dashed border-[#c9ced4] my-2"></div>
                                            <div class="flex justify-between"><span>Subtotal</span><span x-text="previewTotals.subtotal.toLocaleString('id-ID')"></span></div>
                                            <div class="flex justify-between" x-show="draft.receiptShowTax">
                                                <span x-text="`${draft.taxName} (${draft.taxRatePercent}%)`"></span>
                                                <span x-text="previewTotals.tax.toLocaleString('id-ID')"></span>
                                            </div>
                                            <div class="flex justify-between font-bold border-t border-dashed border-[#c9ced4] mt-1.5 pt-1.5">
                                                <span>TOTAL</span><span x-text="previewTotals.total.toLocaleString('id-ID')"></span>
                                            </div>
                                            <div class="flex justify-between" x-show="draft.receiptShowCashier"><span>Kasir</span><span>John Doe</span></div>
                                            <div class="flex justify-between" x-show="draft.receiptShowCustomer"><span>Pelanggan</span><span>Pelanggan Umum</span></div>
                                            <div class="border-t border-dashed border-[#c9ced4] my-2"></div>
                                            <p class="text-center text-ink-muted" x-text="draft.receiptFooter"></p>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-secondary btn-sm w-full mt-3" @click="downloadSampleReceipt()">
                                        <x-icon name="download" class="size-4"/> Unduh Contoh
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- ============ NOTIFIKASI ============ --}}
                        <div x-show="activeTab === 'notifications'" x-cloak role="tabpanel" aria-label="Notifikasi" class="space-y-6">
                            <div>
                                <p class="text-[16px]/[24px] font-medium mb-4">Dalam Aplikasi</p>
                                <div class="space-y-3 max-w-lg">
                                    <div class="flex items-center justify-between rounded-xl border border-line px-4 py-3.5">
                                        <div>
                                            <p class="text-[13.5px]/[19px] font-medium">Peringatan stok menipis</p>
                                            <p class="text-[12px]/[16px] text-ink-muted">Memakai ambang per produk atau default inventaris.</p>
                                        </div>
                                        <button type="button" role="switch" :aria-checked="draft.notifLowStock" @click="draft.notifLowStock = !draft.notifLowStock"
                                                class="relative w-12 h-7 rounded-full transition-colors shrink-0" :class="draft.notifLowStock ? 'bg-primary' : 'bg-[#d6dade]'">
                                            <span class="absolute top-1 size-5 rounded-full bg-white shadow transition-all" :class="draft.notifLowStock ? 'left-6' : 'left-1'"></span>
                                        </button>
                                    </div>
                                    <div class="flex items-center justify-between rounded-xl border border-line px-4 py-3.5">
                                        <div>
                                            <p class="text-[13.5px]/[19px] font-medium">Peringatan pesanan baru</p>
                                            <p class="text-[12px]/[16px] text-ink-muted">Saat pesanan baru menjadi Menunggu.</p>
                                        </div>
                                        <button type="button" role="switch" :aria-checked="draft.notifNewOrder" @click="draft.notifNewOrder = !draft.notifNewOrder"
                                                class="relative w-12 h-7 rounded-full transition-colors shrink-0" :class="draft.notifNewOrder ? 'bg-primary' : 'bg-[#d6dade]'">
                                            <span class="absolute top-1 size-5 rounded-full bg-white shadow transition-all" :class="draft.notifNewOrder ? 'left-6' : 'left-1'"></span>
                                        </button>
                                    </div>
                                    <div class="flex items-center justify-between rounded-xl border border-line px-4 py-3.5">
                                        <div>
                                            <p class="text-[13.5px]/[19px] font-medium">Peringatan permintaan retur</p>
                                            <p class="text-[12px]/[16px] text-ink-muted">Saat retur demo dibuat.</p>
                                        </div>
                                        <button type="button" role="switch" :aria-checked="draft.notifReturn" @click="draft.notifReturn = !draft.notifReturn"
                                                class="relative w-12 h-7 rounded-full transition-colors shrink-0" :class="draft.notifReturn ? 'bg-primary' : 'bg-[#d6dade]'">
                                            <span class="absolute top-1 size-5 rounded-full bg-white shadow transition-all" :class="draft.notifReturn ? 'left-6' : 'left-1'"></span>
                                        </button>
                                    </div>
                                    <div class="rounded-xl border border-line px-4 py-3.5">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-[13.5px]/[19px] font-medium">Peringatan diskon akan berakhir</p>
                                                <p class="text-[12px]/[16px] text-ink-muted">Pemberitahuan demo, bukan penjadwal waktu nyata.</p>
                                            </div>
                                            <button type="button" role="switch" :aria-checked="draft.notifDiscountExpiry" @click="draft.notifDiscountExpiry = !draft.notifDiscountExpiry"
                                                    class="relative w-12 h-7 rounded-full transition-colors shrink-0" :class="draft.notifDiscountExpiry ? 'bg-primary' : 'bg-[#d6dade]'">
                                                <span class="absolute top-1 size-5 rounded-full bg-white shadow transition-all" :class="draft.notifDiscountExpiry ? 'left-6' : 'left-1'"></span>
                                            </button>
                                        </div>
                                        <div class="mt-3 max-w-[200px]" x-show="draft.notifDiscountExpiry">
                                            <label class="field-label" for="s-expdays">Beritahu sebelum berakhir</label>
                                            <select id="s-expdays" class="select" x-model.number="draft.discountExpiryDays">
                                                <option :value="1">1 hari</option><option :value="3">3 hari</option><option :value="7">7 hari</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="border-t border-divider pt-6">
                                <p class="text-[16px]/[24px] font-medium mb-4">Suara</p>
                                <div class="flex items-center justify-between rounded-xl border border-line px-4 py-3.5 max-w-lg">
                                    <div>
                                        <p class="text-[13.5px]/[19px] font-medium">Peringatan suara</p>
                                        <p class="text-[12px]/[16px] text-ink-muted">Jika aktif, audio hanya dari aksi pengguna dan dapat dihentikan.</p>
                                    </div>
                                    <button type="button" role="switch" :aria-checked="draft.soundAlerts" @click="draft.soundAlerts = !draft.soundAlerts"
                                            class="relative w-12 h-7 rounded-full transition-colors shrink-0" :class="draft.soundAlerts ? 'bg-primary' : 'bg-[#d6dade]'">
                                        <span class="absolute top-1 size-5 rounded-full bg-white shadow transition-all" :class="draft.soundAlerts ? 'left-6' : 'left-1'"></span>
                                    </button>
                                </div>
                                <button type="button" class="btn btn-secondary btn-sm mt-4" @click="testNotification()">Uji Notifikasi Dalam Aplikasi</button>
                            </div>
                        </div>

                        {{-- ============ TAMPILAN ============ --}}
                        <div x-show="activeTab === 'appearance'" x-cloak role="tabpanel" aria-label="Tampilan" class="space-y-6">
                            <div>
                                <p class="text-[16px]/[24px] font-medium mb-4">Tema</p>
                                <div class="max-w-xs">
                                    <label class="field-label" for="s-theme">Tema</label>
                                    <select id="s-theme" class="select" x-model="draft.theme">
                                        <option>Light</option>
                                    </select>
                                    <p class="field-help">Satu tema diterapkan pada demo.</p>
                                </div>
                            </div>
                            <div class="border-t border-divider pt-6">
                                <p class="text-[16px]/[24px] font-medium mb-4">Brand</p>
                                <div class="flex items-center gap-3.5">
                                    <span class="grid size-12 place-items-center rounded-xl bg-sidebar text-white"><x-icon name="shopping-bag" class="size-5.5"/></span>
                                    <div>
                                        <p class="text-[13.5px]/[19px] font-medium">PosyMart Teal <span class="font-mono text-[12px] text-ink-muted">#49ADAB</span></p>
                                        <p class="text-[12px]/[16px] text-ink-muted">Warna aksen hanya-baca pada demo; sidebar tetap #4CB0AE.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="border-t border-divider pt-6">
                                <p class="text-[16px]/[24px] font-medium mb-4">Tata Letak</p>
                                <div class="max-w-xs">
                                    <label class="field-label" for="s-density">Kerapatan Tabel</label>
                                    <select id="s-density" class="select" x-model="draft.tableDensity">
                                        <option>Nyaman</option>
                                        <option>Kompak</option>
                                    </select>
                                </div>
                            </div>
                            <div class="border-t border-divider pt-6">
                                <p class="text-[16px]/[24px] font-medium mb-4">Gerakan</p>
                                <div class="flex items-center justify-between rounded-xl border border-line px-4 py-3.5 max-w-lg">
                                    <div>
                                        <p class="text-[13.5px]/[19px] font-medium">Kurangi gerakan</p>
                                        <p class="text-[12px]/[16px] text-ink-muted">Preferensi OS reduced-motion tetap diutamakan.</p>
                                    </div>
                                    <button type="button" role="switch" :aria-checked="draft.reduceMotion" @click="draft.reduceMotion = !draft.reduceMotion"
                                            class="relative w-12 h-7 rounded-full transition-colors shrink-0" :class="draft.reduceMotion ? 'bg-primary' : 'bg-[#d6dade]'">
                                        <span class="absolute top-1 size-5 rounded-full bg-white shadow transition-all" :class="draft.reduceMotion ? 'left-6' : 'left-1'"></span>
                                    </button>
                                </div>
                            </div>
                            <div class="border-t border-divider pt-6 space-y-3">
                                <button type="button" class="btn btn-secondary" @click="resetDefaultsOpen = true">
                                    <x-icon name="refresh" class="size-4"/> Reset ke Default
                                </button>
                                <div class="rounded-xl border border-danger/30 bg-danger-soft/50 px-4 py-4 max-w-lg">
                                    <p class="text-[13.5px]/[19px] font-medium text-danger">Reset data demo</p>
                                    <p class="text-[12.5px]/[18px] text-danger/85 mt-1">Mengembalikan SELURUH modul (produk, penjualan, pesanan, retur, diskon, pengguna, pengaturan, draf POS) ke seed awal.</p>
                                    <button type="button" class="btn btn-danger btn-sm mt-3" @click="resetDataOpen = true">Reset Semua Data Demo</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>

    {{-- ================= DIALOG BUANG SEMUA PERUBAHAN ================= --}}
    <div x-show="discardOpen" x-cloak class="fixed inset-0 z-[95] grid place-items-center p-4" role="dialog" aria-modal="true" aria-label="Buang semua perubahan">
        <div class="absolute inset-0 bg-[#1c2b2b]/50" @click="discardOpen = false"></div>
        <div class="relative w-full max-w-[440px] rounded-2xl bg-white shadow-[0_24px_80px_rgba(30,41,59,0.22)] p-6" x-transition:enter="pop-enter" x-transition:leave="pop-leave">
            <h2 class="text-[18px]/[24px] font-semibold">Buang semua perubahan?</h2>
            <p class="text-[13.5px]/[20px] text-ink-secondary mt-2">Semua draf pada seluruh tab akan dikembalikan ke pengaturan tersimpan.</p>
            <div class="grid grid-cols-2 gap-3 mt-5">
                <button type="button" class="btn btn-secondary" @click="discardOpen = false">Lanjut Mengedit</button>
                <button type="button" class="btn btn-danger" @click="draft = JSON.parse(JSON.stringify(saved)); errors = {}; discardOpen = false">Buang Perubahan</button>
            </div>
        </div>
    </div>

    {{-- ================= DIALOG RESET DEFAULT ================= --}}
    <div x-show="resetDefaultsOpen" x-cloak class="fixed inset-0 z-[95] grid place-items-center p-4" role="dialog" aria-modal="true" aria-label="Reset ke default">
        <div class="absolute inset-0 bg-[#1c2b2b]/50" @click="resetDefaultsOpen = false"></div>
        <div class="relative w-full max-w-[440px] rounded-2xl bg-white shadow-[0_24px_80px_rgba(30,41,59,0.22)] p-6" x-transition:enter="pop-enter" x-transition:leave="pop-leave">
            <h2 class="text-[18px]/[24px] font-semibold">Reset pengaturan demo?</h2>
            <p class="text-[13.5px]/[20px] text-ink-secondary mt-2">Default akan dimuat ke draf. Tekan Simpan Perubahan untuk menerapkan. Produk, pesanan, dan pengguna tidak terpengaruh.</p>
            <div class="grid grid-cols-2 gap-3 mt-5">
                <button type="button" class="btn btn-secondary" @click="resetDefaultsOpen = false">Batal</button>
                <button type="button" class="btn btn-primary" @click="resetToDefaults()">Muat Default</button>
            </div>
        </div>
    </div>

    {{-- ================= DIALOG RESET DATA DEMO ================= --}}
    <div x-show="resetDataOpen" x-cloak class="fixed inset-0 z-[95] grid place-items-center p-4" role="dialog" aria-modal="true" aria-label="Reset semua data demo">
        <div class="absolute inset-0 bg-[#1c2b2b]/50" @click="resetDataOpen = false"></div>
        <div class="relative w-full max-w-[460px] rounded-2xl bg-white shadow-[0_24px_80px_rgba(30,41,59,0.22)] p-6" x-transition:enter="pop-enter" x-transition:leave="pop-leave">
            <h2 class="text-[18px]/[24px] font-semibold text-danger">Reset semua data demo?</h2>
            <p class="text-[13.5px]/[20px] text-ink-secondary mt-2">Seluruh data dan draf akan kembali ke kondisi seed awal: 12 produk, 5 pelanggan, 56 transaksi, 7 pesanan, 5 retur, 7 diskon, 6 pengguna, dan pengaturan default. Tindakan ini tidak dapat dibatalkan.</p>
            <div class="grid grid-cols-2 gap-3 mt-5">
                <button type="button" class="btn btn-secondary" @click="resetDataOpen = false">Batal</button>
                <button type="button" class="btn btn-danger" @click="resetDemoData()">Reset Data Demo</button>
            </div>
        </div>
    </div>
</div>
@endsection
