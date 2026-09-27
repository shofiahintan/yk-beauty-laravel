@extends('layouts.app')

@section('title', __('sukses_meta_title'))

@section('content')

<section class="sukses-section">
  <div class="container">
    <div class="sukses-card" id="suksesLoaded" style="display:none;">
      <div class="sukses-icon">
        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="var(--maroon-800)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
      </div>
      <h1>{{ __('sukses_title') }}</h1>
      <p class="sukses-desc">{{ __('sukses_desc') }}</p>

      <div class="sukses-order-box">
        <div class="sukses-order-header">
          <span>{{ __('sukses_nomor_pesanan') }}</span>
          <span class="sukses-order-id" id="suksesOrderId">—</span>
        </div>
        <div id="suksesItemsWrap"></div>
        <div class="cart-summary-divider"></div>
        <div class="cart-summary-row">
          <span>{{ __('checkout_subtotal') }}</span>
          <span id="suksesSubtotal">Rp0</span>
        </div>
        <div class="cart-summary-row">
          <span>{{ __('pesanan_ongkir') }}</span>
          <span id="suksesShipping">Rp0</span>
        </div>
        <div class="cart-summary-divider"></div>
        <div class="cart-summary-total">
          <span>{{ __('checkout_total') }}</span>
          <span id="suksesTotal">Rp0</span>
        </div>
      </div>

      <div class="sukses-estimasi-box">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--maroon-800)" stroke-width="1.8"><rect x="1" y="7" width="15" height="10" rx="1.5"/><path d="M16 10h3.5l3.5 3.5V17h-7"/><circle cx="6" cy="19" r="1.6"/><circle cx="18" cy="19" r="1.6"/></svg>
        <div>
          <strong>{{ __('sukses_estimasi_title') }}</strong>
          <p id="suksesEstimasi"></p>
        </div>
      </div>

      <div class="sukses-btn-row">
        <a href="#" id="suksesLihatPesanan" class="btn btn-primary">{{ __('sukses_lihat_pesanan') }}</a>
        <a href="{{ route('produk') }}" class="btn btn-outline">{{ __('sukses_kembali_belanja') }}</a>
      </div>
    </div>

    <div class="cart-page-empty" id="suksesNotFound" style="display:none;">
      <div class="cart-page-empty-icon">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      </div>
      <h2>{{ __('pesanan_not_found') }}</h2>
      <a href="{{ route('produk') }}" class="btn btn-primary">{{ __('sukses_kembali_belanja') }}</a>
    </div>
  </div>
</section>

@endsection

@section('inline-data')
<script>
  window.__YK_PRODUCTS__ = @json($products);
  window.__YK_BUNDLES__ = @json($bundles ?? []);
  window.__YK_DB_ORDERS__ = @json($dbOrders);
  const suksesI18n = {
    lang: @json(app()->getLocale()),
    estimasiKurirPrefix: @json(__('sukses_estimasi_desc')),
    estimasiAmbil: @json(__('sukses_estimasi_ambil')),
    varianLabel: @json(__('pesanan_varian_label')),
    qtyLabel: @json(__('pesanan_qty_label')),
    gratis: @json(__('checkout_gratis'))
  };
</script>
@endsection

@push('scripts')
<script src="{{ asset('js/produk.js') }}"></script>
<script src="{{ asset('js/orders.js') }}"></script>
<script src="{{ asset('js/payment-sukses.js') }}"></script>
@endpush
