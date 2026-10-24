const allProduk = window.__YK_PRODUCTS__ || [];

// Fallback kalau shopI18n belum ke-load (misal produk.js dites tanpa produk.php)
const i18n = (typeof shopI18n !== "undefined") ? shopI18n : {
  showing: "Menampilkan",
  of: "dari",
  products: "produk",
  addToCart: "Tambah ke Keranjang",
  outOfStock: "Habis",
  noResultTitle: "Belum Ada Produk yang Cocok",
  noResultDesc: "Coba ubah kombinasi filter atau reset untuk melihat semua produk.",
  resetFilterBtn: "Reset Filter",
  sortNewest: "Urutkan: Terbaru"
};

const PAGE_SIZE = 6;
const PRICE_MIN_DEFAULT = 0;
const PRICE_MAX_DEFAULT = 200000;

let selectedKategori = [];
let selectedBrand = [];
let priceMinVal = PRICE_MIN_DEFAULT;
let priceMaxVal = PRICE_MAX_DEFAULT;
let currentSort = "terbaru";
let visibleCount = PAGE_SIZE;
let searchQuery = "";

function formatRupiah(num) {
  return "Rp" + num.toLocaleString("id-ID");
}

function renderStars(rating) {
  return "★".repeat(rating) + "☆".repeat(5 - rating);
}

function getProdukById(id, type = 'product') {
  if (type === 'bundle') {
    const bundles = window.__YK_BUNDLES__ || [];
    return bundles.find(b => b.id === id) || null;
  }
  return allProduk.find(p => p.id === id) || null;
}

// ===== Filtering & sorting =====
function getFilteredProduk() {
  let hasil = allProduk.filter(p => {
    const cocokKategori = selectedKategori.length === 0 || selectedKategori.includes(p.kategori);
    const cocokBrand = selectedBrand.length === 0 || selectedBrand.includes(p.brand);
    const cocokHarga = p.harga >= priceMinVal && p.harga <= priceMaxVal;
    const cocokSearch = !searchQuery || p.nama.toLowerCase().includes(searchQuery);
    return cocokKategori && cocokBrand && cocokHarga && cocokSearch;
  });

  if (currentSort === "termurah") hasil.sort((a, b) => a.harga - b.harga);
  if (currentSort === "termahal") hasil.sort((a, b) => b.harga - a.harga);
  if (currentSort === "terlaris") hasil.sort((a, b) => b.count - a.count);

  return hasil;
}

// ===== Render grid =====
function renderAllProdukGrid() {
  const grid = document.getElementById('allProdukGrid');
  const countEl = document.getElementById('produkCount');
  const loadMoreWrap = document.getElementById('loadMoreWrap');
  if (!grid) return;

  const semuaHasil = getFilteredProduk();
  const hasil = semuaHasil.slice(0, visibleCount);

  countEl.innerHTML = `${i18n.showing} <strong>${hasil.length}</strong> ${i18n.of} <strong>${semuaHasil.length}</strong> ${i18n.products}`;

  if (semuaHasil.length === 0) {
    grid.innerHTML = `
      <div class="empty-state">
        <div class="empty-state-icon">
          <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
        </div>
        <h3>${i18n.noResultTitle}</h3>
        <p>${i18n.noResultDesc}</p>
        <button type="button" class="btn btn-outline empty-reset-btn">${i18n.resetFilterBtn}</button>
      </div>
    `;
    loadMoreWrap.style.display = "none";
    return;
  }

  grid.innerHTML = "";
  hasil.forEach((p, i) => {
    const card = document.createElement('div');
    card.className = 'produk-card';
    const wished = typeof ykIsWished === "function" && ykIsWished(p.id);
    card.innerHTML = `
      <a href="/produk/${encodeURIComponent(p.id)}" class="produk-img-wrap">
        ${p.badge ? `<span class="produk-badge ${p.badgeClass}">${p.badge}</span>` : ""}
        <div class="wish-btn${wished ? ' active' : ''}" data-id="${p.id}">
          <svg viewBox="0 0 24 24" fill="${wished ? 'currentColor' : 'none'}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 10-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
        </div>
        <img src="${p.img}" alt="${p.nama}">
      </a>
      <div class="produk-info">
        <div class="rating-row"><span class="stars">${renderStars(p.rating)}</span> (${p.count})</div>
        <a href="/produk/${encodeURIComponent(p.id)}" class="nama">${p.nama}</a>
        <div class="harga-row">
          ${p.coret ? `<span class="harga-coret">${formatRupiah(p.coret)}</span>` : ""}
          <span class="harga">${formatRupiah(p.harga)}</span>
        </div>
        <button class="btn-cart" data-id="${p.id}" ${p.stok === 0 ? 'disabled' : ''}>
          ${p.stok === 0 ? i18n.outOfStock : `
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 4h2l2.4 12.4a2 2 0 002 1.6h7.7a2 2 0 002-1.6L21 8H6"/><circle cx="10" cy="21" r="1.3"/><circle cx="18" cy="21" r="1.3"/></svg>
            ${i18n.addToCart}
          `}
        </button>
      </div>
    `;
    grid.appendChild(card);
  });

  loadMoreWrap.style.display = visibleCount < semuaHasil.length ? "block" : "none";
}

