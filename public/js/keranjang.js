document.addEventListener('DOMContentLoaded', () => {
  const wrap = document.getElementById('cartItemsWrap');
  const loadedBlock = document.getElementById('cartLoaded');
  const emptyBlock = document.getElementById('cartEmptyState');
  if (!wrap || typeof allProduk === "undefined") return;

  function render() {
    window.__ykRerenderCart = render;
    const cart = ykGetCart();

    if (cart.length === 0) {
      loadedBlock.style.display = 'none';
      emptyBlock.style.display = 'flex';
      return;
    }
    loadedBlock.style.display = 'block';
    emptyBlock.style.display = 'none';

    wrap.innerHTML = '';
    let subtotal = 0;
    let itemCount = 0;
    let hasError = false;

    cart.forEach((item, index) => {
      const p = getProdukById(item.id, item.type || 'product');
      if (!p) return; // produk mungkin sudah tidak ada di katalog

      let errorMsg = '';
      if (item.qty > p.stok) {
        if (p.stok === 0) {
          errorMsg = `<div style="color:#d32f2f; font-size:11.5px; margin-top:4px;">Stok habis</div>`;
          hasError = true;
        } else {
          // Batasi otomatis jika melebih stok
          item.qty = p.stok;
          ykSetCartQty(index, p.stok);
          errorMsg = `<div style="color:#d32f2f; font-size:11.5px; margin-top:4px;">Maksimal ${p.stok} unit</div>`;
        }
      }

      const lineTotal = p.harga * item.qty;
      subtotal += lineTotal;
      itemCount += item.qty;

      const metaParts = [];
      if (item.varian) metaParts.push(item.varian);
      if (item.ukuran) metaParts.push(item.ukuran);

      const card = document.createElement('div');
      card.className = 'cart-item-card';
      card.innerHTML = `
        <div class="cart-item-img"><img src="${p.img}" alt="${p.nama}"></div>
        <div class="cart-item-body">
          <div class="cart-item-top">
            <a href="/produk/${encodeURIComponent(p.id)}" class="cart-item-nama">${p.nama}</a>
            <span class="cart-item-harga">${formatRupiah(lineTotal)}</span>
          </div>
          ${metaParts.length ? `<div class="cart-item-meta">${metaParts.join(' · ')}</div>` : '<div class="cart-item-meta"></div>'}
          ${errorMsg}
          <div class="cart-item-bottom">
            <div class="qty-stepper">
              <button type="button" class="cart-qty-minus" data-index="${index}">−</button>
              <span>${item.qty}</span>
              <button type="button" class="cart-qty-plus" data-index="${index}" ${item.qty >= p.stok ? 'disabled' : ''}>+</button>
            </div>
            <button type="button" class="cart-remove-btn" data-index="${index}">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7h16M9 7V5a2 2 0 012-2h2a2 2 0 012 2v2m2 0l-1 13a2 2 0 01-2 2H8a2 2 0 01-2-2L5 7h14z"/></svg>
              ${cartI18n.remove}
            </button>
          </div>
        </div>
      `;
      wrap.appendChild(card);
    });

    document.getElementById('cartItemCount').textContent = itemCount;
    document.getElementById('cartSubtotal').textContent = formatRupiah(subtotal);
    document.getElementById('cartTotal').textContent = formatRupiah(subtotal);

    const checkoutBtn = document.getElementById('cartCheckoutBtn');
    if (checkoutBtn) {
      checkoutBtn.disabled = hasError;
    }
  }

  wrap.addEventListener('click', (e) => {
    const minus = e.target.closest('.cart-qty-minus');
    const plus = e.target.closest('.cart-qty-plus');
    const remove = e.target.closest('.cart-remove-btn');
    const cart = ykGetCart();

    if (minus) {
      const i = parseInt(minus.dataset.index, 10);
      if (cart[i] && cart[i].qty > 1) ykSetCartQty(i, cart[i].qty - 1);
      else if (cart[i]) ykRemoveFromCart(i);
      render();
    } else if (plus) {
      const i = parseInt(plus.dataset.index, 10);
      if (cart[i]) ykSetCartQty(i, cart[i].qty + 1);
      render();
    } else if (remove) {
      const i = parseInt(remove.dataset.index, 10);
      ykRemoveFromCart(i);
      render();
    }
  });

  const checkoutBtn = document.getElementById('cartCheckoutBtn');
  if (checkoutBtn) {
    checkoutBtn.addEventListener('click', () => {
      window.location.href = 'checkout.php';
    });
  }

  render();
});