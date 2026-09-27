<footer>
  <div class="container">
    <div class="footer-top">
      <div class="footer-brand">
        <h3>YK Beauty & Skincare</h3>
        <p>{{ __('Kecantikan & Perawatan Terpercaya') }}</p>
        <div class="footer-line">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s-7-6.1-7-11.5A7 7 0 0112 2a7 7 0 017 7.5C19 14.9 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.4"/></svg>
          {{ __('Kec. Pakisaji, Kabupaten Malang, Jawa Timur') }}
        </div>
        <div class="footer-line">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.362 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
          <a href="https://wa.me/6283848835777" target="_blank" rel="noopener">0838-4883-5777</a>
        </div>
      </div>
      <div class="footer-col">
        <h4>{{ __('Belanja') }}</h4>
        <a href="{{ route('produk') }}">{{ __('Semua Produk') }}</a>
        <a href="{{ route('produk') }}?kategori=skincare">{{ __('Skincare') }}</a>
        <a href="{{ route('produk') }}?kategori=makeup">{{ __('Makeup') }}</a>
        <a href="{{ route('produk') }}?kategori=body+care">{{ __('Body Care') }}</a>
      </div>
      <div class="footer-col">
        <h4>{{ __('Bantuan') }}</h4>
        <a href="#">{{ __('Cara Belanja') }}</a>
        <a href="{{ auth()->check() ? route('riwayat') : route('login') }}">{{ __('Lacak Pesanan') }}</a>
        <a href="#">{{ __('Pengiriman') }}</a>
        <a href="#">{{ __('FAQ') }}</a>
      </div>
      <div class="footer-col">
        <h4>{{ __('Perusahaan') }}</h4>
        <a href="{{ route('tentang') }}">{{ __('Tentang Kami') }}</a>
        <a href="{{ route('tentang') }}#kontak">{{ __('Hubungi Kami') }}</a>
        <a href="{{ route('blog') }}">{{ __('Blog / Artikel') }}</a>
      </div>
    </div>
    <div class="footer-bottom">
      <div>© {{ date('Y') }} YK Beauty & Skincare. {{ __('Hak cipta dilindungi.') }}</div>
      <div class="footer-bottom-links">
        <a href="{{ route('syarat') }}">{{ __('Syarat & Ketentuan') }}</a>
        <a href="{{ route('privasi') }}">{{ __('Kebijakan Privasi') }}</a>
      </div>
    </div>
  </div>
</footer>