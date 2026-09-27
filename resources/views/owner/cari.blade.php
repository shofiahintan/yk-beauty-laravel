@extends('layouts.owner')

@section('title', 'Hasil Pencarian')
@section('breadcrumb', 'Hasil Pencarian')

@section('content')
    <h1 class="font-poppins font-semibold text-[24px] text-maroon-900 mb-1">Hasil Pencarian</h1>
    <p class="text-[13px] text-ink-soft mb-8">
        @if (mb_strlen($q) >= 2)
            Menampilkan hasil untuk <strong class="text-ink-900">{{ $q }}</strong>
        @else
            Ketik minimal 2 karakter di kolom pencarian di atas.
        @endif
    </p>

    @if (mb_strlen($q) >= 2 && count($groups) === 0)
        <div class="bg-white rounded-[18px] shadow-owner-card p-10 text-center">
            <p class="text-[14px] text-ink-900 font-medium mb-1">Tidak ada hasil</p>
            <p class="text-[13px] text-ink-soft">Coba kata kunci lain: kode pesanan, nama pembeli, nama produk, atau email pelanggan.</p>
        </div>
    @endif

    <div class="space-y-6">
        @foreach ($groups as $group)
            <div class="bg-white rounded-[18px] shadow-owner-card overflow-hidden">
                <div class="px-5 py-4 border-b border-pink-100 flex items-center justify-between">
                    <h2 class="text-[15px] font-semibold text-ink-900">{{ $group['label'] }}</h2>
                    <x-owner.badge>{{ count($group['items']) }}</x-owner.badge>
                </div>
                <div class="divide-y divide-pink-50">
                    @foreach ($group['items'] as $item)
                        <a href="{{ $item['url'] }}" class="flex items-center gap-3 px-5 py-3.5 hover:bg-pink-50">
                            @if (! empty($item['image']))
                                <img src="{{ $item['image'] }}" alt="" class="h-11 w-11 rounded-xl object-cover shrink-0">
                            @endif
                            <span class="min-w-0 flex-1">
                                <span class="block text-[13.5px] text-ink-900 font-medium truncate">{{ $item['title'] }}</span>
                                <span class="block text-[12px] text-ink-soft truncate">{{ $item['subtitle'] }}</span>
                            </span>
                            @if (! empty($item['badge']))
                                <x-owner.badge>{{ $item['badge'] }}</x-owner.badge>
                            @endif
                            <svg class="shrink-0 text-ink-soft" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></svg>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endsection
