<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PricingDiscountFase2Test extends TestCase
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
            'nama' => 'Produk Uji',
            'brand' => 'Brand Uji',
            'kategori' => 'skincare',
            'harga' => 50000,
            'harga_normal' => 50000,
            'diskon_persen' => null,
            'image' => 'assets/images/test.png',
            'stok' => 10,
            'is_active' => true,
        ], $attrs));
    }

    private function createCollection(array $attrs = []): Collection
    {
        return Collection::create(array_merge([
            'slug' => 'bundle-' . uniqid(),
            'title' => 'Paket Uji',
            'description' => 'Deskripsi paket uji',
            'harga' => 80000,
            'image' => 'assets/images/test.jpg',
            'is_active' => true,
        ], $attrs));
    }

    public function test_01_selling_price_formula_and_nearest_100_rounding(): void
    {
        // Diskon 0 / null -> harga normal
        $this->assertSame(100000, Product::calculateSellingPrice(100000, 0));
        $this->assertSame(100000, Product::calculateSellingPrice(100000, null));

        // Diskon 10% dari 100.000 -> 90.000
        $this->assertSame(90000, Product::calculateSellingPrice(100000, 10));

        // Diskon 15% dari 99.999 = 84999.15 -> dibulatkan ke ratusan terdekat = 85.000
        $this->assertSame(85000, Product::calculateSellingPrice(99999, 15));

        // Dibulatkan ke bawah jika sisa < 50
        // 50140 * 0.9 = 45126 -> 45100
        $this->assertSame(45100, Product::calculateSellingPrice(50140, 10));

        // Dibulatkan ke atas jika sisa >= 50
        // 50180 * 0.9 = 45162 -> 45200
        $this->assertSame(45200, Product::calculateSellingPrice(50180, 10));
    }

    public function test_02_product_coret_accessor_behavior(): void
    {
        $prodTanpaDiskon = $this->createProduct([
            'harga' => 75000,
            'harga_normal' => 75000,
            'diskon_persen' => 0,
        ]);
        $this->assertNull($prodTanpaDiskon->coret);

        $prodDenganDiskon = $this->createProduct([
            'harga' => 68400,
            'harga_normal' => 76000,
            'diskon_persen' => 10,
        ]);
        $this->assertSame(76000, $prodDenganDiskon->coret);
    }

    public function test_03_bundle_coret_and_hemat_accessors(): void
    {
        $p1 = $this->createProduct(['nama' => 'P1', 'harga' => 60000]);
        $p2 = $this->createProduct(['nama' => 'P2', 'harga' => 40000]);

        $bundle = $this->createCollection([
            'slug' => 'paket-glowing-uji',
            'title' => 'Paket Glowing Uji',
            'harga' => 80000,
        ]);

        $bundle->products()->attach([$p1->id, $p2->id]);

        // Coret = total harga produk satuan (60.000 + 40.000 = 100.000)
        $this->assertSame(100000, $bundle->coret);

        // Hemat = coret - harga paket = 100.000 - 80.000 = 20.000
        $this->assertSame(20000, $bundle->hemat);

        // Hemat persen = (20.000 / 100.000) * 100 = 20%
        $this->assertSame(20, $bundle->hemat_persen);
    }

    public function test_04_owner_product_store_calculates_selling_price_and_coret(): void
    {
        $owner = $this->createOwner();

        $image = UploadedFile::fake()->image('produk-fase2.jpg');

        $response = $this->actingAs($owner)->post(route('owner.produk.store'), [
            'nama' => 'Serum Vitamin C Fase 2',
            'brand' => 'YK Beauty',
            'kategori' => 'skincare',
            'harga_normal' => 100000,
            'diskon_persen' => 25,
            'stok' => 20,
            'image' => $image,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('owner.produk.index'));

        $product = Product::where('nama', 'Serum Vitamin C Fase 2')->first();
        $this->assertNotNull($product);
        $this->assertSame(100000, (int) $product->harga_normal);
        $this->assertSame(25, (int) $product->diskon_persen);
        $this->assertSame(75000, (int) $product->harga);
        $this->assertSame(100000, $product->coret);

        // Clean up uploaded image if created
        if ($product->image && File::exists(public_path($product->image))) {
            @unlink(public_path($product->image));
        }
    }

    public function test_05_owner_product_validation_requires_positive_harga_normal_and_diskon_range(): void
    {
        $owner = $this->createOwner();

        // Harga normal kosong / 0
        $response = $this->actingAs($owner)->post(route('owner.produk.store'), [
            'nama' => 'Produk Invalid Harga',
            'brand' => 'YK Beauty',
            'kategori' => 'skincare',
            'harga_normal' => 0,
            'diskon_persen' => 10,
            'stok' => 10,
        ]);
        $response->assertSessionHasErrors(['harga_normal']);

        // Diskon persen > 90%
        $response2 = $this->actingAs($owner)->post(route('owner.produk.store'), [
            'nama' => 'Produk Invalid Diskon',
            'brand' => 'YK Beauty',
            'kategori' => 'skincare',
            'harga_normal' => 50000,
            'diskon_persen' => 95,
            'stok' => 10,
        ]);
        $response2->assertSessionHasErrors(['diskon_persen']);
    }

    public function test_06_owner_bundle_validation_requires_price_strictly_less_than_product_sum(): void
    {
        $owner = $this->createOwner();

        $p1 = $this->createProduct(['harga' => 50000]);
        $p2 = $this->createProduct(['harga' => 50000]);

        $image = UploadedFile::fake()->image('bundle-cover.jpg');

        // Total produk = 100.000. Harga paket = 100.000 (tidak boleh sama dengan total satuan)
        $responseEqual = $this->actingAs($owner)->post(route('owner.koleksi.store'), [
            'title' => 'Paket Tidak Hemat',
            'harga' => 100000,
            'produk' => [$p1->id, $p2->id],
            'image' => $image,
        ]);
        $responseEqual->assertSessionHasErrors(['harga']);

        // Harga paket = 105.000 (lebih mahal dari total satuan)
        $responseExpensive = $this->actingAs($owner)->post(route('owner.koleksi.store'), [
            'title' => 'Paket Lebih Mahal',
            'harga' => 105000,
            'produk' => [$p1->id, $p2->id],
            'image' => $image,
        ]);
        $responseExpensive->assertSessionHasErrors(['harga']);

        // Harga paket = 85.000 (hemat 15.000 -> valid)
        $responseValid = $this->actingAs($owner)->post(route('owner.koleksi.store'), [
            'title' => 'Paket Hemat Berhasil',
            'harga' => 85000,
            'produk' => [$p1->id, $p2->id],
            'image' => $image,
        ]);
        $responseValid->assertRedirect(route('owner.koleksi.index'));

        $bundle = Collection::where('title', 'Paket Hemat Berhasil')->first();
        $this->assertNotNull($bundle);
        $this->assertSame(85000, (int) $bundle->harga);
        $this->assertSame(100000, $bundle->coret);
        $this->assertSame(15000, $bundle->hemat);
        $this->assertSame(15, $bundle->hemat_persen);

        if ($bundle->image && File::exists(public_path($bundle->image))) {
            @unlink(public_path($bundle->image));
        }
    }

    public function test_07_public_koleksi_views_render_dynamic_coret_and_hemat(): void
    {
        $p1 = $this->createProduct(['nama' => 'Krim Pagi', 'harga' => 70000]);
        $p2 = $this->createProduct(['nama' => 'Krim Malam', 'harga' => 80000]);

        $bundle = $this->createCollection([
            'slug' => 'paket-duo-skincare',
            'title' => 'Paket Duo Skincare',
            'description' => 'Paket dua krim',
            'harga' => 120000,
            'is_active' => true,
        ]);
        $bundle->products()->attach([$p1->id, $p2->id]);

        // Detail page: should show coret = 150.000, hemat = 30.000 (20%)
        $responseDetail = $this->get(route('koleksi.detail', $bundle->slug));
        $responseDetail->assertOk();
        $responseDetail->assertSee('Paket Duo Skincare');
        $responseDetail->assertSee(formatRupiah(120000));
        $responseDetail->assertSee(formatRupiah(150000));
        $responseDetail->assertSee(formatRupiah(30000));
        $responseDetail->assertSee('20%');

        // Listing page
        $responseIndex = $this->get(route('koleksi'));
        $responseIndex->assertOk();
        $responseIndex->assertSee('Paket Duo Skincare');
        $responseIndex->assertSee(formatRupiah(30000));
    }
}
