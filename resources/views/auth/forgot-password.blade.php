@extends('layouts.app')

@section('title', __('forgot_meta_title'))

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

        @if (session('status'))
          <div class="auth-success-box" id="forgotSuccessBox" style="display:block;">
            <div class="auth-success-icon">
              <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"/><path d="M4 4l8 8 8-8"/></svg>
            </div>
            <h1 style="font-size:24px; margin-bottom:10px;">{{ __('forgot_success_title') }}</h1>
            <p style="font-size:13.5px; color:var(--ink-soft); line-height:1.6; margin-bottom:20px;">
              {{ session('status') }}
            </p>
            <a href="{{ route('login') }}" class="btn btn-primary" style="display:inline-flex; width:auto; padding:11px 24px;">{{ __('back_to_login') }}</a>
          </div>
        @else
          <div id="forgotFormWrap">
            <div class="auth-head">
              <h1>{{ __('forgot_title') }}</h1>
              <p>{{ __('forgot_subtitle') }}</p>
            </div>

            @if ($errors->any())
              <div class="form-error-msg" style="display:flex; margin-bottom:18px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="13"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span>{{ $errors->first() }}</span>
              </div>
            @endif

            <form id="forgotForm" method="POST" action="{{ route('password.email') }}" novalidate>
              @csrf

              <div class="form-group" id="groupEmail">
                <label for="forgotEmail">{{ __('label_email_address') }}</label>
                <div class="form-input-wrap icon-left">
                  <span class="field-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16v16H4z" fill="none"/><path d="M4 4h16v16H4V4z"/><path d="M4 4l8 8 8-8"/></svg>
                  </span>
                  <input type="email" id="forgotEmail" name="email" value="{{ old('email') }}" placeholder="you@example.com" autocomplete="email" required autofocus>
                </div>
              </div>

              <button type="submit" class="btn btn-primary auth-submit-btn">
                {{ __('btn_send_reset') }}
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
              </button>
            </form>

            <a href="{{ route('login') }}" class="auth-back-link">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
              {{ __('back_to_login') }}
            </a>
          </div>
        @endif

      </div>
    </div>

    </div>
  </div>
</section>

@endsection