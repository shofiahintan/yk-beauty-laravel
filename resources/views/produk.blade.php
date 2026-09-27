@extends('layouts.app')

@section('title', 'Semua Produk')

@section('content')

<section class="page-banner">
  <div class="container">
    <div class="inner">
      <span class="eyebrow">{{ __('Koleksi Kami') }}</span>
      <h1>{{ __('Semua Produk') }}</h1>
      <p>{{ __('Temukan produk skincare dan kecantikan original pilihan, sesuai kebutuhan kulitmu.') }}</p>

      <div class="shop-search-wrap">
        <div class="shop-search-box">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
          <input type="text" id="produkSearchInput" placeholder="{{ __('Cari produk, misal: serum wajah...') }}" autocomplete="off">
          <button type="button" class="shop-search-clear" id="produkSearchClear" aria-label="Clear" style="display:none;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
          </button>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="shop-section">
  <div class="container">
    <div class="shop-layout">

      <!-- SIDEBAR FILTER -->
      <aside class="filter-sidebar">
        <div class="filter-sidebar-head">
          <span class="filter-sidebar-title">{{ __('Filter') }}</span>
          <button type="button" class="filter-reset" id="filterReset">{{ __('Reset') }}</button>
        </div>

        <div class="filter-group">
          <div class="filter-group-title">{{ __('Kategori') }}</div>
          <label class="filter-check">
            <input type="checkbox" name="kategori" value="skincare"> {{ __('Perawatan Wajah') }}
          </label>
          <label class="filter-check">
            <input type="checkbox" name="kategori" value="body care"> {{ __('Perawatan Tubuh') }}
          </label>
          <label class="filter-check">
            <input type="checkbox" name="kategori" value="rambut"> {{ __('Perawatan Rambut') }}
          </label>
          <label class="filter-check">
            <input type="checkbox" name="kategori" value="makeup"> {{ __('Make Up') }}
          </label>
        </div>

        <div class="filter-group">
          <div class="filter-group-title">{{ __('Brand') }}</div>
          <label class="filter-check"><input type="checkbox" name="brand" value="viva"> Viva</label>
          <label class="filter-check"><input type="checkbox" name="brand" value="hanasui"> Hanasui</label>
          <label class="filter-check"><input type="checkbox" name="brand" value="scarlett"> Scarlett</label>
          <label class="filter-check"><input type="checkbox" name="brand" value="emina"> Emina</label>
          <label class="filter-check"><input type="checkbox" name="brand" value="wardah"> Wardah</label>
          <label class="filter-check"><input type="checkbox" name="brand" value="somethinc"> Somethinc</label>
        </div>

        <div class="filter-group">
          <div class="filter-group-title">{{ __('Rentang Harga') }}</div>
          <div class="price-display" id="priceDisplay">Rp0 — Rp200.000</div>
          <div class="price-slider">
            <div class="track"></div>
            <div class="fill" id="priceFill"></div>
            <input type="range" id="priceMin" min="0" max="200000" step="1000" value="0">
            <input type="range" id="priceMax" min="0" max="200000" step="1000" value="200000">
          </div>
          <div class="price-inputs">
            <div class="price-input-wrap">
              <span class="price-input-prefix">Rp</span>
              <input type="number" id="priceMinInput" placeholder="Min" value="0">
            </div>
            <span class="price-sep">—</span>
            <div class="price-input-wrap">
              <span class="price-input-prefix">Rp</span>
              <input type="number" id="priceMaxInput" placeholder="Max" value="200000">
            </div>
          </div>
        </div>
      </aside>

      <!-- MAIN CONTENT -->
      <div class="shop-main">
        <div class="shop-main-head">
          <div class="produk-count" id="produkCount"></div>
          <div class="custom-select" id="sortFilter" tabindex="0">
            <div class="custom-select-trigger">
              <span id="sortFilterLabel">{{ __('Urutkan: Terbaru') }}</span>
              <svg class="custom-select-arrow" width="12" height="8" viewBox="0 0 12 8" fill="none"><path d="M1 1l5 5 5-5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <div class="custom-select-options">
              <div class="custom-select-option active" data-value="terbaru">{{ __('Urutkan: Terbaru') }}</div>
              <div class="custom-select-option" data-value="terlaris">{{ __('Paling Laris') }}</div>
              <div class="custom-select-option" data-value="termurah">{{ __('Harga Terendah') }}</div>
              <div class="custom-select-option" data-value="termahal">{{ __('Harga Tertinggi') }}</div>
            </div>
          </div>
        </div>

        <div class="produk-grid-full" id="allProdukGrid"></div>

        <div class="load-more-wrap" id="loadMoreWrap">
          <button class="btn btn-primary" id="loadMoreBtn">{{ __('MUAT LEBIH BANYAK') }}</button>
        </div>
      </div>

    </div>
  </div>
</section>

@endsection

@section('inline-data')
<script>
  window.__YK_PRODUCTS__ = @json($products);

  const shopI18n = {
    showing: @json(__('Menampilkan')),
    of: @json(__('dari')),
    products: @json(__('produk')),
    addToCart: @json(__('Tambah ke Keranjang')),
    outOfStock: @json(__('Habis')),
    noResultTitle: @json(__('Belum Ada Produk yang Cocok')),
    noResultDesc: @json(__('Coba ubah kata kunci pencarian atau kombinasi filter, atau reset untuk melihat semua produk.')),
    resetFilterBtn: @json(__('Reset Filter')),
    sortNewest: @json(__('Urutkan: Terbaru'))
  };
</script>
@endsection

@push('scripts')
<script src="{{ asset('js/produk.js') }}"></script>
@endpush