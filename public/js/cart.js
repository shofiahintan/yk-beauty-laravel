// ===== Cart & Wishlist — sumber data tunggal (localStorage), dipakai di semua halaman =====
const YK_CART_KEY = 'yk_cart_v1';
const YK_WISH_KEY = 'yk_wishlist_v1';

function ykGetCart() {
  try { return JSON.parse(localStorage.getItem(YK_CART_KEY)) || []; }
  catch (e) { return []; }
}
function ykSaveCart(cart) {
  localStorage.setItem(YK_CART_KEY, JSON.stringify(cart));
  ykUpdateCartBadge();
}
function ykAddToCart(id, qty = 1, varian = null, ukuran = null, type = 'product') {
  const cart = ykGetCart();
  const existing = cart.find(it => it.id === id && it.varian === varian && it.ukuran === ukuran && (it.type || 'product') === type);
  if (existing) existing.qty += qty;
  else cart.push({ id, qty, varian, ukuran, type });
  ykSaveCart(cart);
}
function ykRemoveFromCart(index) {
  const cart = ykGetCart();
  cart.splice(index, 1);
  ykSaveCart(cart);
}
function ykSetCartQty(index, qty) {
  const cart = ykGetCart();
  if (!cart[index]) return;
  cart[index].qty = Math.max(1, qty);
  ykSaveCart(cart);
}
function ykCartCount() {
  return ykGetCart().reduce((sum, it) => sum + it.qty, 0);
}
function ykUpdateCartBadge() {
  const el = document.getElementById('cartCount');
  if (el) el.textContent = ykCartCount();
}

function ykGetWishlist() {
  try { return JSON.parse(localStorage.getItem(YK_WISH_KEY)) || []; }
  catch (e) { return []; }
}
function ykSaveWishlist(list) {
  localStorage.setItem(YK_WISH_KEY, JSON.stringify(list));
}
function ykIsWished(id) {
  return ykGetWishlist().includes(id);
}
// Return true kalau abis toggle produknya jadi tersimpan, false kalau jadi kehapus
function ykToggleWishlist(id) {
  let list = ykGetWishlist();
  let nowSaved;
  if (list.includes(id)) { list = list.filter(x => x !== id); nowSaved = false; }
  else { list.push(id); nowSaved = true; }
  ykSaveWishlist(list);
  return nowSaved;
}
function ykRemoveFromWishlist(id) {
  ykSaveWishlist(ykGetWishlist().filter(x => x !== id));
}

document.addEventListener('DOMContentLoaded', ykUpdateCartBadge);