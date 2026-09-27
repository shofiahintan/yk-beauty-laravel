@extends('layouts.app')

@section('title', __('riwayat_meta_title'))

@section('content')

<section class="page-banner align-left" style="padding-bottom:0;"></section>

<section class="akun-section">
  <div class="container">
    <div class="akun-layout">

      @include('partials.akun-sidebar', ['akun_active' => 'pesanan'])

      <div class="setting-stack">
        <h2 class="setting-page-title">{{ __('riwayat_title') }}</h2>
        <p class="riwayat-page-desc">{{ __('riwayat_desc') }}</p>

        <div id="riwayatList"></div>

        <div class="akun-riwayat-empty" id="riwayatEmptyState" style="display:none;">
          <h3>{{ __('riwayat_empty_title') }}</h3>
          <p>{{ __('riwayat_empty_desc') }}</p>
          <a href="{{ route('produk') }}" class="btn btn-primary" style="margin-top:14px;">{{ __('riwayat_empty_btn') }}</a>
        </div>
      </div>

    </div>
  </div>
</section>

@endsection

@section('inline-data')
<script>
  window.__YK_PRODUCTS__ = @json($products);
  window.__YK_BUNDLES__ = @json($bundles ?? []);
  window.__YK_DB_ORDERS__ = @json($dbOrders);
  const riwayatI18n = {
    lang: @json(app()->getLocale()),
    itemWord: @json(__('riwayat_item_word')),
    totalLabel: @json(__('riwayat_total_label')),
    detailBtn: @json(__('riwayat_detail_btn')),
    beliLagiBtn: @json(__('riwayat_beli_lagi_btn')),
    status: {
      diproses: @json(__('status_diproses')),
      dikirim: @json(__('status_dikirim')),
      selesai: @json(__('status_selesai')),
      dibatalkan: @json(__('status_dibatalkan'))
    }
  };
</script>
@endsection

@push('scripts')
<script src="{{ asset('js/produk.js') }}"></script>
<script src="{{ asset('js/orders.js') }}"></script>
<script src="{{ asset('js/riwayat.js') }}"></script>
@endpush
