<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Data ini dipindah persis dari js/produk.js (20 produk).
     * Field per produk:
     *  - slug, nama, brand, kategori, harga, harga_normal, diskon_persen, badge, badge_class,
     *    rating, review_count, deskripsi, image (gambar utama)
     *  - galeri: array foto umum (opsional)
     *  - varian: array nama varian (opsional)
     *  - varian_galeri: [nama_varian => [foto, ...]] (opsional, cocok sama key di "varian")
     */
    protected function data(): array
    {
        return [
            [
                'slug' => 'viva-queen-pro-age-advance-face-serum',
                'nama' => 'Viva Queen Pro-Age Advance Face Serum',
                'brand' => 'viva', 'kategori' => 'skincare',
                'harga' => 76000,
                'badge' => 'BEST SELLER', 'badge_class' => null,
                'rating' => 4, 'review_count' => 124,
                'image' => 'assets/images/produk/foto1.png',
                'deskripsi' => 'Serum wajah dengan tekstur ringan berkat Higher Pro-Age Active, kombinasi 8x HA (Hyaluronan) Complex, Triple Ceramide, dan Snail Mucin Filtrate. Membantu melembapkan, merawat skin barrier, serta mengatasi tanda-tanda penuaan dini seperti garis halus. Cocok untuk semua jenis kulit.',
            ],
            [
                'slug' => 'viva-gold-whitening-serum',
                'nama' => 'Viva Gold Whitening Serum',
                'brand' => 'viva', 'kategori' => 'skincare',
                'harga' => 24000,
                'badge' => null, 'badge_class' => null,
                'rating' => 4, 'review_count' => 58,
                'image' => 'assets/images/produk/foto2.jpg',
                'deskripsi' => 'Serum pencerah dengan formula Revinage-infused dan Colloidal Gold pertama dari Viva, dilengkapi Triple Brightening Power: 4% Niacinamide, 2% Vitamin C, dan Hydrolized Collagen. Membantu meremajakan kulit, menyamarkan flek dan noda jerawat. Aman digunakan ibu hamil dan menyusui.',
            ],
            [
                'slug' => 'viva-glowing-white-serum-triple-brightening',
                'nama' => 'Viva Glowing White Serum with Triple Brightening Booster',
                'brand' => 'viva', 'kategori' => 'skincare',
                'harga' => 23000,
                'badge' => null, 'badge_class' => null,
                'rating' => 4, 'review_count' => 46,
                'image' => 'assets/images/produk/foto3.png',
                'deskripsi' => 'Serum pencerah dengan 5% Niacinamide, 1% Licorice, dan Glutathione untuk membantu meratakan warna kulit, menyamarkan flek/noda hitam dan bekas jerawat, serta merawat skin barrier. Cocok untuk kulit oily, dry, normal, dan kombinasi. Produk ini berbeda dari Viva Gold Whitening Serum — sama-sama mencerahkan tapi formulanya beda.',
            ],
            [
                'slug' => 'viva-acne-serum-triple-clarifying',
                'nama' => 'Viva Acne Serum with Triple Clarifying Actives',
                'brand' => 'viva', 'kategori' => 'skincare',
                'harga' => 23000,
                'badge' => null, 'badge_class' => null,
                'rating' => 4, 'review_count' => 39,
                'image' => 'assets/images/produk/foto4.png',
                'deskripsi' => 'Serum jerawat teruji dermatologis dengan 4% Niacinamide, 1% Salicylic Acid, dan 1% Centella Asiatica Extract. Membantu mencegah timbulnya jerawat, menenangkan kulit berjerawat, dan menyamarkan noda bekas jerawat. Gunakan 2x sehari bersama Viva Acne Gel & Lotion untuk hasil terbaik.',
            ],
            [
                'slug' => 'viva-anti-aging-serum-triple-rejuvenating',
                'nama' => 'Viva Anti Aging Serum with Triple Rejuvenating Active',
                'brand' => 'viva', 'kategori' => 'skincare',
                'harga' => 23000,
                'badge' => null, 'badge_class' => null,
                'rating' => 4, 'review_count' => 28,
                'image' => 'assets/images/produk/foto5.png',
                'deskripsi' => 'Serum anti-aging dengan 1% Sodium Hyaluronate, 1% Collagen, dan Bakuchiol untuk membantu menyamarkan garis halus, kerutan, kulit kendur, dan flek hitam. Membantu kulit tetap kenyal, lembap, dan skin barrier terjaga.',
            ],
            [
                'slug' => 'viva-peeling-serum-triple-gentle-exfoliating',
                'nama' => 'Viva Peeling Serum with Triple Gentle Exfoliation',
                'brand' => 'viva', 'kategori' => 'skincare',
                'harga' => 23000,
                'badge' => null, 'badge_class' => null,
                'rating' => 4, 'review_count' => 21,
                'image' => 'assets/images/produk/foto6.png',
                'deskripsi' => 'Serum eksfoliasi lembut dengan 3% AHA (Glycolic Acid), 1% BHA (Salicylic Acid), dan 4% PHA (Gluconolactone) untuk mengangkat sel kulit mati dan membersihkan pori. Disarankan dipakai malam hari, 2-3x seminggu agar kulit tidak over-exfoliation.',
            ],
            [
                'slug' => 'viva-moisturizer-gel-glowing-white-50gr',
                'nama' => 'Viva Moisturizer Gel Glowing White (50gr)',
                'brand' => 'viva', 'kategori' => 'skincare',
                'harga' => 31000,
                'badge' => null, 'badge_class' => null,
                'rating' => 4, 'review_count' => 41,
                'image' => 'assets/images/produk/foto7.jpg',
                'galeri' => ['assets/images/produk/foto7.jpg', 'assets/images/produk/foto7-varian2.jpg', 'assets/images/produk/foto7-varian3.jpg'],
                'varian' => ['Glowing White', 'Acne Care', 'Hydra Lift'],
                'varian_galeri' => [
                    'Glowing White' => ['assets/images/produk/foto7.jpg'],
                    'Acne Care' => ['assets/images/produk/foto7-varian2.jpg'],
                    'Hydra Lift' => ['assets/images/produk/foto7-varian3.jpg'],
                ],
                'deskripsi' => 'Pelembap tekstur gel yang ringan dan cepat meresap, memberi sensasi cooling yang nyaman di kulit. Diformulasikan dengan Niacinamide, Glutathione, dan Licorice Extract untuk membantu mencerahkan kulit kusam dan menjaga kelembapan sepanjang hari. Cocok digunakan sebagai makeup base. Tersedia dalam 3 varian sesuai jenis kulit.',
            ],
            [
                'slug' => 'viva-white-soothing-aloe-gel-80gr',
                'nama' => 'Viva White Soothing Aloe Gel (80gr)',
                'brand' => 'viva', 'kategori' => 'skincare',
                'harga' => 21000,
                'badge' => null, 'badge_class' => null,
                'rating' => 5, 'review_count' => 76,
                'image' => 'assets/images/produk/foto8.jpg',
                'deskripsi' => 'Gel multifungsi dengan Pure Aloe Vera Juice Concentrate berkekuatan 200x untuk menghidrasi dan menyejukkan kulit. Diformulasikan tanpa alkohol sehingga cocok untuk semua jenis kulit termasuk kulit sensitif. Bisa digunakan di wajah maupun rambut, teksturnya ringan dan cepat meresap tanpa lengket.',
            ],
            [
                'slug' => 'viva-perfect-mattelized-lip-cream',
                'nama' => 'Viva Perfect Mattelized Lip Cream',
                'brand' => 'viva', 'kategori' => 'makeup',
                'harga' => 51000,
                'badge' => 'BARU', 'badge_class' => null,
                'rating' => 4, 'review_count' => 33,
                'image' => 'assets/images/produk/foto9.jpg',
                'varian' => ['751 Innocent Fairy', '752 Frozen Princess', '753 Blossom Star', '754 Night Glam', '755 Drama Queen'],
                'varian_galeri' => [
                    '751 Innocent Fairy' => ['assets/images/produk/foto9.jpg'],
                    '752 Frozen Princess' => ['assets/images/produk/foto9-varian2.jpg'],
                    '753 Blossom Star' => ['assets/images/produk/foto9-varian3.jpg'],
                    '754 Night Glam' => ['assets/images/produk/foto9-varian4.jpg'],
                    '755 Drama Queen' => ['assets/images/produk/foto9-varian5.jpg'],
                ],
                'deskripsi' => 'Lip cream dengan metallic radiance colors dan Color Lock Technology agar warna tahan lama dan transferproof, tidak menempel di masker. Diperkaya emollient dan Vitamin E untuk menjaga kelembapan bibir sepanjang hari.',
            ],
            [
                'slug' => 'hanasui-serum-cushion-shade-02-natural',
                'nama' => 'Hanasui Serum Cushion (Shade 02 Natural)',
                'brand' => 'hanasui', 'kategori' => 'makeup',
                'harga' => 76000,
                'badge' => null, 'badge_class' => null,
                'rating' => 4, 'review_count' => 67,
                'image' => 'assets/images/produk/foto10.jpg',
                'varian' => ['01 Light', '02 Natural', '03 Medium', '04 Pinkish', '05 Fairy Pinkish'],
                'varian_galeri' => [
                    '01 Light' => ['assets/images/produk/foto10.jpg'],
                    '02 Natural' => ['assets/images/produk/foto10-varian2.jpg'],
                    '03 Medium' => ['assets/images/produk/foto10-varian3.jpg'],
                    '04 Pinkish' => ['assets/images/produk/foto10-varian4.jpg'],
                    '05 Fairy Pinkish' => ['assets/images/produk/foto10-varian5.jpg'],
                ],
                // dulu ada "varianDefault: 02 Natural" di produk.js — belum ada kolom buat ini,
                // lihat catatan di README
                'deskripsi' => 'Cushion hybrid dengan kandungan Niacinamide, Sodium Hyaluronate, Vitamin E, dan Ekstrak Aloe Vera. Memberikan coverage sempurna namun tetap melembapkan, hasil akhir dewy yang natural, serta perlindungan dari sinar UVA & UVB.',
            ],
            [
                'slug' => 'hanasui-vitamin-c-serum',
                'nama' => 'Hanasui Vitamin C Serum',
                'brand' => 'hanasui', 'kategori' => 'skincare',
                'harga' => 26000,
                'badge' => 'BARU', 'badge_class' => null,
                'rating' => 4, 'review_count' => 89,
                'image' => 'assets/images/produk/foto11.jpg',
                'deskripsi' => 'Serum dengan Sodium Ascorbyl Phosphate, bentuk Vitamin C yang stabil dan lembut di kulit, membantu mencerahkan kulit kusam serta menghaluskan tekstur wajah. Diperkaya Niacinamide untuk membantu meratakan warna kulit tanpa menyebabkan iritasi.',
            ],
            [
                'slug' => 'scarlett-whitening-body-lotion-charming-300ml',
                'nama' => 'Scarlett Whitening Body Lotion Charming (300ml)',
                'brand' => 'scarlett', 'kategori' => 'body care',
                'harga' => 60000,
                'badge' => null, 'badge_class' => null,
                'rating' => 5, 'review_count' => 210,
                'image' => 'assets/images/produk/foto12.jpg',
                'deskripsi' => 'Body lotion dengan kandungan Glutathione, Niacinamide, Kojic Acid, dan Vitamin E untuk membantu mencerahkan dan melembapkan kulit tubuh. Teksturnya creamy namun cepat meresap tanpa lengket, dengan aroma floral manis khas varian Charming yang tahan hingga 5 jam.',
            ],
            [
                'slug' => 'scarlett-yordanian-sea-salt-shampoo',
                'nama' => 'Scarlett Yordanian Sea Salt Shampoo',
                'brand' => 'scarlett', 'kategori' => 'rambut',
                'harga' => 76000,
                'badge' => 'BARU', 'badge_class' => null,
                'rating' => 5, 'review_count' => 54,
                'image' => 'assets/images/produk/foto13.png',
                'deskripsi' => 'Shampoo dengan kandungan garam laut Yordania untuk mengurangi minyak berlebih di kulit kepala, mengurangi ketombe, menstimulasi pertumbuhan rambut, dan memperkuat folikel rambut sehingga rambut tampak lebih bervolume.',
            ],
            [
                'slug' => 'scarlett-yordanian-sea-salt-conditioner',
                'nama' => 'Scarlett Yordanian Sea Salt Conditioner',
                'brand' => 'scarlett', 'kategori' => 'rambut',
                'harga' => 76000,
                'badge' => null, 'badge_class' => null,
                'rating' => 5, 'review_count' => 47,
                'image' => 'assets/images/produk/foto14.jpg',
                'deskripsi' => 'Conditioner pasangan Yordanian Sea Salt Shampoo yang membantu melembutkan rambut, membuatnya lebih mudah diatur dan tidak lepek. Gunakan di batang rambut, hindari kulit kepala, untuk hasil rambut halus dan berkilau.',
            ],
            [
                'slug' => 'emina-bright-stuff-face-wash-100ml',
                'nama' => 'Emina Bright Stuff Face Wash (100ml)',
                'brand' => 'emina', 'kategori' => 'skincare',
                'harga' => 26000,
                'badge' => 'BARU', 'badge_class' => null,
                'rating' => 4, 'review_count' => 112,
                'image' => 'assets/images/produk/foto15.jpg',
                'deskripsi' => 'Sabun cuci muka dengan Oxybiome, Niacinamide, dan Summer Plum Extract untuk membersihkan kotoran dan kulit kusam tanpa membuat kulit kering atau ketarik. Cocok dipakai setiap hari untuk kulit tampak lebih cerah dan lembap.',
            ],
            [
                'slug' => 'emina-cheek-lit-blush-on',
                'nama' => 'Emina Cheek Lit Blush On',
                'brand' => 'emina', 'kategori' => 'makeup',
                'harga' => 40000,
                'badge' => null, 'badge_class' => null,
                'rating' => 4, 'review_count' => 65,
                'image' => 'assets/images/produk/foto16.jpg',
                'deskripsi' => 'Blush on bertekstur halus dengan pigmentasi ringan yang mudah dibaurkan, memberi efek pipi merona natural. Cocok untuk pemakaian sehari-hari, tersedia dalam beberapa pilihan warna.',
            ],
            [
                'slug' => 'wardah-lightening-facial-wash',
                'nama' => 'Wardah Lightening Facial Wash',
                'brand' => 'wardah', 'kategori' => 'skincare',
                'harga' => 24000,
                'badge' => null, 'badge_class' => null,
                'rating' => 4, 'review_count' => 143,
                'image' => 'assets/images/produk/foto17.jpg',
                'deskripsi' => 'Sabun cuci muka dengan Bearberry Extract dan Vitamin C untuk membantu mencerahkan kulit wajah sekaligus membersihkan kotoran dan minyak berlebih. Diformulasikan lembut dan cocok dipakai setiap hari.',
            ],
            [
                'slug' => 'wardah-exclusive-matte-lip-cream',
                'nama' => 'Wardah Exclusive Matte Lip Cream',
                'brand' => 'wardah', 'kategori' => 'makeup',
                'harga' => 46000,
                'badge' => 'BEST SELLER', 'badge_class' => null,
                'rating' => 5, 'review_count' => 198,
                'image' => 'assets/images/produk/foto18.jpg',
                'deskripsi' => 'Lip cream dengan hasil akhir matte yang ringan dan nyaman di bibir, warna pigmented dan tahan lama. Salah satu produk lip cream lokal paling populer, tersedia dalam banyak pilihan shade.',
            ],
            [
                'slug' => 'somethinc-level-up-niacinamide-serum',
                'nama' => 'Somethinc Level Up! Niacinamide Moisture Serum',
                'brand' => 'somethinc', 'kategori' => 'skincare',
                'harga' => 90000,
                'badge' => 'BARU', 'badge_class' => null,
                'rating' => 4, 'review_count' => 87,
                'image' => 'assets/images/produk/foto19.jpg',
                'deskripsi' => 'Serum dengan Niacinamide dan kompleks pelembap untuk membantu mencerahkan kulit kusam, meratakan warna kulit, dan menjaga hidrasi. Teksturnya ringan, cepat meresap, cocok dipakai pagi dan malam.',
            ],
            [
                'slug' => 'somethinc-mugwort-anti-acne-serum',
                'nama' => 'Somethinc Mugwort Anti Acne Serum',
                'brand' => 'somethinc', 'kategori' => 'skincare',
                'harga' => 100000,
                'badge' => null, 'badge_class' => null,
                'rating' => 4, 'review_count' => 73,
                'image' => 'assets/images/produk/foto20.jpg',
                'deskripsi' => 'Serum dengan ekstrak Mugwort yang menenangkan kulit berjerawat dan kemerahan, membantu mengontrol minyak berlebih serta menyamarkan bekas jerawat. Cocok untuk kulit sensitif dan berjerawat.',
            ],
        ];
    }

    public function run(): void
    {
        foreach ($this->data() as $row) {
            /** @var Product $product */
            $product = Product::updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'nama' => $row['nama'],
                    'brand' => $row['brand'],
                    'kategori' => $row['kategori'],
                    'harga' => $row['harga'],
                    'harga_normal' => $row['harga_normal'] ?? $row['harga'],
                    'diskon_persen' => $row['diskon_persen'] ?? null,
                    'badge' => $row['badge'] ?? null,
                    'badge_class' => $row['badge_class'] ?? null,
                    // Rating & review_count SELALU mulai dari 0 — sekarang dihitung otomatis
                    // dari ulasan asli pembeli (lihat Product::recalculateRating()), bukan
                    // angka dummy lagi. Nilai 'rating'/'review_count' di data() di bawah
                    // sudah tidak dipakai, sengaja dibiarkan sebagai catatan historis.
                    'rating' => 0,
                    'review_count' => 0,
                    'deskripsi' => $row['deskripsi'] ?? null,
                    'image' => $row['image'],
                    'stok' => $row['slug'] === 'viva-queen-pro-age-advance-face-serum' ? 0 : ($row['slug'] === 'viva-gold-whitening-serum' ? 2 : random_int(10, 120)), // Dummy: 1 habis, 1 menipis
                ]
            );

            // Bersihkan relasi lama biar seeder aman dijalanin ulang (idempotent)
            $product->images()->delete();
            $product->variants()->each(fn ($v) => $v->images()->delete());
            $product->variants()->delete();

            // Foto umum (dulu array "galeri") — hanya kalau bukan produk dengan varianGaleri,
            // biar gak dobel sama foto per-varian
            if (! empty($row['galeri']) && empty($row['varian_galeri'])) {
                foreach ($row['galeri'] as $i => $path) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'product_variant_id' => null,
                        'path' => $path,
                        'sort_order' => $i,
                    ]);
                }
            }

            // Varian + foto per-varian (dulu array "varian" + object "varianGaleri")
            if (! empty($row['varian'])) {
                foreach ($row['varian'] as $vi => $label) {
                    $variant = ProductVariant::create([
                        'product_id' => $product->id,
                        'label' => $label,
                        'sort_order' => $vi,
                    ]);

                    $variantImages = $row['varian_galeri'][$label] ?? [];
                    foreach ($variantImages as $i => $path) {
                        ProductImage::create([
                            'product_id' => $product->id,
                            'product_variant_id' => $variant->id,
                            'path' => $path,
                            'sort_order' => $i,
                        ]);
                    }
                }
            }
        }
    }
}
