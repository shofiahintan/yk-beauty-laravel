// ===== Cart & Wishlist — Hanya untuk pengguna login (database-backed) =====
function ykGetCsrfToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.getAttribute('content') : '';
}

function ykIsLoggedIn() {
  return typeof window.__YK_USER__ !== 'undefined' && window.__YK_USER__ !== null;
}

function ykPromptLogin(message) {
  if (typeof window.ykToast === 'function') {
    window.ykToast(message || 'Silakan login dulu untuk menambahkan ke keranjang', 'error');
  }
  const currentPath = window.location.pathname + window.location.search;
  setTimeout(() => {
    window.location.href = '/login?redirect=' + encodeURIComponent(currentPath);
  }, 1000);
}

function ykGetCart() {
  if (ykIsLoggedIn()) {
    if (!Array.isArray(window.__YK_CART__)) {
      window.__YK_CART__ = [];
    }
    return window.__YK_CART__;
  }
  return [];
}

function ykSaveCart(cart) {
  if (!ykIsLoggedIn()) return;
  window.__YK_CART__ = Array.isArray(cart) ? cart : [];
  ykUpdateCartBadge();
  fetch('/cart/sync', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': ykGetCsrfToken(),
      'Accept': 'application/json',
    },
    body: JSON.stringify({ items: window.__YK_CART__ }),
  }).catch(() => {});
}

function ykAddToCart(id, qty = 1, varian = null, ukuran = null, type = 'product') {
  if (!ykIsLoggedIn()) {
    ykPromptLogin('Silakan login dulu untuk menambahkan ke keranjang');
    return;
  }

  const cart = ykGetCart();
  const existing = cart.find(it => it.id === id && it.varian === varian && it.ukuran === ukuran && (it.type || 'product') === type);
  if (existing) {
    existing.qty = Math.min(99, existing.qty + qty);
  } else {
    cart.push({ id, qty: Math.min(99, qty), varian, ukuran, type });
  }

  ykUpdateCartBadge();
  fetch('/cart/add', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': ykGetCsrfToken(),
      'Accept': 'application/json',
    },
    body: JSON.stringify({ id, qty, varian, ukuran, type }),
  })
  .then(r => r.json())
  .then(d => {
    if (d && d.cart) {
      window.__YK_CART__ = d.cart;
      ykUpdateCartBadge();
      if (typeof window.__ykRerenderCart === 'function') window.__ykRerenderCart();
    }
  })
  .catch(() => {});
}

function ykRemoveFromCart(index) {
  if (!ykIsLoggedIn()) return;
  const cart = ykGetCart();
  const removedItem = cart[index];
  cart.splice(index, 1);
  ykUpdateCartBadge();

  fetch('/cart/remove', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': ykGetCsrfToken(),
      'Accept': 'application/json',
    },
    body: JSON.stringify({
      index: index,
      id: removedItem ? removedItem.id : null,
      varian: removedItem ? removedItem.varian : null,
      ukuran: removedItem ? removedItem.ukuran : null,
      type: removedItem ? (removedItem.type || 'product') : 'product',
    }),
  })
  .then(r => r.json())
  .then(d => {
    if (d && d.cart) {
      window.__YK_CART__ = d.cart;
      ykUpdateCartBadge();
      if (typeof window.__ykRerenderCart === 'function') window.__ykRerenderCart();
    }
  })
  .catch(() => {});
}

function ykSetCartQty(index, qty) {
  if (!ykIsLoggedIn()) return;
  const cart = ykGetCart();
  if (!cart[index]) return;
  const newQty = Math.max(1, Math.min(99, qty));
  cart[index].qty = newQty;
  const item = cart[index];
  ykUpdateCartBadge();

  fetch('/cart/set-qty', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': ykGetCsrfToken(),
      'Accept': 'application/json',
    },
    body: JSON.stringify({
      index: index,
      id: item.id,
      varian: item.varian,
      ukuran: item.ukuran,
      type: item.type || 'product',
      qty: newQty,
    }),
  })
  .then(r => r.json())
  .then(d => {
    if (d && d.cart) {
      window.__YK_CART__ = d.cart;
      ykUpdateCartBadge();
      if (typeof window.__ykRerenderCart === 'function') window.__ykRerenderCart();
    }
  })
  .catch(() => {});
}

