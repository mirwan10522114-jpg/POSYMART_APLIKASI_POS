<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#4CB0AE">
    <title>Masuk Admin · PosyMart</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%234CB0AE' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z'/%3E%3Cpath d='M3 6h18'/%3E%3Cpath d='M16 10a4 4 0 0 1-8 0'/%3E%3C/svg%3E">
</head>
<body class="bg-gradient-to-br from-slate-900 via-[#182a2a] to-[#0f1d1d] min-h-screen text-slate-800 flex items-center justify-center p-4 sm:p-6 antialiased selection:bg-[#4CB0AE] selection:text-white">

    <div class="w-full max-w-md my-auto relative" x-data="{
        showPassword: false,
        email: '{{ old('email', '') }}',
        password: '',
        fillAccount(email, pwd = 'admin123') {
            this.email = email;
            this.password = pwd;
        }
    }">

        {{-- Decorative Glow Background Elements --}}
        <div class="absolute -top-12 -left-12 size-48 bg-[#4CB0AE]/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-12 -right-12 size-48 bg-teal-500/15 rounded-full blur-3xl pointer-events-none"></div>

        {{-- Main Login Card --}}
        <div class="relative bg-white/95 backdrop-blur-xl rounded-3xl p-7 sm:p-9 shadow-[0_20px_60px_rgba(0,0,0,0.35)] border border-white/20">

            {{-- Header & Logo --}}
            <div class="text-center space-y-2 mb-7">
                <div class="inline-flex items-center justify-center size-14 rounded-2xl bg-gradient-to-tr from-[#3a8b89] to-[#5ec7c5] text-white shadow-lg shadow-[#4CB0AE]/30 mb-2">
                    <x-icon name="shopping-bag" class="size-7"/>
                </div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900">
                    Posy<span class="text-[#2E8B89]">Mart</span>
                </h1>
                <p class="text-xs text-slate-500 font-medium">
                    Portal Masuk Administrator &amp; Manajemen POS
                </p>
            </div>

            {{-- Status Alert (e.g. Logout Message) --}}
            @if (session('status'))
                <div class="mb-5 p-3.5 rounded-xl bg-teal-50 border border-teal-200/80 text-teal-800 text-xs flex items-center gap-2.5">
                    <x-icon name="check-circle" class="size-4 shrink-0 text-teal-600"/>
                    <span class="font-medium">{{ session('status') }}</span>
                </div>
            @endif

            {{-- Error Alerts --}}
            @if ($errors->any())
                <div class="mb-5 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs space-y-1">
                    <div class="flex items-center gap-2 font-bold text-rose-900">
                        <x-icon name="alert-triangle" class="size-4 shrink-0 text-rose-600"/>
                        <span>Gagal Masuk</span>
                    </div>
                    <ul class="list-disc list-inside pl-1 text-[11.5px] space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Form Login --}}
            <form method="POST" action="{{ route('login.post') }}" class="space-y-4.5">
                @csrf

                {{-- Email / Username Input --}}
                <div class="space-y-1.5">
                    <label for="email" class="block text-xs font-bold text-slate-700">
                        Email atau Username
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <x-icon name="user" class="size-4.5"/>
                        </span>
                        <input id="email"
                               name="email"
                               type="text"
                               x-model="email"
                               required
                               autofocus
                               autocomplete="username"
                               placeholder="admin@posymart.com"
                               class="input pl-10 text-sm h-11 w-full bg-slate-50/80 border-slate-200 focus:bg-white focus:border-[#4CB0AE] focus:ring-2 focus:ring-[#4CB0AE]/20 transition rounded-xl">
                    </div>
                </div>

                {{-- Password Input --}}
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label for="password" class="block text-xs font-bold text-slate-700">
                            Kata Sandi
                        </label>
                    </div>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <x-icon name="lock" class="size-4.5"/>
                        </span>
                        <input id="password"
                               name="password"
                               :type="showPassword ? 'text' : 'password'"
                               x-model="password"
                               required
                               autocomplete="current-password"
                               placeholder="••••••••"
                               class="input pl-10 pr-10 text-sm h-11 w-full bg-slate-50/80 border-slate-200 focus:bg-white focus:border-[#4CB0AE] focus:ring-2 focus:ring-[#4CB0AE]/20 transition rounded-xl">
                        <button type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition"
                                tabindex="-1"
                                :title="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">
                            <x-icon name="eye" class="size-4.5" x-show="!showPassword"/>
                            <x-icon name="eye-off" class="size-4.5" x-show="showPassword" x-cloak/>
                        </button>
                    </div>
                </div>

                {{-- Remember Me & Help --}}
                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox"
                               name="remember"
                               value="1"
                               class="rounded border-slate-300 text-[#4CB0AE] focus:ring-[#4CB0AE]/30 size-4">
                        <span class="text-xs text-slate-600 font-medium">Ingat saya</span>
                    </label>
                    <span class="text-[11.5px] text-slate-400">Akses Terenkripsi</span>
                </div>

                {{-- Submit Button --}}
                <button type="submit"
                        class="w-full h-11 bg-gradient-to-r from-[#3a8b89] to-[#4CB0AE] hover:from-[#2e7472] hover:to-[#3e9e9c] text-white font-bold text-sm rounded-xl shadow-md shadow-[#4CB0AE]/25 transition-all flex items-center justify-center gap-2 mt-2 active:scale-[0.99]">
                    <span>Masuk ke Panel Admin</span>
                    <x-icon name="arrow-right" class="size-4"/>
                </button>
            </form>

            {{-- Quick Demo Credentials Box --}}
            <div class="mt-7 pt-5 border-t border-slate-100 space-y-2.5">
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider text-center">
                    Kredensial Akun Resmi (Sandi: admin123)
                </p>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <button type="button"
                            @click="fillAccount('owner@posymart.com')"
                            class="p-2 rounded-xl border border-slate-200/80 bg-slate-50/70 hover:bg-teal-50/70 hover:border-[#4CB0AE]/40 transition text-left group">
                        <p class="font-bold text-slate-800 text-[11px] group-hover:text-[#2E8B89]">👑 Owner</p>
                        <p class="text-[10px] text-slate-500 font-mono mt-0.5 truncate">owner@posymart.com</p>
                    </button>
                    <button type="button"
                            @click="fillAccount('admin@posymart.com')"
                            class="p-2 rounded-xl border border-slate-200/80 bg-slate-50/70 hover:bg-teal-50/70 hover:border-[#4CB0AE]/40 transition text-left group">
                        <p class="font-bold text-slate-800 text-[11px] group-hover:text-[#2E8B89]">🔑 Admin</p>
                        <p class="text-[10px] text-slate-500 font-mono mt-0.5 truncate">admin@posymart.com</p>
                    </button>
                </div>
                <div class="grid grid-cols-3 gap-1.5 text-xs">
                    <button type="button"
                            @click="fillAccount('kasir1@posymart.com')"
                            class="p-1.5 rounded-xl border border-slate-200/80 bg-slate-50/70 hover:bg-teal-50/70 hover:border-[#4CB0AE]/40 transition text-center group">
                        <p class="font-bold text-slate-800 text-[10.5px] group-hover:text-[#2E8B89]">☕ Kasir 1</p>
                        <p class="text-[9px] text-slate-400 font-mono truncate">kasir1@</p>
                    </button>
                    <button type="button"
                            @click="fillAccount('kasir2@posymart.com')"
                            class="p-1.5 rounded-xl border border-slate-200/80 bg-slate-50/70 hover:bg-teal-50/70 hover:border-[#4CB0AE]/40 transition text-center group">
                        <p class="font-bold text-slate-800 text-[10.5px] group-hover:text-[#2E8B89]">☕ Kasir 2</p>
                        <p class="text-[9px] text-slate-400 font-mono truncate">kasir2@</p>
                    </button>
                    <button type="button"
                            @click="fillAccount('kasir3@posymart.com')"
                            class="p-1.5 rounded-xl border border-slate-200/80 bg-slate-50/70 hover:bg-teal-50/70 hover:border-[#4CB0AE]/40 transition text-center group">
                        <p class="font-bold text-slate-800 text-[10.5px] group-hover:text-[#2E8B89]">☕ Kasir 3</p>
                        <p class="text-[9px] text-slate-400 font-mono truncate">kasir3@</p>
                    </button>
                </div>
            </div>

            {{-- Guest Link --}}
            <div class="mt-5 text-center">
                <a href="/order" target="_blank"
                   class="text-xs text-slate-500 hover:text-[#2E8B89] font-medium transition inline-flex items-center gap-1.5 group">
                    <x-icon name="qr-code" class="size-3.5 text-[#4CB0AE] group-hover:scale-110 transition-transform"/>
                    <span>Lihat Halaman Pemesanan Pelanggan (Scan Barcode)</span>
                    <x-icon name="external-link" class="size-3 text-slate-400 group-hover:text-[#2E8B89] transition-colors"/>
                </a>
            </div>

        </div>

        {{-- Footer Brand --}}
        <p class="text-center text-[11.5px] text-slate-400 mt-6">
            &copy; {{ date('Y') }} PosyMart POS &amp; Table Ordering System. Hak cipta dilindungi.
        </p>

    </div>

</body>
</html>
