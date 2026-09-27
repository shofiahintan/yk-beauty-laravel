{{--
    Modal form Owner Panel (dipakai Produk, Koleksi, Pengguna).

    Props:
      show      nama variabel Alpine yang membuka modal (default 'open')
      mode      nama variabel Alpine berisi 'create' | 'edit' (default 'mode')
      action    variabel Alpine berisi URL form (default 'formAction')
      title     ekspresi Alpine untuk judul, mis. "mode === 'create' ? 'Tambah Produk' : 'Edit Produk'"
      subtitle  teks kecil di bawah judul
      width     lebar maksimum, mis. 'max-w-[720px]'
      files     true kalau form berisi upload file
      submit    label tombol simpan (default 'Simpan')

    Isi form = slot utama. Tidak perlu <form>, tombol Batal/Simpan sudah ada di footer.
--}}
@props([
    'show' => 'open',
    'mode' => 'mode',
    'action' => 'formAction',
    'title' => "''",
    'subtitle' => null,
    'width' => 'max-w-[720px]',
    'files' => false,
    'submit' => 'Simpan',
])
<div x-show="{{ $show }}" x-cloak
     x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     @keydown.escape.window="{{ $show }} = false"
     class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-6"
     style="background:rgba(46,19,25,0.45); backdrop-filter: blur(3px);">

    <div @click.outside="{{ $show }} = false"
         class="bg-white w-full {{ $width }} max-h-[92vh] flex flex-col overflow-hidden rounded-t-[24px] sm:rounded-[24px] shadow-owner-modal">

        {{-- Header --}}
        <div class="shrink-0 flex items-start justify-between gap-4 px-7 pt-6 pb-5 border-b border-pink-100 bg-gradient-to-b from-pink-50/70 to-white">
            <div class="min-w-0">
                <h2 class="font-poppins font-semibold text-[19px] leading-tight text-maroon-900" x-text="{{ $title }}"></h2>
                @if ($subtitle)
                    <p class="mt-1 text-[12.5px] text-ink-soft">{{ $subtitle }}</p>
                @endif
            </div>
            <button type="button" @click="{{ $show }} = false" title="Tutup"
                    class="-mr-2 -mt-1 h-9 w-9 shrink-0 rounded-full flex items-center justify-center text-ink-soft hover:bg-pink-100 hover:text-maroon-900 transition">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <form :action="{{ $action }}" method="POST" @if ($files) enctype="multipart/form-data" @endif class="flex-1 flex flex-col min-h-0">
            @csrf
            <template x-if="{{ $mode }} === 'edit'"><input type="hidden" name="_method" value="PUT"></template>

            <div class="flex-1 min-h-0 overflow-y-auto owner-notif-scroll px-7 py-6 space-y-7">
                {{ $slot }}
            </div>

            <div class="shrink-0 flex items-center justify-end gap-3 px-7 py-4 border-t border-pink-100 bg-white">
                <button type="button" @click="{{ $show }} = false" class="o-btn-ghost">Batal</button>
                <button type="submit" class="o-btn-primary">{{ $submit }}</button>
            </div>
        </form>
    </div>
</div>
