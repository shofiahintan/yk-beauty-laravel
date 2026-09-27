<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function createCustomer(array $attrs = [])
    {
        return User::factory()->create(array_merge([
            'role' => 'customer',
            'is_active' => true,
        ], $attrs));
    }

    private function createProduct(array $attrs = [])
    {
        return Product::create(array_merge([
            'slug' => 'test-product-' . uniqid(),
            'nama' => 'Test Product',
            'brand' => 'TestBrand',
            'kategori' => 'skincare',
            'harga' => 50000,
            'harga_coret' => null,
            'image' => 'assets/images/test.png',
            'stok' => 10,
            'is_active' => true,
        ], $attrs));
    }

    private function validPayload(Product $product, array $overrides = [])
    {
        return array_merge([
            'buyer' => [
                'nama' => 'Test User',
                'wa' => '08123456789',
            ],
            'shipping' => 'ambil',
            'payment' => 'transfer',
            'items' => [
                ['id' => $product->slug, 'qty' => 1, 'varian' => null, 'ukuran' => null],
            ],
            // Field-field yang seharusnya diabaikan server:
            'subtotal' => 1,
            'shippingCost' => 0,
            'total' => 1,
        ], $overrides);
    }

    /** @test */
    public function harga_dimanipulasi_klien_tidak_berpengaruh()
    {
        $user = $this->createCustomer();
        $product = $this->createProduct(['harga' => 75000, 'stok' => 5]);

        $payload = $this->validPayload($product, [
            // Klien coba kirim harga 1 rupiah
            'items' => [
                [
                    'id' => $product->slug,
                    'qty' => 2,
                    'varian' => null,
                    'ukuran' => null,
                    'harga' => 1,
                    'nama' => 'Hacked Product',
                    'img' => 'hacked.jpg',
                ],
            ],
            'subtotal' => 2,
            'total' => 2,
        ]);

        $response = $this->actingAs($user)->postJson('/checkout', $payload);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        // Verifikasi: harga di DB harus dari products.harga (75000), bukan 1
        $order = Order::where('order_code', $response->json('order_id'))->first();
        $this->assertNotNull($order);
        $this->assertEquals(150000, $order->subtotal); // 75000 * 2
        $this->assertEquals(150000, $order->total);    // ambil di toko, shipping 0
        $this->assertEquals(150000, $response->json('total'));

        $item = $order->items()->first();
        $this->assertEquals(75000, $item->harga);
        $this->assertEquals('Test Product', $item->nama); // bukan 'Hacked Product'
    }

    /** @test */
    public function slug_tidak_dikenal_ditolak()
    {
        $user = $this->createCustomer();
        $product = $this->createProduct();

        $payload = $this->validPayload($product, [
            'items' => [
                ['id' => 'produk-tidak-ada-xyz', 'qty' => 1, 'varian' => null, 'ukuran' => null],
            ],
        ]);

        $response = $this->actingAs($user)->postJson('/checkout', $payload);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('items');
    }

    /** @test */
    public function produk_nonaktif_ditolak()
    {
        $user = $this->createCustomer();
        $product = $this->createProduct(['is_active' => false]);

        $payload = $this->validPayload($product);

        $response = $this->actingAs($user)->postJson('/checkout', $payload);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('items');
    }

    /** @test */
    public function stok_tidak_cukup_ditolak()
    {
        $user = $this->createCustomer();
        $product = $this->createProduct(['stok' => 2]);

        $payload = $this->validPayload($product, [
            'items' => [
                ['id' => $product->slug, 'qty' => 5, 'varian' => null, 'ukuran' => null],
            ],
        ]);

        $response = $this->actingAs($user)->postJson('/checkout', $payload);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('stok');
    }

    /** @test */
    public function user_lain_tidak_bisa_buka_halaman_sukses_pesanan_orang_lain()
    {
        $userA = $this->createCustomer();
        $userB = $this->createCustomer();
        $product = $this->createProduct();

        // User A membuat pesanan
        $payload = $this->validPayload($product);
        $response = $this->actingAs($userA)->postJson('/checkout', $payload);
        $response->assertOk();
        $orderCode = $response->json('order_id');

        // User B mencoba melihat pesanan user A
        $response = $this->actingAs($userB)->get('/payment-sukses?id=' . $orderCode);
        $response->assertOk();
        // Halaman tampil tapi dbOrders harus kosong (pesanan tidak ditampilkan)
        $response->assertViewHas('dbOrders', []);
    }


    /** @test */
    public function alamat_wajib_saat_shipping_kurir()
    {
        $user = $this->createCustomer();
        $product = $this->createProduct();

        $payload = $this->validPayload($product, [
            'shipping' => 'kurir',
            'buyer' => [
                'nama' => 'Test User',
                'wa' => '08123456789',
                'alamat' => '',
                'kota' => '',
                'kodepos' => '',
            ],
        ]);

        $response = $this->actingAs($user)->postJson('/checkout', $payload);
        $response->assertStatus(422);
    }

    /** @test */
    public function shipping_kurir_menambah_ongkir_flat()
    {
        $user = $this->createCustomer();
        $product = $this->createProduct(['harga' => 50000, 'stok' => 10]);

        $payload = $this->validPayload($product, [
            'shipping' => 'kurir',
            'buyer' => [
                'nama' => 'Test User',
                'wa' => '08123456789',
                'alamat' => 'Jl. Test No. 1',
                'kota' => 'Yogyakarta',
                'kodepos' => '55281',
            ],
        ]);

        $response = $this->actingAs($user)->postJson('/checkout', $payload);
        $response->assertOk();

        $order = Order::where('order_code', $response->json('order_id'))->first();
        $this->assertEquals(config('shop.shipping_flat'), $order->shipping_cost);
        $this->assertEquals(50000 + config('shop.shipping_flat'), $order->total);
    }

    /** @test */
    public function status_pesanan_baru_adalah_diproses()
    {
        $user = $this->createCustomer();
        $product = $this->createProduct();

        $response = $this->actingAs($user)->postJson('/checkout', $this->validPayload($product));
        $response->assertOk();

        $order = Order::where('order_code', $response->json('order_id'))->first();
        $this->assertEquals('diproses', $order->status);
    }

    /** @test */
    public function varian_yang_tidak_ada_ditolak()
    {
        $user = $this->createCustomer();
        $product = $this->createProduct();
        ProductVariant::create(['product_id' => $product->id, 'label' => 'Merah', 'sort_order' => 0]);
        ProductVariant::create(['product_id' => $product->id, 'label' => 'Biru', 'sort_order' => 1]);

        $payload = $this->validPayload($product, [
            'items' => [
                ['id' => $product->slug, 'qty' => 1, 'varian' => 'Hijau', 'ukuran' => null],
            ],
        ]);

        $response = $this->actingAs($user)->postJson('/checkout', $payload);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('items');
    }
}
