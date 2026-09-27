document.addEventListener('DOMContentLoaded', () => {
  const loadedBlock = document.getElementById('checkoutLoaded');
  const emptyBlock = document.getElementById('checkoutEmptyState');
  const form = document.getElementById('checkoutForm');
  if (!form || typeof allProduk === 'undefined') return;

  const cart = ykGetCart();
  if (cart.length === 0) {
    loadedBlock.style.display = 'none';
    emptyBlock.style.display = 'flex';
    return;
  }
  loadedBlock.style.display = 'block';
  emptyBlock.style.display = 'none';

  const SHIPPING_COST = window.__YK_SHIPPING_FLAT__ || 15000;
  const itemsWrap = document.getElementById('checkoutItemsWrap');
  let qrisInterval = null;
  let ewalletInterval = null;

  // ---- Prefill nama/WA dari profil kalau sudah pernah disimpan ----
  if (userProfile.nama) document.getElementById('ckNama').value = userProfile.nama;
  if (userProfile.wa) document.getElementById('ckWa').value = userProfile.wa;

  // ---- Bangun line-items dari keranjang ----
  let hasError = false;
  function buildLineItems() {
    const lines = [];
    let subtotal = 0;
    cart.forEach((item) => {
      const p = getProdukById(item.id, item.type || 'product');
      if (!p) return;

      let errorMsg = '';
      if (item.qty > p.stok) {
        if (p.stok === 0) {
          errorMsg = `<div style="color:#d32f2f; font-size:11.5px; margin-top:4px;">Stok habis</div>`;
          hasError = true;
        } else {
          item.qty = p.stok; // limit qty in memory for checkout calculation
          errorMsg = `<div style="color:#d32f2f; font-size:11.5px; margin-top:4px;">Maksimal ${p.stok} unit</div>`;
        }
      }

      const lineTotal = p.harga * item.qty;
      subtotal += lineTotal;
      lines.push({
        id: p.id, nama: p.nama, img: p.img, harga: p.harga, qty: item.qty,
        varian: item.varian, ukuran: item.ukuran, type: item.type || 'product', lineTotal, errorMsg
      });
    });
    return { lines, subtotal };
  }

  const { lines, subtotal } = buildLineItems();

  itemsWrap.innerHTML = lines.map(l => `
    <div class="checkout-summary-item">
      <div class="checkout-summary-item-img"><img src="${l.img}" alt="${l.nama}"></div>
      <div class="checkout-summary-item-body">
        <div class="checkout-summary-item-nama">${l.nama}</div>
        <div class="checkout-summary-item-qty">${l.qty} x ${formatRupiah(l.harga)}</div>
        ${l.errorMsg}
      </div>
      <div class="checkout-summary-item-total">${formatRupiah(l.lineTotal)}</div>
    </div>
  `).join('');

  const submitBtn = form.querySelector('button[type="submit"]');
  if (submitBtn && hasError) {
    submitBtn.disabled = true;
    submitBtn.textContent = 'Harap perbaiki item di keranjang';
  }

  function updateTotals() {
    const shippingCost = SHIPPING_COST;
    const total = subtotal + shippingCost;
    document.getElementById('ckSubtotal').textContent = formatRupiah(subtotal);
    document.getElementById('ckShipping').textContent = formatRupiah(shippingCost);
    document.getElementById('ckTotal').textContent = formatRupiah(total);
    return { shippingCost, total };
  }

  updateTotals();

  // ---- Submit: validasi, buat order, kosongkan keranjang, arahkan ke halaman sukses ----
  function showFieldError(input, errEl, message) {
    const show = !!message;
    input.style.borderColor = show ? '#C23636' : '';
    if (errEl) {
      errEl.textContent = message || '';
      errEl.style.display = show ? 'block' : 'none';
    }
  }

  form.addEventListener('submit', (e) => {
    e.preventDefault();

    const nama = document.getElementById('ckNama');
    const wa = document.getElementById('ckWa');
    const alamatEl = document.getElementById('ckAlamat');
    const kotaEl = document.getElementById('ckKota');
    const kodeposEl = document.getElementById('ckKodePos');
    let valid = true;

    showFieldError(nama, document.getElementById('errCkNama'), null);
    showFieldError(wa, document.getElementById('errCkWa'), null);
    if (!nama.value.trim()) { showFieldError(nama, document.getElementById('errCkNama'), checkoutI18n.errNama); valid = false; }
    if (!wa.value.trim()) { showFieldError(wa, document.getElementById('errCkWa'), checkoutI18n.errWa); valid = false; }

    showFieldError(alamatEl, document.getElementById('errCkAlamat'), null);
    showFieldError(kotaEl, document.getElementById('errCkKota'), null);
    showFieldError(kodeposEl, document.getElementById('errCkKodepos'), null);
    if (!alamatEl.value.trim()) { showFieldError(alamatEl, document.getElementById('errCkAlamat'), checkoutI18n.errAlamat); valid = false; }
    if (!kotaEl.value.trim()) { showFieldError(kotaEl, document.getElementById('errCkKota'), checkoutI18n.errKota); valid = false; }
    if (!kodeposEl.value.trim()) { showFieldError(kodeposEl, document.getElementById('errCkKodepos'), checkoutI18n.errKodepos); valid = false; }

    if (!valid) {
      form.scrollIntoView({ behavior: 'smooth', block: 'start' });
      return;
    }

    const { shippingCost, total } = updateTotals();

    const payload = {
      items: lines.map(l => ({ id: l.id, type: l.type, nama: l.nama, img: l.img, harga: l.harga, qty: l.qty, varian: l.varian, ukuran: l.ukuran })),
      subtotal, shippingCost, total,
      shipping: 'kurir',
      buyer: {
        nama: nama.value.trim(),
        wa: wa.value.trim(),
        alamat: alamatEl.value.trim(),
        kota: kotaEl.value.trim(),
        kodepos: kodeposEl.value.trim()
      }
    };

    const submitBtn = form.querySelector('.cart-checkout-btn');
    if (submitBtn) { submitBtn.disabled = true; submitBtn.style.opacity = '0.6'; }

    const token = document.querySelector('meta[name="csrf-token"]');

    fetch('/checkout', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token ? token.content : '',
        'Accept': 'application/json',
      },
      body: JSON.stringify(payload),
    })
      .then((res) => {
        if (!res.ok) {
          // Tampilkan semua pesan error validasi dari server (stok, produk tidak ditemukan, varian salah, dll)
          return res.json().catch(() => ({})).then((d) => {
            const err = new Error('checkout_failed');
            if (d && d.errors) {
              // Kumpulkan semua pesan error dari semua field
              const messages = [];
              for (const key in d.errors) {
                if (Array.isArray(d.errors[key])) messages.push(...d.errors[key]);
                else messages.push(d.errors[key]);
              }
              err.serverMessage = messages.join('\n');
            } else if (d && d.message) {
              err.serverMessage = d.message;
            }
            throw err;
          });
        }
        return res.json();
      })
             .then((data) => {
        // Pesanan sudah tersimpan beneran di database lewat CheckoutController@store.
        ykSaveCart([]);

        if (data.snap_token && window.snap) {
          if (submitBtn) { submitBtn.disabled = false; submitBtn.style.opacity = ''; }
          window.snap.pay(data.snap_token, {
            onSuccess: function () {
              window.location.href = data.redirect;
            },
            onPending: function () {
              window.location.href = data.redirect;
            },
            onError: function () {
              alert('Pembayaran gagal. Silakan coba lagi dari Riwayat Pesanan.');
              window.location.href = '/riwayat-pesanan';
            },
            onClose: function () {
              window.location.href = '/riwayat-pesanan';
            }
          });
        } else {
          window.location.href = data.redirect || '/payment-sukses?id=' + encodeURIComponent(data.order_id);
        }
      })
      .catch((err) => {
        if (submitBtn) { submitBtn.disabled = false; submitBtn.style.opacity = ''; }
        alert((err && err.serverMessage) || checkoutI18n.errGeneric || 'Gagal memproses pesanan. Silakan coba lagi.');
      });
  });
});