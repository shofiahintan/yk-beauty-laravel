// ===== Produk unggulan di landing — diambil dari katalog asli (allProduk di produk.js) =====
// biar selalu sinkron sama data & foto yang ada di halaman Produk / Detail.
// (renderStars & formatRupiah dipakai dari js/produk.js yang di-load bareng di index.php)
function renderProdukGrid() {
  const grid = document.getElementById('produkGrid');
  if (!grid) return;
  if (typeof allProduk === "undefined") return;

  // Prioritaskan produk yang punya badge (Best Seller / Baru), ambil 3 pertama
  const berbadge = allProduk.filter(p => p.badge);
  const dipilih = (berbadge.length >= 3 ? berbadge : allProduk).slice(0, 3);

  dipilih.forEach((p) => {
    const card = document.createElement('a');
    card.href = `/produk/${encodeURIComponent(p.id)}`;
    card.className = 'produk-card';
    const wished = typeof ykIsWished === "function" && ykIsWished(p.id);
    card.innerHTML = `
      <div class="produk-img-wrap">
        ${p.badge ? `<span class="produk-badge ${p.badgeClass}">${p.badge}</span>` : ""}
        <div class="wish-btn${wished ? ' active' : ''}" data-id="${p.id}">
          <svg viewBox="0 0 24 24" fill="${wished ? 'currentColor' : 'none'}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 10-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
        </div>
        <img src="${p.img}" alt="${p.nama}">
      </div>
      <div class="produk-info">
        <div class="rating-row"><span class="stars">${renderStars(p.rating)}</span> (${p.count})</div>
        <div class="nama">${p.nama}</div>
        <div class="harga-row">
          ${p.coret ? `<span class="harga-coret">${formatRupiah(p.coret)}</span>` : ""}
          <span class="harga">${formatRupiah(p.harga)}</span>
        </div>
        <button class="btn-cart" data-id="${p.id}" onclick="event.preventDefault()">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 4h2l2.4 12.4a2 2 0 002 1.6h7.7a2 2 0 002-1.6L21 8H6"/><circle cx="10" cy="21" r="1.3"/><circle cx="18" cy="21" r="1.3"/></svg>
          Tambah ke Keranjang
        </button>
      </div>
    `;
    grid.appendChild(card);
  });
}

// ===== Wishlist toggle (event delegation, jalan di semua halaman) =====
document.addEventListener('click', (e) => {
  const wish = e.target.closest('.wish-btn');
  if (!wish) return;
  e.preventDefault();
  e.stopPropagation();
  const id = wish.dataset.id;
  if (id && typeof ykToggleWishlist === "function") {
    const nowSaved = ykToggleWishlist(id);
    wish.classList.toggle('active', nowSaved);
    const svg = wish.querySelector('svg');
    if (svg) svg.setAttribute('fill', nowSaved ? 'currentColor' : 'none');
  } else {
    // Fallback (elemen tanpa data-id, mis. item paket di koleksi-detail): visual saja
    wish.classList.toggle('active');
    const svg = wish.querySelector('svg');
    if (svg) svg.setAttribute('fill', wish.classList.contains('active') ? 'currentColor' : 'none');
  }
});

// ===== Cart logic (event delegation, jalan di semua halaman) =====
// .btn-cart dan .koleksi-add-btn -> masuk ke keranjang (database/localStorage via js/cart.js)
document.addEventListener('click', (e) => {
  const btn = e.target.closest('.btn-cart, .koleksi-add-btn');
  if (btn && !btn.disabled && !btn.classList.contains('added')) {
    const id = btn.dataset.id;
    const type = btn.dataset.type || 'product';
    if (id && typeof ykAddToCart === "function") {
      ykAddToCart(id, 1, null, null, type);
    }

    const original = btn.innerHTML;
    const addedLabel = btn.classList.contains('koleksi-add-btn') ? '✓ Paket Ditambahkan' : '✓ Ditambahkan';
    btn.classList.add('added');
    btn.innerHTML = addedLabel;
    setTimeout(() => {
      btn.classList.remove('added');
      btn.innerHTML = original;
    }, 1200);
  }
});

// Carousel ulasan sekarang murni CSS marquee (lihat .ulasan-marquee-wrap di style.css),
// jadi tidak perlu JS sama sekali — otomatis jalan terus dan pause saat di-hover.

// ===== Init =====
document.addEventListener('DOMContentLoaded', () => {
  renderProdukGrid();
});