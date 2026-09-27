@extends('layouts.app')

@section('title', __('detail_pesanan_meta_title'))

@section('content')

<section class="pesanan-section">
  <div class="container">

    <div id="pesananLoaded" style="display:none;">

      <div class="pesanan-header">
        <div>
          <h1 id="pesananOrderId">Order #</h1>
          <p class="pesanan-dipesan-pada"></p>
        </div>
        <div class="pesanan-header-actions">
          <span class="order-status-badge" id="pesananStatusBadge"></span>
          <button type="button" class="btn btn-outline" id="pesananInvoiceBtn">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            {{ __('pesanan_unduh_invoice') }}
          </button>
        </div>
      </div>

      <div class="pesanan-timeline-box" id="pesananTimelineBox">
        <div class="pesanan-timeline" id="pesananTimeline"></div>
        <div class="pesanan-timeline-note" id="pesananTimelineNote"></div>
      </div>

      <div class="pesanan-grid">
        <div class="pesanan-card">
          <h2>{{ __('pesanan_produk_title') }}</h2>
          <div id="pesananItemsWrap"></div>
        </div>

        <aside class="cart-summary-box">
          <h2>{{ __('pesanan_ringkasan_title') }}</h2>
          <div class="cart-summary-row">
            <span id="pesananSubtotalLabel">{{ __('pesanan_subtotal_produk') }}</span>
            <span id="pesananSubtotal">Rp0</span>
          </div>
          <div class="cart-summary-row">
            <span>{{ __('pesanan_ongkir') }}</span>
            <span id="pesananOngkir">Rp0</span>
          </div>
          <div class="cart-summary-divider"></div>
          <div class="cart-summary-total">
            <span>{{ __('pesanan_total_bayar') }}</span>
            <span id="pesananTotal">Rp0</span>
          </div>
          <a href="#" id="pesananBeliLagiBtn" class="btn btn-primary cart-checkout-btn">{{ __('riwayat_beli_lagi_btn') }}</a>
          <a href="https://wa.me/6283848835777" target="_blank" rel="noopener" id="pesananHubungiCsBtn" class="btn btn-outline cart-checkout-btn" style="margin-top:10px;">
            {{ __('pesanan_hubungi_cs') }}
          </a>
          <script>
          (function () {
            var btn = document.getElementById('pesananHubungiCsBtn');
            if (!btn) return;
            btn.addEventListener('click', function (e) {
              // Kalau widget chat di situs ini sudah aktif, buka itu; kalau tidak, tetap fallback ke WA.
              if (window.ykChatOpen) {
                e.preventDefault();
                window.ykChatOpen();
              }
            });
          })();
          </script>
          <div id="pesananCancelWrap" style="display:none; margin-top:10px;">
            <button type="button" class="riwayat-cancel-btn" id="pesananCancelBtn"></button>
          </div>
        </aside>
      </div>

      <div class="pesanan-info-grid">
        <div class="pesanan-card">
          <h3>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s-7-6.1-7-11.5A7 7 0 0112 2a7 7 0 017 7.5C19 14.9 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.4"/></svg>
            {{ __('pesanan_alamat_title') }}
          </h3>
          <div id="pesananAlamatBox"></div>
        </div>
        <div class="pesanan-card">
          <h3>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            {{ __('pesanan_info_title') }}
          </h3>
          <div id="pesananInfoBox"></div>
        </div>
      </div>

    </div>

    <div class="cart-page-empty" id="pesananNotFound" style="display:none;">
      <div class="cart-page-empty-icon">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
      </div>
      <h2>{{ __('pesanan_not_found') }}</h2>
      <a href="{{ route('riwayat') }}" class="btn btn-primary">{{ __('breadcrumb_riwayat') }}</a>
    </div>

  </div>
</section>

@endsection

@section('inline-data')
<script>
  window.__YK_PRODUCTS__ = @json($products);
  window.__YK_BUNDLES__ = @json($bundles ?? []);
  window.__YK_DB_ORDERS__ = @json($dbOrders);
  const pesananI18n = {
    lang: @json(app()->getLocale()),
    dipesanPada: @json(__('pesanan_dipesan_pada')),
    varianLabel: @json(__('pesanan_varian_label')),
    qtyLabel: @json(__('pesanan_qty_label')),
    gratis: @json(__('checkout_gratis')),
    alamatAmbil: @json(__('pesanan_alamat_ambil')),
    metodeBayar: @json(__('pesanan_metode_bayar')),
    jasaKirim: @json(__('pesanan_jasa_kirim')),
    estimasiTiba: @json(__('pesanan_estimasi_tiba')),
    jasaAmbil: @json(__('jasa_ambil_toko')),
    jasaKurir: @json(__('jasa_kurir_instan')),
    bayar: {
      cash: @json(__('bayar_cash')),
      transfer: @json(__('bayar_transfer')),
      qris: @json(__('bayar_qris')),
      ewallet: @json(__('bayar_ewallet'))
    },
    status: {
      diproses: @json(__('status_diproses')),
      dikirim: @json(__('status_dikirim')),
      selesai: @json(__('status_selesai')),
      dibatalkan: @json(__('status_dibatalkan'))
    },
    steps: {
      dibuat: @json(__('step_dibuat')),
      diproses: @json(__('step_diproses')),
      dikirim: @json(__('step_dikirim')),
      selesai: @json(__('step_selesai'))
    },
    notes: {
      diproses: @json(__('timeline_note_diproses')),
      dikirim: @json(__('timeline_note_dikirim')),
      selesai: @json(__('timeline_note_selesai')),
      dibatalkan: @json(__('timeline_note_dibatalkan'))
    },
    cancelBtn: @json(__('status_dibatalkan')),
    beliLagiBtn: @json(__('riwayat_beli_lagi_btn')),
    beriUlasanBtn: @json(__('review_beri_ulasan_btn')),
    sudahDiulas: @json(__('review_sudah_diulas'))
  };
</script>
@endsection

@push('scripts')
<script src="{{ asset('js/produk.js') }}"></script>
<script src="{{ asset('js/orders.js') }}"></script>
<script src="{{ asset('js/pesanan.js') }}"></script>
@endpush