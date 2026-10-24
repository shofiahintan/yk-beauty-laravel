@extends('layouts.app')

@section('title', __('akun_meta_title'))

@section('content')

<section class="page-banner align-left" style="padding-bottom:0;"></section>

<section class="akun-section">
  <div class="container">
    <div class="akun-layout">

      @include('partials.akun-sidebar', ['akun_active' => 'profil'])

      <div class="akun-content-card">
        <h2>{{ __('akun_profil_title') }}</h2>
        <p class="akun-content-desc">{{ __('akun_profil_desc') }}</p>

        @if (session('status') === 'profile-updated')
          <div class="alert alert-success" style="margin-bottom:18px; color:var(--maroon-800); background:#fdf2f2; padding:10px 16px; border-radius:8px; font-size:13.5px;">
            {{ __('akun_saved_toast') }}
          </div>
        @endif

        <form id="akunForm" method="POST" action="{{ route('akun.update') }}" enctype="multipart/form-data">
          @csrf
          <div class="akun-avatar-row">
            <div class="akun-avatar" id="akunAvatarPreview">
              @if (auth()->user()?->foto)
                <img src="{{ asset(auth()->user()->foto) }}" alt="Foto profil">
              @else
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="var(--maroon-800)" stroke-width="1.6"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-6.5 8-6.5S20 16 20 20"/></svg>
              @endif
            </div>
            <div>
              <label for="akunFotoInput" class="btn btn-primary" style="cursor:pointer; padding:10px 20px; font-size:13px;">{{ __('akun_ubah_foto') }}</label>
              <input type="file" id="akunFotoInput" name="foto" accept="image/png, image/jpeg" style="display:none;">
              <div class="akun-avatar-hint">{{ __('akun_foto_hint') }}</div>
            </div>
          </div>

          <div class="akun-form-row">
            <div class="akun-form-group">
              <label>{{ __('akun_label_nama') }}</label>
              <input type="text" id="akunNama" name="nama" value="{{ old('nama', auth()->user()?->name ?? '') }}" required>
            </div>
            <div class="akun-form-group">
              <label>{{ __('akun_label_wa') }}</label>
              <input type="text" id="akunWa" name="wa" value="{{ old('wa', auth()->user()?->whatsapp ?? '') }}">
            </div>
          </div>

          <div class="akun-form-group" style="margin-bottom:22px;">
            <label>{{ __('akun_label_email') }}</label>
            <input type="email" id="akunEmail" name="email" value="{{ old('email', auth()->user()?->email ?? '') }}" required>
          </div>

          <div class="akun-form-group">
            <label>{{ __('akun_label_alamat') }}</label>
            <textarea id="akunAlamat" name="alamat" rows="3">{{ old('alamat', auth()->user()?->alamat ?? '') }}</textarea>
          </div>

          <div class="akun-form-row">
            <div class="akun-form-group">
              <label>{{ __('Kota / Kabupaten') }}</label>
              <input type="text" id="akunKota" name="kota" value="{{ old('kota', auth()->user()?->kota ?? '') }}">
            </div>
            <div class="akun-form-group">
              <label>{{ __('Kode Pos') }}</label>
              <input type="text" id="akunKodePos" name="kodepos" value="{{ old('kodepos', auth()->user()?->kodepos ?? '') }}">
            </div>
          </div>

          <div class="akun-form-divider"></div>

          <div class="akun-save-row">
            <button type="submit" class="btn btn-primary">{{ __('akun_simpan_btn') }}</button>
          </div>
        </form>
      </div>

    </div>
  </div>
</section>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('akunForm');
  if (!form) return;

  const namaEl = document.getElementById('akunNama');
  const waEl = document.getElementById('akunWa');
  const emailEl = document.getElementById('akunEmail');
  const alamatEl = document.getElementById('akunAlamat');
  const kotaEl = document.getElementById('akunKota');
  const kodePosEl = document.getElementById('akunKodePos');
  const avatarPreview = document.getElementById('akunAvatarPreview');
  const fotoInput = document.getElementById('akunFotoInput');

  fotoInput.addEventListener('change', () => {
    const file = fotoInput.files && fotoInput.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (e) => {
      avatarPreview.innerHTML = `<img src="${e.target.result}" alt="Foto profil">`;
    };
    reader.readAsDataURL(file);
  });

  form.addEventListener('submit', function(e) {
    // Send AJAX to update DB
    e.preventDefault();
    const formData = new FormData(form);
    fetch(form.action, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      },
      body: formData
    })
    .then(async (r) => {
      let body = null;
      try { body = await r.json(); } catch (err) { /* non-JSON response */ }

      if (!r.ok || !body || body.success !== true) {
        // Validation error (422) or any other failure: show what actually went wrong
        const firstError = body && body.errors
          ? Object.values(body.errors)[0]?.[0]
          : null;
        window.ykToast(firstError || (body && body.message) || 'Gagal menyimpan profil.', 'error');
        return;
      }

      // Only sync to localStorage once the server has actually confirmed success
      const imgEl = avatarPreview.querySelector('img');
      const data = {
        nama: namaEl.value.trim(),
        wa: waEl.value.trim(),
        email: emailEl.value.trim(),
        alamat: alamatEl.value.trim(),
        kota: kotaEl ? kotaEl.value.trim() : '',
        kodepos: kodePosEl ? kodePosEl.value.trim() : '',
        foto: imgEl ? imgEl.src : null
      };
      try { localStorage.setItem('yk_profile_v1', JSON.stringify(data)); } catch (err) {}

      window.ykToast(@js(__('akun_saved_toast')), 'success');
    })
    .catch(() => {
      // Network-level failure (fetch itself rejected): fall back to a normal form submit
      form.submit();
    });
  });
});
</script>
@endpush
