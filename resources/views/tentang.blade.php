@extends('layouts.app')

@section('title', __('tentang_meta_title'))

@section('content')

<section class="tentang-hero-section">
  <div class="container">
    <div class="tentang-hero-grid">
      <div class="tentang-hero-text">
        <h1>{{ __('tentang_hero_title') }}</h1>
        <p>{{ __('tentang_hero_desc1') }}</p>
        <p>{{ __('tentang_hero_desc2') }}</p>
      </div>
      <div class="tentang-hero-image">
        <img src="{{ asset('assets/images/landing1.jpg') }}" alt="YK Beauty & Skincare">
      </div>
    </div>
  </div>
</section>

<section class="nilai-section">
  <div class="container">
    <div class="section-title-deco">
      <h2>{{ __('tentang_nilai_title') }}</h2>
      <div class="deco-line"></div>
    </div>
    <div class="nilai-grid">
      <div class="nilai-card">
        <div class="nilai-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2C7 6 4 10 4 14a8 8 0 0016 0c0-4-3-8-8-12z"/></svg>
        </div>
        <h3>{{ __('val1_title') }}</h3>
        <p>{{ __('val1_desc') }}</p>
      </div>
      <div class="nilai-card">
        <div class="nilai-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="6"/><path d="M9 13.5L7 22l5-3 5 3-2-8.5"/></svg>
        </div>
        <h3>{{ __('val2_title') }}</h3>
        <p>{{ __('val2_desc') }}</p>
      </div>
      <div class="nilai-card">
        <div class="nilai-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M19 9a4 4 0 00-4-4h-1a5 5 0 00-9.8 1.5A3.5 3.5 0 003 10c0 2 1.5 3.5 3.5 3.5"/><path d="M6 13.5V17a2 2 0 002 2h6a2 2 0 002-2v-3"/><circle cx="15" cy="8.5" r="1"/></svg>
        </div>
        <h3>{{ __('val3_title') }}</h3>
        <p>{{ __('val3_desc') }}</p>
      </div>
    </div>
  </div>
</section>

<section class="kontak-section" id="kontak">
  <div class="container">
    <div class="kontak-box">
      <div class="kontak-box-text">
        <h2>{{ __('tentang_kontak_title') }}</h2>
        <p>{{ __('tentang_kontak_desc') }}</p>
      </div>
      <div class="kontak-info-card">
        <div class="kontak-info-row">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s-7-6.1-7-11.5A7 7 0 0112 2a7 7 0 017 7.5C19 14.9 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.4"/></svg>
          <div>
            <div class="label">{{ __('kontak_alamat_label') }}</div>
            <div class="value">{{ __('footer_address') }}</div>
          </div>
        </div>
        <div class="kontak-info-row">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.362 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
          <div>
            <div class="label">{{ __('kontak_telepon_label') }}</div>
            <div class="value"><a href="https://wa.me/6283848835777" target="_blank" rel="noopener">0838-4883-5777</a></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

@endsection
