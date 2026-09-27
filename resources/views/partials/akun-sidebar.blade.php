@php
  $activeTab = $akun_active ?? 'profil';
@endphp

<aside class="akun-sidebar">
  <h2>{{ __('akun_sidebar_title') }}</h2>
  <a href="{{ route('akun') }}" class="akun-nav-item {{ $activeTab === 'profil' ? 'active' : '' }}">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-6.5 8-6.5S20 16 20 20"/></svg>
    {{ __('akun_nav_profil') }}
  </a>
  <a href="{{ route('riwayat') }}" class="akun-nav-item {{ $activeTab === 'pesanan' ? 'active' : '' }}">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="1" y="7" width="15" height="10" rx="1.5"/><path d="M16 10h3.5l3.5 3.5V17h-7"/><circle cx="6" cy="19" r="1.6"/><circle cx="18" cy="19" r="1.6"/></svg>
    {{ __('akun_nav_pesanan') }}
  </a>
  <a href="{{ route('wishlist') }}" class="akun-nav-item {{ $activeTab === 'wishlist' ? 'active' : '' }}">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 10-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
    {{ __('akun_nav_wishlist') }}
  </a>
  <a href="{{ route('pengaturan') }}" class="akun-nav-item {{ $activeTab === 'pengaturan' ? 'active' : '' }}">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09a1.65 1.65 0 00-1-1.51 1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09a1.65 1.65 0 001.51-1 1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
    {{ __('akun_nav_pengaturan') }}
  </a>
  <div class="akun-nav-divider"></div>
  <form method="POST" action="{{ route('logout') }}" id="logoutForm">
    @csrf
    <button type="submit" class="akun-nav-item logout" style="width:100%; border:none; background:none; text-align:left; cursor:pointer; font:inherit;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
      {{ __('akun_nav_keluar') }}
    </button>
  </form>
</aside>
