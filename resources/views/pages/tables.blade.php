@extends('layouts.app')

@section('title', 'Meja & QR')
@section('subtitle', 'Daftar Meja dan Kode QR Pemesanan Mandiri.')

@section('content')
<div class="space-y-6" x-data="{ tables: [], loaded: false }" x-init="
    fetch('/api/v1/admin/tables')
        .then(r => r.json())
        .then(d => { tables = d.data || []; loaded = true; });
">
    <x-page-header title="Meja & QR" subtitle="Daftar 50 Meja dan Kode QR Pemesanan Mandiri Kafe.">
        <a href="/order" target="_blank" class="btn btn-primary text-xs flex items-center gap-1.5">
            <x-icon name="qr-code" class="size-4"/>
            <span>Buka Portal QR Tamu (/order)</span>
        </a>
    </x-page-header>

    @php
        $seededTables = \App\Models\Table::with('activeQr')
            ->orderByRaw("CAST(SUBSTR(table_code, 2) AS INTEGER) ASC")
            ->get();
    @endphp

    <div class="card p-6">
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 lg:grid-cols-10 gap-3">
            @foreach($seededTables as $tbl)
                <a href="/m/{{ $tbl->activeQr?->public_token ?? $tbl->table_code }}" target="_blank"
                   class="group border border-slate-200 rounded-xl p-3 text-center bg-white shadow-2xs hover:border-[#4CB0AE] hover:shadow-md transition block cursor-pointer relative"
                   title="Buka Menu Pemesanan Mandiri untuk {{ $tbl->label }}">
                    <span class="text-xs font-black text-slate-800 block group-hover:text-teal-700 transition">{{ $tbl->table_code }}</span>
                    <span class="text-[11px] font-semibold text-[#2E8B89] block">{{ $tbl->label }}</span>
                    <span class="text-[9px] text-slate-400 block truncate mt-1 font-mono">{{ $tbl->activeQr?->public_token ?? '' }}</span>
                    <span class="text-[10px] text-teal-600 opacity-0 group-hover:opacity-100 font-bold block mt-1 transition">Buka ↗</span>
                </a>
            @endforeach
        </div>
    </div>
</div>
@endsection
