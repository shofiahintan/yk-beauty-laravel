@extends('layouts.app')

@section('title', __('koleksi_listing_meta_title'))

@section('content')

<section class="page-banner">
  <div class="container">
    <div class="inner">
      <span class="eyebrow">{{ __('koleksi_listing_eyebrow') }}</span>
      <h1>{{ __('koleksi_listing_title') }}</h1>
      <p>{{ __('koleksi_listing_desc') }}</p>
    </div>
  </div>
</section>

<section class="koleksi-listing-section">
  <div class="container">

    @if ($collections->isEmpty())
      <div class="cart-page-empty">
        <div class="cart-page-empty-icon">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="8" width="18" height="13" rx="2"/><path d="M3 8l2.5-5h13L21 8"/><path d="M12 8v13M8 8V5a4 4 0 118 0v3"/></svg>
        </div>
        <h2>{{ __('koleksi_listing_empty_title') }}</h2>
        <p>{{ __('koleksi_listing_empty_desc') }}</p>
        <a href="{{ route('produk') }}" class="btn btn-primary">{{ __('koleksi_listing_empty_btn') }}</a>
      </div>
    @else
      <div class="koleksi-listing-grid">
        @foreach ($collections as $collection)
          @php
            $hemat = $collection->hemat;
          @endphp
          <a href="{{ route('koleksi.detail', $collection->slug) }}" class="koleksi-card">
            <div class="koleksi-card-img-wrap">
              @if ($hemat > 0)
                <span class="koleksi-card-badge">{{ __('Hemat') }} {{ formatRupiah($hemat) }}</span>
              @endif
              <img src="{{ asset($collection->image ?? 'assets/images/landing9.jpg') }}" alt="{{ $collection->title }}">
            </div>
            <div class="koleksi-card-info">
              <h3>{{ $collection->title }}</h3>
              <p>{{ $collection->description ?: __('koleksi_card_desc') }}</p>
              <div class="koleksi-card-price-row">
                <span class="harga">{{ formatRupiah($collection->harga) }}</span>
                @if ($collection->coret)
                  <span class="harga-coret">{{ formatRupiah($collection->coret) }}</span>
                @endif
              </div>
              <span class="koleksi-card-link">{{ __('koleksi_card_link') }}</span>
            </div>
          </a>
        @endforeach
      </div>
    @endif

  </div>
</section>

@endsection