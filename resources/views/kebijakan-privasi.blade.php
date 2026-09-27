@extends('layouts.app')

@section('title', __('privasi_meta_title'))

@section('content')

<section class="tos-section">
  <div class="container">

    <div class="tos-head">
      <h1>{{ __('privasi_title') }}</h1>
      <p>{{ __('privasi_updated') }}</p>
    </div>

    <div class="tos-card" style="max-width:900px; margin:0 auto;">

      <div class="tos-block">
        <h2>
          <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 12h6M9 16h6M9 8h6"/><rect x="4" y="3" width="16" height="18" rx="2"/></svg>
          {{ __('privasi_s1_title') }}
        </h2>
        <p>{{ __('privasi_s1_p1') }}</p>
      </div>

      <div class="tos-block">
        <h2>
          <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
          {{ __('privasi_s2_title') }}
        </h2>
        <ul>
          <li>{{ __('privasi_s2_li1') }}</li>
          <li>{{ __('privasi_s2_li2') }}</li>
          <li>{{ __('privasi_s2_li3') }}</li>
          <li>{{ __('privasi_s2_li4') }}</li>
        </ul>
      </div>

      <div class="tos-block">
        <h2>
          <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 018 0v3"/></svg>
          {{ __('privasi_s3_title') }}
        </h2>
        <p>{{ __('privasi_s3_p1') }}</p>
      </div>

      <div class="tos-block">
        <h2>
          <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="7" r="4"/><path d="M1 20c0-4 3.6-6.5 8-6.5"/><circle cx="18" cy="9" r="3"/><path d="M14 20c0-3 2-5 6-5"/></svg>
          {{ __('privasi_s4_title') }}
        </h2>
        <p>{{ __('privasi_s4_p1') }}</p>
      </div>

      <div class="tos-block">
        <h2>
          <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          {{ __('privasi_s5_title') }}
        </h2>
        <p>{{ __('privasi_s5_p1') }}</p>
      </div>

      <div class="tos-help-box">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--maroon-800)" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <div>
          <h3>{{ __('privasi_help_title') }}</h3>
          <p>{{ __('privasi_help_desc') }}</p>
          <a href="{{ route('tentang') }}#kontak" class="btn btn-outline" style="margin-top:8px;">{{ __('privasi_help_btn') }}</a>
        </div>
      </div>

    </div>

  </div>
</section>

@endsection