// ===== Price slider (dual thumb) =====
function initPriceSlider() {
  const priceMin = document.getElementById('priceMin');
  const priceMax = document.getElementById('priceMax');
  const priceFill = document.getElementById('priceFill');
  const priceMinInput = document.getElementById('priceMinInput');
  const priceMaxInput = document.getElementById('priceMaxInput');
  if (!priceMin || !priceMax) return;

  const rangeTotal = priceMin.max - priceMin.min;

  function updateFill() {
    const min = parseInt(priceMin.value);
    const max = parseInt(priceMax.value);
    const leftPct = ((min - priceMin.min) / rangeTotal) * 100;
    const rightPct = ((max - priceMin.min) / rangeTotal) * 100;
    priceFill.style.left = leftPct + "%";
    priceFill.style.width = (rightPct - leftPct) + "%";

    const priceDisplay = document.getElementById('priceDisplay');
    if (priceDisplay) priceDisplay.textContent = `${formatRupiah(min)} — ${formatRupiah(max)}`;
  }

  function syncFromSliders() {
    let min = parseInt(priceMin.value);
    let max = parseInt(priceMax.value);
    if (min > max) { min = max; priceMin.value = min; }
    priceMinInput.value = min;
    priceMaxInput.value = max;
    priceMinVal = min;
    priceMaxVal = max;
    updateFill();
    visibleCount = PAGE_SIZE;
    renderAllProdukGrid();
  }

  function syncFromNumberInputs() {
    let min = parseInt(priceMinInput.value) || 0;
    let max = parseInt(priceMaxInput.value) || parseInt(priceMax.max);
    min = Math.max(parseInt(priceMin.min), Math.min(min, parseInt(priceMin.max)));
    max = Math.max(parseInt(priceMax.min), Math.min(max, parseInt(priceMax.max)));
    if (min > max) { const t = min; min = max; max = t; }
    priceMin.value = min;
    priceMax.value = max;
    priceMinInput.value = min;
    priceMaxInput.value = max;
    priceMinVal = min;
    priceMaxVal = max;
    updateFill();
    visibleCount = PAGE_SIZE;
    renderAllProdukGrid();
  }

  priceMin.addEventListener('mousedown', () => { priceMin.style.zIndex = 5; priceMax.style.zIndex = 4; });
  priceMax.addEventListener('mousedown', () => { priceMax.style.zIndex = 5; priceMin.style.zIndex = 4; });
  priceMin.addEventListener('touchstart', () => { priceMin.style.zIndex = 5; priceMax.style.zIndex = 4; });
  priceMax.addEventListener('touchstart', () => { priceMax.style.zIndex = 5; priceMin.style.zIndex = 4; });

  priceMin.addEventListener('input', syncFromSliders);
  priceMax.addEventListener('input', syncFromSliders);
  priceMinInput.addEventListener('change', syncFromNumberInputs);
  priceMaxInput.addEventListener('change', syncFromNumberInputs);

  updateFill();
}

// ===== Search bar =====
function initSearchBar() {
  const searchInput = document.getElementById('produkSearchInput');
  const searchClear = document.getElementById('produkSearchClear');
  if (!searchInput) return;

  let debounceTimer = null;

  searchInput.addEventListener('input', () => {
    searchClear.style.display = searchInput.value ? "flex" : "none";

    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
      searchQuery = searchInput.value.trim().toLowerCase();
      visibleCount = PAGE_SIZE;
      renderAllProdukGrid();
    }, 150);
  });

  searchInput.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      searchInput.value = "";
      searchClear.style.display = "none";
      searchQuery = "";
      visibleCount = PAGE_SIZE;
      renderAllProdukGrid();
      searchInput.blur();
    }
  });

  searchClear.addEventListener('click', () => {
    searchInput.value = "";
    searchClear.style.display = "none";
    searchQuery = "";
    visibleCount = PAGE_SIZE;
    renderAllProdukGrid();
    searchInput.focus();
  });
}

