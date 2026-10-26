@extends('layouts.app')

@section('title', __('detail_pesanan_meta_title'))

@section('content')

<section class="pesanan-section">
  <div class="container">

    @if (session('status'))
      <div style="background:#F0FDF4; border:1px solid #BBF7D0; color:#166534; padding:14px 18px; border-radius:14px; margin-bottom:20px; font-size:13.5px;">
        {{ session('status') }}
      </div>
    @endif

    @if ($errors->any())
      <div style="background:#FEF2F2; border:1px solid #FECACA; color:#991B1B; padding:14px 18px; border-radius:14px; margin-bottom:20px; font-size:13.5px;">
        <ul style="margin:0; padding-left:18px;">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

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

      {{-- Blok Status Refund --}}
      @if ($dbOrder && $dbOrder->refunds->isNotEmpty())
        @php $latestRefund = $dbOrder->refunds->first(); @endphp
        <div class="pesanan-refund-box refund-{{ $latestRefund->status }}">
          <div class="pesanan-refund-icon">
            @if ($latestRefund->status === 'diajukan')
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            @elseif ($latestRefund->status === 'disetujui')
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            @elseif ($latestRefund->status === 'ditolak')
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            @else
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
            @endif
          </div>
          <div class="pesanan-refund-content">
            @if ($latestRefund->status === 'diajukan')
              <h4>{{ __('Pengajuan Refund Diajukan') }}</h4>
              <p>{{ __('Pengajuan refund Anda sedang ditinjau oleh tim kami.') }}</p>
              <div class="pesanan-refund-meta">
                <span>{{ __('Alasan Pengajuan') }}: {{ $latestRefund->alasan_label }}</span>
              </div>
            @elseif ($latestRefund->status === 'disetujui')
              <h4>{{ __('Pengajuan Refund Disetujui') }}</h4>
              <p>{{ __('Pengajuan refund Anda telah disetujui.') }}</p>
              <div class="pesanan-refund-meta">
                <span>{{ __('Nominal Disetujui') }}: <strong>{{ formatRupiah($latestRefund->totalNominal()) }}</strong></span>
                @if ($latestRefund->nominal_ongkir > 0)
                  <span> ({{ __('Termasuk ongkir') }} {{ formatRupiah($latestRefund->nominal_ongkir) }})</span>
                @endif
              </div>
              @if ($latestRefund->catatan_owner)
                <div class="pesanan-refund-note">
                  <strong>Catatan:</strong> {{ $latestRefund->catatan_owner }}
                </div>
              @endif
            @elseif ($latestRefund->status === 'ditolak')
              <h4>{{ __('Pengajuan Refund Ditolak') }}</h4>
              <p class="pesanan-refund-note"><strong>{{ __('Alasan Penolakan') }}:</strong> {{ $latestRefund->catatan_owner }}</p>
              <p class="pesanan-refund-sub">{{ __('Untuk pertanyaan lebih lanjut, silakan hubungi Customer Service kami lewat pesan.') }}</p>
            @elseif ($latestRefund->status === 'selesai')
              <h4>{{ __('Pengembalian Dana Selesai') }}</h4>
              <p>{{ __('Uang telah dikembalikan ke rekening/metode pembayaran Anda.') }}</p>
              <div class="pesanan-refund-meta">
                <span>{{ __('Total Dikembalikan') }}: <strong>{{ formatRupiah($latestRefund->totalNominal()) }}</strong></span>
                @if ($latestRefund->referensi_pengembalian)
                  <span> (Ref: {{ $latestRefund->referensi_pengembalian }})</span>
                @endif
              </div>
            @endif
          </div>
        </div>
      @endif

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

          {{-- Tombol Ajukan Pengembalian Dana --}}
          @if ($dbOrder && $dbOrder->canApplyRefund())
            <button type="button" class="btn btn-outline cart-checkout-btn" id="openRefundBtn" style="margin-top:10px; border-color:var(--maroon-accent); color:var(--maroon-accent);">
              {{ __('Ajukan Pengembalian Dana') }}
            </button>
          @endif

          <script>
          (function () {
            var btn = document.getElementById('pesananHubungiCsBtn');
            if (!btn) return;
            btn.addEventListener('click', function (e) {
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

    {{-- MODAL AJUKAN REFUND --}}
    @if ($dbOrder && $dbOrder->canApplyRefund())
      @php
        $allowedReasons = \App\Support\RefundService::allowedReasons($dbOrder);
        $needBank = \App\Models\Refund::requiresBankAccount($dbOrder->payment_method);
      @endphp
      <div id="refundModal" class="yk-modal-overlay" style="display:none;">
        <div class="yk-modal-dialog">
          <div class="yk-modal-header">
            <h3>{{ __('Ajukan Pengembalian Dana') }}</h3>
            <button type="button" class="yk-modal-close" id="closeRefundBtn">&times;</button>
          </div>
          <form method="POST" action="{{ route('pesanan.refund', ['orderCode' => $dbOrder->order_code]) }}" enctype="multipart/form-data">
            @csrf
            <div class="yk-modal-body">
              <div class="yk-form-group">
                <label for="refundAlasan">{{ __('Alasan Pengajuan') }} *</label>
                <select name="alasan" id="refundAlasan" required class="yk-form-control">
                  @foreach ($allowedReasons as $rCode)
                    <option value="{{ $rCode }}" {{ old('alasan') === $rCode ? 'selected' : '' }}>
                      {{ __(\App\Models\Refund::ALASAN_OPTIONS[$rCode] ?? $rCode) }}
                    </option>
                  @endforeach
                </select>
              </div>

              <div class="yk-form-group">
                <label for="refundKeterangan">{{ __('Keterangan / Detail Masalah') }}</label>
                <textarea name="keterangan" id="refundKeterangan" rows="3" class="yk-form-control"
                          placeholder="Jelaskan kendala pesanan Anda...">{{ old('keterangan') }}</textarea>
              </div>

              <div class="yk-form-group" id="refundBuktiGroup" style="{{ in_array('batal_sebelum_kirim', $allowedReasons) && count($allowedReasons) === 1 ? 'display:none;' : '' }}">
                <label for="refundBukti">{{ __('Foto Bukti') }} <span id="buktiRequiredStar">*</span></label>
                <input type="file" name="bukti[]" id="refundBukti" multiple accept=".jpg,.jpeg,.png,.webp" class="yk-form-control">
                <span class="yk-form-hint">{{ __('Unggah 1 sampai 3 foto bukti (maksimal 2 MB per foto)') }}</span>
              </div>

              <div class="yk-form-group" style="margin-top:16px; border-top:1px solid var(--line); pt-3;">
                <label style="font-weight:600; font-size:13.5px; margin-bottom:8px;">
                  {{ __('Informasi Rekening Pengembalian Dana') }}
                  @if ($needBank) <span style="color:#BE123C;">*</span> @endif
                </label>

                @if (! $needBank)
                  <p class="yk-form-hint" style="margin-bottom:12px; background:var(--pink-50); padding:10px; border-radius:10px;">
                    Metode pembayaran Anda ({{ strtoupper($dbOrder->payment_method) }}) mendukung pengembalian dana langsung lewat payment gateway. Rekening bank opsional.
                  </p>
                @endif

                <div style="display:grid; grid-template-columns:1fr; gap:12px;">
                  <div>
                    <input type="text" name="rekening_bank" value="{{ old('rekening_bank') }}"
                           placeholder="{{ __('Nama Bank') }} (misal BCA, Mandiri, BRI){{ $needBank ? ' *' : '' }}"
                           {{ $needBank ? 'required' : '' }} class="yk-form-control">
                  </div>
                  <div>
                    <input type="text" name="rekening_nomor" value="{{ old('rekening_nomor') }}"
                           placeholder="{{ __('Nomor Rekening') }}{{ $needBank ? ' *' : '' }}"
                           {{ $needBank ? 'required' : '' }} class="yk-form-control">
                  </div>
                  <div>
                    <input type="text" name="rekening_nama" value="{{ old('rekening_nama') }}"
                           placeholder="{{ __('Nama Pemilik Rekening') }}{{ $needBank ? ' *' : '' }}"
                           {{ $needBank ? 'required' : '' }} class="yk-form-control">
                  </div>
                </div>
              </div>

            </div>
            <div class="yk-modal-footer">
              <button type="button" class="btn btn-outline" id="cancelRefundBtn">{{ __('Batal') }}</button>
              <button type="submit" class="btn btn-primary">{{ __('Kirim Pengajuan') }}</button>
            </div>
          </form>
        </div>
      </div>

      <script>
      (function() {
        const modal = document.getElementById('refundModal');
        const openBtn = document.getElementById('openRefundBtn');
        const closeBtn = document.getElementById('closeRefundBtn');
        const cancelBtn = document.getElementById('cancelRefundBtn');
        const alasanSelect = document.getElementById('refundAlasan');
        const buktiGroup = document.getElementById('refundBuktiGroup');
        const buktiInput = document.getElementById('refundBukti');
        const star = document.getElementById('buktiRequiredStar');

        function toggleBukti() {
          if (!alasanSelect || !buktiGroup) return;
          const isBatal = alasanSelect.value === 'batal_sebelum_kirim';
          buktiGroup.style.display = isBatal ? 'none' : 'block';
          if (buktiInput) {
            buktiInput.required = !isBatal;
          }
          if (star) {
            star.style.display = isBatal ? 'none' : 'inline';
          }
        }

        if (alasanSelect) {
          alasanSelect.addEventListener('change', toggleBukti);
          toggleBukti();
        }

        if (openBtn && modal) {
          openBtn.addEventListener('click', () => modal.style.display = 'flex');
        }
        if (closeBtn && modal) {
          closeBtn.addEventListener('click', () => modal.style.display = 'none');
        }
        if (cancelBtn && modal) {
          cancelBtn.addEventListener('click', () => modal.style.display = 'none');
        }
        if (modal) {
          modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.style.display = 'none';
          });
        }
      })();
      </script>
    @endif

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