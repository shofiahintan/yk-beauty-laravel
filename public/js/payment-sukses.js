document.addEventListener('DOMContentLoaded', () => {
  const loaded = document.getElementById('suksesLoaded');
  const notFound = document.getElementById('suksesNotFound');

  const params = new URLSearchParams(window.location.search);
  const orderId = params.get('id');
  const order = orderId ? ykGetOrderById(orderId) : null;

  if (!order) {
    loaded.style.display = 'none';
    notFound.style.display = 'flex';
    return;
  }
  loaded.style.display = 'block';
  notFound.style.display = 'none';

  document.getElementById('suksesOrderId').textContent = '#' + order.id;

  const itemsWrap = document.getElementById('suksesItemsWrap');
  itemsWrap.innerHTML = order.items.map(it => {
    const metaParts = [];
    if (it.varian) metaParts.push(it.varian);
    if (it.ukuran) metaParts.push(it.ukuran);
    const meta = metaParts.length ? metaParts.join(' · ') + ' · ' : '';
    return `
      <div class="checkout-summary-item">
        <div class="checkout-summary-item-img"><img src="${it.img}" alt="${it.nama}"></div>
        <div class="checkout-summary-item-body">
          <div class="checkout-summary-item-nama">${it.nama}</div>
          <div class="checkout-summary-item-qty">${meta}${suksesI18n.qtyLabel}: ${it.qty}</div>
        </div>
        <div class="checkout-summary-item-total">${formatRupiah(it.harga * it.qty)}</div>
      </div>
    `;
  }).join('');

  document.getElementById('suksesSubtotal').textContent = formatRupiah(order.subtotal);
  document.getElementById('suksesShipping').textContent = order.shippingCost === 0 ? suksesI18n.gratis : formatRupiah(order.shippingCost);
  document.getElementById('suksesTotal').textContent = formatRupiah(order.total);

  const estimasiEl = document.getElementById('suksesEstimasi');
  if (order.shippingMethod === 'kurir') {
    const start = new Date(order.createdAt);
    const end = new Date(order.createdAt);
    start.setDate(start.getDate() + 3);
    end.setDate(end.getDate() + 6);
    estimasiEl.textContent = `${suksesI18n.estimasiKurirPrefix} ${ykFormatTanggal(start, suksesI18n.lang)} - ${ykFormatTanggal(end, suksesI18n.lang)}.`;
  } else {
    estimasiEl.textContent = suksesI18n.estimasiAmbil;
  }

  document.getElementById('suksesLihatPesanan').href = '/pesanan?id=' + encodeURIComponent(order.id);
});