// ===== Reset filter =====
function resetAllFilters() {
  selectedKategori = [];
  selectedBrand = [];
  priceMinVal = PRICE_MIN_DEFAULT;
  priceMaxVal = PRICE_MAX_DEFAULT;
  currentSort = "terbaru";
  visibleCount = PAGE_SIZE;
  searchQuery = "";

  document.querySelectorAll('input[name="kategori"], input[name="brand"]').forEach(cb => cb.checked = false);

  const searchInput = document.getElementById('produkSearchInput');
  const searchClear = document.getElementById('produkSearchClear');
  if (searchInput) searchInput.value = "";
  if (searchClear) searchClear.style.display = "none";

  const priceMin = document.getElementById('priceMin');
  const priceMax = document.getElementById('priceMax');
  const priceMinInput = document.getElementById('priceMinInput');
  const priceMaxInput = document.getElementById('priceMaxInput');
  const priceFill = document.getElementById('priceFill');
  if (priceMin) priceMin.value = PRICE_MIN_DEFAULT;
  if (priceMax) priceMax.value = PRICE_MAX_DEFAULT;
  if (priceMinInput) priceMinInput.value = PRICE_MIN_DEFAULT;
  if (priceMaxInput) priceMaxInput.value = PRICE_MAX_DEFAULT;
  if (priceFill) { priceFill.style.left = "0%"; priceFill.style.width = "100%"; }
  const priceDisplay = document.getElementById('priceDisplay');
  if (priceDisplay) priceDisplay.textContent = `${formatRupiah(PRICE_MIN_DEFAULT)} — ${formatRupiah(PRICE_MAX_DEFAULT)}`;

  const sortDropdown = document.getElementById('sortFilter');
  if (sortDropdown) {
    const label = sortDropdown.querySelector('#sortFilterLabel');
    const options = sortDropdown.querySelectorAll('.custom-select-option');
    options.forEach(o => o.classList.remove('active'));
    const defaultOpt = sortDropdown.querySelector('.custom-select-option[data-value="terbaru"]');
    if (defaultOpt) defaultOpt.classList.add('active');
    if (label) label.textContent = i18n.sortNewest;
  }

  renderAllProdukGrid();
}

// ===== Baca ?kategori= dari URL (dipakai dari link kartu kategori di landing) =====
function initKategoriFromQuery() {
  const params = new URLSearchParams(window.location.search);
  const kategoriParam = params.get('kategori');
  if (!kategoriParam) return;
  const cb = document.querySelector(`input[name="kategori"][value="${CSS.escape(kategoriParam)}"]`);
  if (cb) {
    cb.checked = true;
    selectedKategori = [kategoriParam];
  }
}

// ===== Init =====
document.addEventListener('DOMContentLoaded', () => {
  initKategoriFromQuery();
  renderAllProdukGrid();
  initPriceSlider();
  initSearchBar();

  document.querySelectorAll('input[name="kategori"]').forEach(cb => {
    cb.addEventListener('change', () => {
      selectedKategori = Array.from(document.querySelectorAll('input[name="kategori"]:checked')).map(el => el.value);
      visibleCount = PAGE_SIZE;
      renderAllProdukGrid();
    });
  });

  document.querySelectorAll('input[name="brand"]').forEach(cb => {
    cb.addEventListener('change', () => {
      selectedBrand = Array.from(document.querySelectorAll('input[name="brand"]:checked')).map(el => el.value);
      visibleCount = PAGE_SIZE;
      renderAllProdukGrid();
    });
  });

  const sortDropdown = document.getElementById('sortFilter');
  if (sortDropdown) {
    const trigger = sortDropdown.querySelector('.custom-select-trigger');
    const label = sortDropdown.querySelector('#sortFilterLabel');
    const options = sortDropdown.querySelectorAll('.custom-select-option');

    trigger.addEventListener('click', (e) => {
      e.stopPropagation();
      sortDropdown.classList.toggle('open');
    });

    options.forEach(opt => {
      opt.addEventListener('click', () => {
        options.forEach(o => o.classList.remove('active'));
        opt.classList.add('active');
        label.textContent = opt.textContent;
        currentSort = opt.dataset.value;
        visibleCount = PAGE_SIZE;
        sortDropdown.classList.remove('open');
        renderAllProdukGrid();
      });
    });

    document.addEventListener('click', (e) => {
      if (!sortDropdown.contains(e.target)) sortDropdown.classList.remove('open');
    });
  }

  const loadMoreBtn = document.getElementById('loadMoreBtn');
  if (loadMoreBtn) {
    loadMoreBtn.addEventListener('click', () => {
      visibleCount += PAGE_SIZE;
      renderAllProdukGrid();
    });
  }

  const filterReset = document.getElementById('filterReset');
  if (filterReset) {
    filterReset.addEventListener('click', resetAllFilters);
  }

  document.addEventListener('click', (e) => {
    if (e.target.closest('.empty-reset-btn')) resetAllFilters();
  });
});
