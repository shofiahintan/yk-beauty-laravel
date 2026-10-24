<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ProductVariantPhotoFase3Test extends TestCase
{
    use RefreshDatabase;

    private function createOwner(): User
    {
        return User::factory()->create([
            'role' => 'owner',
            'is_active' => true,
        ]);
    }

    private function createProduct(array $attrs = []): Product
    {
        return Product::create(array_merge([
            'slug' => 'product-' . uniqid(),
            'nama' => 'Produk Uji Fase 3',
            'brand' => 'YK Beauty',
            'kategori' => 'skincare',
            'harga' => 50000,
            'harga_normal' => 50000,
            'diskon_persen' => null,
            'image' => 'assets/images/test.png',
            'stok' => 10,
            'is_active' => true,
        ], $attrs));
    }

    protected function tearDown(): void
    {
        // Bersihkan file tes apapun di assets/images/products jika ada
        $testFiles = glob(public_path('assets/images/products/test_*'));
        if ($testFiles) {
            foreach ($testFiles as $f) {
                if (File::exists($f)) {
                    @unlink($f);
                }
            }
        }

        parent::tearDown();
    }

    /**
     * 1. Sinkronisasi varian berbasis ID:
     *    Edit produk berulang (ganti harga, ubah label, ubah urutan) tidak mengubah
     *    foto varian menjadi foto umum (product_variant_id tetap terikat dan tidak jadi null).
     */
    public function test_01_variant_sync_by_id_preserves_variant_images(): void
    {
        $owner = $this->createOwner();
        $product = $this->createProduct();

        $v1 = ProductVariant::create([
            'product_id' => $product->id,
            'label' => 'Shade Merah',
            'sort_order' => 0,
        ]);
        $img1 = ProductImage::create([
            'product_id' => $product->id,
            'product_variant_id' => $v1->id,
            'path' => 'assets/images/products/test_v1.jpg',
            'sort_order' => 0,
        ]);

        $v2 = ProductVariant::create([
            'product_id' => $product->id,
            'label' => 'Shade Biru',
            'sort_order' => 1,
        ]);
        $img2 = ProductImage::create([
            'product_id' => $product->id,
            'product_variant_id' => $v2->id,
            'path' => 'assets/images/products/test_v2.jpg',
            'sort_order' => 0,
        ]);

        // Simpan berulang kali lewat panel owner: ganti nama label dan urutan
        $response = $this->actingAs($owner)->put(route('owner.produk.update', $product), [
            'nama' => 'Produk Uji Diperbarui',
            'brand' => 'YK Beauty',
            'kategori' => 'skincare',
            'harga_normal' => 60000,
            'stok' => 15,
            'varian' => [
                ['id' => $v2->id, 'label' => 'Shade Biru Tua'],
                ['id' => $v1->id, 'label' => 'Shade Merah Muda'],
            ],
        ]);

        $response->assertRedirect(route('owner.produk.index'));

        // Cek ID varian tetap sama (tidak di-recreate)
        $this->assertDatabaseHas('product_variants', ['id' => $v1->id, 'label' => 'Shade Merah Muda', 'sort_order' => 1]);
        $this->assertDatabaseHas('product_variants', ['id' => $v2->id, 'label' => 'Shade Biru Tua', 'sort_order' => 0]);

        // Cek foto varian TIDAK menjadi foto umum (product_variant_id TIDAK null)
        $img1Fresh = ProductImage::find($img1->id);
        $this->assertNotNull($img1Fresh);
        $this->assertSame($v1->id, $img1Fresh->product_variant_id);

        $img2Fresh = ProductImage::find($img2->id);
        $this->assertNotNull($img2Fresh);
        $this->assertSame($v2->id, $img2Fresh->product_variant_id);

        // Tidak ada foto yang bocor jadi galeri umum
        $this->assertSame(0, $product->images()->count());
    }

    /**
     * 2. Upload foto khusus varian di panel owner:
     *    Tiap baris varian bisa diunggah foto khusus dan tersimpan di product_images dengan product_variant_id terisi.
     */
    public function test_02_owner_can_upload_variant_specific_photos(): void
    {
        $owner = $this->createOwner();
        $product = $this->createProduct();

        $v = ProductVariant::create([
            'product_id' => $product->id,
            'label' => 'Natural Beige',
            'sort_order' => 0,
        ]);

        $file1 = UploadedFile::fake()->image('test_var1.jpg');
        $file2 = UploadedFile::fake()->image('test_var2.png');

        $response = $this->actingAs($owner)->put(route('owner.produk.update', $product), [
            'nama' => $product->nama,
            'brand' => $product->brand,
            'kategori' => $product->kategori,
            'harga_normal' => 50000,
            'stok' => 10,
            'varian' => [
                [
                    'id' => $v->id,
                    'label' => 'Natural Beige',
                    'foto' => [$file1, $file2],
                ],
            ],
        ]);

        $response->assertRedirect(route('owner.produk.index'));

        $varImages = ProductImage::where('product_id', $product->id)
            ->where('product_variant_id', $v->id)
            ->get();

        $this->assertCount(2, $varImages);
        foreach ($varImages as $vi) {
            $this->assertFileExists(public_path($vi->path));
            @unlink(public_path($vi->path));
        }
    }

    /**
     * 3. Menghapus varian dari form ikut menghapus foto varian dari DB dan fisik.
     */
    public function test_03_removing_variant_from_form_deletes_its_images_and_files(): void
    {
        $owner = $this->createOwner();
        $product = $this->createProduct();

        $uploadDir = public_path('assets/images/products');
        if (!File::isDirectory($uploadDir)) {
            File::makeDirectory($uploadDir, 0755, true);
        }

        $dummyFilePath = $uploadDir . '/test_to_be_deleted.jpg';
        file_put_contents($dummyFilePath, 'dummy image content');
        $this->assertFileExists($dummyFilePath);

        $vDihapus = ProductVariant::create([
            'product_id' => $product->id,
            'label' => 'Varian Hapus',
            'sort_order' => 0,
        ]);

        $img = ProductImage::create([
            'product_id' => $product->id,
            'product_variant_id' => $vDihapus->id,
            'path' => 'assets/images/products/test_to_be_deleted.jpg',
            'sort_order' => 0,
        ]);

        $vTetap = ProductVariant::create([
            'product_id' => $product->id,
            'label' => 'Varian Tetap',
            'sort_order' => 1,
        ]);

        // Kirim update hanya dengan Varian Tetap (Varian Hapus dihilangkan)
        $response = $this->actingAs($owner)->put(route('owner.produk.update', $product), [
            'nama' => $product->nama,
            'brand' => $product->brand,
            'kategori' => $product->kategori,
            'harga_normal' => 50000,
            'stok' => 10,
            'varian' => [
                ['id' => $vTetap->id, 'label' => 'Varian Tetap'],
            ],
        ]);

        $response->assertRedirect(route('owner.produk.index'));

        // Varian dan gambarnya terhapus dari DB
        $this->assertDatabaseMissing('product_variants', ['id' => $vDihapus->id]);
        $this->assertDatabaseMissing('product_images', ['id' => $img->id]);

        // File fisik terhapus dari filesystem
        $this->assertFileDoesNotExist($dummyFilePath);
    }

    /**
     * 4. Penghapusan foto dibatasi per produk:
     *    Tidak bisa menghapus foto umum atau foto varian milik produk lain.
     */
    public function test_04_removing_photos_is_scoped_to_current_product(): void
    {
        $owner = $this->createOwner();

        $productA = $this->createProduct(['nama' => 'Produk A']);
        $productB = $this->createProduct(['nama' => 'Produk B']);

        $imgB = ProductImage::create([
            'product_id' => $productB->id,
            'product_variant_id' => null,
            'path' => 'assets/images/products/test_prod_b.jpg',
            'sort_order' => 0,
        ]);

        $vB = ProductVariant::create([
            'product_id' => $productB->id,
            'label' => 'Varian B',
            'sort_order' => 0,
        ]);
        $imgVarB = ProductImage::create([
            'product_id' => $productB->id,
            'product_variant_id' => $vB->id,
            'path' => 'assets/images/products/test_var_b.jpg',
            'sort_order' => 0,
        ]);

        // Update Produk A, mencoba menghapus gambar milik Produk B
        $response = $this->actingAs($owner)->put(route('owner.produk.update', $productA), [
            'nama' => $productA->nama,
            'brand' => $productA->brand,
            'kategori' => $productA->kategori,
            'harga_normal' => 50000,
            'stok' => 10,
            'hapus_galeri' => [$imgB->id],
            'hapus_foto_varian' => [$imgVarB->id],
        ]);

        $response->assertRedirect(route('owner.produk.index'));

        // Foto milik Produk B TIDAK boleh terhapus dari DB
        $this->assertDatabaseHas('product_images', ['id' => $imgB->id]);
        $this->assertDatabaseHas('product_images', ['id' => $imgVarB->id]);
    }

    /**
     * 5. File foto yang masih dipakai riwayat pesanan (order_items.image) tidak boleh ikut terhapus.
     */
    public function test_05_image_file_referenced_by_order_items_is_not_deleted(): void
    {
        $owner = $this->createOwner();

        $uploadDir = public_path('assets/images/products');
        if (!File::isDirectory($uploadDir)) {
            File::makeDirectory($uploadDir, 0755, true);
        }

        $orderedImagePath = 'assets/images/products/test_ordered_photo.jpg';
        $fullPath = public_path($orderedImagePath);
        file_put_contents($fullPath, 'order photo content');
        $this->assertFileExists($fullPath);

        $product = $this->createProduct(['image' => $orderedImagePath]);

        $customer = User::factory()->create();

        // Buat pesanan yang merujuk foto ini
        $order = Order::create([
            'order_code' => 'ORD-FASE3-' . uniqid(),
            'user_id' => $customer->id,
            'status' => Order::STATUS_MENUNGGU,
            'payment_status' => Order::PAYMENT_PENDING,
            'subtotal' => 50000,
            'shipping_cost' => 0,
            'total' => 50000,
            'shipping_method' => 'ambil',
            'payment_method' => 'qris',
            'buyer_nama' => 'Pembeli Uji',
            'buyer_wa' => '08123456789',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'nama' => $product->nama,
            'image' => $orderedImagePath,
            'harga' => 50000,
            'qty' => 1,
            'line_total' => 50000,
        ]);

        // Hapus produk
        $response = $this->actingAs($owner)->delete(route('owner.produk.destroy', $product));
        $response->assertRedirect(route('owner.produk.index'));

        // Produk terhapus dari DB
        $this->assertDatabaseMissing('products', ['id' => $product->id]);

        // TAPI file fisik gambar TETAP ADA karena riwayat pesanan masih merujuknya!
        $this->assertFileExists($fullPath);

        // Bersihkan file fisik di akhir tes
        @unlink($fullPath);
    }

    /**
     * 6. Validasi upload:
     *    - Menolak ekstensi selain jpg, jpeg, png, webp.
     *    - Menolak ukuran file > 2 MB.
     *    - Pesan error dalam bahasa Indonesia.
     */
    public function test_06_validation_rejects_unsupported_mimes_and_oversized_images(): void
    {
        $owner = $this->createOwner();

        // 1. Format tidak valid (misal text/pdf)
        $invalidFile = UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf');

        $res1 = $this->actingAs($owner)->post(route('owner.produk.store'), [
            'nama' => 'Produk Invalid Mime',
            'brand' => 'YK Beauty',
            'kategori' => 'skincare',
            'harga_normal' => 50000,
            'stok' => 10,
            'image' => $invalidFile,
        ]);
        $res1->assertSessionHasErrors(['image']);

        // 2. Ukuran > 2 MB (2500 KB)
        $oversizedFile = UploadedFile::fake()->image('besar.jpg')->size(2500);

        $res2 = $this->actingAs($owner)->post(route('owner.produk.store'), [
            'nama' => 'Produk Oversize',
            'brand' => 'YK Beauty',
            'kategori' => 'skincare',
            'harga_normal' => 50000,
            'stok' => 10,
            'image' => $oversizedFile,
        ]);
        $res2->assertSessionHasErrors(['image']);
    }

    /**
     * 7. Validasi batas maksimal 8 foto galeri per produk.
     */
    public function test_07_validation_rejects_more_than_8_gallery_images(): void
    {
        $owner = $this->createOwner();

        // Mengunggah 9 foto galeri sekaligus pada produk baru -> gagal
        $nineFiles = [];
        for ($i = 0; $i < 9; $i++) {
            $nineFiles[] = UploadedFile::fake()->image("galeri_{$i}.jpg");
        }

        $res1 = $this->actingAs($owner)->post(route('owner.produk.store'), [
            'nama' => 'Produk Banyak Galeri',
            'brand' => 'YK Beauty',
            'kategori' => 'skincare',
            'harga_normal' => 50000,
            'stok' => 10,
            'image' => UploadedFile::fake()->image('utama.jpg'),
            'galeri' => $nineFiles,
        ]);
        $res1->assertSessionHasErrors(['galeri']);

        // Produk sudah punya 6 foto galeri, mencoba menambah 3 lagi (6 + 3 = 9 > 8) -> gagal
        $product = $this->createProduct();
        for ($i = 0; $i < 6; $i++) {
            ProductImage::create([
                'product_id' => $product->id,
                'product_variant_id' => null,
                'path' => "assets/images/products/galeri_{$i}.jpg",
                'sort_order' => $i,
            ]);
        }

        $threeFiles = [
            UploadedFile::fake()->image('new_1.jpg'),
            UploadedFile::fake()->image('new_2.jpg'),
            UploadedFile::fake()->image('new_3.jpg'),
        ];

        $res2 = $this->actingAs($owner)->put(route('owner.produk.update', $product), [
            'nama' => $product->nama,
            'brand' => $product->brand,
            'kategori' => $product->kategori,
            'harga_normal' => 50000,
            'stok' => 10,
            'galeri' => $threeFiles,
        ]);
        $res2->assertSessionHasErrors(['galeri']);
    }

    /**
     * 8. Product::catalogArray menghasilkan 'galeri' dan 'varianGaleri' yang benar.
     */
    public function test_08_catalog_array_generates_correct_gallery_and_variant_gallery(): void
    {
        $product = $this->createProduct(['nama' => 'Lip Cream Matte']);

        // Galeri umum
        ProductImage::create([
            'product_id' => $product->id,
            'product_variant_id' => null,
            'path' => 'assets/images/products/lip_swatch.jpg',
            'sort_order' => 0,
        ]);

        // Varian dengan foto
        $v1 = ProductVariant::create(['product_id' => $product->id, 'label' => '01 Nude', 'sort_order' => 0]);
        ProductImage::create([
            'product_id' => $product->id,
            'product_variant_id' => $v1->id,
            'path' => 'assets/images/products/lip_01.jpg',
            'sort_order' => 0,
        ]);

        $catalog = Product::catalogArray();
        $item = $catalog->firstWhere('id', $product->slug);

        $this->assertNotNull($item);
        $this->assertCount(1, $item['galeri']);
        $this->assertStringContainsString('lip_swatch.jpg', $item['galeri'][0]);
        $this->assertSame(['01 Nude'], $item['varian']->all());
        $this->assertArrayHasKey('01 Nude', $item['varianGaleri']);
        $this->assertStringContainsString('lip_01.jpg', $item['varianGaleri']['01 Nude'][0]);
    }
}
