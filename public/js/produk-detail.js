document.addEventListener('DOMContentLoaded', () => {
  // Slug produk sekarang ada di path URL: /produk/{slug} (bukan lagi ?id=...)
  const pathParts = window.location.pathname.split('/').filter(Boolean);
  const id = pathParts.length ? decodeURIComponent(pathParts[pathParts.length - 1]) : null;
  const produk = id ? getProdukById(id) : null;

  const gridEl = document.getElementById('produkDetailGrid');
  const notFoundEl = document.getElementById('produkNotFound');

  if (!produk) {
    notFoundEl.style.display = 'block';
    return;
  }

  gridEl.style.display = 'grid';
  document.title = `${produk.nama} — YK Beauty & Skincare`;

  const mainImg = document.getElementById('detailMainImg');
  const thumbsWrap = document.getElementById('detailThumbs');
  const punyaVarianGaleri = !!(produk.varianGaleri && produk.varian && produk.varian.length);

  // Render thumbnail biasa (galeri foto dari sudut/angle yang sama, TANPA varian)
  function renderGaleriPolos(fotoList) {
    mainImg.src = fotoList[0];
    thumbsWrap.innerHTML = '';
    if (fotoList.length > 1) {
      thumbsWrap.style.display = 'flex';
      fotoList.forEach((src, i) => {
        const thumb = document.createElement('div');
        thumb.className = 'produk-detail-thumb' + (i === 0 ? ' active' : '');
        thumb.innerHTML = `<img src="${src}" alt="${produk.nama} - foto ${i + 1}">`;
        thumb.addEventListener('click', () => {
          mainImg.src = src;
          thumbsWrap.querySelectorAll('.produk-detail-thumb').forEach(t => t.classList.remove('active'));
          thumb.classList.add('active');
        });
        thumbsWrap.appendChild(thumb);
      });
    } else {
      thumbsWrap.style.display = 'none';
    }
  }

  // Render thumbnail berbasis VARIAN: satu thumbnail = satu jenis/varian produk.
  // Klik thumbnail otomatis pindah varian juga (sinkron dengan tombol "Pilih Jenis").
  function renderGaleriVarian(varianAktif) {
    mainImg.src = produk.varianGaleri[varianAktif][0];
    thumbsWrap.innerHTML = '';
    thumbsWrap.style.display = 'flex';
    produk.varian.forEach((v) => {
      if (!produk.varianGaleri[v]) return;
      const thumb = document.createElement('div');
      thumb.className = 'produk-detail-thumb' + (v === varianAktif ? ' active' : '');
      thumb.innerHTML = `<img src="${produk.varianGaleri[v][0]}" alt="${produk.nama} - ${v}">`;
      thumb.addEventListener('click', () => selectVarian(v));
      thumbsWrap.appendChild(thumb);
    });
  }

  // Pindah varian aktif: update foto utama, thumbnail, dan tombol "Pilih Jenis" sekaligus
  function selectVarian(v) {
    renderGaleriVarian(v);
    const varianOptions = document.getElementById('detailVarianOptions');
    if (varianOptions) {
      varianOptions.querySelectorAll('.produk-detail-option-btn').forEach(b => {
        b.classList.toggle('active', b.textContent === v);
      });
    }
  }

  // Galeri awal
  const varianAwal = produk.varianDefault || (produk.varian ? produk.varian[0] : null);
  if (punyaVarianGaleri && varianAwal && produk.varianGaleri[varianAwal]) {
    renderGaleriVarian(varianAwal);
  } else {
    renderGaleriPolos((produk.galeri && produk.galeri.length) ? produk.galeri : [produk.img]);
  }

  // Badge
  const badgeEl = document.getElementById('detailBadge');
  const badgeInlineEl = document.getElementById('detailBadgeInline');
  if (produk.badge) {
    badgeEl.textContent = produk.badge;
    badgeEl.style.display = 'inline-block';
    badgeInlineEl.textContent = produk.badge;
    badgeInlineEl.style.display = 'inline-block';
  }

  document.getElementById('detailNama').textContent = produk.nama;
  document.getElementById('detailStars').textContent = '★'.repeat(produk.rating) + '☆'.repeat(5 - produk.rating);
  document.getElementById('detailHarga').textContent = formatRupiah(produk.harga);

  const hargaCoretEl = document.getElementById('detailHargaCoret');
  if (produk.coret) {
    hargaCoretEl.textContent = formatRupiah(produk.coret);
    hargaCoretEl.style.display = 'inline';
  }

  const deskripsiEl = document.getElementById('detailDeskripsi');
  if (produk.deskripsi) {
    deskripsiEl.textContent = produk.deskripsi;
    deskripsiEl.style.display = 'block';
  }

  // Varian ("Pilih Jenis") — klik varian ganti foto kalau produk punya varianGaleri
  let varianAktif = produk.varian ? (produk.varianDefault || produk.varian[0]) : null;
  if (produk.varian && produk.varian.length) {
    const varianBlock = document.getElementById('detailVarianBlock');
    const varianOptions = document.getElementById('detailVarianOptions');
    varianBlock.style.display = 'block';
    produk.varian.forEach((v) => {
      const isDefault = v === varianAktif;
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'produk-detail-option-btn' + (isDefault ? ' active' : '');
      btn.textContent = v;
      btn.addEventListener('click', () => {
        varianOptions.querySelectorAll('.produk-detail-option-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        varianAktif = v;
        if (punyaVarianGaleri && produk.varianGaleri[v]) {
          renderGaleriVarian(v);
        }
      });
      varianOptions.appendChild(btn);
    });
  }

  // Ukuran
  let ukuranAktif = produk.ukuran ? produk.ukuran[0] : null;
  if (produk.ukuran && produk.ukuran.length) {
    const ukuranBlock = document.getElementById('detailUkuranBlock');
    const ukuranOptions = document.getElementById('detailUkuranOptions');
    ukuranBlock.style.display = 'block';
    produk.ukuran.forEach((u, i) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'produk-detail-option-btn' + (i === 0 ? ' active' : '');
      btn.textContent = u;
      btn.addEventListener('click', () => {
        ukuranOptions.querySelectorAll('.produk-detail-option-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        ukuranAktif = u;
      });
      ukuranOptions.appendChild(btn);
    });
  }

  // Qty stepper
  let qty = 1;
  const qtyValueEl = document.getElementById('detailQtyValue');
  const qtyMinusBtn = document.getElementById('detailQtyMinus');
  const qtyPlusBtn = document.getElementById('detailQtyPlus');
  
  if (produk.stok === 0) {
    qtyMinusBtn.disabled = true;
    qtyPlusBtn.disabled = true;
  } else {
    qtyMinusBtn.addEventListener('click', () => {
      if (qty > 1) { qty--; qtyValueEl.textContent = qty; }
    });
    qtyPlusBtn.addEventListener('click', () => {
      if (qty < produk.stok) { qty++; qtyValueEl.textContent = qty; }
    });
  }

  const wishBtn = document.getElementById('detailWishBtn');
  if (typeof ykIsWished === "function" && ykIsWished(produk.id)) {
    wishBtn.classList.add('active');
  }
  wishBtn.addEventListener('click', function () {
    if (typeof ykToggleWishlist === "function") {
      const nowSaved = ykToggleWishlist(produk.id);
      this.classList.toggle('active', nowSaved);
    } else {
      this.classList.toggle('active');
    }
  });

  const detailAddBtn = document.getElementById('detailAddBtn');
  if (produk.stok === 0) {
    detailAddBtn.disabled = true;
    detailAddBtn.textContent = (typeof detailI18n !== "undefined" && detailI18n.outOfStock) ? detailI18n.outOfStock : 'Habis';
  } else {
    detailAddBtn.addEventListener('click', function () {
      if (typeof ykAddToCart === "function") {
        ykAddToCart(produk.id, qty, varianAktif, ukuranAktif);
      }
      this.classList.add('added');
      setTimeout(() => this.classList.remove('added'), 900);
    });
  }
});