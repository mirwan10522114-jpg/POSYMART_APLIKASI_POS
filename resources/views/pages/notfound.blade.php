@extends('layouts.app')

@section('title', 'Halaman Tidak Ditemukan')
@section('subtitle', '')

@section('content')
<div class="py-16">
    <div class="card p-12 text-center max-w-lg mx-auto">
        <span class="mx-auto mb-4 grid size-14 place-items-center rounded-full bg-surface-muted text-ink-muted">
            <x-icon name="search" class="size-7"/>
        </span>
        <h1 class="text-[22px]/[30px] font-semibold">Halaman Tidak Ditemukan</h1>
        <p class="text-[14px]/[21px] text-ink-secondary mt-2">Halaman yang Anda cari tidak tersedia atau sudah dipindahkan.</p>
        <a href="/dashboard" class="btn btn-primary mt-6">Kembali ke Dasbor</a>
    </div>
</div>
@endsection
