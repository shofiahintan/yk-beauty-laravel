@extends('layouts.app')

@section('title', __('Wishlist Saya'))

@section('content')

<section class="page-banner align-left">
  <div class="container">
    <div class="inner">
      <span class="eyebrow">{{ __('Koleksi Kami') }}</span>
      <h1>{{ __('Wishlist Saya') }}</h1>
      <p>{{ __('Koleksi produk perawatan kulit pilihan Anda. Simpan produk favorit Anda di sini untuk pembelian nanti.') }}</p>
    </div>
  </div>
</section>

<section class="wishlist-section">
  <div class="container">
    <div class="produk-grid-full" id="wishlistGrid"></div>

    <div class="wishlist-page-empty" id="wishlistEmptyState" style="display:none;">
      <div class="wishlist-page-empty-icon">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 10-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
      </div>
      <h2>{{ __('Wishlist Anda Masih Kosong') }}</h2>
      <p>{{ __('Tandai produk favorit Anda dengan ikon hati agar mudah ditemukan lagi.') }}</p>
      <a href="{{ route('produk') }}" class="btn btn-primary">{{ __('Jelajahi Produk') }}</a>
    </div>
  </div>
</section>

@endsection

@section('inline-data')
<script>
  window.__YK_PRODUCTS__ = @json($products);
  const wishI18n = {
    addToCart: @json(__('Tambah ke Keranjang')),
    stockIn: @json(__('Stok Tersedia')),
    stockOut: @json(__('Stok Habis')),
    notify: @json(__('Beritahu Saya')),
    catFace: @json(__('Perawatan Wajah')),
    catBody: @json(__('Perawatan Tubuh')),
    catHair: @json(__('Perawatan Rambut')),
    catMakeup: @json(__('Make Up'))
  };
</script>
@endsection

@push('scripts')
<script src="{{ asset('js/produk.js') }}"></script>
<script src="{{ asset('js/wishlist.js') }}"></script>
@endpush