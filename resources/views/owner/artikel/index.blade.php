@extends('layouts.owner')

@section('title', 'Kelola Artikel')
@section('breadcrumb', 'Artikel')

@section('content')
<div x-data="artikelPanel()">

    {{-- HEADER: search + tambah --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <form method="GET" action="{{ route('owner.artikel.index') }}" data-autofilter class="flex items-center gap-2">
            <div class="relative">
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari judul atau ringkasan..."
                       class="h-11 pl-10 pr-4 rounded-xl border border-pink-200 bg-white text-[13.5px] text-ink-900 focus:outline-none focus:border-maroon-700 w-64">
                <svg class="absolute left-3.5 top-3.5 text-ink-soft" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </div>
            @if (!empty($filters['q']))
                <a href="{{ route('owner.artikel.index') }}" class="h-11 px-3 rounded-xl border border-pink-200 text-ink-soft hover:text-maroon-900 flex items-center text-[13px]">Reset</a>
            @endif
        </form>

        <button type="button" @click="openCreate()" class="h-11 px-5 rounded-xl bg-maroon-900 text-white text-[13.5px] font-medium hover:bg-maroon-800 shrink-0">
            + Tambah Artikel
        </button>
    </div>

    {{-- TABLE --}}
    <div class="bg-white rounded-[18px] shadow-owner-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-[13.5px]">
                <thead>
                    <tr class="border-b border-pink-100 text-left text-ink-soft text-[12px] uppercase tracking-wide">
                        <th class="px-5 py-3 font-medium">Artikel</th>
                        <th class="px-5 py-3 font-medium">Kategori</th>
                        <th class="px-5 py-3 font-medium">Tanggal Terbit</th>
                        <th class="px-5 py-3 font-medium">Ringkasan</th>
                        <th class="px-5 py-3 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-pink-50">
                    @forelse ($posts as $post)
                        @php
                            $postData = [
                                'id'           => $post->id,
                                'title'        => $post->title,
                                'category'     => $post->getRawOriginal('category') ?? $post->category,
                                'published_at' => $post->published_at ? $post->published_at->format('Y-m-d') : '',
                                'excerpt'      => $post->excerpt,
                                'body'         => is_array($post->body) ? implode("\n\n", $post->body) : '',
                                'image_url'    => $post->image_url,
                                'action'       => route('owner.artikel.update', $post),
                            ];
                        @endphp
                        <tr class="hover:bg-pink-50/50">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $post->image_url }}" class="h-12 w-16 rounded-xl object-cover shrink-0 bg-pink-100" alt="">
                                    <div class="min-w-0 max-w-[260px]">
                                        <p class="font-medium text-ink-900 leading-snug truncate" title="{{ $post->title }}">{{ $post->title }}</p>
                                        <p class="text-[11.5px] text-ink-soft font-mono mt-0.5 truncate">/blog/{{ $post->slug }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                <span class="inline-block px-2.5 py-1 rounded-full text-[11.5px] font-medium bg-pink-100 text-maroon-900">
                                    {{ $post->category }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-ink-soft whitespace-nowrap">
                                {{ $post->published_at ? $post->published_at->format('d M Y') : '-' }}
                            </td>
                            <td class="px-5 py-3 text-ink-soft text-[12.5px] max-w-[300px]">
                                <p class="line-clamp-2 leading-relaxed">{{ $post->excerpt }}</p>
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <button type="button" @click="openEdit(@js($postData))"
                                            class="h-8 px-3 rounded-lg border border-pink-200 text-[12.5px] text-maroon-800 font-medium hover:bg-pink-50">
                                        Edit
                                    </button>
                                    <form method="POST" action="{{ route('owner.artikel.destroy', $post) }}"
                                          onsubmit="return confirm('Hapus artikel &quot;{{ addslashes($post->title) }}&quot;? File gambar upload juga akan dihapus.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="h-8 w-8 rounded-lg border border-pink-200 text-ink-soft hover:text-maroon-900 hover:border-pink-300 flex items-center justify-center" title="Hapus">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-ink-soft">
                                Belum ada artikel yang dipublikasikan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $posts->links() }}</div>

    {{-- MODAL FORM (TAMBAH / EDIT) --}}
    <x-owner.modal title="mode === 'create' ? 'Tambah Artikel' : 'Edit Artikel'"
                   subtitle="Kelola artikel, berita kecantikan, dan tips skincare untuk blog."
                   width="max-w-[700px]" files>

        <div class="space-y-5">
            <div>
                <label class="o-label">Judul Artikel *</label>
                <input type="text" name="title" x-model="form.title" required class="o-input" placeholder="mis. 5 Tips Memilih Sunscreen untuk Kulit Sensitif">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="o-label">Kategori *</label>
                    <input type="text" name="category" x-model="form.category" required class="o-input" placeholder="mis. Tips & Trik / Skincare 101">
                </div>
                <div>
                    <label class="o-label">Tanggal Terbit *</label>
                    <input type="date" name="published_at" x-model="form.published_at" required class="o-input">
                </div>
            </div>

            <div>
                <label class="o-label">Gambar Utama * <span class="text-[12px] font-normal text-ink-soft">(JPG, PNG, WebP — Maks. 2 MB)</span></label>
                <input type="file" name="image" accept="image/jpeg,image/png,image/webp" :required="mode === 'create'"
                       @change="handleImagePreview($event)" class="o-input text-[12.5px] file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-[12px] file:font-medium file:bg-pink-100 file:text-maroon-900 hover:file:bg-pink-200">
                <template x-if="imagePreview">
                    <div class="mt-2.5">
                        <img :src="imagePreview" class="h-28 rounded-xl object-cover border border-pink-200" alt="Preview">
                    </div>
                </template>
            </div>

            <div>
                <label class="o-label">Ringkasan Artikel (Excerpt) *</label>
                <textarea name="excerpt" x-model="form.excerpt" rows="2" required class="o-textarea" placeholder="Ringkasan singkat yang akan tampil di kartu artikel pada halaman daftar blog..."></textarea>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="o-label mb-0">Isi Artikel *</label>
                    <span class="text-[11.5px] text-ink-soft">Pisahkan tiap paragraf dengan 1 baris kosong (Enter 2x)</span>
                </div>
                <textarea name="body" x-model="form.body" rows="8" required class="o-textarea font-sans leading-relaxed" placeholder="Tuliskan isi lengkap artikel di sini...

