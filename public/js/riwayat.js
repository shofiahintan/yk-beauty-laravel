document.addEventListener('DOMContentLoaded', () => {
  const listWrap = document.getElementById('riwayatList');
  const emptyState = document.getElementById('riwayatEmptyState');
  if (!listWrap) return;

  const orders = ykGetOrders();

  if (orders.length === 0) {
    listWrap.style.display = 'none';
    emptyState.style.display = 'block';
    return;
  }
  listWrap.style.display = 'flex';
  emptyState.style.display = 'none';

  listWrap.innerHTML = orders.map((order) => {
    const first = order.items[0] || {};
    const itemCount = order.items.reduce((sum, it) => sum + it.qty, 0);
    const statusLabel = riwayatI18n.status[order.status] || order.status;
    const isCancelled = order.status === 'dibatalkan';
    const isSelesai = order.status === 'selesai';

    return `
      <div class="riwayat-order-card">
        <div class="riwayat-order-top">
          <span class="riwayat-order-id">ID: #${order.id}</span>
          <span class="order-status-badge status-${order.status}">${statusLabel}</span>
          <span class="riwayat-order-date">${ykFormatTanggal(order.createdAt, riwayatI18n.lang)}</span>
        </div>
        <div class="riwayat-order-body">
          <div class="riwayat-order-img${isCancelled ? ' cancelled' : ''}">
            ${first.img ? `<img src="${first.img}" alt="${first.nama || ''}">` : ''}
          </div>
          <div class="riwayat-order-info${isCancelled ? ' cancelled' : ''}">
            <div class="riwayat-order-nama">${first.nama || ''}</div>
            <div class="riwayat-order-qty">${itemCount} ${riwayatI18n.itemWord}</div>
          </div>
          <div class="riwayat-order-side">
            <span class="riwayat-order-total-label">${riwayatI18n.totalLabel}</span>
            <span class="riwayat-order-total">${formatRupiah(order.total)}</span>
            ${isSelesai
              ? `<button type="button" class="riwayat-order-link riwayat-beli-lagi" data-id="${order.id}">${riwayatI18n.beliLagiBtn}</button>`
              : `<a href="/pesanan?id=${encodeURIComponent(order.id)}" class="riwayat-order-link${isCancelled ? ' disabled' : ''}">${riwayatI18n.detailBtn}</a>`
            }
          </div>
        </div>
      </div>
    `;
  }).join('');

  listWrap.addEventListener('click', (e) => {
    const btn = e.target.closest('.riwayat-beli-lagi');
    if (!btn) return;
    const order = ykGetOrderById(btn.dataset.id);
    if (!order) return;
    const cart = ykGetCart();
    order.items.forEach((it) => {
      const existing = cart.find(c => c.id === it.id && c.varian === it.varian && c.ukuran === it.ukuran);
      if (existing) existing.qty += it.qty;
      else cart.push({ id: it.id, qty: it.qty, varian: it.varian || null, ukuran: it.ukuran || null });
    });
    ykSaveCart(cart);
    window.location.href = '/keranjang';
  });
});
