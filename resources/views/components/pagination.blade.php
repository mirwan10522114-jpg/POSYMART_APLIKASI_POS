{{-- Kontrol pagination generik. Konteks x-data harus menyediakan:
     page, pageSize, totalRows, rowLabel ("penjualan"), totalPages,
     setPage(n), setPageSize(n), prev(), next() --}}
@props(['rowLabel' => 'baris'])
<div class="flex flex-col sm:flex-row items-center justify-between gap-3 px-4 py-3.5 border-t border-divider mt-0 flex-wrap">
    <p class="text-[13px]/[19px] text-ink-secondary tabular-nums order-2 sm:order-1"
       x-text="totalRows === 0 ? 'Tidak ada {{ $rowLabel }}' : `Menampilkan ${(page-1)*pageSize+1}–${Math.min(page*pageSize, totalRows)} dari ${totalRows} {{ $rowLabel }}`"></p>
    <div class="flex items-center gap-2.5 order-1 sm:order-2 flex-wrap justify-center">
        <label class="flex items-center gap-2 text-[13px] text-ink-secondary">
            <span class="max-[520px]:sr-only">Baris per halaman</span>
            <select class="select !w-auto !min-h-9 !py-1 !pl-3 !pr-8 !text-[13px]" x-model.number="pageSize"
                    @change="setPageSize($event.target.value)" aria-label="Baris per halaman">
                @foreach ([10, 20, 50] as $size)
                    <option value="{{ $size }}">{{ $size }}</option>
                @endforeach
            </select>
        </label>
        <div class="flex items-center gap-1">
            <button type="button" class="btn btn-secondary btn-sm" :disabled="page <= 1" @click="prev()" aria-label="Halaman sebelumnya">Sebelumnya</button>
            <template x-for="p in pageNumbers" :key="p">
                <button type="button"
                        class="btn btn-sm min-w-9 px-0 !justify-center"
                        :class="p === page ? 'btn-primary' : 'btn-secondary'"
                        :aria-current="p === page ? 'page' : null"
                        @click="setPage(p)" x-text="p"></button>
            </template>
            <button type="button" class="btn btn-secondary btn-sm" :disabled="page >= totalPages" @click="next()" aria-label="Halaman berikutnya">Berikutnya</button>
        </div>
    </div>
</div>
