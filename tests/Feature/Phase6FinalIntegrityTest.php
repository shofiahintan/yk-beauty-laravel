<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class Phase6FinalIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function createSampleProduct(string $slug = 'sample-prod', int $harga = 50000, int $stok = 10, bool $isActive = true): Product
    {
        return Product::create([
            'slug' => $slug,
            'nama' => 'Produk ' . $slug,
            'brand' => 'YK Beauty',
            'kategori' => 'skincare',
            'harga' => $harga,
            'harga_normal' => $harga + 25000,
            'stok' => $stok,
            'is_active' => $isActive,
            'image' => 'assets/images/products/sample.png',
        ]);
    }

    private function createSampleBundle(string $slug = 'paket-lengkap', int $harga = 100000): Collection
    {
        $coll = Collection::create([
            'slug' => $slug,
            'title' => 'Paket Lengkap',
            'description' => 'Paket perawatan wajah komplit',
            'image' => 'assets/images/bundles/bundle.png',
            'harga' => $harga,
            'is_active' => true,
        ]);

        return $coll;
    }

    /**
     * Poin 1: Paket bundling dengan isi nonaktif atau stok 0 dianggap TIDAK tersedia:
     * tampil "Habis" dan tombol beli nonaktif di halaman paket dan katalog paket (checkout menolaknya).
     */
    public function test_bundling_package_with_inactive_or_zero_stock_product_is_considered_out_of_stock(): void
    {
        $p1 = $this->createSampleProduct('prod-1', 40000, 10, true);
        $p2 = $this->createSampleProduct('prod-2', 60000, 5, true);

        $bundle = $this->createSampleBundle('paket-duo', 90000);
        $bundle->products()->sync([
            $p1->id => ['sort_order' => 1],
            $p2->id => ['sort_order' => 2],
        ]);

        // Awal: semua aktif dan stok > 0
        $this->assertTrue($bundle->fresh()->isInStock());

        // 1. Ubah stok salah satu produk menjadi 0
        $p1->update(['stok' => 0]);
        $bundleFresh = $bundle->fresh(['products']);
        $this->assertFalse($bundleFresh->isInStock());

        // Cek Collection::catalogArray
        $catalog = Collection::catalogArray();
        $this->assertNotEmpty($catalog);
        $bundleCatalog = collect($catalog)->firstWhere('id', $bundle->slug);
        $this->assertNotNull($bundleCatalog);
        $this->assertFalse($bundleCatalog['inStock']);
        $this->assertSame(0, $bundleCatalog['stok']);

        // Cek halaman katalog paket (/paket-bundling)
        $resCatalog = $this->get('/paket-bundling');
        $resCatalog->assertOk();
        $resCatalog->assertSee('Habis');

        // Cek halaman detail paket (/paket-bundling/{slug})
        $resDetail = $this->get('/paket-bundling/' . $bundle->slug);
        $resDetail->assertOk();
        $resDetail->assertSee('Habis');
        $resDetail->assertSee('disabled', false);

        // Cek checkout menolaknya
        $user = User::factory()->create();
        $resCheckout = $this->actingAs($user)->postJson('/checkout', [
            'buyer' => [
                'nama' => 'Budi Santoso',
                'wa' => '08123456789',
                'alamat' => 'Jl. Kaliurang KM 5',
                'kota' => 'Yogyakarta',
                'kodepos' => '55281',
            ],
            'items' => [
                [
                    'id' => $bundle->slug,
                    'type' => 'bundle',
                    'qty' => 1,
                ],
            ],
        ]);
        $resCheckout->assertStatus(422);
        $resCheckout->assertJsonValidationErrors(['items']);

        // 2. Kembalikan stok, tapi nonaktifkan salah satu produk
        $p1->update(['stok' => 10]);
        $p2->update(['is_active' => false]);
        $bundleFresh2 = $bundle->fresh(['products']);
        $this->assertFalse($bundleFresh2->isInStock());

        // Checkout tetap menolak
        $resCheckout2 = $this->actingAs($user)->postJson('/checkout', [
            'buyer' => [
                'nama' => 'Budi Santoso',
                'wa' => '08123456789',
                'alamat' => 'Jl. Kaliurang KM 5',
                'kota' => 'Yogyakarta',
                'kodepos' => '55281',
            ],
            'items' => [
                [
                    'id' => $bundle->slug,
                    'type' => 'bundle',
                    'qty' => 1,
                ],
            ],
        ]);
        $resCheckout2->assertStatus(422);
        $resCheckout2->assertJsonValidationErrors(['items']);
    }

    /**
     * Poin 2: Perubahan stok oleh owner di Kelola Produk (tambah produk)
     * tercatat di stock_movements lewat Product::adjustStock sebagai "stok awal".
     */
    public function test_owner_product_creation_records_stok_awal_in_stock_movements(): void
    {
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
        ]);

        $testImagePath = public_path('assets/images/products');
        if (! File::isDirectory($testImagePath)) {
            File::makeDirectory($testImagePath, 0755, true);
        }

        $fakeImage = UploadedFile::fake()->image('new-prod.jpg', 300, 300);

        $response = $this->actingAs($owner)->post('/owner/produk', [
            'nama' => 'Produk Baru Eksklusif',
            'brand' => 'viva',
            'kategori' => 'skincare',
            'harga' => 75000,
            'harga_normal' => 100000,
            'stok' => 35,
            'is_active' => '1',
            'deskripsi' => 'Deskripsi produk baru eksklusif',
            'image' => $fakeImage,
        ]);

        $response->assertRedirect(route('owner.produk.index'));

        $product = Product::where('nama', 'Produk Baru Eksklusif')->first();
        $this->assertNotNull($product);
        $this->assertSame(35, (int) $product->stok);

        // Verifikasi pencatatan di stock_movements
        $movement = StockMovement::where('product_id', $product->id)->first();
        $this->assertNotNull($movement);
        $this->assertSame(35, (int) $movement->delta);
        $this->assertSame(35, (int) $movement->stok_after);
        $this->assertSame($owner->id, (int) $movement->user_id);

        if ($product->image && File::exists(public_path($product->image))) {
            File::delete(public_path($product->image));
        }
    }

    /**
     * Poin 2: Perubahan stok oleh owner di Kelola Produk (edit stok)
     * tercatat di stock_movements lewat Product::adjustStock sebagai "edit owner".
     */
    public function test_owner_product_update_records_edit_owner_in_stock_movements(): void
    {
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
        ]);

        $product = $this->createSampleProduct('prod-edit-stok', 50000, 20, true);

        // Update stok naik dari 20 ke 30 (+10)
        $response = $this->actingAs($owner)->put('/owner/produk/' . $product->id, [
            'nama' => $product->nama,
            'brand' => $product->brand,
            'kategori' => $product->kategori,
            'harga' => $product->harga,
            'harga_normal' => $product->harga_normal,
            'stok' => 30,
            'is_active' => '1',
            'deskripsi' => $product->deskripsi,
        ]);

        $response->assertRedirect(route('owner.produk.index'));
        $this->assertSame(30, (int) $product->fresh()->stok);

        $movement = StockMovement::where('product_id', $product->id)->latest('id')->first();
        $this->assertNotNull($movement);
        $this->assertSame(10, (int) $movement->delta);
        $this->assertSame(30, (int) $movement->stok_after);
        $this->assertSame('edit owner', $movement->alasan);
        $this->assertSame($owner->id, (int) $movement->user_id);

        // Update stok turun dari 30 ke 15 (-15)
        $this->actingAs($owner)->put('/owner/produk/' . $product->id, [
            'nama' => $product->nama,
            'brand' => $product->brand,
            'kategori' => $product->kategori,
            'harga' => $product->harga,
            'harga_normal' => $product->harga_normal,
            'stok' => 15,
            'is_active' => '1',
            'deskripsi' => $product->deskripsi,
        ]);

        $this->assertSame(15, (int) $product->fresh()->stok);
        $movement2 = StockMovement::where('product_id', $product->id)->latest('id')->first();
        $this->assertSame(-15, (int) $movement2->delta);
        $this->assertSame(15, (int) $movement2->stok_after);
        $this->assertSame('edit owner', $movement2->alasan);

        // Update tanpa mengubah stok: tidak boleh menambah record stock_movement
        $countBefore = StockMovement::where('product_id', $product->id)->count();
        $this->actingAs($owner)->put('/owner/produk/' . $product->id, [
            'nama' => $product->nama . ' Updated',
            'brand' => $product->brand,
            'kategori' => $product->kategori,
            'harga' => $product->harga,
            'harga_normal' => $product->harga_normal,
            'stok' => 15,
            'is_active' => '1',
            'deskripsi' => $product->deskripsi,
        ]);
        $countAfter = StockMovement::where('product_id', $product->id)->count();
        $this->assertSame($countBefore, $countAfter);
    }

    /**
     * Poin 3: users.kota dan users.kodepos masuk $fillable, disimpan oleh form profil,
     * dan dipakai sebagai isian awal form checkout.
     */
    public function test_user_profile_saves_kota_and_kodepos_and_prefills_checkout(): void
    {
        // 1. Verifikasi $fillable di User
        $userModel = new User();
        $this->assertContains('kota', $userModel->getFillable());
        $this->assertContains('kodepos', $userModel->getFillable());

        // 2. Mass assignment pembuatan user
        $user = User::create([
            'name' => 'Dewi Lestari',
            'email' => 'dewi@example.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_CUSTOMER,
            'whatsapp' => '08987654321',
            'alamat' => 'Jl. Malioboro No. 10',
            'kota' => 'Yogyakarta',
            'kodepos' => '55213',
            'is_active' => true,
        ]);
        $this->assertSame('Yogyakarta', $user->kota);
        $this->assertSame('55213', $user->kodepos);

        // 3. Update profil via AccountController
        $resUpdate = $this->actingAs($user)->postJson(route('akun.update'), [
            'nama' => 'Dewi Lestari M.',
            'email' => 'dewi.m@example.com',
            'wa' => '08987654321',
            'alamat' => 'Jl. Solo KM 9 No. 45',
            'kota' => 'Sleman',
            'kodepos' => '55282',
        ]);
        $resUpdate->assertOk();
        $resUpdate->assertJson([
            'success' => true,
            'user' => [
                'nama' => 'Dewi Lestari M.',
                'email' => 'dewi.m@example.com',
                'alamat' => 'Jl. Solo KM 9 No. 45',
                'kota' => 'Sleman',
                'kodepos' => '55282',
            ],
        ]);

        $userFresh = $user->fresh();
        $this->assertSame('Sleman', $userFresh->kota);
        $this->assertSame('55282', $userFresh->kodepos);

        // 4. Pastikan form checkout (/checkout) merender isian awal kota dan kodepos
        $resCheckoutView = $this->actingAs($userFresh)->get('/checkout');
        $resCheckoutView->assertOk();
        $resCheckoutView->assertSee('value="Sleman"', false);
        $resCheckoutView->assertSee('value="55282"', false);
        $resCheckoutView->assertSee('kota: "Sleman"', false);
        $resCheckoutView->assertSee('kodepos: "55282"', false);
    }
}
