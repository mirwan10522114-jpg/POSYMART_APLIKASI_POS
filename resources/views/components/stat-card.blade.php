{{-- Kartu statistik: ikon lingkaran teal + label + nilai + catatan --}}
@props([
    'label' => '',
    'value' => '',
    'note' => '',
    'icon' => 'dollar',
    'growth' => null,        // angka persen, positif = naik
    'wide' => false,
])
@php
    // Nilai panjang (mis. Rp48.924.500) memakai ukuran font lebih kecil agar tidak overflow.
    $valueLength = mb_strlen((string) $value);
    $valueClass = $valueLength > 10 ? 'text-[21px]/[27px]' : ($valueLength > 8 ? 'text-[24px]/[30px]' : 'text-[28px]/[34px]');
@endphp
<div class="card p-5 sm:p-6 min-h-[120px] flex flex-col min-w-0">
    <div class="flex items-start justify-between gap-3">
        <div class="flex items-center gap-3.5 min-w-0">
            <span class="grid size-[52px] shrink-0 place-items-center rounded-full bg-primary-soft text-focus max-[480px]:size-11">
                <x-icon name="{{ $icon }}" class="size-[26px] max-[480px]:size-6"/>
            </span>
            <div class="min-w-0">
                <p class="text-[14px]/[20px] text-ink-secondary truncate">{{ $label }}</p>
                <p class="font-semibold text-ink tabular-nums mt-0.5 {{ $valueClass }} tracking-tight">{{ $value }}</p>
            </div>
        </div>
        {{ $menu ?? '' }}
    </div>
    @if ($growth !== null)
        <p class="mt-auto pt-3 text-[12px]/[18px] flex items-center gap-1.5">
            <span class="inline-flex items-center gap-0.5 font-medium text-positive tabular-nums">
                <x-icon name="arrow-up" class="size-3.5"/><span>+{{ $growth }}%</span>
            </span>
            <span class="text-ink-muted">{{ $note }}</span>
        </p>
    @elseif ($note !== '')
        <p class="mt-auto pt-3 text-[12px]/[18px] text-ink-muted">{{ $note }}</p>
    @endif
</div>
