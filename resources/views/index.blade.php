@extends('layouts.app')

@section('title', 'Beranda')

@section('content')

<section class="hero-section">
  <div class="hero-card">
    <div class="hero-inner" style="background-image:url('{{ asset('assets/images/landing1.jpg') }}');">
      <div class="hero-text">
        <div class="hero-badge">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.9 6.6L22 9.3l-5 4.9 1.2 7.1L12 17.9l-6.2 3.4L7 14.2 2 9.3l7.1-.7L12 2z"/></svg>
          {{ __('home_hero_badge') }}
        </div>
        <h1>{{ __('Kulit glowing adalah pilihanmu.') }}</h1>
        <p>{{ __('Temukan koleksi skincare dan kecantikan pilihan untuk kulit sehat dan bercahaya alami.') }}</p>
        <a href="{{ route('produk') }}" class="btn btn-primary">
          {{ __('BELANJA SEKARANG') }}
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </a>
      </div>
    </div>
  </div>
</section>

<section class="kategori-section">
  <div class="container">
    <div class="section-title-deco">
      <span class="eyebrow">{{ __('Jelajahi') }}</span>
      <h2>{{ __('Kategori Produk') }}</h2>
      <div class="deco-line"></div>
    </div>
    <div class="kategori-grid">
      <a href="{{ route('produk') }}?kategori=skincare" class="kategori-card">
        <img src="{{ asset('assets/images/landing2.jpg') }}" alt="Perawatan Wajah">
        <div class="kategori-label">
          <div class="kat-title">{{ __('Perawatan Wajah') }}</div>
          <div class="kat-link">{{ __('Belanja') }} <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></div>
        </div>
      </a>
      <a href="{{ route('produk') }}?kategori=body+care" class="kategori-card">
        <img src="{{ asset('assets/images/landing3.jpg') }}" alt="Perawatan Tubuh">
        <div class="kategori-label">
          <div class="kat-title">{{ __('Perawatan Tubuh') }}</div>
          <div class="kat-link">{{ __('Belanja') }} <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></div>
        </div>
      </a>
      <a href="{{ route('produk') }}?kategori=rambut" class="kategori-card">
        <img src="{{ asset('assets/images/landing4.jpg') }}" alt="Perawatan Rambut">
        <div class="kategori-label">
          <div class="kat-title">{{ __('Perawatan Rambut') }}</div>
          <div class="kat-link">{{ __('Belanja') }} <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></div>
        </div>
      </a>
      <a href="{{ route('produk') }}?kategori=makeup" class="kategori-card">
        <img src="{{ asset('assets/images/landing5.jpg') }}" alt="Make Up">
        <div class="kategori-label">
          <div class="kat-title">{{ __('Make Up') }}</div>
          <div class="kat-link">{{ __('Belanja') }} <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></div>
        </div>
      </a>
    </div>
  </div>
</section>

<section class="produk-section">
  <div class="container">
    <div class="produk-head">
      <div>
        <span class="eyebrow">{{ __('Pilihan Kami') }}</span>
        <h2>{{ __('Produk Terlaris') }}</h2>
      </div>
      <a href="{{ route('produk') }}" class="lihat-semua">
        {{ __('Lihat Semua Koleksi') }}
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
      </a>
    </div>
    <div class="produk-grid" id="produkGrid"></div>
  </div>
</section>

<section class="promo-section">
  <div class="container">
    <div class="promo-banner">
      <span class="promo-glow promo-glow-1" aria-hidden="true"></span>
      <span class="promo-glow promo-glow-2" aria-hidden="true"></span>

      <div class="promo-text">
        <div class="promo-badge">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.9 6.6L22 9.3l-5 4.9 1.2 7.1L12 17.9l-6.2 3.4L7 14.2 2 9.3l7.1-.7L12 2z"/></svg>
          {{ __('home_promo_badge') }}
        </div>
        <span class="eyebrow">{{ __('Penawaran Spesial') }}</span>
        <h2>{{ __('Paket Daily Glowing') }}</h2>
        <p>{{ __('home_promo_intro') }}</p>
        <ul class="promo-feature-list">
          <li>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            {{ __('home_promo_item_1') }}
          </li>
          <li>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            {{ __('home_promo_item_2') }}
          </li>
          <li>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            {{ __('home_promo_item_3') }}
          </li>
          <li>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            {{ __('home_promo_item_4') }}
          </li>
        </ul>
        <a href="{{ route('koleksi') }}" class="btn btn-promo">
          {{ __('LIHAT KOLEKSI →') }}
        </a>
      </div>

      <div class="promo-image">
        <div class="promo-image-frame">
          <img src="{{ asset('assets/images/produk/paket1.png') }}" alt="Paket Daily Glowing">
        </div>
      </div>
    </div>
  </div>
