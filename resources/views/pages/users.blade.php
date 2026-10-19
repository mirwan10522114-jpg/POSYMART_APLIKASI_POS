@extends('layouts.app')

@@section('title', 'Staf & Tim Toko')
@section('subtitle', 'Kelola tim toko, peran (Owner, Admin, Kasir), dan akses pengguna.')

@section('content')
<div x-data="usersPage" x-cloak @keydown.window.escape="formOpen ? closeForm() : (detailId && closeDetail())">

    {{-- penjaga akses demo --}}
    <template x-if="!allowed">
        <div class="py-16">
            <div class="card p-12 text-center max-w-lg mx-auto">
                <span class="mx-auto mb-4 grid size-14 place-items-center rounded-full bg-danger-soft text-danger">
                    <x-icon name="shield-check" class="size-7"/>
                </span>
                <h1 class="text-[22px]/[30px] font-semibold">Anda tidak memiliki akses ke halaman ini</h1>
                <p class="text-[14px]/[21px] text-ink-secondary mt-2">Peran demo Anda hanya dapat mengelola penjualan, produk (lihat), dan pelanggan. Manajemen tim tersedia untuk Admin.</p>
                <p class="text-[12px]/[17px] text-ink-muted mt-3">Ini hanya simulasi izin frontend — bukan proteksi server.</p>
                <a href="/dashboard" class="btn btn-primary mt-6">Kembali ke Dasbor</a>
            </div>
        </div>
    </template>

    <template x-if="allowed">
        <div>
            <x-page-header title="Staf &amp; Tim Toko" subtitle="Kelola akses tim staf, peran (Owner, Admin, Kasir), dan izin pengguna.">
                <div class="flex items-center gap-2">
                    <button type="button" class="btn btn-secondary" @click="permsOpen = true">
                        <x-icon name="shield-check" class="size-4.5"/> Lihat Izin
                    </button>
                    <button type="button" class="btn btn-primary" @click="openCreate()">
                        <x-icon name="user-plus" class="size-4.5"/> Undang Pengguna
                    </button>
                </div>
            </x-page-header>
            {{-- ================= STATISTIK TIM ================= --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-[22px] mt-6" role="group" aria-label="Statistik tim">
                <div class="card p-5 min-h-[120px] flex flex-col">
                    <div class="flex items-center gap-3.5 min-w-0">
                        <span class="grid size-11 shrink-0 place-items-center rounded-full bg-primary-soft text-focus"><x-icon name="users" class="size-5.5"/></span>
                        <div class="min-w-0">
                            <p class="text-[13px]/[18px] text-ink-secondary truncate">Total Pengguna</p>
                            <p class="text-[26px]/[32px] font-semibold tabular-nums" x-text="stats.total"></p>
                        </div>
                    </div>
                    <p class="mt-auto pt-2.5 text-[12px]/[17px] text-ink-muted">Seluruh tim</p>
                </div>
                <div class="card p-5 min-h-[120px] flex flex-col">
                    <div class="flex items-center gap-3.5 min-w-0">
                        <span class="grid size-11 shrink-0 place-items-center rounded-full bg-primary-soft text-focus"><x-icon name="check-circle" class="size-5.5"/></span>
                        <div class="min-w-0">
                            <p class="text-[13px]/[18px] text-ink-secondary truncate">Pengguna Aktif</p>
                            <p class="text-[26px]/[32px] font-semibold tabular-nums" x-text="stats.active"></p>
                        </div>
                    </div>
                    <p class="mt-auto pt-2.5 text-[12px]/[17px] text-ink-muted">Seluruh tim</p>
                </div>
                <div class="card p-5 min-h-[120px] flex flex-col">
                    <div class="flex items-center gap-3.5 min-w-0">
                        <span class="grid size-11 shrink-0 place-items-center rounded-full bg-amber-soft text-amber"><x-icon name="mail" class="size-5.5"/></span>
                        <div class="min-w-0">
                            <p class="text-[13px]/[18px] text-ink-secondary truncate">Undangan Menunggu</p>
                            <p class="text-[26px]/[32px] font-semibold tabular-nums" x-text="stats.invited"></p>
                        </div>
                    </div>
                    <p class="mt-auto pt-2.5 text-[12px]/[17px] text-ink-muted">Tidak ada email yang dikirim</p>
                </div>
            </div>

            {{-- ================= PANEL TIM ================= --}}
            <section class="panel mt-6 overflow-hidden" aria-labelledby="team-title">
                <h2 id="team-title" class="sr-only">Anggota tim</h2>
                <div class="p-5 pb-0">
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="relative w-[300px] max-w-full">
                            <x-icon name="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-ink-muted pointer-events-none"/>
                            <label class="sr-only" for="users-search">Cari pengguna</label>
                            <input id="users-search" type="search" class="input pl-10" placeholder="Cari pengguna…" x-model="query" @input="resetPage()">
                        </div>
                        <div>
                            <label class="sr-only" for="users-role">Peran</label>
                            <select id="users-role" class="select !w-[140px]" x-model="role" @change="resetPage()">
                                <option value="all">Semua</option>
                                <option value="Owner">Owner</option>
                                <option value="Admin">Admin</option>
                                <option value="Manager">Manager</option>
                                <option value="Cashier">Cashier</option>
                            </select>
                        </div>
                        <div>
                            <label class="sr-only" for="users-status">Status</label>
                            <select id="users-status" class="select !w-[150px]" x-model="status" @change="resetPage()">
                                <option value="all">Semua</option>
                                <option value="Active">Aktif</option>
                                <option value="Invited">Diundang</option>
                                <option value="Disabled">Nonaktif</option>
                            </select>
                        </div>
                        <div>
                            <label class="sr-only" for="users-sort">Urutkan</label>
                            <select id="users-sort" class="select !w-[170px]" x-model="sortBy" @change="resetPage()">
                                <option value="name-asc">Nama: A–Z</option>
                                <option value="name-desc">Nama: Z–A</option>
                                <option value="last-desc">Terakhir Aktif</option>
                                <option value="added-desc">Terbaru Ditambahkan</option>
                            </select>
                        </div>
                        <button type="button" class="btn btn-secondary" @click="resetFilters()">
                            <x-icon name="x" class="size-4"/> Reset Filter
                        </button>
                    </div>
                </div>

                <div class="mt-4 hidden md:block">
                    <template x-if="totalRows === 0">
                        <div class="py-16 text-center">
                            <span class="mx-auto mb-3 grid size-12 place-items-center rounded-full bg-surface-muted text-ink-muted"><x-icon name="users" class="size-6"/></span>
                            <p class="text-[15px]/[22px] font-medium">Tidak ada anggota tim yang ditemukan</p>
                            <button type="button" class="btn btn-secondary btn-sm mt-4" @click="resetFilters()">Reset Filter</button>
                        </div>
                    </template>
                    <div class="table-wrap" x-show="totalRows > 0">
                        <table class="table min-w-[860px]">
                            <thead>
                                <tr>
                                    <th>Pengguna</th>
                                    <th>Peran</th>
                                    <th>Status</th>
                                    <th>Terakhir Aktif</th>
                                    <th>Ditambahkan</th>
                                    <th class="w-16">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="u in paged" :key="u.id">
                                    <tr class="row-click" @click="openDetail(u.id)">
                                        <td>
                                            <div class="flex items-center gap-3">
                                                <span class="grid size-9 place-items-center rounded-lg bg-primary-soft text-focus text-[12px] font-semibold" x-text="initials(u.name)"></span>
                                                <div>
                                                    <p class="font-medium flex items-center gap-2">
                                                        <span x-text="u.name"></span>
                                                        <span class="badge badge-teal" x-show="u.current">Anda</span>
                                                    </p>
                                                    <p class="text-[12px] text-ink-muted" x-text="u.email"></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge" :class="['Owner', 'Admin'].includes(u.role) ? 'badge-teal' : 'badge-neutral'" x-text="u.role"></span></td>
                                        <td><span class="badge" :class="statusBadge(u)" x-text="statusLabel(u)"></span></td>
                                        <td class="text-ink-secondary whitespace-nowrap" x-text="u.current ? 'Sekarang' : (u.lastActiveAt ? $rpfmt.dateTime(u.lastActiveAt) : 'Belum bergabung')"></td>
                                        <td class="text-ink-secondary whitespace-nowrap" x-text="$rpfmt.date(u.addedAt)"></td>
                                        <td>
                                            <div class="flex justify-end" @click.stop>
                                                <button type="button" class="btn btn-secondary btn-icon btn-xs" @click="openDetail(u.id)" :aria-label="`Lihat detail ${u.name}`">
                                                    <x-icon name="eye" class="size-4"/>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- kartu mobile --}}
                <div class="md:hidden px-4 pb-2">
                    <template x-for="u in paged" :key="u.id">
                        <div class="rounded-xl border border-line p-4 mb-2.5">
                            <div class="flex items-center gap-3">
                                <span class="grid size-10 place-items-center rounded-lg bg-primary-soft text-focus text-[12px] font-semibold" x-text="initials(u.name)"></span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <p class="font-medium truncate" x-text="u.name"></p>
                                        <span class="badge badge-teal" x-show="u.current">Anda</span>
                                    </div>
                                    <p class="text-[12px] text-ink-muted break-all" x-text="u.email"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 mt-2.5">
                                <span class="badge" :class="['Owner', 'Admin'].includes(u.role) ? 'badge-teal' : 'badge-neutral'" x-text="u.role"></span>
                                <span class="badge" :class="statusBadge(u)" x-text="statusLabel(u)"></span>
                            </div>
                            <div class="flex items-center justify-between mt-2 text-[12.5px] text-ink-secondary">
                                <span x-text="u.current ? 'Aktif sekarang' : (u.lastActiveAt ? $rpfmt.dateTime(u.lastActiveAt) : 'Belum bergabung')"></span>
                                <span x-text="`Gabung ${$rpfmt.date(u.addedAt)}`"></span>
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm w-full mt-3" @click="openDetail(u.id)">Lihat Detail</button>
                        </div>
                    </template>
                </div>

                <x-pagination row-label="pengguna"/>
            </section>
        </div>
    </template>

    {{-- ================= DIALOG IZIN PERAN ================= --}}
    <div x-show="permsOpen" x-cloak class="fixed inset-0 z-[85] grid place-items-center p-4" role="dialog" aria-modal="true" aria-label="Izin peran">
        <div class="absolute inset-0 bg-[#1c2b2b]/45" @click="permsOpen = false" x-transition:enter="fade-enter" x-transition:leave="fade-leave"></div>
        <div class="relative w-full max-w-[760px] max-h-[86vh] rounded-2xl bg-white shadow-[0_24px_80px_rgba(30,41,59,0.22)] flex flex-col overflow-hidden"
             x-ref="permsPanel" @keydown.tab="PM.trap($event, $refs.permsPanel)" @keydown.escape="permsOpen = false"
             x-transition:enter="pop-enter" x-transition:leave="pop-leave">
            <div class="px-6 py-5 border-b border-divider flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-[18px]/[24px] font-semibold">Izin Peran</h2>
                    <p class="text-[13px]/[19px] text-ink-secondary mt-0.5">Izin bersifat simulasi pada demo ini — bukan proteksi server.</p>
                </div>
                <button type="button" class="btn btn-secondary btn-icon btn-sm" @click="permsOpen = false" aria-label="Tutup"><x-icon name="x" class="size-4"/></button>
            </div>
            <div class="px-6 pt-4">
                <label class="field-label" for="preview-role">Pratinjau peran</label>
                <select id="preview-role" class="select !w-[220px]" x-model="previewRole">
                    <option value="Admin">Admin</option>
                    <option value="Manager">Manager</option>
                    <option value="Cashier">Cashier (Kasir)</option>
                </select>
            </div>
            <div class="flex-1 overflow-y-auto nice-scroll px-6 py-5">
                <table class="table">
                    <thead>
                        <tr><th>Aksi / Rute</th><th class="w-24 text-center">Admin</th><th class="w-24 text-center">Manager</th><th class="w-24 text-center">Cashier</th></tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, i) in permRows()" :key="i">
                            <tr>
                                <td class="text-[13px]/[18px]" x-text="row[0]"></td>
                                <template x-for="j in 3" :key="j">
                                    <td class="text-center">
                                        <span class="grid size-6 place-items-center rounded-full mx-auto"
                                              :class="permRows()[i][j] ? 'bg-[#e7f4f0] text-[#2f7d68]' : 'bg-surface-muted text-ink-muted'">
                                            <span x-show="permRows()[i][j]"><x-icon name="check" class="size-3.5"/></span>
                                            <span x-show="!permRows()[i][j]" x-cloak><x-icon name="x" class="size-3.5"/></span>
                                        </span>
                                        <span class="sr-only" x-text="permRows()[i][j] ? 'Ya' : 'Tidak'"></span>
                                    </td>
                                </template>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <div class="mt-4 rounded-xl bg-surface-muted px-4 py-3.5" x-show="previewRole !== 'Admin'">
                    <p class="text-[12.5px]/[19px] text-ink-secondary">
                        Pratinjau <span class="font-medium text-ink" x-text="previewRole"></span>:
                        <span x-text="Object.entries(rolePerms(previewRole)).filter(([,v]) => !v).map(([k]) => ({
                            manageProducts: 'mengelola produk', manageUsers: 'mengelola tim', manageSettings: 'mengubah pengaturan',
                            approveReturns: 'menyetujui retur', voidSales: 'membatalkan penjualan', viewReports: 'melihat laporan',
                            manageDiscounts: 'mengelola diskon', cancelOrders: 'membatalkan pesanan'
                        }[k])).join(', ') || 'semua aksi tersedia'"></span> akan dinonaktifkan pada tampilan.
                    </p>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-divider flex justify-end">
                <button type="button" class="btn btn-secondary" @click="permsOpen = false">Tutup</button>
            </div>
        </div>
    </div>

    {{-- ================= DRAWER FORM UNDANG/UBAH ================= --}}
    <div x-show="formOpen" x-cloak class="fixed inset-0 z-[75]" role="dialog" aria-modal="true" :aria-label="editingId ? 'Ubah Pengguna' : 'Undang Pengguna'">
        <div class="absolute inset-0 bg-[#1c2b2b]/40" @click="closeForm()" x-transition:enter="fade-enter" x-transition:leave="fade-leave"></div>
        <div class="absolute inset-y-0 right-0 w-[520px] max-w-[92vw] bg-white flex flex-col"
             x-ref="formPanel" @keydown.tab="PM.trap($event, $refs.formPanel)" @keydown.escape="closeForm()"
             x-transition:enter="slide-enter" x-transition:leave="slide-leave">
            <div class="px-6 py-5 border-b border-divider flex items-center justify-between">
                <h2 class="text-[18px]/[24px] font-semibold" x-text="editingId ? 'Ubah Pengguna' : 'Undang Pengguna'"></h2>
                <button type="button" class="btn btn-secondary btn-icon btn-sm" @click="closeForm()" aria-label="Tutup form"><x-icon name="x" class="size-4"/></button>
            </div>
            <div class="flex-1 overflow-y-auto nice-scroll px-6 py-5">
                <p class="text-[12.5px]/[18px] text-ink-muted mb-4 rounded-lg bg-surface-muted px-3.5 py-2.5">
                    <template x-if="!editingId"><span>Buat undangan demo. Tidak ada email yang dikirim.</span></template>
                    <template x-if="editingId">
                        <span x-text="$store.posy.user(editingId)?.status === 'Active' ? 'Email terhubung dengan pengguna demo ini dan tidak dapat diubah.' : 'Email dapat diubah karena pengguna belum aktif.'"></span>
                    </template>
                </p>
                <div class="space-y-4">
                    <div>
                        <label class="field-label" for="uf-name">Nama Lengkap <span class="text-danger">*</span></label>
                        <input id="uf-name" type="text" class="input" x-model="form.name" maxlength="80" :aria-invalid="!!formErrors.name">
                        <p class="field-error" x-show="formErrors.name" x-text="formErrors.name"></p>
                    </div>
                    <div>
                        <label class="field-label" for="uf-email">Alamat Email <span class="text-danger">*</span></label>
                        <input id="uf-email" type="email" class="input" x-model="form.email" :aria-invalid="!!formErrors.email" :disabled="editingId && $store.posy.user(editingId)?.status === 'Active'">
                        <p class="field-error" x-show="formErrors.email" x-text="formErrors.email"></p>
                    </div>
                    <div>
                        <label class="field-label" for="uf-role">Peran <span class="text-danger">*</span></label>
                        <select id="uf-role" class="select" x-model="form.role" :disabled="editingId && $store.posy.user(editingId)?.current">
                            <option value="Owner">Owner (Pemilik Toko)</option>
                            <option value="Admin">Admin</option>
                            <option value="Manager">Manager</option>
                            <option value="Cashier">Cashier (Kasir)</option>
                        </select>
                        <p class="field-error" x-show="editingId && $store.posy.user(editingId)?.current">Anda tidak dapat mengubah peran akun Anda sendiri.</p>
                        <div class="mt-2.5 rounded-lg bg-surface-muted px-3.5 py-2.5 text-[12px]/[18px] text-ink-secondary">
                            <span class="font-medium text-ink">Ringkasan izin:</span>
                            <span x-text="rolePerms(form.role).manageUsers ? 'akses penuh termasuk tim & pengaturan.' : rolePerms(form.role).viewReports ? 'dapat mengelola operasional tanpa tim & pengaturan.' : 'fokus pada kasir: penjualan, produk (lihat), pelanggan, draf retur.'"></span>
                        </div>
                    </div>
                    <div>
                        <label class="field-label" for="uf-note">Catatan Staf</label>
                        <textarea id="uf-note" class="textarea !min-h-[60px]" x-model="form.note" maxlength="240"></textarea>
                        <p class="field-help">Bukan kata sandi atau informasi sensitif.</p>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-divider flex justify-end gap-2.5">
                <button type="button" class="btn btn-secondary" @click="closeForm()" :disabled="saving">Batal</button>
                <button type="button" class="btn btn-primary" @click="saveForm()" :disabled="saving"
                        x-text="saving ? 'Menyimpan…' : (editingId ? 'Simpan Perubahan' : 'Buat Undangan')"></button>
            </div>
        </div>
    </div>

    {{-- ================= DRAWER DETAIL PENGGUNA ================= --}}
    <div x-show="detailId" x-cloak class="fixed inset-0 z-[75]" role="dialog" aria-modal="true" aria-label="Detail pengguna">
        <div class="absolute inset-0 bg-[#1c2b2b]/40" @click="closeDetail()" x-transition:enter="fade-enter" x-transition:leave="fade-leave"></div>
        <div class="absolute inset-y-0 right-0 w-[480px] max-w-[92vw] bg-white flex flex-col"
             x-ref="detailPanel" @keydown.tab="PM.trap($event, $refs.detailPanel)" @keydown.escape="closeDetail()"
             x-transition:enter="slide-enter" x-transition:leave="slide-leave">
            <template x-if="detailUser">
                <div class="flex flex-col min-h-0 flex-1">
                    <div class="px-6 py-5 border-b border-divider flex items-start justify-between gap-3">
                        <div class="flex items-center gap-4 min-w-0">
                            <span class="grid size-14 place-items-center rounded-2xl bg-primary-soft text-focus text-[16px] font-semibold" x-text="initials(detailUser.name)"></span>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h2 class="text-[19px]/[25px] font-semibold" x-text="detailUser.name"></h2>
                                    <span class="badge badge-teal" x-show="detailUser.current">Anda</span>
                                </div>
                                <p class="text-[12.5px]/[17px] text-ink-muted mt-0.5 break-all" x-text="detailUser.email"></p>
                            </div>
                        </div>
                        <button type="button" class="btn btn-secondary btn-icon btn-sm" @click="closeDetail()" aria-label="Tutup detail"><x-icon name="x" class="size-4"/></button>
                    </div>
                    <div class="flex-1 overflow-y-auto nice-scroll px-6 py-5 space-y-5">
                        <dl class="grid grid-cols-2 gap-x-4 gap-y-3.5 text-[13.5px]/[19px]">
                            <div><dt class="text-ink-muted">Peran</dt><dd class="mt-0.5"><span class="badge" :class="['Owner', 'Admin'].includes(detailUser.role) ? 'badge-teal' : 'badge-neutral'" x-text="detailUser.role"></span></dd></div>
                            <div><dt class="text-ink-muted">Status</dt><dd class="mt-0.5"><span class="badge" :class="statusBadge(detailUser)" x-text="statusLabel(detailUser)"></span></dd></div>
                            <div><dt class="text-ink-muted">Terakhir Aktif</dt>
                                <dd class="mt-0.5" x-text="detailUser.current ? 'Sekarang' : (detailUser.lastActiveAt ? $rpfmt.dateTime(detailUser.lastActiveAt) : 'Belum bergabung')"></dd>
                            </div>
                            <div><dt class="text-ink-muted">Ditambahkan</dt><dd class="mt-0.5" x-text="$rpfmt.date(detailUser.addedAt)"></dd></div>
                        </dl>

                        <div>
                            <p class="text-[13px]/[18px] font-medium mb-2">Izin (simulasi)</p>
                            <ul class="space-y-1.5">
                                <template x-for="(row, i) in permRows()" :key="i">
                                    <li class="flex items-center justify-between gap-3 text-[12.5px]/[18px]">
                                        <span class="text-ink-secondary" x-text="row[0]"></span>
                                        <span class="flex items-center gap-1.5 shrink-0" :class="row[{ Owner: 1, Admin: 1, Manager: 2, Cashier: 3 }[detailUser.role]] ? 'text-[#2f7d68]' : 'text-ink-muted'">
                                            <span x-show="row[{ Owner: 1, Admin: 1, Manager: 2, Cashier: 3 }[detailUser.role]]"><x-icon name="check" class="size-3.5"/></span>
                                            <span x-show="!row[{ Owner: 1, Admin: 1, Manager: 2, Cashier: 3 }[detailUser.role]]" x-cloak><x-icon name="x" class="size-3.5"/></span>
                                            <span x-text="row[{ Owner: 1, Admin: 1, Manager: 2, Cashier: 3 }[detailUser.role]] ? 'Ya' : 'Tidak'"></span>
                                        </span>
                                    </li>
                                </template>
                            </ul>
                        </div>

                        <div x-show="detailUser.note">
                            <p class="text-[12.5px]/[17px] text-ink-muted mb-1">Catatan Staf</p>
                            <p class="text-[13.5px]/[20px]" x-text="detailUser.note"></p>
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-divider flex items-center gap-2.5 flex-wrap">
                        <button type="button" class="btn btn-secondary" @click="closeDetail()">Tutup</button>
                        <button type="button" class="btn btn-primary" @click="openEdit(detailUser.id)">Ubah Pengguna</button>
                        <div class="flex-1"></div>
                        <template x-if="detailUser.status === 'Invited'">
                            <div class="flex items-center gap-2 flex-wrap">
                                <button type="button" class="btn btn-secondary btn-sm" @click="copyInvite(detailUser)">Salin Tautan Undangan</button>
                                <button type="button" class="btn btn-primary btn-sm" @click="simulateAccept(detailUser.id)">Simulasikan Terima</button>
                                <button type="button" class="btn btn-danger-soft btn-sm" @click="openCancelInvite(detailUser.id)">Batalkan Undangan</button>
                            </div>
                        </template>
                        <template x-if="detailUser.status === 'Active'">
                            <button type="button" class="btn btn-danger-soft" @click="openStatus(detailUser.id, 'Disabled')"
                                    :disabled="detailUser.current" :title="detailUser.current ? 'Anda tidak dapat menonaktifkan akun Anda sendiri.' : ''">
                                Nonaktifkan Pengguna
                            </button>
                        </template>
                        <template x-if="detailUser.status === 'Disabled'">
                            <button type="button" class="btn btn-primary" @click="openStatus(detailUser.id, 'Active')">Aktifkan Pengguna</button>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- ================= DIALOG STATUS ================= --}}
    <div x-show="statusOpen" x-cloak class="fixed inset-0 z-[85] grid place-items-center p-4" role="dialog" aria-modal="true" aria-label="Ubah status pengguna">
        <div class="absolute inset-0 bg-[#1c2b2b]/45" @click="statusOpen = false" x-transition:enter="fade-enter" x-transition:leave="fade-leave"></div>
        <div class="relative w-full max-w-[420px] rounded-2xl bg-white shadow-[0_24px_80px_rgba(30,41,59,0.22)] p-6" x-transition:enter="pop-enter" x-transition:leave="pop-leave">
            <template x-if="statusTarget">
                <div>
                    <h2 class="text-[18px]/[24px] font-semibold" x-text="statusTo === 'Active' ? 'Aktifkan pengguna ini?' : 'Nonaktifkan pengguna ini?'"></h2>
                    <p class="text-[13.5px]/[20px] text-ink-secondary mt-2">
                        <span class="font-medium text-ink" x-text="statusTarget.name"></span>
                        <span x-text="statusTo === 'Active' ? ' akan kembali mendapat akses demo.' : ' tidak akan dapat mengakses demo hingga diaktifkan kembali. Riwayat tetap tersimpan.'"></span>
                    </p>
                    <div class="grid grid-cols-2 gap-3 mt-5">
                        <button type="button" class="btn btn-secondary" @click="statusOpen = false">Batal</button>
                        <button type="button" class="btn btn-primary" @click="confirmStatus()" x-text="statusTo === 'Active' ? 'Aktifkan' : 'Nonaktifkan'"></button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- ================= DIALOG BATALKAN UNDANGAN ================= --}}
    <div x-show="cancelInviteOpen" x-cloak class="fixed inset-0 z-[85] grid place-items-center p-4" role="dialog" aria-modal="true" aria-label="Batalkan undangan">
        <div class="absolute inset-0 bg-[#1c2b2b]/45" @click="cancelInviteOpen = false" x-transition:enter="fade-enter" x-transition:leave="fade-leave"></div>
        <div class="relative w-full max-w-[420px] rounded-2xl bg-white shadow-[0_24px_80px_rgba(30,41,59,0.22)] p-6" x-transition:enter="pop-enter" x-transition:leave="pop-leave">
            <h2 class="text-[18px]/[24px] font-semibold">Batalkan undangan <span x-text="cancelTarget?.name"></span>?</h2>
            <p class="text-[13.5px]/[20px] text-ink-secondary mt-2">Undangan demo ini akan dihapus dari daftar tim.</p>
            <div class="grid grid-cols-2 gap-3 mt-5">
                <button type="button" class="btn btn-secondary" @click="cancelInviteOpen = false">Batal</button>
                <button type="button" class="btn btn-danger" @click="confirmCancelInvite()">Batalkan Undangan</button>
            </div>
        </div>
    </div>

    {{-- ================= DIALOG BUANG PERUBAHAN ================= --}}
    <div x-show="discardOpen" x-cloak class="fixed inset-0 z-[95] grid place-items-center p-4" role="dialog" aria-modal="true" aria-label="Buang perubahan">
        <div class="absolute inset-0 bg-[#1c2b2b]/50" @click="discardOpen = false"></div>
        <div class="relative w-full max-w-[420px] rounded-2xl bg-white shadow-[0_24px_80px_rgba(30,41,59,0.22)] p-6" x-transition:enter="pop-enter" x-transition:leave="pop-leave">
            <h2 class="text-[18px]/[24px] font-semibold">Buang perubahan?</h2>
            <p class="text-[13.5px]/[20px] text-ink-secondary mt-2">Perubahan yang belum disimpan akan hilang.</p>
            <div class="grid grid-cols-2 gap-3 mt-5">
                <button type="button" class="btn btn-secondary" @click="discardOpen = false">Lanjut Mengedit</button>
                <button type="button" class="btn btn-danger" @click="discardOpen = false; closeForm(true)">Buang</button>
            </div>
        </div>
    </div>
</div>
@endsection
