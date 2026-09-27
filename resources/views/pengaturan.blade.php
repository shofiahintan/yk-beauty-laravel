@extends('layouts.app')

@section('title', __('setting_meta_title'))

@section('content')

<section class="page-banner align-left" style="padding-bottom:0;"></section>

<section class="akun-section">
  <div class="container">
    <div class="akun-layout">

      @include('partials.akun-sidebar', ['akun_active' => 'pengaturan'])

      <div class="setting-stack">

        <h2 class="setting-page-title">{{ __('setting_title') }}</h2>

        <div class="setting-card">
          <h3 class="setting-card-title">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 018 0v3"/></svg>
            {{ __('setting_keamanan') }}
          </h3>

          <form id="passwordForm" method="POST" action="{{ route('pengaturan.password') }}">
            @csrf
            <div class="form-group subtle" id="groupPassLama">
              <label for="passLama">{{ __('setting_pass_lama') }}</label>
              <div class="form-input-wrap icon-right underline">
                <input type="password" id="passLama" name="pass_lama" placeholder="{{ __('setting_ph_pass_lama') }}" autocomplete="current-password">
                <button type="button" class="toggle-password" data-target="passLama" aria-label="Show/hide password">
                  <svg class="icon-eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
                  <svg class="icon-eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="display:none;"><path d="M17.94 17.94A10.94 10.94 0 0112 19c-7 0-11-7-11-7a21.6 21.6 0 015.06-6.06M9.9 4.24A10.4 10.4 0 0112 4c7 0 11 7 11 7a21.6 21.6 0 01-2.68 3.68M14.12 14.12a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
              </div>
            </div>

            <div class="form-group subtle" id="groupPassBaru">
              <label for="passBaru">{{ __('setting_pass_baru') }}</label>
              <div class="form-input-wrap icon-right underline">
                <input type="password" id="passBaru" name="pass_baru" placeholder="{{ __('setting_ph_pass_baru') }}" autocomplete="new-password">
                <button type="button" class="toggle-password" data-target="passBaru" aria-label="Show/hide password">
                  <svg class="icon-eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
                  <svg class="icon-eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="display:none;"><path d="M17.94 17.94A10.94 10.94 0 0112 19c-7 0-11-7-11-7a21.6 21.6 0 015.06-6.06M9.9 4.24A10.4 10.4 0 0112 4c7 0 11 7 11 7a21.6 21.6 0 01-2.68 3.68M14.12 14.12a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
              </div>
            </div>

            <div class="form-group subtle" id="groupPassKonfirmasi" style="margin-bottom:22px;">
              <label for="passKonfirmasi">{{ __('setting_pass_konfirmasi') }}</label>
              <div class="form-input-wrap icon-right underline">
                <input type="password" id="passKonfirmasi" name="pass_konfirmasi" placeholder="{{ __('setting_ph_pass_konfirmasi') }}" autocomplete="new-password">
                <button type="button" class="toggle-password" data-target="passKonfirmasi" aria-label="Show/hide password">
                  <svg class="icon-eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
                  <svg class="icon-eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="display:none;"><path d="M17.94 17.94A10.94 10.94 0 0112 19c-7 0-11-7-11-7a21.6 21.6 0 015.06-6.06M9.9 4.24A10.4 10.4 0 0112 4c7 0 11 7 11 7a21.6 21.6 0 01-2.68 3.68M14.12 14.12a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
              </div>
            </div>

            <div class="form-error-msg" id="passFormError" style="display:none; margin-bottom:16px;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="13"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              <span id="passFormErrorText"></span>
            </div>

            <div class="akun-save-row">
              <button type="submit" class="btn btn-primary">{{ __('setting_btn_ubah_pass') }}</button>
              <span class="akun-save-toast" id="passSaveToast">{{ __('setting_pass_saved') }}</span>
            </div>
          </form>
        </div>

        <div class="setting-card">
          <h3 class="setting-card-title">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a14.5 14.5 0 010 18"/><path d="M12 3a14.5 14.5 0 000 18"/></svg>
            {{ __('setting_bahasa') }}
          </h3>
          <div class="form-group subtle" style="margin-bottom:0;">
            <label id="bahasaSelectLabelFor">{{ __('setting_pilih_bahasa') }}</label>
            <div class="setting-select-wrap" id="bahasaSelectWrap">
              <button type="button" class="setting-select-trigger" id="bahasaSelectTrigger" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="bahasaSelectLabelFor">
                <span id="bahasaSelectLabel">{{ app()->getLocale() === 'en' ? __('lang_en') : __('lang_id') }}</span>
                <svg class="setting-select-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
              </button>
              <ul class="setting-select-list" id="bahasaSelectList" role="listbox" hidden>
                <li class="setting-select-option {{ app()->getLocale() === 'id' ? 'is-selected' : '' }}" role="option" data-value="id" aria-selected="{{ app()->getLocale() === 'id' ? 'true' : 'false' }}">{{ __('lang_id') }}</li>
                <li class="setting-select-option {{ app()->getLocale() === 'en' ? 'is-selected' : '' }}" role="option" data-value="en" aria-selected="{{ app()->getLocale() === 'en' ? 'true' : 'false' }}">{{ __('lang_en') }}</li>
              </ul>
            </div>
          </div>
        </div>

        <div class="setting-card">
          <h3 class="setting-card-title">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
            {{ __('setting_notifikasi') }}
          </h3>

          <div class="setting-toggle-row">
            <div>
              <div class="setting-toggle-label">{{ __('setting_notif_promo') }}</div>
              <div class="setting-toggle-desc">{{ __('setting_notif_promo_desc') }}</div>
            </div>
            <label class="setting-switch">
              <input type="checkbox" id="notifPromo" checked>
              <span class="setting-switch-track"><span class="setting-switch-thumb"></span></span>
            </label>
          </div>

          <div class="setting-toggle-row">
            <div>
              <div class="setting-toggle-label">{{ __('setting_notif_pesanan') }}</div>
              <div class="setting-toggle-desc">{{ __('setting_notif_pesanan_desc') }}</div>
            </div>
            <label class="setting-switch">
              <input type="checkbox" id="notifPesanan" checked>
              <span class="setting-switch-track"><span class="setting-switch-thumb"></span></span>
            </label>
          </div>
        </div>

      </div>

    </div>
  </div>
</section>

@endsection

@section('inline-data')
<script>
  const settingI18n = {
    empty: @json(__('setting_pass_empty')),
    mismatch: @json(__('setting_pass_mismatch'))
  };
</script>
@endsection

@push('scripts')
<script src="{{ asset('js/pengaturan.js') }}"></script>
@endpush