</section>

<section class="keunggulan-section">
  <div class="container">
    <div class="section-title-deco">
      <span class="eyebrow">{{ __('Keunggulan') }}</span>
      <h2>{{ __('Kenapa Memilih YK Beauty?') }}</h2>
      <div class="deco-line"></div>
    </div>
    <div class="keunggulan-grid">
      <div class="keunggulan-card">
        <div class="keunggulan-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2l8 3v6c0 5-3.5 8.5-8 11-4.5-2.5-8-6-8-11V5l8-3z"/></svg>
        </div>
        <h3>{{ __('100% Original & BPOM') }}</h3>
        <p>{{ __('Kualitas terjamin aman untuk kulit Anda.') }}</p>
      </div>
      <div class="keunggulan-card">
        <div class="keunggulan-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/></svg>
        </div>
        <h3>{{ __('Konsultasi Gratis') }}</h3>
        <p>{{ __('Rekomendasi produk sesuai jenis dan kondisi kulit Anda.') }}</p>
      </div>
      <div class="keunggulan-card">
        <div class="keunggulan-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="5" width="20" height="15" rx="2"/><circle cx="12" cy="12.5" r="3.2"/><path d="M7 5l1.5-2h7L17 5"/></svg>
        </div>
        <h3>{{ __('Pembayaran Aman') }}</h3>
        <p>{{ __('Transaksi terenkripsi lewat Transfer Bank, QRIS, dan E-Wallet.') }}</p>
      </div>
      <div class="keunggulan-card">
        <div class="keunggulan-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15a2 2 0 01-2 2H8l-5 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
        </div>
        <h3>{{ __('CS Responsif') }}</h3>
        <p>{{ __('Siap membantu Anda setiap hari.') }}</p>
      </div>
    </div>
  </div>
</section>

<section class="lokasi-section">
  <div class="container">
    <div class="section-title-deco">
      <span class="eyebrow">{{ __('Kunjungi Kami') }}</span>
      <h2>{{ __('Lokasi Toko') }}</h2>
      <div class="deco-line"></div>
      <p>{{ __('Mampir langsung ke toko kami untuk konsultasi produk dan coba sebelum beli.') }}</p>
    </div>

    <a href="https://www.google.com/maps/search/?api=1&query=YSCell%203%20Sonosari%20Malang" target="_blank" rel="noopener" class="lokasi-card">
      <div class="lokasi-map-wrap">
        <iframe
          src="https://maps.google.com/maps?q=YSCell%203%2C%20Sonosari%2C%20Malang&t=&z=16&ie=UTF8&iwloc=&output=embed"
          loading="lazy"
          referrerpolicy="no-referrer-when-downgrade">
        </iframe>
        <div class="lokasi-map-overlay">
          <span class="lokasi-map-badge">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
            {{ __('Buka di Google Maps') }}
          </span>
        </div>
      </div>
      <div class="lokasi-info">
        <h3>YK Beauty & Skincare</h3>
        <p class="lokasi-alamat">YSCell 3, Sonosari, Malang, Jawa Timur</p>
        <div class="lokasi-jam">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
          <span>{{ __('Senin–Sabtu, 09.00–20.00') }}</span>
        </div>
        <span class="lokasi-arrow">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </span>
      </div>
    </a>
  </div>
</section>

<section class="ulasan-section">
  <div class="container">
    <div class="ulasan-head-row">
      <span style="width:38px"></span>
      <div class="ulasan-titles">
        <span class="eyebrow">{{ __('Ulasan') }}</span>
        <h2>{{ __('Apa Kata Mereka') }}</h2>
      </div>
      <span style="width:38px"></span>
    </div>
    <div class="ulasan-marquee-wrap" id="ulasanMarquee">
      <div class="ulasan-carousel" id="ulasanCarousel">
        @include('partials.ulasan-cards')
        <div class="ulasan-track-dup" aria-hidden="true">
          @include('partials.ulasan-cards')
        </div>
      </div>
    </div>
  </div>
</section>

@endsection

@section('inline-data')
<script>
  window.__YK_PRODUCTS__ = @json($products);
</script>
@endsection

@push('scripts')
<script src="{{ asset('js/produk.js') }}"></script>
@endpush