document.addEventListener('DOMContentLoaded', () => {
  const loaded = document.getElementById('pesananLoaded');
  const notFound = document.getElementById('pesananNotFound');
  if (!loaded) return;

  const params = new URLSearchParams(window.location.search);
  const orderId = params.get('id');
  let order = orderId ? ykGetOrderById(orderId) : null;

  if (!order) {
    loaded.style.display = 'none';
    notFound.style.display = 'flex';
    return;
  }
  loaded.style.display = 'block';
  notFound.style.display = 'none';

  function render() {
    document.getElementById('pesananOrderId').textContent = 'Order #' + order.id;
    document.querySelector('.pesanan-dipesan-pada').textContent =
      `${pesananI18n.dipesanPada} ${ykFormatTanggalWaktu(order.createdAt, pesananI18n.lang)}`;

    const badge = document.getElementById('pesananStatusBadge');
    badge.textContent = pesananI18n.status[order.status] || order.status;
    badge.className = 'order-status-badge status-' + order.status;

    renderTimeline();
    renderItems();
    renderRingkasan();
    renderAlamatInfo();
    renderActions();
  }

  function renderTimeline() {
    const wrap = document.getElementById('pesananTimeline');
    const noteEl = document.getElementById('pesananTimelineNote');
    const box = document.getElementById('pesananTimelineBox');

    if (order.status === 'dibatalkan') {
      box.classList.add('cancelled');
      wrap.innerHTML = `
        <div class="timeline-step done">
          <div class="timeline-dot done"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></div>
          <span>${pesananI18n.steps.dibuat}</span>
        </div>
        <div class="timeline-line"></div>
        <div class="timeline-step cancelled-step">
          <div class="timeline-dot cancelled"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></div>
          <span>${pesananI18n.status.dibatalkan}</span>
        </div>
      `;
      noteEl.textContent = pesananI18n.notes.dibatalkan;
      return;
    }

    const order_steps = ['diproses', 'dikirim', 'selesai'];
    const currentIndex = order_steps.indexOf(order.status); // 0,1,2
    const labels = [pesananI18n.steps.dibuat, pesananI18n.steps.diproses, pesananI18n.steps.dikirim, pesananI18n.steps.selesai];
    const icons = [
      '<polyline points="20 6 9 17 4 12"/>', // dibuat: check
      '<rect x="3" y="3" width="18" height="18" rx="2"/>', // diproses: box
      '<path d="M1 3h13v10H1z"/><path d="M14 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="1.8"/><circle cx="17.5" cy="18.5" r="1.8"/>', // dikirim: truck
      '<polyline points="20 6 9 17 4 12"/><polyline points="20 12 12 20 9 17"/>' // selesai: double-check-ish (fallback simple check)
    ];

    let html = '';
    for (let i = 0; i < 4; i++) {
      const stepDone = i <= currentIndex + 1; // dibuat always done(0); step i done if i<=currentIndex+1
      const isCurrent = i === currentIndex + 1;
      html += `
        <div class="timeline-step${stepDone ? ' done' : ''}${isCurrent ? ' current' : ''}">
          <div class="timeline-dot${stepDone ? ' done' : ''}${isCurrent ? ' current' : ''}">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="${stepDone ? '#fff' : 'var(--ink-soft)'}" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">${icons[i]}</svg>
          </div>
          <span>${labels[i]}</span>
        </div>
      `;
      if (i < 3) html += `<div class="timeline-line${i <= currentIndex ? ' done' : ''}"></div>`;
    }
    wrap.innerHTML = html;
    noteEl.textContent = pesananI18n.notes[order.status] || '';
  }

  function renderItems() {
    const wrap = document.getElementById('pesananItemsWrap');
    wrap.innerHTML = order.items.map((it) => {
      const metaParts = [];
      if (it.varian) metaParts.push(`${pesananI18n.varianLabel}: ${it.varian}`);
      if (it.ukuran) metaParts.push(it.ukuran);

      let reviewHtml = '';
      if (order.status === 'selesai' && it.reviewable) {
        reviewHtml = it.reviewed
          ? `<div class="pesanan-item-reviewed-badge">${pesananI18n.sudahDiulas}</div>`
          : `<button type="button" class="pesanan-item-review-btn" data-order-item-id="${it.orderItemId}"
               data-nama="${it.nama.replace(/"/g, '&quot;')}" data-img="${it.img}"
               data-varian="${it.varian || ''}" data-ukuran="${it.ukuran || ''}">${pesananI18n.beriUlasanBtn}</button>`;
      }

      return `
        <div class="pesanan-item-row">
          <div class="pesanan-item-img"><img src="${it.img}" alt="${it.nama}"></div>
          <div class="pesanan-item-body">
            <div class="pesanan-item-nama">${it.nama}</div>
            ${metaParts.length ? `<div class="pesanan-item-meta">${metaParts.join(' · ')}</div>` : ''}
            <div class="pesanan-item-meta">${pesananI18n.qtyLabel}: ${it.qty}</div>
            ${reviewHtml}
          </div>
          <div class="pesanan-item-harga">${formatRupiah(it.harga * it.qty)}</div>
        </div>
      `;
    }).join('');

    wrap.querySelectorAll('.pesanan-item-review-btn').forEach((btn) => {
      btn.addEventListener('click', () => {
        if (typeof window.ykOpenReviewModal !== 'function') return;
        window.ykOpenReviewModal({
          orderItemId: parseInt(btn.dataset.orderItemId, 10),
          productNama: btn.dataset.nama,
          productImage: btn.dataset.img,
          varian: btn.dataset.varian || null,
          ukuran: btn.dataset.ukuran || null,
        }, {
          onSuccess: () => {
            btn.outerHTML = `<div class="pesanan-item-reviewed-badge">${pesananI18n.sudahDiulas}</div>`;
          }
        });
      });
    });
  }

  function renderRingkasan() {
    const itemCount = order.items.reduce((s, it) => s + it.qty, 0);
    document.getElementById('pesananSubtotalLabel').textContent =
      document.getElementById('pesananSubtotalLabel').textContent.replace(/\(.*\)/, '') + ` (${itemCount})`;
    document.getElementById('pesananSubtotal').textContent = formatRupiah(order.subtotal);
    document.getElementById('pesananOngkir').textContent = order.shippingCost === 0 ? pesananI18n.gratis : formatRupiah(order.shippingCost);
    document.getElementById('pesananTotal').textContent = formatRupiah(order.total);
  }

  function renderAlamatInfo() {
    const alamatBox = document.getElementById('pesananAlamatBox');
    if (order.shippingMethod === 'ambil') {
      alamatBox.innerHTML = `<p>${pesananI18n.alamatAmbil}</p>`;
    } else {
      alamatBox.innerHTML = `
        <p><strong>${order.buyer.nama || ''}</strong></p>
        <p>${order.buyer.wa || ''}</p>
        <p>${[order.buyer.alamat, order.buyer.kota, order.buyer.kodepos].filter(Boolean).join(', ')}</p>
      `;
    }

    const infoBox = document.getElementById('pesananInfoBox');
    let estimasiRow = '';
    if (order.shippingMethod === 'kurir') {
      const start = new Date(order.createdAt);
      const end = new Date(order.createdAt);
      start.setDate(start.getDate() + 3);
      end.setDate(end.getDate() + 6);
      estimasiRow = `
        <div class="pesanan-info-label">${pesananI18n.estimasiTiba}</div>
        <div class="pesanan-info-value">${ykFormatTanggal(start, pesananI18n.lang)} - ${ykFormatTanggal(end, pesananI18n.lang)}</div>
      `;
    }
    infoBox.innerHTML = `
      <div class="pesanan-info-label">${pesananI18n.metodeBayar}</div>
      <div class="pesanan-info-value">${pesananI18n.bayar[order.paymentMethod] || order.paymentMethod}</div>
      <div class="pesanan-info-label">${pesananI18n.jasaKirim}</div>
      <div class="pesanan-info-value">${order.shippingMethod === 'ambil' ? pesananI18n.jasaAmbil : pesananI18n.jasaKurir}</div>
      ${estimasiRow}
    `;
  }

  function renderActions() {
    const beliLagi = document.getElementById('pesananBeliLagiBtn');
    beliLagi.addEventListener('click', (e) => {
      e.preventDefault();
      const cart = ykGetCart();
      order.items.forEach((it) => {
        const existing = cart.find(c => c.id === it.id && c.varian === it.varian && c.ukuran === it.ukuran);
        if (existing) existing.qty += it.qty;
        else cart.push({ id: it.id, qty: it.qty, varian: it.varian || null, ukuran: it.ukuran || null });
      });
      ykSaveCart(cart);
      window.location.href = '/keranjang';
    });

    const cancelWrap = document.getElementById('pesananCancelWrap');
    const cancelBtn = document.getElementById('pesananCancelBtn');
    if (order.status === 'diproses') {
      cancelWrap.style.display = 'block';
      cancelBtn.textContent = pesananI18n.cancelBtn;
      cancelBtn.addEventListener('click', () => {
        order = ykUpdateOrderStatus(order.id, 'dibatalkan');
        render();
      });
    } else {
      cancelWrap.style.display = 'none';
    }

    document.getElementById('pesananInvoiceBtn').addEventListener('click', () => {
      window.print();
    });
  }

  render();
});
