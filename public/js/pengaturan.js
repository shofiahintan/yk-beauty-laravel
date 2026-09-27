const YK_NOTIF_KEY = 'yk_notif_prefs_v1';

document.addEventListener('DOMContentLoaded', () => {

  // ---- Toggle show/hide password (dipakai 3x di halaman ini) ----
  document.querySelectorAll('.toggle-password').forEach((btn) => {
    btn.addEventListener('click', () => {
      const input = document.getElementById(btn.getAttribute('data-target'));
      const eye = btn.querySelector('.icon-eye');
      const eyeOff = btn.querySelector('.icon-eye-off');
      const showing = input.type === 'text';
      input.type = showing ? 'password' : 'text';
      eye.style.display = showing ? '' : 'none';
      eyeOff.style.display = showing ? 'none' : '';
    });
  });

  // ---- Form ubah password (POST ke backend Laravel) ----
  const passForm = document.getElementById('passwordForm');
  if (passForm) {
    const passLama = document.getElementById('passLama');
    const passBaru = document.getElementById('passBaru');
    const passKonfirmasi = document.getElementById('passKonfirmasi');
    const errBox = document.getElementById('passFormError');
    const errText = document.getElementById('passFormErrorText');
    const toast = document.getElementById('passSaveToast');
    const submitBtn = passForm.querySelector('[type="submit"]');

    passForm.addEventListener('submit', (e) => {
      e.preventDefault();
      errBox.style.display = 'none';

      if (!passLama.value.trim() || !passBaru.value.trim() || !passKonfirmasi.value.trim()) {
        errText.textContent = settingI18n.empty;
        errBox.style.display = 'flex';
        return;
      }
      if (passBaru.value !== passKonfirmasi.value) {
        errText.textContent = settingI18n.mismatch;
        errBox.style.display = 'flex';
        return;
      }

      const formData = new FormData(passForm);
      // Laravel expects pass_baru_confirmation for confirmed rule
      formData.set('pass_baru_confirmation', passKonfirmasi.value);

      if (submitBtn) submitBtn.disabled = true;

      fetch(passForm.action, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: formData
      })
      .then(r => r.json())
      .then(res => {
        if (submitBtn) submitBtn.disabled = false;
        if (res.success) {
          passForm.reset();
          toast.classList.add('show');
          setTimeout(() => toast.classList.remove('show'), 2200);
        } else if (res.errors) {
          const firstErr = Object.values(res.errors)[0];
          errText.textContent = Array.isArray(firstErr) ? firstErr[0] : firstErr;
          errBox.style.display = 'flex';
        } else if (res.message) {
          errText.textContent = res.message;
          errBox.style.display = 'flex';
        }
      })
      .catch(() => {
        if (submitBtn) submitBtn.disabled = false;
        // Fallback: submit form biasa
        passForm.submit();
      });
    });
  }

  // ---- Ganti bahasa langsung dari dropdown custom ----
  const bahasaWrap = document.getElementById('bahasaSelectWrap');
  const bahasaTrigger = document.getElementById('bahasaSelectTrigger');
  const bahasaList = document.getElementById('bahasaSelectList');

  if (bahasaWrap && bahasaTrigger && bahasaList) {
    const closeBahasaList = () => {
      bahasaWrap.classList.remove('is-open');
      bahasaTrigger.setAttribute('aria-expanded', 'false');
      bahasaList.hidden = true;
    };
    const openBahasaList = () => {
      bahasaWrap.classList.add('is-open');
      bahasaTrigger.setAttribute('aria-expanded', 'true');
      bahasaList.hidden = false;
    };

    bahasaTrigger.addEventListener('click', () => {
      if (bahasaList.hidden) openBahasaList();
      else closeBahasaList();
    });

    bahasaList.querySelectorAll('.setting-select-option').forEach((opt) => {
      opt.addEventListener('click', () => {
        const lang = opt.dataset.value;
        window.location.href = '/lang/' + encodeURIComponent(lang);
      });
    });

    document.addEventListener('click', (e) => {
      if (!bahasaWrap.contains(e.target)) closeBahasaList();
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeBahasaList();
    });
  }

  // ---- Preferensi notifikasi (disimpan di localStorage) ----
  const notifPromo = document.getElementById('notifPromo');
  const notifPesanan = document.getElementById('notifPesanan');

  function loadNotifPrefs() {
    let prefs = {};
    try { prefs = JSON.parse(localStorage.getItem(YK_NOTIF_KEY)) || {}; }
    catch (e) { prefs = {}; }
    if (notifPromo) notifPromo.checked = prefs.promo !== false;
    if (notifPesanan) notifPesanan.checked = prefs.pesanan !== false;
  }
  function saveNotifPrefs() {
    localStorage.setItem(YK_NOTIF_KEY, JSON.stringify({
      promo: notifPromo ? notifPromo.checked : true,
      pesanan: notifPesanan ? notifPesanan.checked : true
    }));
  }
  if (notifPromo) notifPromo.addEventListener('change', saveNotifPrefs);
  if (notifPesanan) notifPesanan.addEventListener('change', saveNotifPrefs);
  loadNotifPrefs();
});