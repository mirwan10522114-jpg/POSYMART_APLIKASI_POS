{{-- Header halaman: judul + deskripsi + tombol aksi --}}
@props(['title', 'subtitle' => ''])
<div class="flex items-start sm:items-center justify-between gap-4 flex-wrap pb-5 mb-6 border-b border-line/70">
    <div class="min-w-0">
        <h1 class="text-[26px]/[34px] font-bold tracking-tight text-ink">{{ $title }}</h1>
        @if ($subtitle)
            <p class="text-[14px]/[20px] text-ink-secondary mt-1">{{ $subtitle }}</p>
        @endif
    </div>
    @if (isset($slot) && $slot->isNotEmpty())
        <div class="flex items-center gap-2.5 flex-wrap">
            {{ $slot }}
        </div>
    @elseif (isset($actions))
        <div class="flex items-center gap-2.5 flex-wrap">
            {{ $actions }}
        </div>
    @endif
</div>
