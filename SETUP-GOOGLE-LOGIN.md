# Setup Login/Daftar dengan Google

Kode di sisi Laravel sudah lengkap (controller, route, migration, tombol di
login & register). Yang perlu kamu lakukan sendiri cuma 3 langkah di bawah —
ini nggak bisa aku lakukan dari sini karena butuh akses ke Google Cloud
Console (akun Google kamu) dan `composer install` di komputer kamu sendiri.

## 1. Install package Socialite

Buka terminal di folder project (Laragon), jalankan:

```
composer require laravel/socialite
```

## 2. Bikin kredensial di Google Cloud Console

1. Buka https://console.cloud.google.com/
2. Bikin project baru (atau pakai yang sudah ada) — nama bebas, misal "YK Beauty"
3. Buka menu **APIs & Services > OAuth consent screen**
   - User Type: **External**
   - Isi nama aplikasi (misal "YK Beauty"), email support, email developer
   - Scopes: biarkan default (email, profile)
   - Test users: tambahkan email Google kamu sendiri dulu (supaya bisa dites
     sebelum aplikasi di-*publish*)
4. Buka menu **APIs & Services > Credentials**
   - Klik **Create Credentials > OAuth client ID**
   - Application type: **Web application**
   - **Authorized redirect URIs**, tambahkan persis:
     ```
     http://localhost:8000/auth/google/callback
     ```
     (sesuaikan host/port dengan `APP_URL` kamu di `.env` — kalau pakai
     domain Laragon custom seperti `http://yk-beauty.test`, redirect URI-nya
     jadi `http://yk-beauty.test/auth/google/callback`)
   - Klik **Create** — nanti muncul **Client ID** dan **Client Secret**

## 3. Isi ke file `.env`

Tambahkan (atau lengkapi) baris berikut di `.env`:

```
GOOGLE_CLIENT_ID=isi_client_id_dari_google
GOOGLE_CLIENT_SECRET=isi_client_secret_dari_google
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

Pastikan `APP_URL` di `.env` sama persis dengan yang kamu daftarkan sebagai
redirect URI di langkah 2 (termasuk `http://` vs `https://` dan ada/tidaknya
trailing slash).

## 4. Jalankan migration

```
php artisan migrate
```

Ini menambahkan kolom `google_id` ke tabel `users`, dan membuat kolom
`password` boleh kosong (karena user yang daftar lewat Google tidak
mengisi password sendiri).

## Cara kerjanya (ringkas)

- Tombol "Login dengan Google" (di halaman login) dan "Daftar dengan Google"
  (di halaman register) sekarang mengarah ke route yang sama:
  `GET /auth/google` → redirect ke halaman consent Google.
- Setelah user setuju, Google redirect balik ke `GET /auth/google/callback`.
- `GoogleAuthController@callback` akan:
  1. Cari user berdasarkan `google_id` — kalau ketemu, langsung login.
  2. Kalau belum ada, cek lagi berdasarkan `email` — kalau ada (user yang
     sebelumnya daftar manual), akun itu ditautkan ke Google (diisi
     `google_id`-nya), bukan bikin akun baru/duplikat.
  3. Kalau email juga belum ada sama sekali, baru dibuatkan user baru
     dengan role `customer` dan `email_verified_at` langsung terisi
     (karena email sudah diverifikasi oleh Google).
- User yang statusnya `is_active = false` tetap ditolak login lewat Google
  juga (konsisten dengan aturan login manual).

## Testing

1. Jalankan `php artisan serve` (atau buka lewat Laragon seperti biasa)
2. Buka halaman `/login`, klik "Masuk dengan Google"
3. Pilih akun Google yang sudah kamu daftarkan sebagai **test user** di
   langkah 2 tadi
4. Harusnya langsung ter-redirect ke halaman utama (customer) dalam
   keadaan sudah login

Kalau muncul error "redirect_uri_mismatch", itu artinya redirect URI di
Google Console beda persis (karakter demi karakter) dari `GOOGLE_REDIRECT_URI`
di `.env` kamu — cek lagi keduanya.
