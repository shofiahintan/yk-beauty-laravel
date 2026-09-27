@extends('layouts.app')

@section('title', __('checkout_meta_title'))

@section('content')

<section class="page-banner align-left" style="padding-bottom:0;">
  <div class="container"><div class="inner"><h1>{{ __('checkout_title') }}</h1></div></div>
</section>

<section class="checkout-section">
  <div class="container">

    <div id="checkoutLoaded" style="display:none;">
      <form id="checkoutForm" class="checkout-layout" novalidate>
        @csrf

        <div class="checkout-main">

          <div class="checkout-card">
            <h2>{{ __('checkout_data_pembeli') }}</h2>
            <div class="akun-form-row">
              <div class="akun-form-group">
                <label>{{ __('checkout_nama_lengkap') }}</label>
                <input type="text" id="ckNama" placeholder="{{ __('checkout_ph_nama') }}" value="{{ auth()->user()?->name ?? '' }}">
                <div class="checkout-field-err" id="errCkNama" style="display:none;"></div>
              </div>
              <div class="akun-form-group">
                <label>{{ __('checkout_no_wa') }}</label>
                <input type="text" id="ckWa" placeholder="{{ __('checkout_ph_wa') }}" value="{{ auth()->user()?->whatsapp ?? '' }}">
                <div class="checkout-field-err" id="errCkWa" style="display:none;"></div>
              </div>
            </div>
          </div>

          <div class="checkout-card">
            <h2>{{ __('checkout_metode_kirim') }}</h2>
            <div class="checkout-info-box">
              <strong>{{ __('checkout_kurir_instan') }}</strong>
              <span>{{ __('checkout_dihitung_bayar') }}</span>
            </div>

            <div id="kurirAddressBox" style="margin-top:18px;">
              <div class="akun-form-group" style="margin-bottom:18px;">
                <label>{{ __('checkout_alamat_lengkap') }}</label>
                <input type="text" id="ckAlamat" placeholder="{{ __('checkout_ph_alamat') }}" value="{{ auth()->user()?->alamat ?? '' }}">
                <div class="checkout-field-err" id="errCkAlamat" style="display:none;"></div>
              </div>
              <div class="akun-form-row" style="margin-bottom:0;">
                <div class="akun-form-group">
                  <label>{{ __('checkout_kota') }}</label>
                  <input type="text" id="ckKota" placeholder="{{ __('checkout_ph_kota') }}">
                  <div class="checkout-field-err" id="errCkKota" style="display:none;"></div>
                </div>
                <div class="akun-form-group">
                  <label>{{ __('checkout_kode_pos') }}</label>
                  <input type="text" id="ckKodePos" placeholder="{{ __('checkout_ph_kodepos') }}">
                  <div class="checkout-field-err" id="errCkKodepos" style="display:none;"></div>
                </div>
              </div>
            </div>
          </div>

          <div class="checkout-card">
            <h2>{{ __('checkout_metode_bayar') }}</h2>
            <div class="checkout-info-box">
              <strong>{{ __('checkout_midtrans_title') }}</strong>
              <span>{{ __('checkout_midtrans_note') }}</span>
            </div>
          </div>

        </div>

        <aside class="cart-summary-box checkout-summary-box">
          <h2>{{ __('checkout_ringkasan') }}</h2>
          <div id="checkoutItemsWrap"></div>
          <div class="cart-summary-divider"></div>
          <div class="cart-summary-row">
            <span>{{ __('checkout_subtotal') }}</span>
            <span id="ckSubtotal">Rp0</span>
          </div>
          <div class="cart-summary-row">
            <span>{{ __('checkout_pengiriman') }}</span>
            <span id="ckShipping">{{ __('checkout_gratis') }}</span>
          </div>
          <div class="cart-summary-divider"></div>
          <div class="cart-summary-total">
            <span>{{ __('checkout_total') }}</span>
            <span id="ckTotal">Rp0</span>
          </div>
          <button type="submit" class="btn btn-primary cart-checkout-btn">{{ __('checkout_selesaikan_btn') }}</button>
        </aside>

      </form>
    </div>

    <div class="cart-page-empty" id="checkoutEmptyState" style="display:none;">
      <div class="cart-page-empty-icon">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 4h2l2.4 12.4a2 2 0 002 1.6h7.7a2 2 0 002-1.6L21 8H6"/><circle cx="10" cy="21" r="1.3"/><circle cx="18" cy="21" r="1.3"/></svg>
      </div>
      <h2>{{ __('checkout_cart_kosong') }}</h2>
      <a href="{{ route('keranjang') }}" class="btn btn-primary">{{ __('checkout_kembali_keranjang') }}</a>
    </div>

  </div>
</section>

@endsection

@section('inline-data')
<script>
  window.__YK_PRODUCTS__ = @json($products);
  window.__YK_BUNDLES__ = @json($bundles ?? []);
  window.__YK_SHIPPING_FLAT__ = @json($shippingFlat);
  const checkoutI18n = {
    gratis: @json(__('checkout_gratis')),
    errNama: @json(__('err_nama_required')),
    errWa: @json(__('err_wa_required')),
    errAlamat: @json(__('err_alamat_required')),
    errKota: @json(__('err_kota_required')),
    errKodepos: @json(__('err_kodepos_required')),
    errGeneric: @json(__('Gagal memproses pesanan. Silakan coba lagi.')),
    varianLabel: @json(__('pesanan_varian_label')),
    qtyLabel: @json(__('pesanan_qty_label'))
  };
  const userProfile = {
    nama: @json(auth()->user()?->name ?? ''),
    wa: @json(auth()->user()?->whatsapp ?? ''),
    alamat: @json(auth()->user()?->alamat ?? '')
  };
</script>
@endsection

@push('scripts')
<script src="{{ asset('js/produk.js') }}"></script>
<script src="{{ asset('js/orders.js') }}"></script>
<script src="{{ config('midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}" data-client-key="{{ config('midtrans.client_key') }}"></script>
<script src="{{ asset('js/checkout.js') }}"></script>
@endpush