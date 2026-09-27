# Katalog Foto Produk — YK Beauty

Semua foto produk sekarang ada di satu folder: `public/assets/images/produk/`

Cara pakai: cari/foto produk aslinya, lalu **timpa (replace)** file dengan nama yang sama persis di folder itu (boleh ganti isi filenya, jangan ganti nama filenya, biar nggak perlu ubah kode lagi). Kalau foto pengganti beda ekstensi (misal kamu punya `.jpg` tapi nama filenya `.png`), tinggal bilang nanti aku sesuaikan kodenya.

Kolom "Status" nunjukin dugaan aku:
- ✅ **Kemungkinan sudah foto asli** — nama file lama sudah sesuai nama produknya, jadi mungkin nggak perlu diganti. Cek dulu sebelum diganti.
- ⚠️ **Perlu diganti** — foto lama itu foto generik/banner/nggak jelas asalnya, sebaiknya diganti foto asli produknya.

## Produk utama (1 foto per produk / varian utama)

| File baru | Produk | Status |
|---|---|---|
| `foto1.png` | Viva Queen Pro-Age Advance Face Serum | ⚠️ Perlu diganti (lama: banner landing page) |
| `foto2.jpg` | Viva Gold Whitening Serum | ⚠️ Perlu diganti (lama: `produk1.jpg`, nggak jelas) |
| `foto3.png` | Viva Glowing White Serum with Triple Brightening Booster | ✅ Kemungkinan sudah benar |
| `foto4.png` | Viva Acne Serum with Triple Clarifying Actives | ✅ Kemungkinan sudah benar |
| `foto5.png` | Viva Anti Aging Serum with Triple Rejuvenating Active | ✅ Kemungkinan sudah benar |
| `foto6.png` | Viva Peeling Serum with Triple Gentle Exfoliation | ✅ Kemungkinan sudah benar |
| `foto7.jpg` | Viva Moisturizer Gel Glowing White (50gr) — varian **Glowing White** | ⚠️ Perlu diganti |
| `foto8.jpg` | Viva White Soothing Aloe Gel (80gr) | ⚠️ Perlu diganti |
| `foto9.jpg` | Viva Perfect Mattelized Lip Cream — varian **751 Innocent Fairy** | ⚠️ Perlu diganti |
| `foto10.jpg` | Hanasui Serum Cushion — varian **01 Light** | ⚠️ Perlu diganti |
| `foto11.jpg` | Hanasui Vitamin C Serum | ⚠️ Perlu diganti (lama: banner landing page) |
| `foto12.jpg` | Scarlett Whitening Body Lotion Charming (300ml) | ⚠️ Perlu diganti |
| `foto13.jpg` | Scarlett Yordanian Sea Salt Shampoo | ⚠️ Perlu diganti |
| `foto14.jpg` | Scarlett Yordanian Sea Salt Conditioner | ⚠️ Perlu diganti |
| `foto15.jpg` | Emina Bright Stuff Face Wash (100ml) | ⚠️ Perlu diganti |
| `foto16.jpg` | Emina Cheek Lit Blush On | ⚠️ Perlu diganti |
| `foto17.jpg` | Wardah Lightening Facial Wash | ⚠️ Perlu diganti |
| `foto18.jpg` | Wardah Exclusive Matte Lip Cream | ⚠️ Perlu diganti |
| `foto19.jpg` | Somethinc Level Up! Niacinamide Moisture Serum | ⚠️ Perlu diganti |
| `foto20.jpg` | Somethinc Mugwort Anti Acne Serum | ⚠️ Perlu diganti |

## Foto varian tambahan (produk dengan beberapa pilihan warna/tipe)

**Produk #7 — Viva Moisturizer Gel Glowing White**
| File baru | Varian |
|---|---|
| `foto7-varian2.jpg` | Acne Care |
| `foto7-varian3.jpg` | Hydra Lift |

**Produk #9 — Viva Perfect Mattelized Lip Cream** (shade)
| File baru | Varian |
|---|---|
| `foto9-varian2.jpg` | 752 Frozen Princess |
| `foto9-varian3.jpg` | 753 Blossom Star |
| `foto9-varian4.jpg` | 754 Night Glam |
| `foto9-varian5.jpg` | 755 Drama Queen |

**Produk #10 — Hanasui Serum Cushion** (shade)
| File baru | Varian |
|---|---|
| `foto10-varian2.jpg` | 02 Natural |
| `foto10-varian3.jpg` | 03 Medium |
| `foto10-varian4.jpg` | 04 Pinkish |
| `foto10-varian5.jpg` | 05 Fairy Pinkish |

## Paket bundling

| File baru | Isi |
|---|---|
| `paket1.jpg` | Paket Daily Glowing (Rp150.000) | ⚠️ Perlu diganti (lama: banner landing page)

---

**Catatan teknis:**
- File lama nggak dihapus, cuma di-*copy* ke nama baru — jadi foto yang masih dipakai di tempat lain (misalnya banner landing page) tetap aman.
- Setelah ganti foto-foto ini, jalankan `php artisan db:seed --class=ProductSeeder` (dan `--class=CollectionSeeder` untuk paket) supaya data di database ikut ke-update. Lihat bagian bawah chat untuk detail perintahnya.
