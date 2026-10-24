document.addEventListener('DOMContentLoaded', () => {
  const grid = document.getElementById('wishlistGrid');
  const emptyState = document.getElementById('wishlistEmptyState');
  if (!grid || typeof allProduk === "undefined") return;

  const kategoriLabel = {
    skincare: wishI18n.catFace,
    "body care": wishI18n.catBody,
    rambut: wishI18n.catHair,
    makeup: wishI18n.catMakeup
  };

  function render() {
    window.__ykRerenderWishlist = render;
    const ids = ykGetWishlist();
    const items = ids.map(id => getProdukById(id)).filter(Boolean);

    if (items.length === 0) {
      grid.style.display = 'none';
      emptyState.style.display = 'flex';
      return;
    }
    grid.style.display = 'grid';
    emptyState.style.display = 'none';

    grid.innerHTML = '';
    items.forEach((p) => {
      const inStock = p.stok > 0;
      const card = document.createElement('div');
      card.className = 'produk-card' + (inStock ? '' : ' out-of-stock');
      card.innerHTML = `
        <a href="/produk/${encodeURIComponent(p.id)}" class="produk-img-wrap">
          <span class="stock-badge ${inStock ? 'in-stock' : 'out-stock'}">${inStock ? wishI18n.stockIn : wishI18n.stockOut}</span>
          <div class="wish-btn active" data-id="${p.id}">
            <svg viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 10-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
          </div>
          <img src="${p.img}" alt="${p.nama}">
        </a>
        <div class="produk-info">
          ${kategoriLabel[p.kategori] ? `<div class="produk-kategori-label">${kategoriLabel[p.kategori]}</div>` : ''}
          <a href="/produk/${encodeURIComponent(p.id)}" class="nama">${p.nama}</a>
          <div class="rating-row"><span class="stars">${renderStars(p.rating)}</span></div>
          <div class="harga-row">
            ${p.coret ? `<span class="harga-coret">${formatRupiah(p.coret)}</span>` : ""}
            <span class="harga">${formatRupiah(p.harga)}</span>
          </div>
          ${inStock
            ? `<button class="btn-cart" data-id="${p.id}">
                 <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 4h2l2.4 12.4a2 2 0 002 1.6h7.7a2 2 0 002-1.6L21 8H6"/><circle cx="10" cy="21" r="1.3"/><circle cx="18" cy="21" r="1.3"/></svg>
                 ${wishI18n.addToCart}
               </button>`
            : `<button type="button" class="notify-btn">${wishI18n.notify}</button>`
          }
        </div>
      `;
      grid.appendChild(card);
    });
  }

  // Kartu ke-hapus dari wishlist kalau hati-nya diklik lagi (dengar event dari toggle global di main.js)
  grid.addEventListener('click', (e) => {
    const wish = e.target.closest('.wish-btn');
    if (wish) {
      // Kasih main.js waktu buat toggle localStorage-nya duluan, baru render ulang grid
      setTimeout(render, 0);
    }
  });

  render();
});