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
          <div class="pesanan-refund-card-header">
            <span class="pesanan-refund-badge">
              @if ($latestRefund->status === 'diajukan')
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                {{ __('Menunggu Tinjauan') }}
              @elseif ($latestRefund->status === 'disetujui')
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                {{ __('Disetujui') }}
              @elseif ($latestRefund->status === 'ditolak')
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                {{ __('Ditolak') }}
              @else
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                {{ __('Selesai') }}
              @endif
            </span>
            <span class="pesanan-refund-date">
              @if ($latestRefund->status === 'diajukan')
                {{ __('Diajukan pada') }} {{ $latestRefund->created_at->translatedFormat('d M Y, H:i') }}
              @elseif ($latestRefund->status === 'disetujui')
                {{ __('Disetujui pada') }} {{ ($latestRefund->disetujui_at ?? $latestRefund->updated_at)->translatedFormat('d M Y, H:i') }}
              @elseif ($latestRefund->status === 'ditolak')
                {{ __('Ditolak pada') }} {{ $latestRefund->updated_at->translatedFormat('d M Y, H:i') }}
              @else
                {{ __('Selesai pada') }} {{ ($latestRefund->selesai_at ?? $latestRefund->updated_at)->translatedFormat('d M Y, H:i') }}
              @endif
            </span>
          </div>

          <div class="pesanan-refund-card-body">
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
                <p class="pesanan-refund-desc">{{ __('Pengajuan refund Anda sedang ditinjau oleh tim kami.') }}</p>
                <div class="pesanan-refund-meta">
                  <strong>{{ __('Alasan') }}:</strong> {{ $latestRefund->alasan_label }}
                </div>
                @if ($latestRefund->keterangan)
                  <p class="pesanan-refund-sub" style="margin-top:6px; font-style:italic;">
                    "{{ $latestRefund->keterangan }}"
                  </p>
                @endif
              @elseif ($latestRefund->status === 'disetujui')
                <h4>{{ __('Pengajuan Refund Disetujui') }}</h4>
                <p class="pesanan-refund-desc">{{ __('Pengajuan refund Anda telah disetujui. Dana akan segera dikembalikan oleh pemilik toko.') }}</p>

                <div class="pesanan-refund-grid">
                  <div class="pesanan-refund-grid-item">
                    <span class="pesanan-refund-grid-label">{{ __('Alasan') }}</span>
                    <span class="pesanan-refund-grid-val">{{ $latestRefund->alasan_label }}</span>
                  </div>
                  <div class="pesanan-refund-grid-item">
                    <span class="pesanan-refund-grid-label">{{ __('Nominal Barang') }}</span>
                    <span class="pesanan-refund-grid-val">{{ formatRupiah($latestRefund->nominal_barang) }}</span>
                  </div>
                  @if ($latestRefund->nominal_ongkir > 0)
                    <div class="pesanan-refund-grid-item">
                      <span class="pesanan-refund-grid-label">{{ __('Nominal Ongkir') }}</span>
                      <span class="pesanan-refund-grid-val">{{ formatRupiah($latestRefund->nominal_ongkir) }}</span>
                    </div>
                  @endif
                  <div class="pesanan-refund-grid-item">
                    <span class="pesanan-refund-grid-label">{{ __('Total Refund') }}</span>
                    <span class="pesanan-refund-grid-val is-total">{{ formatRupiah($latestRefund->totalNominal()) }}</span>
                  </div>
                </div>

                @if ($latestRefund->catatan_owner)
                  <div class="pesanan-refund-note">
                    <strong>{{ __('Catatan Toko') }}:</strong> {{ $latestRefund->catatan_owner }}
                  </div>
                @endif
              @elseif ($latestRefund->status === 'ditolak')
                <h4>{{ __('Pengajuan Refund Ditolak') }}</h4>
                <p class="pesanan-refund-desc">{{ __('Mohon maaf, pengajuan refund Anda tidak dapat disetujui.') }}</p>
                @if ($latestRefund->catatan_owner)
                  <div class="pesanan-refund-note">
                    <strong>{{ __('Alasan Penolakan') }}:</strong> {{ $latestRefund->catatan_owner }}
                  </div>
                @endif
                <p class="pesanan-refund-sub">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                  {{ __('Untuk pertanyaan lebih lanjut, silakan hubungi Customer Service kami lewat pesan.') }}
                </p>
              @elseif ($latestRefund->status === 'selesai')
                <h4>{{ __('Pengembalian Dana Selesai') }}</h4>
                <p class="pesanan-refund-desc">{{ __('Uang telah dikembalikan ke rekening/metode pembayaran Anda.') }}</p>

                <div class="pesanan-refund-grid">
                  <div class="pesanan-refund-grid-item">
                    <span class="pesanan-refund-grid-label">{{ __('Nominal Barang') }}</span>
                    <span class="pesanan-refund-grid-val">{{ formatRupiah($latestRefund->nominal_barang) }}</span>
                  </div>
                  @if ($latestRefund->nominal_ongkir > 0)
                    <div class="pesanan-refund-grid-item">
                      <span class="pesanan-refund-grid-label">{{ __('Nominal Ongkir') }}</span>
                      <span class="pesanan-refund-grid-val">{{ formatRupiah($latestRefund->nominal_ongkir) }}</span>
                    </div>
                  @endif
                  <div class="pesanan-refund-grid-item">
                    <span class="pesanan-refund-grid-label">{{ __('Total Dikembalikan') }}</span>
                    <span class="pesanan-refund-grid-val is-total">{{ formatRupiah($latestRefund->totalNominal()) }}</span>
                  </div>
                  @if ($latestRefund->referensi_pengembalian)
                    <div class="pesanan-refund-grid-item">
                      <span class="pesanan-refund-grid-label">{{ __('Referensi Transfer') }}</span>
                      <span class="pesanan-refund-grid-val" style="font-family:monospace; font-size:12.5px;">{{ $latestRefund->referensi_pengembalian }}</span>
                    </div>
                  @endif
                </div>

                @if ($latestRefund->catatan_owner)
                  <div class="pesanan-refund-note">
                    <strong>{{ __('Catatan Toko') }}:</strong> {{ $latestRefund->catatan_owner }}
                  </div>
                @endif
              @endif
            </div>
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
      <div id="refundModal" class="yk-modal-overlay" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="refundModalTitle">
        <div class="yk-modal-dialog">
          <div class="yk-modal-header">
            <div class="yk-modal-header-text">
              <h3 id="refundModalTitle">{{ __('Ajukan Pengembalian Dana') }}</h3>
              <p class="yk-modal-sub">
                {{ __('Pesanan') }} #{{ $dbOrder->order_code }} &bull; {{ __('Total Pembayaran') }}: <strong>{{ formatRupiah($dbOrder->total) }}</strong>
              </p>
            </div>
            <button type="button" class="yk-modal-close" id="closeRefundBtn" aria-label="{{ __('Tutup') }}">&times;</button>
          </div>

          <form id="refundForm" method="POST" action="{{ route('pesanan.refund', ['orderCode' => $dbOrder->order_code]) }}" enctype="multipart/form-data">
            @csrf
            <div class="yk-modal-body">

              {{-- 1. Pilihan Alasan (Radio Cards) --}}
              <div class="yk-form-group">
                <span class="yk-reason-group-label">{{ __('Alasan Pengajuan') }} <span style="color:#BE123C;">*</span></span>
                <div class="yk-reason-grid">
                  @foreach ($allowedReasons as $rCode)
                    <label class="yk-reason-card" for="alasan_{{ $rCode }}">
                      <input type="radio" name="alasan" id="alasan_{{ $rCode }}" value="{{ $rCode }}"
                             {{ old('alasan', $loop->first ? $rCode : '') === $rCode ? 'checked' : '' }}
                             required class="yk-reason-radio">
                      <div class="yk-reason-card-inner">
                        <div class="yk-reason-icon">
                          @if ($rCode === 'batal_sebelum_kirim')
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                          @elseif ($rCode === 'barang_rusak')
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                          @elseif ($rCode === 'salah_kirim')
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                          @elseif ($rCode === 'tidak_sesuai')
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                          @else
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                          @endif
                        </div>
                        <div class="yk-reason-text">
                          <span class="yk-reason-title">
                            @if ($rCode === 'batal_sebelum_kirim')
                              {{ __('Batalkan Pesanan') }}
                            @elseif ($rCode === 'barang_rusak')
                              {{ __('Barang Rusak') }}
                            @elseif ($rCode === 'salah_kirim')
                              {{ __('Salah Kirim') }}
                            @elseif ($rCode === 'tidak_sesuai')
                              {{ __('Tidak Sesuai') }}
                            @else
                              {{ __('Lainnya') }}
                            @endif
                          </span>
                          <span class="yk-reason-desc">
                            @if ($rCode === 'batal_sebelum_kirim')
                              {{ __('Pesanan belum dikirim dan ingin dibatalkan.') }}
                            @elseif ($rCode === 'barang_rusak')
                              {{ __('Produk diterima dalam kondisi rusak atau cacat fisik.') }}
                            @elseif ($rCode === 'salah_kirim')
                              {{ __('Varian, ukuran, atau produk yang dikirim tidak sesuai.') }}
                            @elseif ($rCode === 'tidak_sesuai')
                              {{ __('Produk berbeda dari deskripsi atau foto di toko.') }}
                            @else
                              {{ __('Kendala atau alasan lain terkait barang yang diterima.') }}
                            @endif
                          </span>
                        </div>
                        <div class="yk-reason-check">
                          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                      </div>
                    </label>
                  @endforeach
                </div>
                @error('alasan')
                  <span class="yk-field-error">{{ $message }}</span>
                @enderror
              </div>

              {{-- 2. Keterangan / Detail Kendala --}}
              <div class="yk-form-group">
                <div style="display:flex; justify-content:space-between; align-items:baseline; margin-bottom:6px;">
                  <label for="refundKeterangan" style="margin-bottom:0;">{{ __('Keterangan / Detail Kendala') }}</label>
                  <span class="yk-char-counter"><span id="charCount">0</span>/1000 {{ __('karakter') }}</span>
                </div>
                <textarea name="keterangan" id="refundKeterangan" rows="3" maxlength="1000" class="yk-form-control @error('keterangan') is-invalid @enderror"
                          placeholder="{{ __('Jelaskan kendala pesanan Anda secara rinci...') }}">{{ old('keterangan') }}</textarea>
                @error('keterangan')
                  <span class="yk-field-error">{{ $message }}</span>
                @enderror
              </div>

              {{-- 3. Foto Bukti (Hanya untuk Barang Bermasalah) --}}
              <div class="yk-form-group" id="refundBuktiGroup" style="{{ in_array('batal_sebelum_kirim', $allowedReasons) && count($allowedReasons) === 1 ? 'display:none;' : '' }}">
                <label>{{ __('Foto Bukti') }} <span id="buktiRequiredStar" style="color:#BE123C;">*</span></label>
                
                <div class="yk-upload-dropzone" id="uploadDropzone">
                  <input type="file" name="bukti[]" id="refundBukti" multiple accept=".jpg,.jpeg,.png,.webp" class="yk-file-hidden">
                  <div class="yk-upload-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                  </div>
                  <div class="yk-upload-title">{{ __('Pilih foto bukti dari perangkat Anda') }}</div>
                  <div class="yk-upload-hint">{{ __('Format: JPG, PNG, WebP • Maksimal 3 foto • Maks 2 MB per foto') }}</div>
                </div>

                <div id="buktiThumbnails" class="yk-thumbnails-wrap"></div>

                @error('bukti')
                  <span class="yk-field-error">{{ $message }}</span>
                @enderror
                @error('bukti.*')
                  <span class="yk-field-error">{{ $message }}</span>
                @enderror
              </div>

              {{-- 4. Rekening Tujuan / Informasi Refund Asal --}}
              @if ($needBank)
                <div class="yk-bank-box">
                  <div class="yk-bank-header">
                    <div class="yk-bank-icon">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v4M12 14v4M16 14v4"/></svg>
                    </div>
                    <div>
                      <h4 class="yk-bank-title">{{ __('Rekening Bank Tujuan Pengembalian') }} <span style="color:#BE123C;">*</span></h4>
                      <p class="yk-bank-note">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline; vertical-align:-1px; margin-right:2px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        {{ __('Informasi rekening ini dienkripsi dan hanya dapat dilihat oleh pemilik toko untuk proses transfer manual.') }}
                      </p>
                    </div>
                  </div>

                  <div class="yk-bank-fields">
                    <div class="yk-form-group">
                      <label for="rekening_bank">{{ __('Nama Bank') }} <span style="color:#BE123C;">*</span></label>
                      <input type="text" name="rekening_bank" id="rekening_bank" value="{{ old('rekening_bank') }}"
                             placeholder="{{ __('Contoh: BCA, Mandiri, BRI, BNI') }}" required class="yk-form-control @error('rekening_bank') is-invalid @enderror">
                      @error('rekening_bank') <span class="yk-field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="yk-form-group">
                      <label for="rekening_nomor">{{ __('Nomor Rekening') }} <span style="color:#BE123C;">*</span></label>
                      <input type="text" name="rekening_nomor" id="rekening_nomor" value="{{ old('rekening_nomor') }}"
                             placeholder="{{ __('Contoh: 1234567890') }}" required class="yk-form-control @error('rekening_nomor') is-invalid @enderror">
                      @error('rekening_nomor') <span class="yk-field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="yk-form-group">
                      <label for="rekening_nama">{{ __('Nama Pemilik Rekening') }} <span style="color:#BE123C;">*</span></label>
                      <input type="text" name="rekening_nama" id="rekening_nama" value="{{ old('rekening_nama') }}"
                             placeholder="{{ __('Sesuai buku tabungan / e-banking') }}" required class="yk-form-control @error('rekening_nama') is-invalid @enderror">
                      @error('rekening_nama') <span class="yk-field-error">{{ $message }}</span> @enderror
                    </div>
                  </div>
                </div>
              @else
                <div class="yk-orig-payment-box">
                  <div class="yk-orig-payment-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                  </div>
                  <div>
                    <h4 class="yk-orig-payment-title">{{ __('Dana dikembalikan ke metode pembayaran asal') }}</h4>
                    <p class="yk-orig-payment-desc">
                      {{ __('Metode pembayaran asli Anda') }} (<strong>{{ strtoupper($dbOrder->payment_method) }}</strong>) {{ __('mendukung pengembalian langsung. Anda tidak perlu memasukkan nomor rekening.') }}
                    </p>
                  </div>
                </div>
              @endif

              {{-- 5. Ringkasan Kebijakan --}}
              <div class="yk-policy-box">
                <div class="yk-policy-header">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                  <span>{{ __('Ringkasan Ketentuan Refund') }}</span>
                </div>
                <ul class="yk-policy-list">
                  <li>{{ __('Batas pengajuan maksimal 2x24 jam sejak pesanan diterima (status Selesai).') }}</li>
                  <li>{{ __('Ongkir dikembalikan jika kesalahan toko atau batal sebelum dikirim.') }}</li>
                  <li>{{ __('Setiap pengajuan ditinjau manual oleh pihak toko.') }}</li>
                </ul>
                <a href="{{ route('syarat') }}" target="_blank" rel="noopener" class="yk-policy-link">
                  {{ __('Baca Syarat & Ketentuan selengkapnya') }} &rarr;
                </a>
              </div>

            </div>

            <div class="yk-modal-footer">
              <button type="button" class="btn btn-outline" id="cancelRefundBtn">{{ __('Batal') }}</button>
              <button type="submit" class="btn btn-primary" id="submitRefundBtn">{{ __('Kirim Pengajuan') }}</button>
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
        const refundForm = document.getElementById('refundForm');
        const submitBtn = document.getElementById('submitRefundBtn');
        const ketTextarea = document.getElementById('refundKeterangan');
        const charCount = document.getElementById('charCount');
        const buktiGroup = document.getElementById('refundBuktiGroup');
        const buktiInput = document.getElementById('refundBukti');
        const buktiStar = document.getElementById('buktiRequiredStar');
        const thumbsWrap = document.getElementById('buktiThumbnails');
        const reasonRadios = document.querySelectorAll('input[name="alasan"]');

        let selectedFiles = [];

        function openModal() {
          if (!modal) return;
          modal.style.display = 'flex';
          document.body.style.overflow = 'hidden';
        }

        function closeModal() {
          if (!modal) return;
          modal.style.display = 'none';
          document.body.style.overflow = '';
        }

        if (openBtn) openBtn.addEventListener('click', openModal);
        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

        // Tutup saat klik di luar dialog
        if (modal) {
          modal.addEventListener('click', function(e) {
            if (e.target === modal) closeModal();
          });
        }

        // Tutup dengan tombol Esc
        window.addEventListener('keydown', function(e) {
          if (e.key === 'Escape' && modal && modal.style.display === 'flex') {
            closeModal();
          }
        });

        // Toggle foto bukti berdasarkan radio alasan yang dipilih
        function getSelectedReason() {
          const checked = document.querySelector('input[name="alasan"]:checked');
          return checked ? checked.value : '';
        }

        function syncBuktiRequirement() {
          if (!buktiGroup) return;
          const isBatal = getSelectedReason() === 'batal_sebelum_kirim';
          buktiGroup.style.display = isBatal ? 'none' : 'block';

          if (buktiStar) {
            buktiStar.style.display = isBatal ? 'none' : 'inline';
          }

          if (buktiInput) {
            // Wajib jika bukan batal dan belum ada file yang dipilih
            buktiInput.required = !isBatal && selectedFiles.length === 0;
          }
        }

        reasonRadios.forEach(function(radio) {
          radio.addEventListener('change', syncBuktiRequirement);
        });
        syncBuktiRequirement();

        // Karakter counter keterangan
        if (ketTextarea && charCount) {
          function updateCharCount() {
            charCount.textContent = ketTextarea.value.length;
          }
          ketTextarea.addEventListener('input', updateCharCount);
          updateCharCount();
        }

        // Pengelolaan File Bukti & Thumbnail Previews
        function syncFileInput() {
          if (!buktiInput) return;
          const dt = new DataTransfer();
          selectedFiles.forEach(file => dt.items.add(file));
          buktiInput.files = dt.files;
          syncBuktiRequirement();
        }

        function renderThumbnails() {
          if (!thumbsWrap) return;
          thumbsWrap.innerHTML = '';

          selectedFiles.forEach(function(file, index) {
            const item = document.createElement('div');
            item.className = 'yk-thumbnail-item';

            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.alt = file.name;

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'yk-thumb-remove';
            removeBtn.setAttribute('aria-label', {{ json_encode(__('Hapus foto')) }});
            removeBtn.innerHTML = '&times;';
            removeBtn.addEventListener('click', function(e) {
              e.preventDefault();
              e.stopPropagation();
              selectedFiles.splice(index, 1);
              syncFileInput();
              renderThumbnails();
            });

            item.appendChild(img);
            item.appendChild(removeBtn);
            thumbsWrap.appendChild(item);
          });
        }

        if (buktiInput) {
          buktiInput.addEventListener('change', function() {
            const files = Array.from(buktiInput.files);
            const maxFiles = 3;
            const maxSize = 2 * 1024 * 1024; // 2MB

            for (let i = 0; i < files.length; i++) {
              const file = files[i];
              if (selectedFiles.length >= maxFiles) {
                alert({{ json_encode(__('Maksimal 3 foto bukti yang dapat diunggah.')) }});
                break;
              }
              if (file.size > maxSize) {
                alert({{ json_encode(__('Ukuran foto tidak boleh melebihi 2 MB: ')) }} + file.name);
                continue;
              }
              selectedFiles.push(file);
            }

            syncFileInput();
            renderThumbnails();
          });
        }

        // Form submit loading state
        if (refundForm && submitBtn) {
          refundForm.addEventListener('submit', function() {
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
              <svg class="yk-spinner" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <circle cx="12" cy="12" r="10" stroke-opacity="0.25"/>
                <path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor"/>
              </svg>
              {{ __('Mengirim Pengajuan...') }}
            `;
          });
        }

        // Auto-open modal jika ada error validasi di sesi ini
        @if ($errors->any())
          openModal();
        @endif
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