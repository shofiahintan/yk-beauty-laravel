CARA PASANG
============
1. Extract zip ini.
2. Copy semua isi folder "public" dan "resources" di sini, TIMPA (replace) ke
   project kamu di C:\xampp2\htdocs\yk-beauty-laravel\ — total 7 file.
3. TIDAK PERLU npm run build lagi — sudah aku build-in dan sertakan
   langsung di public/build/.
4. Hard refresh browser: Ctrl+Shift+R.

APA YANG DIPERBAIKI ROUND INI
================================

1) Notifikasi sukses muncul DOBEL di panel Owner (bug BARU yang kamu
   screenshot)
   File: resources/views/layouts/owner.blade.php + 4 halaman Owner
   (produk/index, produk/koleksi, transaksi, pengguna/index)
   - Akar masalahnya: layouts/owner.blade.php sudah nampilin
     `session('status')` SEKALI di level layout (bungkus semua halaman
     Owner) — tapi 4 halaman itu masing-masing PUNYA lagi kode yang sama
     buat nampilin `session('status')` di dalam konten mereka sendiri.
     Hasilnya: pesan yang sama muncul 2x tiap kali kamu update/hapus/dll.
   - Fix: dihapus semua duplikatnya, sekarang cuma 1 sumber (di layout).

2) Notifikasi sukses/gagal di panel Owner sekalian diganti jadi popup
   pojok kanan atas (sesuai request kamu soal Akun kemarin, sekarang
   dipasang juga di panel Owner)
   - Sebelumnya berupa kotak pink polos yang nangkring di tengah halaman.
     Sekarang jadi popup card di pojok kanan atas (mirip yang di halaman
     Akun round kemarin, tapi versi Tailwind buat panel Owner): hijau
     dengan ✓ untuk sukses, merah dengan ! untuk pesan error validasi,
     auto-hilang 4-5 detik atau bisa ditutup manual pakai ✕.
   - Sekalian ngerapiin: pesan error validasi ($errors->any()) yang
     sebelumnya juga kotak polos terpisah, sekarang masuk ke sistem popup
     yang sama, jadi konsisten satu gaya di semua halaman Owner.

3) Tombol Bahasa dikembalikan di panel Owner
   File: resources/views/layouts/owner.blade.php
   - Icon globe ditambahin lagi di topbar (sebelah lonceng notifikasi),
     dropdown pilih "Bahasa Indonesia" / "English" — sama persis
     mekanismenya kayak yang di storefront (route `/lang/{locale}`, cookie
     `site_lang`), karena itu middleware GLOBAL yang sebenarnya selama ini
     tetap jalan di semua route termasuk Owner, cuma tombolnya aja yang
     kemarin dihapus dari tampilan.

   ⚠️ PENTING — TOLONG DIBACA:
   Nyalain tombol ini SEKARANG cuma bakal ganti locale-nya, TAPI belum
   akan ada efeknya di tulisan panel Owner. Aku cek satu-satu ke 9 file
   Blade panel Owner (dashboard, kelola produk, koleksi, transaksi,
   laporan, kelola pengguna, notifikasi, layout, ~1300 baris total) —
   SEMUA teksnya masih hardcode Bahasa Indonesia langsung di file, belum
   ada satupun yang pakai fungsi terjemahan `__()` kayak yang di storefront
   (storefront itu dari awal memang didesain bilingual, makanya sudah pakai
   __() + lang/id.json + lang/en.json).

   Jadi buat panel Owner beneran bisa ganti ke Inggris, tiap teks di 9 file
   itu perlu dibungkus __('key_baru') satu-satu + ditambahin entry
   terjemahannya di lang/en.json — ini kerjaan yang lumayan besar (bukan
   sekadar nyalain tombol), dan aku sengaja belum kerjain sekalian di
   round ini biar gak buru-buru dan malah ada yang kelewat/salah terjemah.

   Kabarin aku kalau kamu mau aku lanjut ke tahap itu (translate isi 9
   halaman Owner ke Inggris) — aku bisa kerjain bertahap per halaman biar
   gampang direview satu-satu, atau sekaligus kalau kamu maunya cepat.

YANG PERLU KAMU TES ULANG
============================
- Owner -> Kelola Produk -> edit produk apa aja -> simpan -> notifikasi
  sukses harus muncul SATU KALI aja di pojok kanan atas (bukan dobel lagi,
  bukan di tengah halaman lagi)
- Ulangi juga di Koleksi & Promo, Kelola Transaksi, Kelola Data Pengguna
- Owner -> klik icon globe di sebelah lonceng -> dropdown "Bahasa
  Indonesia" / "English" harus muncul dan bisa diklik (tapi ingat poin 3
  di atas — teksnya belum berubah, itu memang belum dikerjain)
