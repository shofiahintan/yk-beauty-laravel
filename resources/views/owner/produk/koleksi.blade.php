@extends('layouts.owner')

@section('title', 'Paket Bundling')
@section('breadcrumb', 'Kelola Produk')

@section('content')
<div x-data="koleksiPanel()">

    {{-- TABS --}}
    <div class="flex items-center gap-1 mb-6 border-b border-pink-200">
        <a href="{{ route('owner.produk.index') }}"
           class="px-4 py-3 text-[14px] font-medium border-b-2 -mb-px {{ request()->routeIs('owner.produk.index') ? 'border-maroon-800 text-maroon-900' : 'border-transparent text-ink-soft hover:text-ink-900' }}">
            Produk
        </a>
        <a href="{{ route('owner.koleksi.index') }}"
           class="px-4 py-3 text-[14px] font-medium border-b-2 -mb-px {{ request()->routeIs('owner.koleksi.*') ? 'border-maroon-800 text-maroon-900' : 'border-transparent text-ink-soft hover:text-ink-900' }}">
            Paket Bundling
        </a>
    </div>

    <div class="flex justify-end mb-6">
        <button type="button" @click="openCreate()" class="h-11 px-5 rounded-xl bg-maroon-900 text-white text-[13.5px] font-medium hover:bg-maroon-800">
            + Tambah Paket Bundling
        </button>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
        @forelse ($collections as $coll)
            <div class="bg-white rounded-[18px] shadow-owner-card overflow-hidden">
                <img src="{{ asset($coll->image) }}" class="h-40 w-full object-cover" alt="">
                <div class="p-4">
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="font-poppins font-semibold text-[15px] text-ink-900 leading-snug">{{ $coll->title }}</h3>
                        <x-owner.badge :classes="$coll->is_active ? 'bg-pink-100 text-maroon-900' : 'bg-pink-50 text-ink-soft'">
                            {{ $coll->is_active ? 'Aktif' : 'Nonaktif' }}
                        </x-owner.badge>
                    </div>
                    <p class="text-[12.5px] text-ink-soft mt-1 line-clamp-2">{{ $coll->description }}</p>
                    <p class="text-[12px] text-ink-soft mt-2">{{ $coll->products_count }} produk di dalamnya</p>
                    <p class="mt-2">
                        <span class="text-maroon-900 font-semibold whitespace-nowrap">{{ formatRupiahOwner($coll->harga) }}</span>
                        @if ($coll->harga_coret)
                            <span class="text-[12px] text-ink-soft line-through whitespace-nowrap ml-1">{{ formatRupiahOwner($coll->harga_coret) }}</span>
                        @endif
                    </p>
                    <div class="flex items-center gap-2 mt-4">
                        <button type="button" @click="openEdit({{ $coll->id }})" class="flex-1 h-9 rounded-lg border border-pink-200 text-[12.5px] text-maroon-800 font-medium hover:bg-pink-50">Edit</button>
                        <form method="POST" action="{{ route('owner.koleksi.destroy', $coll) }}" onsubmit="return confirm('Hapus koleksi {{ addslashes($coll->title) }}?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="h-9 w-9 rounded-lg border border-pink-200 text-ink-soft hover:text-maroon-900 flex items-center justify-center">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-[18px] shadow-owner-card p-10 text-center text-ink-soft">Belum ada koleksi/promo.</div>
        @endforelse
    </div>

    <div class="mt-5">{{ $collections->links() }}</div>

    {{-- MODAL --}}
    <x-owner.modal title="mode === 'create' ? 'Tambah Paket Bundling' : 'Edit Paket Bundling'"
                   subtitle="Kelompokkan beberapa produk jadi satu paket atau promo." width="max-w-[680px]" files>

        <section>
            <p class="o-section">Informasi Paket</p>
            <div class="space-y-4">
                <div>
                    <label class="o-label">Judul koleksi *</label>
                    <input type="text" name="title" x-model="form.title" required class="o-input" placeholder="mis. Paket Glowing Pagi">
                </div>
                <div>
                    <label class="o-label">Deskripsi</label>
                    <textarea name="description" x-model="form.description" rows="2" class="o-textarea" placeholder="Ceritakan singkat isi paketnya"></textarea>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="o-label">Harga paket *</label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-[13px] text-ink-soft">Rp</span>
                            <input type="number" name="harga" x-model="form.harga" required min="0" class="o-input pl-10" placeholder="0">
                        </div>
                    </div>
                    <div>
                        <label class="o-label">Harga coret</label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-[13px] text-ink-soft">Rp</span>
                            <input type="number" name="harga_coret" x-model="form.harga_coret" min="0" class="o-input pl-10" placeholder="Opsional">
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section>
            <p class="o-section">Foto Sampul</p>
            <div class="flex items-center gap-4">
                <div class="h-[84px] w-[84px] shrink-0 overflow-hidden rounded-2xl border border-dashed border-pink-200 bg-pink-50 flex items-center justify-center text-maroon-500">
                    <template x-if="imageUrl"><img :src="imageUrl" class="h-full w-full object-cover" alt=""></template>
                    <template x-if="!imageUrl">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="3"/><circle cx="9" cy="10" r="1.6"/><path d="m21 16-5-5-8 8"/></svg>
                    </template>
                </div>
                <div class="min-w-0">
                    <label class="o-btn-soft">
                        <span x-text="imageUrl ? 'Ganti foto' : 'Pilih foto'"></span>
                        <input type="file" name="image" accept="image/*" class="sr-only o-modal-file" :required="mode === 'create'"
                               @change="if ($event.target.files[0]) { imageUrl = URL.createObjectURL($event.target.files[0]); imageName = $event.target.files[0].name; }">
                    </label>
                    <p class="o-hint truncate" x-text="imageName || (mode === 'edit' ? 'Kosongkan jika tidak ingin mengganti.' : 'JPG atau PNG, maksimal 2 MB.')"></p>
                </div>
            </div>
        </section>

        <section>
            <p class="o-section">Isi Paket</p>
            <div class="max-h-[240px] overflow-y-auto owner-notif-scroll rounded-2xl border border-pink-100 divide-y divide-pink-50">
                @foreach ($allProducts as $p)
                    <label class="flex cursor-pointer items-center gap-3 px-4 py-2.5 hover:bg-pink-50/70">
                        <input type="checkbox" name="produk[]" value="{{ $p->id }}" :checked="form.produk_ids.includes({{ $p->id }})" class="h-4 w-4 rounded border-pink-300 text-maroon-800 focus:ring-maroon-700/30">
                        <img src="{{ asset($p->image) }}" class="h-9 w-9 rounded-lg object-cover" alt="">
                        <span class="min-w-0 text-[13px] text-ink-900 truncate">{{ $p->nama }} <span class="text-ink-soft">· {{ $p->brand }}</span></span>
                    </label>
                @endforeach
            </div>
            <p class="o-hint">Centang produk yang masuk ke dalam paket ini.</p>
        </section>

        <label class="flex cursor-pointer items-center justify-between gap-4 rounded-2xl border border-pink-100 bg-pink-50/60 px-5 py-4">
            <span>
                <span class="block text-[13.5px] font-medium text-ink-900">Tampilkan di toko</span>
                <span class="block text-[12px] text-ink-soft">Paket nonaktif tidak terlihat oleh pelanggan.</span>
            </span>
            <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="peer sr-only">
            <span class="relative h-6 w-11 shrink-0 rounded-full bg-pink-200 transition peer-checked:bg-maroon-800 peer-focus-visible:ring-2 peer-focus-visible:ring-maroon-700/40
                         after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition after:content-[''] peer-checked:after:translate-x-5"></span>
        </label>
    </x-owner.modal>
