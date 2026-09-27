@extends('layouts.app')

@section('title', $collection?->title ?? __('koleksi_page_title'))

@section('content')

<!-- HERO PAKET -->
<section class="koleksi-detail-section">
  <div class="container">
    <div class="koleksi-detail-grid">

      <div class="koleksi-detail-image-wrap">
        <img src="{{ asset($collection?->image ?? 'assets/images/landing9.jpg') }}" alt="{{ $collection?->title ?? __('koleksi_page_title') }}">
      </div>

      <div class="koleksi-detail-info">
        <span class="koleksi-badge-save">{{ __('koleksi_badge_save') }}</span>
        <h1>{{ $collection?->title ?? __('koleksi_page_title') }}</h1>
        <p class="koleksi-desc">
          {{ $collection?->description ?? __('koleksi_desc') }}
        </p>

        <div class="koleksi-price-row">
          <span class="harga">{{ formatRupiah($collection?->harga ?? 150000) }}</span>
          <span class="harga-coret">{{ formatRupiah($collection?->harga_coret ?? 165000) }}</span>
        </div>

        @php
          $bundleInStock = $collection->products->isNotEmpty() && $collection->products->min('stok') > 0;
        @endphp
        <button class="koleksi-add-btn" id="btnTambahPaket" type="button"
                data-id="{{ $collection->slug }}" data-type="bundle"
                @if(!$bundleInStock) disabled @endif>
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 4h2l2.4 12.4a2 2 0 002 1.6h7.7a2 2 0 002-1.6L21 8H6"/><circle cx="10" cy="21" r="1.3"/><circle cx="18" cy="21" r="1.3"/></svg>
          {{ $bundleInStock ? __('koleksi_add_btn') : __('Habis') }}
        </button>

        <div class="koleksi-trust-row">
          <div class="koleksi-trust-item">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2l8 3v6c0 5-3.5 8.5-8 11-4.5-2.5-8-6-8-11V5l8-3z"/></svg>
            {{ __('trust_bpom') }}
          </div>
          <div class="koleksi-trust-item">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 12v6a2 2 0 01-2 2H6a2 2 0 01-2-2v-6"/><path d="M2 7h20v5H2z"/><path d="M12 22V7"/><path d="M12 7S9.5 2 6.5 2 4 5 6.5 5 12 7 12 7z"/><path d="M12 7s2.5-5 5.5-5 2.5 3 0 3-5.5 2-5.5 2z"/></svg>
            {{ __('trust_freegift') }}
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ISI PAKET INI -->
<section class="isi-paket-section">
  <div class="container">
    <div class="section-title-deco">
      <span class="eyebrow">{{ __('isipaket_eyebrow') }}</span>
      <h2>{{ __('isipaket_title') }}</h2>
      <div class="deco-line"></div>
      <p>{{ __('isipaket_desc') }}</p>
    </div>

    <div class="paket-item-grid">
      @forelse ($collection->products as $index => $product)
        <div class="paket-item-card">
          <div class="paket-item-img-wrap">
            <div class="wish-btn" data-id="{{ $product->slug }}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 10-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
            </div>
            <img src="{{ asset($product->image ?? 'assets/images/landing9.jpg') }}" alt="{{ $product->nama }}">
          </div>
          <div class="paket-item-info">
            <div class="subtitle">{{ __('Langkah') }} {{ $index + 1 }}</div>
            <div class="nama">{{ $product->nama }}</div>
            @if($product->deskripsi)
              <p>{{ $product->deskripsi }}</p>
            @endif
            @if((int) $product->stok === 0)
              <p style="color:#d32f2f; font-size:12px; margin-top:4px;">{{ __('Habis') }}</p>
            @endif
          </div>
        </div>
      @empty
        <p>{{ __('Paket ini belum memiliki produk.') }}</p>
      @endforelse
    </div>
  </div>
</section>

<!-- RITUAL HARIAN -->
<section class="ritual-section">
  <div class="container">
    <div class="section-title-deco">
      <span class="eyebrow">{{ __('ritual_eyebrow') }}</span>
      <h2>{{ __('ritual_title') }}</h2>
      <div class="deco-line"></div>
      <p>{{ __('ritual_desc') }}</p>
    </div>

    <div class="ritual-grid">
      <div class="ritual-card">
        <div class="ritual-number">1</div>
        <div class="ritual-icon-title">
          <div class="ritual-icon">
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="5"/><path d="M12 1v3M12 20v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M1 12h3M20 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"/></svg>
          </div>
          <h3>{{ __('ritual_pagi_title') }}</h3>
        </div>
        <p>{{ __('ritual_pagi_desc') }}</p>
      </div>

      <div class="ritual-card">
        <div class="ritual-number">2</div>
        <div class="ritual-icon-title">
          <div class="ritual-icon">
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 14.5A8.5 8.5 0 019.5 4a8.5 8.5 0 1010.5 10.5z"/></svg>
          </div>
          <h3>{{ __('ritual_malam_title') }}</h3>
        </div>
        <p>{{ __('ritual_malam_desc') }}</p>
      </div>
    </div>
  </div>
</section>

@endsection


