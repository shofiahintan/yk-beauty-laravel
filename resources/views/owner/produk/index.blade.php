@extends('layouts.owner')

@section('title', 'Kelola Produk')
@section('breadcrumb', 'Kelola Produk')

@section('content')
<div x-data="produkPanel()">

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

    {{-- HEADER: filter + tambah --}}
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6">
        <form method="GET" action="{{ route('owner.produk.index') }}" data-autofilter class="flex flex-wrap items-center gap-2">
            @php
                $kategoriOptions = ['' => 'Semua Kategori'];
                foreach ($kategoriList as $kat) { $kategoriOptions[$kat] = ucfirst($kat); }
            @endphp
            <x-owner.select name="kategori" :options="$kategoriOptions" :value="$filters['kategori'] ?? ''" submit />
            <x-owner.select name="status" :value="$filters['status'] ?? ''" submit
                :options="['' => 'Semua Status', 'aktif' => 'Aktif', 'nonaktif' => 'Nonaktif', 'menipis' => 'Stok Menipis']" />
        </form>

        <button type="button" @click="openCreate()" class="h-11 px-5 rounded-xl bg-maroon-900 text-white text-[13.5px] font-medium hover:bg-maroon-800 shrink-0">
            + Tambah Produk
        </button>
    </div>

    {{-- TABLE --}}
    <div class="bg-white rounded-[18px] shadow-owner-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-[13.5px]">
                <thead>
                    <tr class="border-b border-pink-100 text-left text-ink-soft text-[12px] uppercase tracking-wide">
                        <th class="px-5 py-3 font-medium">Produk</th>
                        <th class="px-5 py-3 font-medium">Kategori</th>
                        <th class="px-5 py-3 font-medium">Harga</th>
                        <th class="px-5 py-3 font-medium">Stok</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-pink-50">
                    @forelse ($products as $product)
                        <tr class="hover:bg-pink-50/50">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <img src="{{ asset($product->image) }}" class="h-11 w-11 rounded-xl object-cover shrink-0" alt="">
                                    <div class="min-w-0">
                                        <p class="font-medium text-ink-900 truncate max-w-[220px]">{{ $product->nama }}</p>
                                        <p class="text-[12px] text-ink-soft">{{ $product->brand }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-ink-900">{{ ucfirst($product->kategori) }}</td>
                            <td class="px-5 py-3">
                                <p class="text-ink-900 whitespace-nowrap">{{ formatRupiahOwner($product->harga) }}</p>
                                @if ($product->coret)
                                    <p class="text-[11px] text-ink-soft line-through whitespace-nowrap">{{ formatRupiahOwner($product->coret) }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-3 {{ $product->isLowStock() ? 'text-maroon-900 font-semibold' : 'text-ink-900' }}">{{ $product->stok }}</td>
                            <td class="px-5 py-3">
                                <x-owner.badge :classes="$product->statusLabel() === 'Aktif' ? 'bg-pink-100 text-maroon-900' : ($product->statusLabel() === 'Stok Menipis' ? 'bg-maroon-900 text-white' : 'bg-pink-50 text-ink-soft')">
                                    {{ $product->statusLabel() }}
                                </x-owner.badge>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button" @click="openEdit({{ $product->id }})" class="p-2 rounded-lg text-ink-soft hover:text-maroon-800 hover:bg-pink-50" title="Edit">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                    </button>
                                    <form method="POST" action="{{ route('owner.produk.toggle-status', $product) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="p-2 rounded-lg text-ink-soft hover:text-maroon-800 hover:bg-pink-50" title="{{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M9 12l2 2 4-4"/></svg>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('owner.produk.destroy', $product) }}" onsubmit="return confirm('Hapus produk {{ addslashes($product->nama) }}?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-2 rounded-lg text-ink-soft hover:text-maroon-900 hover:bg-pink-50" title="Hapus">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-ink-soft">Belum ada produk. Klik "Tambah Produk" untuk mulai.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $products->links() }}</div>

    {{-- MODAL: tambah/edit --}}
    <x-owner.modal title="mode === 'create' ? 'Tambah Produk' : 'Edit Produk'"
                   subtitle="Data ini tampil di toko. Kolom bertanda * wajib diisi." files>

        {{-- Informasi --}}
        <section>
            <p class="o-section">Informasi Produk</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-4">
                <div class="sm:col-span-2">
                    <label class="o-label">Nama Produk *</label>
                    <input type="text" name="nama" x-model="form.nama" required class="o-input" placeholder="mis. Viva Gold Whitening Serum">
                </div>
                <div>
                    <label class="o-label">Brand *</label>
                    <input type="text" name="brand" x-model="form.brand" required class="o-input" placeholder="mis. Viva">
                </div>
                <div>
                    <label class="o-label">Kategori *</label>
                    @php
                        $kategoriModalOptions = ['' => 'Pilih kategori'];
                        foreach ($kategoriList as $kat) { $kategoriModalOptions[$kat] = ucfirst($kat); }
                        $kategoriModalOptions['new'] = '+ Kategori baru';
                    @endphp
                    <x-owner.select model="kategoriSelect" variant="field" :options="$kategoriModalOptions"
                        after="if (kategoriSelect !== 'new') { form.kategori = kategoriSelect; } else { form.kategori = ''; }" />
                    <input type="text" name="kategori" x-show="kategoriSelect === 'new'" x-model="form.kategori" placeholder="Ketik nama kategori baru"
                           class="o-input mt-2" :required="kategoriSelect === 'new'" :disabled="kategoriSelect !== 'new'">
                    <input type="hidden" name="kategori" :disabled="kategoriSelect === 'new'" :value="form.kategori">
                </div>
                <div class="sm:col-span-2">
                    <label class="o-label">Deskripsi</label>
                    <textarea name="deskripsi" x-model="form.deskripsi" rows="3" class="o-textarea" placeholder="Manfaat, kandungan, dan cara pakai singkat"></textarea>
                </div>
            </div>
        </section>

        {{-- Harga & stok --}}
        <section>
            <p class="o-section">Harga &amp; Stok</p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="o-label">Harga normal *</label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-[13px] text-ink-soft">Rp</span>
                        <input type="number" name="harga_normal" x-model.number="form.harga_normal" required min="1" class="o-input pl-10" placeholder="0">
                    </div>
                </div>
                <div>
                    <label class="o-label">Diskon (%)</label>
                    <div class="relative">
                        <input type="number" name="diskon_persen" x-model.number="form.diskon_persen" min="0" max="90" class="o-input pr-10" placeholder="0">
                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[13px] text-ink-soft">%</span>
                    </div>
                </div>
                <div>
                    <label class="o-label">Stok *</label>
                    <div class="relative">
                        <input type="number" name="stok" x-model="form.stok" required min="0" class="o-input pr-14" placeholder="0">
                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[12px] text-ink-soft">unit</span>
                    </div>
                </div>
            </div>

            <div class="mt-3 p-3.5 rounded-xl bg-pink-50 border border-pink-100 flex items-center justify-between text-[12.5px] text-ink-800">
                <div>
                    <span class="text-ink-soft">Harga Jual Otomatis:</span>
                    <span class="font-bold text-maroon-900 text-sm ml-1" x-text="formatRupiah(calculatedSellingPrice)"></span>
                    <template x-if="Number(form.diskon_persen) > 0">
                        <span class="ml-2 text-[11px] text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full font-medium" x-text="'Hemat ' + formatRupiah(Math.max(0, Number(form.harga_normal) - calculatedSellingPrice))"></span>
                    </template>
                </div>
                <span class="text-[11px] text-ink-soft">Dibulatkan ke ratusan terdekat</span>
            </div>
        </section>

        {{-- Foto --}}
        <section>
            <p class="o-section">Foto</p>
            <div class="space-y-5">
                <div>
                    <label class="o-label">Foto utama <span x-show="mode === 'create'">*</span></label>
                    <div class="flex items-center gap-4">
                        <div class="h-[84px] w-[84px] shrink-0 overflow-hidden rounded-2xl border border-dashed border-pink-200 bg-pink-50 flex items-center justify-center text-maroon-500">
                            <template x-if="form.image_url"><img :src="form.image_url" class="h-full w-full object-cover" alt=""></template>
                            <template x-if="!form.image_url">
                                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="3"/><circle cx="9" cy="10" r="1.6"/><path d="m21 16-5-5-8 8"/></svg>
                            </template>
                        </div>
                        <div class="min-w-0">
                            <label class="o-btn-soft">
                                <span x-text="form.image_url ? 'Ganti foto' : 'Pilih foto'"></span>
                                <input type="file" name="image" accept="image/*" class="sr-only o-modal-file" :required="mode === 'create'"
                                       @change="if ($event.target.files[0]) { form.image_url = URL.createObjectURL($event.target.files[0]); imageName = $event.target.files[0].name; }">
                            </label>
                            <p class="o-hint truncate" x-text="imageName || (mode === 'edit' ? 'Kosongkan jika tidak ingin mengganti.' : 'JPG atau PNG, maksimal 2 MB.')"></p>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="o-label">Foto galeri <span class="font-normal text-ink-soft">(opsional)</span></label>
                    <div class="flex flex-wrap items-center gap-2.5">
                        <template x-for="img in (form.gallery || [])" :key="img.id">
                            <button type="button" @click="toggleRemove(img.id)" :title="removeIds.includes(img.id) ? 'Batal hapus' : 'Klik untuk menghapus saat disimpan'"
                                    class="group relative h-16 w-16 overflow-hidden rounded-xl border border-pink-200">
                                <img :src="img.url" class="h-full w-full object-cover transition" :class="removeIds.includes(img.id) ? 'opacity-30 grayscale' : ''" alt="">
                                <span class="absolute inset-0 flex items-center justify-center bg-maroon-900/0 text-white opacity-0 transition group-hover:bg-maroon-900/45 group-hover:opacity-100"
                                      :class="removeIds.includes(img.id) ? '!opacity-100 !bg-maroon-900/60' : ''">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                </span>
                            </button>
                        </template>
                        <template x-for="id in removeIds" :key="'rm' + id"><input type="hidden" name="hapus_galeri[]" :value="id"></template>

                        <label class="o-btn-soft !h-16 !w-16 !p-0 flex-col !gap-0.5 border-dashed" title="Tambah foto galeri">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                            <span class="text-[11px]">Tambah</span>
                            <input type="file" name="galeri[]" accept="image/*" multiple class="sr-only o-modal-file" @change="galleryCount = $event.target.files.length">
                        </label>
                    </div>
                    <p class="o-hint" x-show="galleryCount > 0" x-text="galleryCount + ' foto baru akan ditambahkan saat disimpan.'"></p>
                    <p class="o-hint" x-show="removeIds.length > 0" x-text="removeIds.length + ' foto ditandai untuk dihapus.'"></p>
                    <p class="o-hint" x-show="galleryCount === 0 && removeIds.length === 0 && form.gallery && form.gallery.length">Klik foto untuk menandainya dihapus.</p>
                </div>
            </div>
        </section>

        {{-- Varian & tampilan --}}
        <section>
            <p class="o-section">Varian &amp; Label</p>
            <div class="space-y-4">
                <div>
                    <label class="o-label">Varian <span class="font-normal text-ink-soft">(opsional, mis. warna atau ukuran)</span></label>
                    <div class="space-y-2">
                        <template x-for="(v, i) in variants" :key="i">
                            <div class="flex items-center gap-2">
                                <input type="text" :name="'varian[' + i + ']'" x-model="variants[i]" placeholder="Nama varian" class="o-input !h-10">
                                <button type="button" @click="variants.splice(i, 1)" title="Hapus varian"
                                        class="h-10 w-10 shrink-0 rounded-xl flex items-center justify-center text-ink-soft hover:bg-pink-50 hover:text-maroon-900">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </template>
                    </div>
                    <button type="button" @click="variants.push('')" class="o-btn-soft mt-2 border-dashed">+ Tambah varian</button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="o-label">Badge <span class="font-normal text-ink-soft">(opsional)</span></label>
                        <input type="text" name="badge" x-model="form.badge" placeholder="mis. BEST SELLER" class="o-input">
                    </div>
                    <div>
                        <label class="o-label">Warna badge</label>
                        <x-owner.select name="badge_class" model="form.badge_class" variant="field" up
                            :options="['bg-pink-100 text-maroon-900' => 'Pink', 'bg-maroon-900 text-white' => 'Maroon', 'bg-yellow-100 text-yellow-800' => 'Gold']" />
                    </div>
                </div>
            </div>
        </section>

        {{-- Status --}}
        <label class="flex cursor-pointer items-center justify-between gap-4 rounded-2xl border border-pink-100 bg-pink-50/60 px-5 py-4">
            <span>
                <span class="block text-[13.5px] font-medium text-ink-900">Tampilkan di toko</span>
                <span class="block text-[12px] text-ink-soft">Produk nonaktif tidak terlihat oleh pelanggan.</span>
            </span>
            <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="peer sr-only">
            <span class="relative h-6 w-11 shrink-0 rounded-full bg-pink-200 transition peer-checked:bg-maroon-800 peer-focus-visible:ring-2 peer-focus-visible:ring-maroon-700/40
                         after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition after:content-[''] peer-checked:after:translate-x-5"></span>
        </label>
    </x-owner.modal>
</div>

@push('scripts')
<script>
function produkPanel() {
    return {
        open: false,
        mode: 'create',
        formAction: '{{ route('owner.produk.store') }}',
        variants: [],
        kategoriSelect: '',
        imageName: '',
        galleryCount: 0,
        removeIds: [],
        form: { nama: '', brand: '', kategori: '', harga_normal: '', diskon_persen: '', stok: '', badge: '', badge_class: 'bg-pink-100 text-maroon-900', deskripsi: '', is_active: true, image_url: null, gallery: [] },
        init() {
            @if(request()->filled('cari') && $products->count() == 1)
                this.openEdit({{ $products->first()->id }});
            @endif
        },

        get calculatedSellingPrice() {
            const normal = Number(this.form.harga_normal) || 0;
            const diskon = Number(this.form.diskon_persen) || 0;
            if (normal <= 0) return 0;
            if (diskon <= 0) return normal;
            const raw = normal * (1 - (diskon / 100));
            return Math.round(raw / 100) * 100;
        },

        formatRupiah(num) {
            return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
        },

        resetFiles() {
            this.imageName = '';
            this.galleryCount = 0;
            this.removeIds = [];
            document.querySelectorAll('.o-modal-file').forEach(i => i.value = '');
        },

        toggleRemove(id) {
            this.removeIds = this.removeIds.includes(id)
                ? this.removeIds.filter(x => x !== id)
                : [...this.removeIds, id];
        },

        openCreate() {
            this.mode = 'create';
            this.formAction = '{{ route('owner.produk.store') }}';
            this.variants = [];
            this.kategoriSelect = '';
            this.resetFiles();
            this.form = { nama: '', brand: '', kategori: '', harga_normal: '', diskon_persen: '', stok: '', badge: '', badge_class: 'bg-pink-100 text-maroon-900', deskripsi: '', is_active: true, image_url: null, gallery: [] };
            this.open = true;
        },

        async openEdit(id) {
            this.mode = 'edit';
            this.formAction = '/owner/produk/' + id;
            this.open = true;
            this.resetFiles();
            try {
                const res = await fetch('/owner/produk/' + id, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                
                // Set kategoriSelect based on data.kategori
                let knownCategories = @json($kategoriList);
                if (knownCategories.includes(data.kategori)) {
                    this.kategoriSelect = data.kategori;
                } else {
                    this.kategoriSelect = 'new';
                }

                this.form = {
                    nama: data.nama, brand: data.brand, kategori: data.kategori,
                    harga_normal: data.harga_normal || data.harga,
                    diskon_persen: data.diskon_persen || '',
                    stok: data.stok,
                    badge: data.badge, badge_class: data.badge_class || 'bg-pink-100 text-maroon-900', deskripsi: data.deskripsi, is_active: data.is_active,
                    image_url: data.image_url, gallery: data.gallery,
                };
                this.variants = data.variants && data.variants.length ? data.variants : [];
            } catch (e) {
                alert('Gagal memuat data produk.');
                this.open = false;
            }
        },
    };
}
</script>
@endpush
@endsection