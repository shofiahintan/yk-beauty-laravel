/**
 * YK Review — modal ulasan produk.
 *
 * Dipakai dua tempat:
 *   1. Modal wajib global (partials.review-required-modal, di-include di semua halaman lewat
 *      layouts/app.blade.php) — otomatis kebuka kalau user login punya item pesanan "Selesai"
 *      yang belum direview. Satu per satu: submit satu, kalau masih ada sisa langsung lanjut
 *      ke item berikutnya (chaining lewat field `next` di response POST /review).
 *   2. Tombol "Beri Ulasan" manual di halaman /pesanan (lewat window.ykOpenReviewModal).
 *
 * Modal ditutup (tombol X / klik overlay) cuma sembunyi di halaman ini — tidak ada state yang
 * disimpan di client, jadi begitu user buka/refresh halaman lain, modal wajib muncul lagi kalau
 * memang masih ada ulasan yang belum diisi (query-nya selalu fresh dari server tiap page load).
 */
(function () {
  'use strict';

  var CSRF = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

  function jsonFetch(url, options) {
    options = options || {};
    options.credentials = 'same-origin';
    options.headers = Object.assign({
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': CSRF
    }, options.headers || {});

    return fetch(url, options).then(function (res) {
      return res.json().catch(function () { return {}; }).then(function (data) {
        if (!res.ok) {
          var err = new Error('HTTP ' + res.status);
          err.status = res.status;
          err.data = data;
          throw err;
        }
        return data;
      });
    });
  }

  var overlay, modalBody, currentItem, selectedRating = 0;

  function ensureOverlay() {
    if (overlay) return overlay;
    overlay = document.createElement('div');
    overlay.className = 'yk-review-overlay';
    overlay.innerHTML =
      '<div class="yk-review-modal" role="dialog" aria-modal="true">' +
        '<button type="button" class="yk-review-close" aria-label="Tutup">&times;</button>' +
        '<div class="yk-review-body"></div>' +
      '</div>';
    document.body.appendChild(overlay);
    overlay.querySelector('.yk-review-close').addEventListener('click', closeModal);
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) closeModal();
    });
    modalBody = overlay.querySelector('.yk-review-body');
    return overlay;
  }

  function closeModal() {
    if (overlay) overlay.classList.remove('open');
  }

  function buildStars(container) {
    container.innerHTML = '';
    for (var i = 1; i <= 5; i++) {
      var b = document.createElement('button');
      b.type = 'button';
      b.dataset.value = String(i);
      b.textContent = '★';
      b.addEventListener('click', function () {
        selectedRating = parseInt(this.dataset.value, 10);
        syncStars(container);
      });
      container.appendChild(b);
    }
  }

  function syncStars(container) {
    Array.prototype.forEach.call(container.children, function (b) {
      b.classList.toggle('filled', parseInt(b.dataset.value, 10) <= selectedRating);
    });
  }

  function openReviewModal(item, opts) {
    opts = opts || {};
    var t = window.YK_REVIEW_I18N || {};
    ensureOverlay();
    currentItem = item;
    selectedRating = 0;

    var metaParts = [];
    if (item.varian) metaParts.push(item.varian);
    if (item.ukuran) metaParts.push(item.ukuran);

    modalBody.innerHTML =
      '<p class="yk-review-eyebrow">' + (t.eyebrow || '') + '</p>' +
      '<h3>' + (t.title || '') + '</h3>' +
      '<div class="yk-review-product">' +
        '<img src="' + (item.productImage || '') + '" alt="">' +
        '<div>' +
          '<div class="yk-review-product-nama">' + (item.productNama || '') + '</div>' +
          (metaParts.length ? '<div class="yk-review-product-meta">' + metaParts.join(' · ') + '</div>' : '') +
        '</div>' +
      '</div>' +
      '<div class="yk-review-stars-label">' + (t.ratingLabel || '') + '</div>' +
      '<div class="yk-review-stars" id="ykReviewStars"></div>' +
      '<textarea id="ykReviewComment" maxlength="1000" placeholder="' + (t.placeholder || '') + '"></textarea>' +
      '<p class="yk-review-error" id="ykReviewError"></p>' +
      '<button type="button" class="btn btn-primary" id="ykReviewSubmit">' + (t.submitBtn || 'Kirim') + '</button>';

    buildStars(document.getElementById('ykReviewStars'));

    document.getElementById('ykReviewSubmit').addEventListener('click', function () {
      submitReview(opts);
    });

    overlay.classList.add('open');
  }

  function submitReview(opts) {
    var t = window.YK_REVIEW_I18N || {};
    var errorEl = document.getElementById('ykReviewError');
    var comment = document.getElementById('ykReviewComment').value.trim();
    errorEl.textContent = '';

    if (selectedRating < 1) {
      errorEl.textContent = t.errRating || 'Pilih rating bintang dulu ya.';
      return;
    }
    if (!comment) {
      errorEl.textContent = t.errComment || 'Komentar wajib diisi.';
      return;
    }

    var btn = document.getElementById('ykReviewSubmit');
    btn.disabled = true;
    btn.textContent = t.submitting || 'Mengirim...';

    jsonFetch(window.YK_REVIEW.storeUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        order_item_id: currentItem.orderItemId,
        rating: selectedRating,
        comment: comment
      })
    }).then(function (data) {
      if (typeof opts.onSuccess === 'function') opts.onSuccess();
      if (data.next) {
        openReviewModal(data.next, {});
      } else {
        closeModal();
      }
    }).catch(function (err) {
      errorEl.textContent = (err.data && err.data.errors && err.data.errors.order_item_id)
        ? err.data.errors.order_item_id[0]
        : (t.errSubmit || 'Gagal mengirim ulasan, coba lagi.');
      btn.disabled = false;
      btn.textContent = t.submitBtn || 'Kirim';
    });
  }

  window.ykOpenReviewModal = openReviewModal;

  document.addEventListener('DOMContentLoaded', function () {
    if (window.YK_REVIEW && window.YK_REVIEW.pending) {
      openReviewModal(window.YK_REVIEW.pending, {});
    }
  });
})();
