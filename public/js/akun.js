const YK_PROFILE_KEY = 'yk_profile_v1';

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('akunForm');
  if (!form) return;

  const namaEl = document.getElementById('akunNama');
  const waEl = document.getElementById('akunWa');
  const emailEl = document.getElementById('akunEmail');
  const alamatEl = document.getElementById('akunAlamat');
  const avatarPreview = document.getElementById('akunAvatarPreview');
  const fotoInput = document.getElementById('akunFotoInput');
  const toast = document.getElementById('akunSaveToast');

  // Prefill dari localStorage kalau sebelumnya pernah disimpan
  function loadProfile() {
    let data = {};
    try { data = JSON.parse(localStorage.getItem(YK_PROFILE_KEY)) || {}; }
    catch (e) { data = {}; }

    if (data.nama) namaEl.value = data.nama;
    if (data.wa) waEl.value = data.wa;
    if (data.email) emailEl.value = data.email;
    if (data.alamat) alamatEl.value = data.alamat;
    if (data.foto) avatarPreview.innerHTML = `<img src="${data.foto}" alt="Foto profil">`;
  }

  fotoInput.addEventListener('change', () => {
    const file = fotoInput.files && fotoInput.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (e) => {
      avatarPreview.innerHTML = `<img src="${e.target.result}" alt="Foto profil">`;
    };
    reader.readAsDataURL(file);
  });

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const imgEl = avatarPreview.querySelector('img');
    const data = {
      nama: namaEl.value.trim(),
      wa: waEl.value.trim(),
      email: emailEl.value.trim(),
      alamat: alamatEl.value.trim(),
      foto: imgEl ? imgEl.src : null
    };
    localStorage.setItem(YK_PROFILE_KEY, JSON.stringify(data));

    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 2200);
  });

  loadProfile();
});