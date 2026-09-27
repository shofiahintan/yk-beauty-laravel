/**
 * YK Chat — mesin percakapan bersama.
 *
 * Dipakai oleh:
 *   - widget chat pelanggan di storefront  (bagian "Widget pelanggan" di bawah)
 *   - panel Owner /owner/pesan             (lewat public/js/chat-owner.js, memakai window.YKChat)
 *
 * Cara kerja:
 *   - Pesan diambil lewat endpoint JSON (GET ?after=ID). Kalau Pusher aktif, pesan baru
 *     datang seketika lewat WebSocket dan polling melambat jadi 30 detik (jaring pengaman).
 *     Kalau Pusher belum diatur / gagal tersambung, polling tiap 4 detik — chat tetap jalan.
 *   - Teks pesan selalu dipasang lewat textContent (bukan innerHTML), jadi aman dari XSS.
 */
(function () {
  'use strict';

  var CSRF = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
  var MAX_BYTES = 5 * 1024 * 1024;
  var ALLOWED = /^image\/(jpeg|png|webp)$/;

  /* ------------------------------------------------------------------ util */

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

  function el(tag, cls, text) {
    var n = document.createElement(tag);
    if (cls) n.className = cls;
    if (text != null) n.textContent = text;
    return n;
  }

  function pad(n) { return n < 10 ? '0' + n : String(n); }

  function dayKey(d) { return d.getFullYear() + '-' + d.getMonth() + '-' + d.getDate(); }

  function timeLabel(d) { return pad(d.getHours()) + ':' + pad(d.getMinutes()); }

  function dayLabel(d, i18n) {
    var now = new Date();
    if (dayKey(d) === dayKey(now)) return i18n.today;
    var y = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 1);
    if (dayKey(d) === dayKey(y)) return i18n.yesterday;
    try {
      return d.toLocaleDateString(i18n.locale || undefined, { day: 'numeric', month: 'long', year: 'numeric' });
    } catch (e) {
      return dayKey(d);
    }
  }

  function errorText(err, i18n) {
    if (err && (err.status === 401 || err.status === 419)) return i18n.errSession;
    var d = err && err.data;
    if (d && d.errors) {
      var k = Object.keys(d.errors)[0];
      if (k && d.errors[k] && d.errors[k][0]) return d.errors[k][0];
    }
    return i18n.errSend;
  }

  /* --------------------------------------------------------------- Pusher */

  /**
   * Sambung ke Pusher (kalau cfg ada). Script pusher-js dimuat dari CDN saat dibutuhkan;
   * kalau gagal dimuat, semua tetap jalan lewat polling.
   * subscribe() boleh dipanggil sebelum koneksi siap — antreannya dieksekusi belakangan.
   */
  function connectPusher(cfg) {
    var client = null;
    var connected = false;
    var pending = [];
    var listeners = [];

    function bindNow(channel, event, fn) {
      try { client.subscribe(channel).bind(event, fn); } catch (e) { /* abaikan */ }
    }

    var api = {
      subscribe: function (channel, event, fn) {
        if (client) bindNow(channel, event, fn);
        else pending.push([channel, event, fn]);
      },
      isConnected: function () { return connected; },
      onState: function (fn) { listeners.push(fn); }
    };

    if (!cfg || !cfg.key) return api;

    var s = document.createElement('script');
    s.src = 'https://js.pusher.com/8.2.0/pusher.min.js';
    s.async = true;
    s.onload = function () {
      try {
        client = new window.Pusher(cfg.key, {
          cluster: cfg.cluster,
          forceTLS: true,
          channelAuthorization: {
            endpoint: cfg.authUrl,
            transport: 'ajax',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
          }
        });
        client.connection.bind('state_change', function (st) {
          connected = st.current === 'connected';
          listeners.forEach(function (fn) { fn(connected); });
        });
        pending.forEach(function (p) { bindNow(p[0], p[1], p[2]); });
        pending = [];
      } catch (e) {
        client = null;
      }
    };
    document.head.appendChild(s);

    return api;
  }

  /* --------------------------------------------------------------- Thread */

  /**
   * Satu ruang chat: daftar pesan + kotak tulis.
   *
   * opts:
   *   me        'customer' | 'owner'  — sisi mana yang sedang memakai (buat menentukan bubble kanan/kiri)
   *   listUrl   GET  daftar pesan   sendUrl  POST kirim pesan
   *   i18n      teks antarmuka
   *   els       { box, form, text, file, attach, send, preview, error }
   *   isVisible()  true kalau ruang chat sedang dilihat → pesan lawan ditandai terbaca
   *   pollMs()     jeda polling (ms)
   *   onChange()   dipanggil setiap ada pesan baru yang masuk ke daftar (opsional)
   *   onUnread(n)  dipanggil dengan sisa pesan belum dibaca dari server (opsional)
   */
  function createThread(opts) {
    var els = opts.els;
    var i18n = opts.i18n;
    var box = els.box;

    var msgs = [];
    var ids = {};
    var hasMore = false;
    var loadedOnce = false;
    var busy = false;
    var again = false;
    var sending = false;
    var active = false;
    var stickBottom = true;
    var timer = null;
    var errTimer = null;
    var pendingFile = null;
    var previewUrl = null;

    /* ---- daftar pesan ---- */

    function merge(list) {
      var added = false;
      (list || []).forEach(function (m) {
        if (!m || ids[m.id]) return;
        ids[m.id] = true;
        msgs.push(m);
        added = true;
      });
      if (added) msgs.sort(function (a, b) { return a.id - b.id; });
      return added;
    }

    // mode: 'bottom' (paksa ke bawah) | 'keep' (ke bawah hanya kalau sedang di bawah) | 'prepend' (pesan lama ditambah di atas)
    function render(mode) {
      var prevH = box.scrollHeight;
      var prevTop = box.scrollTop;
      var nearBottom = (prevH - prevTop - box.clientHeight) < 80;
      var frag = document.createDocumentFragment();

      if (hasMore) {
        var older = el('button', 'yk-chat-older', i18n.older);
        older.type = 'button';
        older.addEventListener('click', loadOlder);
        frag.appendChild(older);
      }

      if (!msgs.length) frag.appendChild(el('div', 'yk-chat-empty', i18n.empty));

      var lastDay = '';
      msgs.forEach(function (m) {
        var d = new Date(m.created_at);
        var dk = dayKey(d);
        if (dk !== lastDay) {
          lastDay = dk;
          frag.appendChild(el('div', 'yk-chat-day', dayLabel(d, i18n)));
        }

        var row = el('div', 'yk-chat-msg ' + (m.sender === opts.me ? 'is-mine' : 'is-theirs'));
        var bubble = el('div', 'yk-chat-bubble' + (m.is_bot ? ' is-bot' : ''));

        if (m.is_bot && i18n.botLabel) bubble.appendChild(el('span', 'yk-chat-bot-tag', i18n.botLabel));

        if (m.image_url) {
          var link = el('a', 'yk-chat-imglink');
          link.href = m.image_url;
          link.target = '_blank';
          link.rel = 'noopener';
          var img = el('img', 'yk-chat-img');
          img.src = m.image_url;
          img.alt = '';
          img.addEventListener('load', function () {
            if (stickBottom) box.scrollTop = box.scrollHeight;
          });
          link.appendChild(img);
          bubble.appendChild(link);
        }

        if (m.body) bubble.appendChild(el('div', 'yk-chat-text', m.body));
        bubble.appendChild(el('span', 'yk-chat-time', timeLabel(d)));
        row.appendChild(bubble);
        frag.appendChild(row);
      });

      box.innerHTML = '';
      box.appendChild(frag);

      var stick = mode === 'bottom' || (mode === 'keep' && nearBottom);
      if (stick) box.scrollTop = box.scrollHeight;
      else if (mode === 'prepend') box.scrollTop = prevTop + (box.scrollHeight - prevH);
      else box.scrollTop = prevTop;
      stickBottom = stick;
    }

    box.addEventListener('scroll', function () {
      stickBottom = (box.scrollHeight - box.scrollTop - box.clientHeight) < 80;
    });

    /* ---- ambil pesan ---- */

    function fetchNew() {
      if (busy) { again = true; return Promise.resolve(); }
      busy = true;

      var q = [];
      var last = msgs.length ? msgs[msgs.length - 1].id : 0;
      if (last) q.push('after=' + last);
      if (opts.isVisible()) q.push('open=1');

      return jsonFetch(opts.listUrl + (q.length ? '?' + q.join('&') : ''))
        .then(function (data) {
          var first = !loadedOnce;
          if (first) {
            hasMore = !!data.has_more;
            loadedOnce = true;
          }
          var added = merge(data.messages);
          if (added || first) render(first ? 'bottom' : 'keep');
          if (added && opts.onChange) opts.onChange();
          if (opts.onUnread) opts.onUnread(data.unread || 0);
        })
        .catch(function () { /* jaringan putus — dicoba lagi di putaran berikutnya */ })
        .then(function () {
          busy = false;
          if (again) { again = false; return fetchNew(); }
        });
    }

    function loadOlder() {
      if (!msgs.length || busy) return;
      busy = true;
      jsonFetch(opts.listUrl + '?before=' + msgs[0].id)
        .then(function (data) {
          hasMore = !!data.has_more;
          merge(data.messages);
          render('prepend');
        })
        .catch(function () {})
        .then(function () { busy = false; });
    }

    function schedule() {
      clearTimeout(timer);
      if (!active) return;
      timer = setTimeout(function () {
        if (!active) return;
        if (document.visibilityState === 'hidden') { schedule(); return; }
        fetchNew().then(schedule);
      }, opts.pollMs());
    }

    function start() {
      active = true;
      return fetchNew().then(schedule);
    }

    function stop() {
      active = false;
      clearTimeout(timer);
    }

    // Pesan datang lewat Pusher
    function receive(m) {
      if (!m) return;
      if (merge([m])) {
        render('keep');
        if (opts.onChange) opts.onChange();
        // sedang dilihat & dari pihak lawan → minta server menandainya terbaca
        if (m.sender !== opts.me && opts.isVisible()) fetchNew();
      }
    }

    /* ---- kotak tulis ---- */

    function showError(msg) {
      if (!els.error) return;
      els.error.textContent = msg;
      els.error.classList.add('is-on');
      clearTimeout(errTimer);
      errTimer = setTimeout(function () { els.error.classList.remove('is-on'); }, 5000);
    }

    function clearFile() {
      pendingFile = null;
      if (els.file) els.file.value = '';
      if (previewUrl) { URL.revokeObjectURL(previewUrl); previewUrl = null; }
      if (els.preview) els.preview.classList.remove('is-on');
    }

    function pickFile(file) {
      if (!file) return;
      if (!ALLOWED.test(file.type)) { showError(i18n.errType); if (els.file) els.file.value = ''; return; }
      if (file.size > MAX_BYTES) { showError(i18n.errSize); if (els.file) els.file.value = ''; return; }
      clearFile();
      pendingFile = file;
      previewUrl = URL.createObjectURL(file);
      els.preview.querySelector('img').src = previewUrl;
      els.preview.classList.add('is-on');
    }

    function autosize() {
      var t = els.text;
      t.style.height = 'auto';
      t.style.height = Math.min(t.scrollHeight, 110) + 'px';
    }

    function setBusy(b) {
      sending = b;
      if (els.send) els.send.disabled = b;
    }

    function send() {
      if (sending) return;
      var text = (els.text.value || '').trim();
      if (!text && !pendingFile) { showError(i18n.errEmpty); return; }

      var fd = new FormData();
      if (text) fd.append('body', text);
      if (pendingFile) fd.append('image', pendingFile);

      setBusy(true);
      jsonFetch(opts.sendUrl, { method: 'POST', body: fd })
        .then(function (data) {
          els.text.value = '';
          autosize();
          clearFile();
          if (data && data.message) {
            merge([data.message]);
            render('bottom');
            if (opts.onChange) opts.onChange();
          }
        })
        .catch(function (err) { showError(errorText(err, i18n)); })
        .then(function () {
          setBusy(false);
          els.text.focus();
        });
    }

    els.form.addEventListener('submit', function (e) { e.preventDefault(); send(); });
    els.text.addEventListener('input', autosize);
    els.text.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) { e.preventDefault(); send(); }
    });
    els.attach.addEventListener('click', function () { els.file.click(); });
    els.file.addEventListener('change', function () { pickFile(els.file.files && els.file.files[0]); });
    els.preview.querySelector('.yk-chat-preview-x').addEventListener('click', clearFile);

    return {
      start: start,
      stop: stop,
      refresh: fetchNew,
      receive: receive,
      focus: function () { els.text.focus(); }
    };
  }

  window.YKChat = { createThread: createThread, connectPusher: connectPusher, jsonFetch: jsonFetch };

  /* ------------------------------------------------------ Widget pelanggan */

  function initWidget() {
    var root = document.getElementById('ykChat');
    var cfg = window.YK_CHAT;
    if (!root || !cfg) return;

    var launcher = root.querySelector('.yk-chat-launcher');
    var closeBtn = root.querySelector('.yk-chat-close');
    var badge = root.querySelector('.yk-chat-badge');
    var thread = null;
    var open = false;
    var pusher = connectPusher(cfg.pusher);

    function setBadge(n) {
      badge.textContent = n > 99 ? '99+' : String(n);
      badge.style.display = n > 0 ? 'flex' : 'none';
    }

    function refreshUnread() {
      if (open) return;
      jsonFetch(cfg.unreadUrl)
        .then(function (d) { if (!open) setBadge(d.unread || 0); })
        .catch(function () {});
    }

    function openPanel() {
      open = true;
      root.classList.add('is-open');
      launcher.setAttribute('aria-expanded', 'true');
      setBadge(0);

      if (!thread) {
        thread = createThread({
          me: 'customer',
          listUrl: cfg.listUrl,
          sendUrl: cfg.sendUrl,
          i18n: cfg.i18n,
          els: {
            box: document.getElementById('ykChatBox'),
            form: document.getElementById('ykChatForm'),
            text: document.getElementById('ykChatText'),
            file: document.getElementById('ykChatFile'),
            attach: document.getElementById('ykChatAttach'),
            send: document.getElementById('ykChatSend'),
            preview: document.getElementById('ykChatPreview'),
            error: document.getElementById('ykChatError')
          },
          isVisible: function () { return open; },
          pollMs: function () { return pusher.isConnected() ? 30000 : 4000; },
          onUnread: function () { if (open) setBadge(0); }
        });
      }

      thread.start();
      if (!('ontouchstart' in window)) setTimeout(function () { thread.focus(); }, 150);
    }

    function closePanel() {
      open = false;
      root.classList.remove('is-open');
      launcher.setAttribute('aria-expanded', 'false');
      if (thread) thread.stop();
    }

    // ---- Geser (drag) tombol chat ke posisi mana pun di layar ----
    (function enableDrag() {
      var POS_KEY = 'ykChatPos';
      var margin = 12;
      var dragging = false, moved = false;
      var startX = 0, startY = 0, startLeft = 0, startTop = 0;

      function applyPos(left, top) {
        var w = root.offsetWidth || 58;
        var h = root.offsetHeight || 58;
        left = Math.min(Math.max(left, margin), window.innerWidth - w - margin);
        top = Math.min(Math.max(top, margin), window.innerHeight - h - margin);
        root.style.left = left + 'px';
        root.style.top = top + 'px';
        root.style.right = 'auto';
        root.style.bottom = 'auto';
      }

      try {
        var saved = JSON.parse(localStorage.getItem(POS_KEY) || 'null');
        if (saved && typeof saved.left === 'number' && typeof saved.top === 'number') applyPos(saved.left, saved.top);
      } catch (e) {}

      launcher.addEventListener('click', function (e) {
        if (moved) { e.stopImmediatePropagation(); e.preventDefault(); moved = false; }
      });

      launcher.addEventListener('pointerdown', function (e) {
        if (e.button !== undefined && e.button !== 0) return;
        var rect = root.getBoundingClientRect();
        startLeft = rect.left; startTop = rect.top;
        startX = e.clientX; startY = e.clientY;
        dragging = true; moved = false;
        if (launcher.setPointerCapture) { try { launcher.setPointerCapture(e.pointerId); } catch (err) {} }
      });

      launcher.addEventListener('pointermove', function (e) {
        if (!dragging) return;
        var dx = e.clientX - startX, dy = e.clientY - startY;
        if (!moved && (Math.abs(dx) > 4 || Math.abs(dy) > 4)) { moved = true; root.classList.add('is-dragging'); }
        if (moved) applyPos(startLeft + dx, startTop + dy);
      });

      function endDrag() {
        if (!dragging) return;
        dragging = false;
        root.classList.remove('is-dragging');
        if (moved) {
          var rect = root.getBoundingClientRect();
          try { localStorage.setItem(POS_KEY, JSON.stringify({ left: rect.left, top: rect.top })); } catch (err) {}
        }
      }
      launcher.addEventListener('pointerup', endDrag);
      launcher.addEventListener('pointercancel', endDrag);

      window.addEventListener('resize', function () {
        var rect = root.getBoundingClientRect();
        if (root.style.left || root.style.top) applyPos(rect.left, rect.top);
      });
    })();

    launcher.addEventListener('click', function () { open ? closePanel() : openPanel(); });
    closeBtn.addEventListener('click', closePanel);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && open) closePanel(); });

    // Dipanggil dari halaman lain (mis. tombol "Hubungi CS" di detail pesanan) untuk buka widget ini
    window.ykChatOpen = function () { if (!open) openPanel(); };

    // Real-time: pesan baru dari Owner
    pusher.subscribe('private-chat.' + cfg.userId, 'message.sent', function (data) {
      var m = data && data.message;
      if (!m) return;
      if (open && thread) thread.receive(m);
      else if (m.sender === 'owner') refreshUnread();
    });

    // Koneksi baru tersambung / tersambung ulang → kejar pesan yang mungkin terlewat
    pusher.onState(function (connected) {
      if (!connected) return;
      if (open && thread) thread.refresh();
      else refreshUnread();
    });

    // Badge saat widget tertutup (dan jaring pengaman kalau Pusher belum aktif)
    setInterval(function () {
      if (document.visibilityState !== 'hidden') refreshUnread();
    }, 30000);

    setBadge(cfg.unread || 0);
  }

  initWidget();
})();