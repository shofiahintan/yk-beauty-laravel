<header>
  <div class="nav-wrap">
    <a href="{{ route('home') }}" class="logo">
      <img src="{{ asset('assets/images/logoyk2.png') }}" alt="YK Beauty & Skincare">
    </a>
    <div class="nav-overlay" id="navOverlay"></div>
    <ul class="nav-links" id="navLinks">
      <li><a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">{{ __('nav_home') }}</a></li>
      <li><a href="{{ route('produk') }}" class="{{ request()->routeIs('produk') ? 'active' : '' }}">{{ __('nav_products') }}</a></li>
      <li><a href="{{ route('koleksi') }}" class="{{ request()->routeIs('koleksi*') ? 'active' : '' }}">{{ __('nav_collection') }}</a></li>
      <li><a href="{{ route('tentang') }}" class="{{ request()->routeIs('tentang') ? 'active' : '' }}">{{ __('nav_about') }}</a></li>
    </ul>
    <div class="nav-icons">
      <a href="{{ auth()->check() ? route('wishlist') : route('login', ['redirect' => '/wishlist']) }}" class="icon-btn" id="wishNavIcon" title="Wishlist">
        <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 10-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
      </a>
      <a href="{{ auth()->check() ? route('keranjang') : route('login', ['redirect' => '/keranjang']) }}" class="icon-btn" id="cartNavIcon" title="Keranjang">
        <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 4h2l2.4 12.4a2 2 0 002 1.6h7.7a2 2 0 002-1.6L21 8H6"/><circle cx="10" cy="21" r="1.3"/><circle cx="18" cy="21" r="1.3"/></svg>
        <span class="cart-badge" id="cartCount" @guest style="display:none;" @endguest>@auth {{ auth()->user()->cartItems()->sum('qty') }} @else 0 @endauth</span>
      </a>
      <a href="{{ auth()->check() ? route('akun') : route('login') }}" class="icon-btn" title="Akun">
        <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-6.5 8-6.5S20 16 20 20"/></svg>
      </a>
      <div class="icon-btn lang-switch" id="langSwitch" title="Bahasa / Language">
        <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a14.5 14.5 0 010 18"/><path d="M12 3a14.5 14.5 0 000 18"/></svg>
        <div class="lang-switch-menu" id="langSwitchMenu">
          <a href="{{ route('lang.switch', 'id') }}" class="{{ app()->getLocale() === 'id' ? 'active' : '' }}">{{ __('lang_id') }}</a>
          <a href="{{ route('lang.switch', 'en') }}" class="{{ app()->getLocale() === 'en' ? 'active' : '' }}">{{ __('lang_en') }}</a>
        </div>
      </div>
      <button type="button" class="nav-toggle" id="navToggle" aria-label="Buka menu">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
      </button>
    </div>
  </div>
</header>

<script>
(function () {
  var wrap = document.getElementById('langSwitch');
  if (!wrap) return;
  wrap.addEventListener('click', function (e) { e.stopPropagation(); wrap.classList.toggle('open'); });
  document.addEventListener('click', function () { wrap.classList.remove('open'); });
})();
</script>
<script>
(function () {
  var toggle = document.getElementById('navToggle');
  var links = document.getElementById('navLinks');
  var overlay = document.getElementById('navOverlay');
  if (!toggle || !links || !overlay) return;
  function closeMenu() { links.classList.remove('open'); overlay.classList.remove('open'); }
  toggle.addEventListener('click', function () { links.classList.toggle('open'); overlay.classList.toggle('open'); });
  overlay.addEventListener('click', closeMenu);
  links.querySelectorAll('a').forEach(function (a) { a.addEventListener('click', closeMenu); });
})();
</script>