</div>

@push('scripts')
<script>
function koleksiPanel() {
    return {
        open: false,
        mode: 'create',
        formAction: '{{ route('owner.koleksi.store') }}',
        form: { title: '', description: '', harga: '', harga_coret: '', is_active: true, produk_ids: [] },
        collections: @json($collections->items()),
        assetBase: @json(rtrim(asset(''), '/')),
        imageUrl: null,
        imageName: '',

        resetFiles(url = null) {
            this.imageUrl = url;
            this.imageName = '';
            document.querySelectorAll('.o-modal-file').forEach(i => i.value = '');
        },

        openCreate() {
            this.mode = 'create';
            this.formAction = '{{ route('owner.koleksi.store') }}';
            this.form = { title: '', description: '', harga: '', harga_coret: '', is_active: true, produk_ids: [] };
            this.resetFiles();
            this.open = true;
        },

        openEdit(id) {
            const coll = this.collections.find(c => c.id === id);
            if (!coll) return;
            this.mode = 'edit';
            this.formAction = '/owner/koleksi/' + id;
            this.form = {
                title: coll.title, description: coll.description,
                harga: coll.harga, harga_coret: coll.harga_coret,
                is_active: coll.is_active,
                produk_ids: coll.products ? coll.products.map(p => p.id) : [],
            };
            this.resetFiles(coll.image ? this.assetBase + '/' + coll.image : null);
            this.open = true;
        },
    };
}
</script>
@endpush
@endsection