function ykCartCount() {
  if (!ykIsLoggedIn()) return 0;
  return ykGetCart().reduce((sum, it) => sum + (parseInt(it.qty, 10) || 0), 0);
}

function ykUpdateCartBadge() {
  const el = document.getElementById('cartCount');
  if (!el) return;
  if (!ykIsLoggedIn()) {
    el.textContent = '0';
    el.style.display = 'none';
    return;
  }
  const count = ykCartCount();
  el.textContent = count;
  el.style.display = count > 0 ? 'flex' : 'none';
}

function ykGetWishlist() {
  if (ykIsLoggedIn()) {
    if (!Array.isArray(window.__YK_WISHLIST__)) {
      window.__YK_WISHLIST__ = [];
    }
    return window.__YK_WISHLIST__;
  }
  return [];
}

function ykSaveWishlist(list) {
  if (!ykIsLoggedIn()) return;
  window.__YK_WISHLIST__ = Array.isArray(list) ? list : [];
}

function ykIsWished(id) {
  if (!ykIsLoggedIn()) return false;
  return ykGetWishlist().includes(id);
}

const ykIsInWishlist = ykIsWished;

function ykToggleWishlist(id) {
  if (!ykIsLoggedIn()) {
    ykPromptLogin('Silakan login dulu untuk menambahkan ke keranjang');
    return false;
  }

  let list = ykGetWishlist();
  let nowSaved;
  if (list.includes(id)) {
    list = list.filter(x => x !== id);
    nowSaved = false;
  } else {
    list.push(id);
    nowSaved = true;
  }
  window.__YK_WISHLIST__ = list;

  fetch('/wishlist/toggle', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': ykGetCsrfToken(),
      'Accept': 'application/json',
    },
    body: JSON.stringify({ id }),
  })
  .then(r => r.json())
  .then(d => {
    if (d && d.wishlist) {
      window.__YK_WISHLIST__ = d.wishlist;
      if (typeof window.__ykRerenderWishlist === 'function') window.__ykRerenderWishlist();
    }
  })
  .catch(() => {});

  return nowSaved;
}

function ykRemoveFromWishlist(id) {
  if (!ykIsLoggedIn()) return;
  const list = ykGetWishlist().filter(x => x !== id);
  window.__YK_WISHLIST__ = list;

  fetch('/wishlist/remove', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': ykGetCsrfToken(),
      'Accept': 'application/json',
    },
    body: JSON.stringify({ id }),
  })
  .then(r => r.json())
  .then(d => {
    if (d && d.wishlist) {
      window.__YK_WISHLIST__ = d.wishlist;
      if (typeof window.__ykRerenderWishlist === 'function') window.__ykRerenderWishlist();
    }
  })
  .catch(() => {});
}

function ykCleanupLegacyStorage() {
  try {
    localStorage.removeItem('yk_cart_v1');
    localStorage.removeItem('yk_wishlist_v1');
    for (let i = localStorage.length - 1; i >= 0; i--) {
      const k = localStorage.key(i);
      if (k && (k.startsWith('yk_cart') || k.startsWith('yk_wishlist') || k === 'cart' || k === 'wishlist')) {
        localStorage.removeItem(k);
      }
    }
  } catch (e) {}
}

// Bersihkan data usang localStorage segera dan saat DOM siap
ykCleanupLegacyStorage();

document.addEventListener('DOMContentLoaded', () => {
  ykCleanupLegacyStorage();
  ykUpdateCartBadge();
});