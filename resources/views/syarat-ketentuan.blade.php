@extends('layouts.app')

@section('title', __('syarat_meta_title'))

@section('content')

<section class="tos-section">
  <div class="container">

    <div class="tos-head">
      <h1>{{ __('syarat_title') }}</h1>
      <p>{{ __('syarat_updated') }}</p>
    </div>

    <div class="tos-layout">

      <aside class="tos-toc">
        <div class="tos-toc-label">{{ mb_strtoupper(__('syarat_toc_title')) }}</div>
        <a href="#s1">1. {{ __('syarat_s1_title') }}</a>
        <a href="#s2">2. {{ __('syarat_s2_title') }}</a>
        <a href="#s3">3. {{ __('syarat_s3_title') }}</a>
        <a href="#s4">4. {{ __('syarat_s4_title') }}</a>
        <a href="#s5">5. {{ __('syarat_s5_title') }}</a>
      </aside>

      <div class="tos-card">

        <div class="tos-block" id="s1">
          <h2>
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 12h6M9 16h6M9 8h6"/><rect x="4" y="3" width="16" height="18" rx="2"/></svg>
            {{ __('syarat_s1_title') }}
          </h2>
          <p>{{ __('syarat_s1_p1') }}</p>
          <p>{{ __('syarat_s1_p2') }}</p>
        </div>

        <div class="tos-block" id="s2">
          <h2>
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-6.5 8-6.5S20 16 20 20"/></svg>
            {{ __('syarat_s2_title') }}
          </h2>
          <p>{{ __('syarat_s2_p1') }}</p>
          <ul>
            <li>{{ __('syarat_s2_li1') }}</li>
            <li>{{ __('syarat_s2_li2') }}</li>
            <li>{{ __('syarat_s2_li3') }}</li>
          </ul>
        </div>

        <div class="tos-block" id="s3">
          <h2>
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
            {{ __('syarat_s3_title') }}
          </h2>
          <p>{{ __('syarat_s3_p1') }}</p>
          <p>{{ __('syarat_s3_p2') }}</p>
        </div>

        <div class="tos-block" id="s4">
          <h2>
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
            {{ __('syarat_s4_title') }}
          </h2>
          <p>{{ __('syarat_s4_p1') }}</p>
          <p>{{ __('syarat_s4_p2') }}</p>
        </div>

        <div class="tos-block" id="s5">
          <h2>
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/></svg>
            {{ __('syarat_s5_title') }}
          </h2>
          <p>{{ __('syarat_s5_p1') }}</p>
          <p>{{ __('syarat_s5_p2') }}</p>
        </div>

        <div class="tos-help-box">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--maroon-800)" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
          <div>
            <h3>{{ __('syarat_help_title') }}</h3>
            <p>{{ __('syarat_help_desc') }}</p>
            <a href="{{ route('tentang') }}#kontak" class="btn btn-outline" style="margin-top:8px;">{{ __('syarat_help_btn') }}</a>
          </div>
        </div>

      </div>

    </div>

  </div>
</section>

@endsection
