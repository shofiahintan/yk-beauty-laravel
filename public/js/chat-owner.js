/**
 * YK Chat — sisi Owner.
 *
 * Dimuat di SEMUA halaman panel Owner (lewat layouts/owner.blade.php), setelah chat.js:
 *   1. Badge "Pesan" di sidebar selalu mutakhir (Pusher kalau aktif, kalau tidak polling).
 *   2. Di halaman /owner/pesan (ada window.YK_OWNER_CHAT_PAGE): menampilkan daftar percakapan
 *      + ruang chat pelanggan yang sedang dibuka.
 *
 * Catatan: markup yang dibuat dari sini memakai class di public/css/chat.css (bukan Tailwind),
 * karena Tailwind hanya memindai file blade — class yang cuma muncul di file JS tidak akan terbentuk.
 */
(function () {
  'use strict';

  var Y = window.YKChat;
  if (!Y) return;

  var G = window.YK_OWNER_CHAT || {};
  var P = window.YK_OWNER_CHAT_PAGE || null;
  var jsonFetch = Y.jsonFetch;
  var pusher = Y.connectPusher(G.pusher);

  var badges = document.querySelectorAll('[data-chat-badge]');

  function setBadge(n) {
    badges.forEach(function (b) {
      b.textContent = n > 99 ? '99+' : String(n);
      b.style.display = n > 0 ? '' : 'none';
    });
  }

  // Jalankan fn berulang dengan jeda yang bisa berubah (Pusher tersambung → jeda lebih panjang)
  function every(fn, delayFn) {
    (function tick() {
      setTimeout(function () {
        if (document.visibilityState !== 'hidden') fn();
        tick();
      }, delayFn());
    })();
  }

  function refreshUnread() {
    if (!G.unreadUrl) return;
    jsonFetch(G.unreadUrl)
      .then(function (d) { setBadge(d.unread || 0); })
      .catch(function () {});
  }

  /* ---------------------------------------------------- halaman non-Pesan */

  if (!P) {
    pusher.subscribe('private-chat.owner', 'message.sent', refreshUnread);
    pusher.onState(function (connected) { if (connected) refreshUnread(); });
    every(refreshUnread, function () { return pusher.isConnected() ? 120000 : 30000; });
    return;
  }

  /* ------------------------------------------------------- halaman /pesan */

  function pad(n) { return n < 10 ? '0' + n : String(n); }

  function shortTime(iso) {
    var d = new Date(iso);
    var n = new Date();
    if (d.toDateString() === n.toDateString()) return pad(d.getHours()) + ':' + pad(d.getMinutes());
    return pad(d.getDate()) + '/' + pad(d.getMonth() + 1);
  }

  function el(tag, cls, text) {
    var n = document.createElement(tag);
    if (cls) n.className = cls;
    if (text != null) n.textContent = text;
    return n;
  }

  var listEl = document.getElementById('ownerChatList');
  var searchEl = document.getElementById('ownerChatSearch');
  var threads = P.threads || [];
  var term = '';

  function renderList() {
    var shown = threads.filter(function (t) {
      return !term || t.name.toLowerCase().indexOf(term) !== -1;
    });

    listEl.innerHTML = '';

    if (!shown.length) {
      listEl.appendChild(el('div', 'yk-ol-empty', threads.length ? P.i18n.noMatch : P.i18n.noThreads));
      return;
    }

    shown.forEach(function (t) {
      var a = el('a', 'yk-ol-item' + (t.user_id === P.activeId ? ' is-active' : '') + (t.unread > 0 ? ' has-unread' : ''));
      a.href = t.url;

      a.appendChild(el('span', 'yk-ol-avatar', (t.name || '?').charAt(0).toUpperCase()));

      var main = el('span', 'yk-ol-main');
      var top = el('span', 'yk-ol-top');
      top.appendChild(el('span', 'yk-ol-name', t.name));
      top.appendChild(el('span', 'yk-ol-time', t.last_at ? shortTime(t.last_at) : ''));
      main.appendChild(top);

      var bottom = el('span', 'yk-ol-bottom');
      var previewWrap = el('span', 'yk-ol-preview-wrap');
      if (t.is_bot) previewWrap.appendChild(el('span', 'yk-ol-bot-tag', 'Bot'));
      previewWrap.appendChild(el('span', 'yk-ol-preview', t.preview));
      bottom.appendChild(previewWrap);
      if (t.unread > 0) bottom.appendChild(el('span', 'yk-ol-badge', t.unread > 99 ? '99+' : String(t.unread)));
      main.appendChild(bottom);

      a.appendChild(main);
      listEl.appendChild(a);
    });
  }

  function refreshThreads() {
    jsonFetch(P.threadsUrl)
      .then(function (d) {
        threads = d.threads || [];
        renderList();
        setBadge(d.unread || 0);
      })
      .catch(function () {});
  }

  if (searchEl) {
    searchEl.addEventListener('input', function () {
      term = searchEl.value.trim().toLowerCase();
      renderList();
    });
  }

  renderList();

  // Ruang chat pelanggan yang sedang dibuka
  var thread = null;

  if (P.activeId) {
    thread = Y.createThread({
      me: 'owner',
      listUrl: P.messagesUrl,
      sendUrl: P.sendUrl,
      i18n: P.i18n,
      els: {
        box: document.getElementById('ownerChatBox'),
        form: document.getElementById('ownerChatForm'),
        text: document.getElementById('ownerChatText'),
        file: document.getElementById('ownerChatFile'),
        attach: document.getElementById('ownerChatAttach'),
        send: document.getElementById('ownerChatSend'),
        preview: document.getElementById('ownerChatPreview'),
        error: document.getElementById('ownerChatError')
      },
      isVisible: function () { return document.visibilityState !== 'hidden'; },
      pollMs: function () { return pusher.isConnected() ? 30000 : 4000; },
      onChange: refreshThreads // ada pesan masuk/keluar → pratinjau & badge di daftar ikut berubah
    });

    thread.start().then(refreshThreads); // membuka ruang chat menandai pesan terbaca → segarkan badge

    pusher.subscribe('private-chat.' + P.activeId, 'message.sent', function (data) {
      if (data && data.message) thread.receive(data.message);
    });

    // Balik ke tab ini → kejar pesan & tandai terbaca
    document.addEventListener('visibilitychange', function () {
      if (document.visibilityState === 'visible') thread.refresh();
    });
  }

  // Pesan baru dari pelanggan mana pun → segarkan daftar percakapan
  pusher.subscribe('private-chat.owner', 'message.sent', refreshThreads);

  pusher.onState(function (connected) {
    if (!connected) return;
    refreshThreads();
    if (thread) thread.refresh();
  });

  every(refreshThreads, function () { return pusher.isConnected() ? 60000 : 8000; });
})();