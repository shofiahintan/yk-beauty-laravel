@extends('layouts.app')

@section('title', __('cart_meta_title'))

@section('content')

<section class="page-banner align-left">
  <div class="container">
    <div class="inner">
      <span class="eyebrow">{{ __('shop_eyebrow') }}</span>
      <h1>{{ __('cart_title') }}</h1>
      <p>{{ __('cart_desc') }}</p>
    </div>
  </div>
</section>

<section class="cart-section">
  <div class="container">
    <div id="cartLoaded" style="display:none;">
      <div class="cart-layout">
        <div class="cart-items" id="cartItemsWrap"></div>

        <aside class="cart-summary-box">
          <h2>{{ __('cart_summary_title') }}</h2>
          <div class="cart-summary-row">
            <span>{{ __('cart_subtotal') }} (<span id="cartItemCount">0</span> {{ __('cart_item_word') }})</span>
            <span id="cartSubtotal">Rp0</span>
          </div>
          <div class="cart-summary-row">
            <span>{{ __('cart_shipping') }}</span>
            <span>{{ __('cart_shipping_note') }}</span>
          </div>
          <div class="cart-summary-divider"></div>
          <div class="cart-summary-total">
            <span>{{ __('cart_total') }}</span>
            <span id="cartTotal">Rp0</span>
          </div>
          <p style="font-size:11.5px; color:var(--ink-soft); margin:-14px 0 18px;">{{ __('cart_total_note') }}</p>
          <button type="button" class="btn btn-primary cart-checkout-btn" id="cartCheckoutBtn">
            {{ __('cart_checkout_btn') }}
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
          </button>
          <div class="cart-secure-note">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 018 0v3"/></svg>
            {{ __('cart_secure_note') }}
          </div>
        </aside>
      </div>
    </div>

    <div class="cart-page-empty" id="cartEmptyState" style="display:none;">
      <div class="cart-page-empty-icon">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 4h2l2.4 12.4a2 2 0 002 1.6h7.7a2 2 0 002-1.6L21 8H6"/><circle cx="10" cy="21" r="1.3"/><circle cx="18" cy="21" r="1.3"/></svg>
      </div>
      <h2>{{ __('cart_empty_title') }}</h2>
      <p>{{ __('cart_empty_desc') }}</p>
      <a href="{{ route('produk') }}" class="btn btn-primary">{{ __('cart_empty_btn') }}</a>
    </div>
  </div>
</section>

@endsection

@section('inline-data')
<script>
  window.__YK_PRODUCTS__ = @json($products);
  window.__YK_BUNDLES__ = @json($bundles ?? []);
  const cartI18n = {
    remove: @json(__('cart_remove')),
    checkoutSoon: @json(__('cart_checkout_soon'))
  };
</script>
@endsection

@push('scripts')
<script src="{{ asset('js/produk.js') }}"></script>
<script src="{{ asset('js/keranjang.js') }}"></script>
@endpush
