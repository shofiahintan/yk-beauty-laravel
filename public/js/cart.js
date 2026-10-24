// ===== Cart & Wishlist — dual source: DB untuk user login, localStorage untuk tamu =====
const YK_CART_KEY = 'yk_cart_v1';
const YK_WISH_KEY = 'yk_wishlist_v1';

function ykGetCsrfToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.getAttribute('content') : '';
}

function ykIsLoggedIn() {
  return typeof window.__YK_USER__ !== 'undefined' && window.__YK_USER__ !== null;
}

function ykGetCart() {
  if (ykIsLoggedIn()) {
    if (!Array.isArray(window.__YK_CART__)) {
      window.__YK_CART__ = [];
    }
    return window.__YK_CART__;
  }
  try { return JSON.parse(localStorage.getItem(YK_CART_KEY)) || []; }
  catch (e) { return []; }
}

function ykSaveCart(cart) {
  if (ykIsLoggedIn()) {
    window.__YK_CART__ = cart;
    ykUpdateCartBadge();
    fetch('/cart/sync', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': ykGetCsrfToken(),
        'Accept': 'application/json',
      },
      body: JSON.stringify({ items: cart, merge: false }),
    }).catch(() => {});
  } else {
    localStorage.setItem(YK_CART_KEY, JSON.stringify(cart));
    ykUpdateCartBadge();
  }
}

function ykAddToCart(id, qty = 1, varian = null, ukuran = null, type = 'product') {
  const cart = ykGetCart();
  const existing = cart.find(it => it.id === id && it.varian === varian && it.ukuran === ukuran && (it.type || 'product') === type);
  if (existing) {
    existing.qty += qty;
  } else {
    cart.push({ id, qty, varian, ukuran, type });
  }

  if (ykIsLoggedIn()) {
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
  } else {
    ykSaveCart(cart);
  }
}

function ykRemoveFromCart(index) {
  const cart = ykGetCart();
  const removedItem = cart[index];
  cart.splice(index, 1);

  if (ykIsLoggedIn()) {
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
      }
    })
    .catch(() => {});
  } else {
    ykSaveCart(cart);
  }
}

function ykSetCartQty(index, qty) {
  const cart = ykGetCart();
  if (!cart[index]) return;
  const newQty = Math.max(1, qty);
  cart[index].qty = newQty;
  const item = cart[index];

  if (ykIsLoggedIn()) {
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
      }
    })
    .catch(() => {});
  } else {
    ykSaveCart(cart);
  }
}

function ykCartCount() {
  return ykGetCart().reduce((sum, it) => sum + (parseInt(it.qty, 10) || 0), 0);
}

function ykUpdateCartBadge() {
  const el = document.getElementById('cartCount');
  if (el) el.textContent = ykCartCount();
}

function ykGetWishlist() {
  if (ykIsLoggedIn()) {
    if (!Array.isArray(window.__YK_WISHLIST__)) {
      window.__YK_WISHLIST__ = [];
    }
    return window.__YK_WISHLIST__;
  }
  try { return JSON.parse(localStorage.getItem(YK_WISH_KEY)) || []; }
  catch (e) { return []; }
}

function ykSaveWishlist(list) {
  if (ykIsLoggedIn()) {
    window.__YK_WISHLIST__ = list;
    fetch('/wishlist/sync', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': ykGetCsrfToken(),
        'Accept': 'application/json',
      },
      body: JSON.stringify({ items: list }),
    }).catch(() => {});
  } else {
    localStorage.setItem(YK_WISH_KEY, JSON.stringify(list));
  }
}

function ykIsWished(id) {
  return ykGetWishlist().includes(id);
}

const ykIsInWishlist = ykIsWished;

// Return true kalau abis toggle produknya jadi tersimpan, false kalau jadi kehapus
function ykToggleWishlist(id) {
  let list = ykGetWishlist();
  let nowSaved;
  if (list.includes(id)) {
    list = list.filter(x => x !== id);
    nowSaved = false;
  } else {
    list.push(id);
    nowSaved = true;
  }

  if (ykIsLoggedIn()) {
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
      }
    })
    .catch(() => {});
    return nowSaved;
  } else {
    ykSaveWishlist(list);
    return nowSaved;
  }
}

function ykRemoveFromWishlist(id) {
  const list = ykGetWishlist().filter(x => x !== id);
  if (ykIsLoggedIn()) {
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
      }
    })
    .catch(() => {});
  } else {
    ykSaveWishlist(list);
  }
}

function ykCheckAndMergeGuestData() {
  if (!ykIsLoggedIn()) return;

  let localCart = [];
  try { localCart = JSON.parse(localStorage.getItem(YK_CART_KEY)) || []; } catch (e) {}
  let localWish = [];
  try { localWish = JSON.parse(localStorage.getItem(YK_WISH_KEY)) || []; } catch (e) {}

  if (Array.isArray(localCart) && localCart.length > 0) {
    fetch('/cart/sync', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': ykGetCsrfToken(),
        'Accept': 'application/json',
      },
      body: JSON.stringify({ items: localCart, merge: true }),
    })
    .then(r => r.json())
    .then(d => {
      if (d && d.cart) {
        window.__YK_CART__ = d.cart;
        ykUpdateCartBadge();
        if (typeof window.__ykRerenderCart === 'function') window.__ykRerenderCart();
      }
      localStorage.removeItem(YK_CART_KEY);
    })
    .catch(() => {});
  } else {
    localStorage.removeItem(YK_CART_KEY);
  }

  if (Array.isArray(localWish) && localWish.length > 0) {
    fetch('/wishlist/sync', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': ykGetCsrfToken(),
        'Accept': 'application/json',
      },
      body: JSON.stringify({ items: localWish }),
    })
    .then(r => r.json())
    .then(d => {
      if (d && d.wishlist) {
        window.__YK_WISHLIST__ = d.wishlist;
        if (typeof window.__ykRerenderWishlist === 'function') window.__ykRerenderWishlist();
      }
      localStorage.removeItem(YK_WISH_KEY);
    })
    .catch(() => {});
  } else {
    localStorage.removeItem(YK_WISH_KEY);
  }
}

document.addEventListener('DOMContentLoaded', () => {
  ykUpdateCartBadge();
  ykCheckAndMergeGuestData();
});