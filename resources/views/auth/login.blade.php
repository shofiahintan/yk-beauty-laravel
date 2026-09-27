@extends('layouts.app')

@section('title', __('login_meta_title'))

@section('content')

<section class="auth-split-section">
  <div class="container">
    <div class="auth-split-card">

    <div class="auth-visual-panel">
      <div class="auth-visual-logo">
        <img src="{{ asset('assets/images/logoyk2-transparent.png') }}" alt="YK Beauty & Skincare">
      </div>

      <div class="auth-visual-quote">
        <p>&ldquo;{{ __('Kecantikan yang Memahami Anda.') }}&rdquo;</p>
        <span>YK Beauty &amp; Skincare</span>
      </div>
      <div class="auth-visual-deco"><i></i><i></i></div>

      <div class="auth-visual-badges">
        <div class="auth-visual-badge">
<div>
            <strong>{{ __('100% Original & BPOM') }}</strong>
            <span>{{ __('Kualitas terjamin aman untuk kulit Anda.') }}</span>
          </div>
</div>
        <div class="auth-visual-badge">
<div>
            <strong>{{ __('Pembayaran Aman') }}</strong>
            <span>{{ __('Transaksi terenkripsi lewat Transfer Bank, QRIS, dan E-Wallet.') }}</span>
          </div>
</div>
        <div class="auth-visual-badge">
<div>
            <strong>{{ __('CS Responsif') }}</strong>
            <span>{{ __('Siap membantu Anda setiap hari.') }}</span>
          </div>
</div>
      </div>
    </div>

    <div class="auth-form-panel">
      <div class="auth-card-inner">

        <div class="auth-head">
          <h1>{{ __('login_title') }}</h1>
          <p>{{ __('login_subtitle') }}</p>
        </div>

        @if ($errors->any())
          <div class="form-error-msg" style="display:flex; margin-bottom:18px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="13"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span>{{ $errors->first() }}</span>
          </div>
        @endif

        @if (session('status'))
          <div class="alert alert-success" style="display:flex; margin-bottom:18px; color:var(--maroon-800); background:#fdf2f2; padding:10px 14px; border-radius:8px; font-size:13px;">
            {{ session('status') }}
          </div>
        @endif

        <form id="loginForm" method="POST" action="{{ route('login') }}" novalidate>
          @csrf

          @if (request()->has('redirect'))
            <input type="hidden" name="redirect" value="{{ request('redirect') }}">
          @endif

          <div class="form-group" id="groupEmail">
            <label for="loginEmail">{{ __('label_email') }}</label>
            <div class="form-input-wrap">
              <input type="email" id="loginEmail" name="email" value="{{ old('email') }}" placeholder="{{ __('ph_email') }}" autocomplete="email" required autofocus>
            </div>
            @error('email')
              <div class="form-error-msg" style="display:flex;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="13"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span>{{ $message }}</span>
              </div>
            @enderror
          </div>

          <div class="form-group">
            <label for="loginPassword">{{ __('label_password') }}</label>
            <div class="form-input-wrap icon-right">
              <input type="password" id="loginPassword" name="password" placeholder="{{ __('ph_password') }}" autocomplete="current-password" required>
              <button type="button" class="toggle-password" data-target="loginPassword" aria-label="Show/hide password">
                <svg class="icon-eye" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="icon-eye-off" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="display:none;"><path d="M17.94 17.94A10.94 10.94 0 0112 19c-7 0-11-7-11-7a21.6 21.6 0 015.06-6.06M9.9 4.24A10.4 10.4 0 0112 4c7 0 11 7 11 7a21.6 21.6 0 01-2.68 3.68M14.12 14.12a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
              </button>
            </div>
            @error('password')
              <div class="form-error-msg" style="display:flex;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="13"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span>{{ $message }}</span>
              </div>
            @enderror
          </div>

          <div class="form-row-between">
            <label style="display:flex; align-items:center; gap:8px; font-size:13px; color:var(--ink-soft); cursor:pointer;">
              <input type="checkbox" name="remember" style="accent-color:var(--maroon-800);">
              {{ __('Ingat saya') }}
            </label>
            @if (Route::has('password.request'))
              <a href="{{ route('password.request') }}" class="form-link">{{ __('forgot_password_link') }}</a>
            @endif
          </div>

          <button type="submit" class="btn btn-primary auth-submit-btn">{{ __('btn_login') }}</button>
        </form>

        <div class="auth-divider">{{ __('auth_or') }}</div>

        <a href="{{ route('auth.google.redirect') }}" class="btn btn-outline btn-google" id="googleLoginBtn">
          <svg viewBox="0 0 48 48"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.5 29.3 35.5 24 35.5c-6.4 0-11.5-5.1-11.5-11.5S17.6 12.5 24 12.5c2.9 0 5.5 1.1 7.5 2.9l5.7-5.7C33.9 6.5 29.2 4.5 24 4.5 13.2 4.5 4.5 13.2 4.5 24S13.2 43.5 24 43.5c-7.6 0-14.2 4.3-17.7 10.2z"/><path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.6 15.9 18.9 12.5 24 12.5c2.9 0 5.5 1.1 7.5 2.9l5.7-5.7C33.9 6.5 29.2 4.5 24 4.5c-7.6 0-14.2 4.3-17.7 10.2z"/><path fill="#4CAF50" d="M24 43.5c5.2 0 9.9-2 13.4-5.2l-6.2-5.2c-2 1.4-4.5 2.2-7.2 2.2-5.3 0-9.7-3.4-11.3-8.1l-6.5 5C9.6 39.1 16.2 43.5 24 43.5z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.3-2.3 4.2-4.1 5.6l6.2 5.2C40.9 36 43.5 30.5 43.5 24c0-1.2-.1-2.3-.4-3.5z"/></svg>
          {{ __('btn_google_login') }}
        </a>

        <p class="auth-switch-text">{{ __('login_switch_text') }} <a href="{{ route('register') }}">{{ __('login_switch_link') }}</a></p>

      </div>
    </div>

    </div>
  </div>
</section>

@endsection

@push('scripts')
<script>
document.querySelectorAll('.toggle-password').forEach(function(btn) {
  btn.addEventListener('click', function() {
    var target = document.getElementById(btn.getAttribute('data-target'));
    if (!target) return;
    var eye = btn.querySelector('.icon-eye');
    var eyeOff = btn.querySelector('.icon-eye-off');
    if (target.type === 'password') {
      target.type = 'text';
      if (eye) eye.style.display = 'none';
      if (eyeOff) eyeOff.style.display = '';
    } else {
      target.type = 'password';
      if (eye) eye.style.display = '';
      if (eyeOff) eyeOff.style.display = 'none';
    }
  });
});
</script>
@endpush