Paragraf kedua dipisahkan dengan baris kosong seperti ini.

Paragraf ketiga dan seterusnya."></textarea>
            </div>
        </div>
    </x-owner.modal>

</div>

@push('scripts')
<script>
function artikelPanel() {
    return {
        open: false,
        mode: 'create',
        formAction: '{{ route('owner.artikel.store') }}',
        imagePreview: null,
        form: {
            id: null,
            title: '',
            category: '',
            published_at: new Date().toISOString().slice(0, 10),
            excerpt: '',
            body: '',
        },
        openCreate() {
            this.mode = 'create';
            this.formAction = '{{ route('owner.artikel.store') }}';
            this.imagePreview = null;
            this.form = {
                id: null,
                title: '',
                category: '',
                published_at: new Date().toISOString().slice(0, 10),
                excerpt: '',
                body: '',
            };
            this.open = true;
        },
        openEdit(post) {
            this.mode = 'edit';
            this.formAction = post.action;
            this.imagePreview = post.image_url;
            this.form = {
                id: post.id,
                title: post.title,
                category: post.category,
                published_at: post.published_at,
                excerpt: post.excerpt,
                body: post.body,
            };
            this.open = true;
        },
        handleImagePreview(event) {
            const file = event.target.files[0];
            if (file) {
                this.imagePreview = URL.createObjectURL(file);
            }
        }
    };
}
</script>
@endpush
@endsection
