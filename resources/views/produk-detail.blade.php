@extends('layouts.app')

@section('title', __('Detail Produk'))

@section('content')

<div class="container">
  <a href="{{ route('produk') }}" class="produk-detail-back-link">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
    {{ __('Kembali ke Semua Produk') }}
  </a>
</div>

<section class="produk-detail-section">
  <div class="container">

    <!-- Ditampilkan JS kalau produk ketemu -->
    <div class="produk-detail-grid" id="produkDetailGrid" style="display:none;">

      <div class="produk-detail-gallery">
        <div class="produk-detail-thumbs" id="detailThumbs"></div>
        <div class="produk-detail-image-wrap">
          <span class="produk-detail-badge" id="detailBadge" style="display:none;"></span>
          <div class="produk-detail-wish-btn" id="detailWishBtn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 10-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
          </div>
          <img src="" alt="" id="detailMainImg">
        </div>
      </div>

      <div class="produk-detail-info">
        <div class="produk-badge-inline" id="detailBadgeInline" style="display:none;"></div>
        <h1 id="detailNama"></h1>

        <div class="produk-detail-rating-row">
          <span class="stars" id="detailStars"></span>
        </div>

        <div class="produk-detail-price-row">
          <span class="harga" id="detailHarga"></span>
          <span class="harga-coret" id="detailHargaCoret" style="display:none;"></span>
        </div>

        <p class="produk-detail-desc" id="detailDeskripsi" style="display:none;"></p>

        <div class="produk-detail-divider"></div>

        <div class="produk-detail-option-group" id="detailVarianBlock" style="display:none;">
          <div class="produk-detail-option-label">{{ __('Pilih Jenis') }}</div>
          <div class="produk-detail-option-buttons" id="detailVarianOptions"></div>
        </div>

        <div class="produk-detail-option-group" id="detailUkuranBlock" style="display:none;">
          <div class="produk-detail-option-label">{{ __('Ukuran') }}</div>
          <div class="produk-detail-option-buttons" id="detailUkuranOptions"></div>
        </div>

        <div class="produk-detail-action-row">
          <div class="produk-detail-qty">
            <button type="button" id="detailQtyMinus" aria-label="Kurangi">−</button>
            <span id="detailQtyValue">1</span>
            <button type="button" id="detailQtyPlus" aria-label="Tambah">+</button>
          </div>
          <button class="produk-detail-add-btn" id="detailAddBtn">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 4h2l2.4 12.4a2 2 0 002 1.6h7.7a2 2 0 002-1.6L21 8H6"/><circle cx="10" cy="21" r="1.3"/><circle cx="18" cy="21" r="1.3"/></svg>
            {{ __('Tambah ke Keranjang') }}
          </button>
        </div>

        <div class="produk-detail-trust-grid">
          <div class="produk-detail-trust-item">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2l8 3v6c0 5-3.5 8.5-8 11-4.5-2.5-8-6-8-11V5l8-3z"/></svg>
            {{ __('100% Vegan') }}
          </div>
          <div class="produk-detail-trust-item">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
            {{ __('Teruji Klinis') }}
          </div>
          <div class="produk-detail-trust-item">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="1" y="5" width="15" height="13" rx="2"/><path d="M16 9h3.6a1 1 0 01.9.55L22 13v5h-2"/><circle cx="6" cy="18.5" r="1.6"/><circle cx="17.5" cy="18.5" r="1.6"/></svg>
            {{ __('Gratis Ongkir') }}
          </div>
          <div class="produk-detail-trust-item">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
            {{ __('Garansi 3 Hari') }}
          </div>
        </div>
      </div>
    </div>

    <!-- Ditampilkan JS kalau id produk tidak ketemu -->
    <div class="produk-detail-not-found" id="produkNotFound" style="display:none;">
      <h2>{{ __('Produk Tidak Ditemukan') }}</h2>
      <p>{{ __('Produk yang Anda cari mungkin sudah tidak tersedia atau tautannya salah.') }}</p>
      <a href="{{ route('produk') }}" class="btn btn-primary">{{ __('Kembali ke Semua Produk') }}</a>
    </div>

  </div>
</section>

@endsection

@section('inline-data')
<script>
  window.__YK_PRODUCTS__ = @json($products);
  const detailI18n = {
    reviewsWord: @json(__('Ulasan')),
    metaFallback: @json(__('Detail Produk')),
    outOfStock: @json(__('Habis'))
  };
</script>
@endsection

@push('scripts')
<script src="{{ asset('js/produk.js') }}"></script>
<script src="{{ asset('js/produk-detail.js') }}"></script>
@endpush