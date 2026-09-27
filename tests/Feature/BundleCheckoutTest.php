<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BundleCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_checkout_bundle_successfully()
    {
        $user = User::factory()->create();

        $product1 = Product::create(['slug' => 'p1', 'nama' => 'P1', 'stok' => 10, 'harga' => 10000, 'brand' => 'viva', 'kategori' => 'skincare', 'is_active' => true, 'image' => 'p1.png']);
        $product2 = Product::create(['slug' => 'p2', 'nama' => 'P2', 'stok' => 10, 'harga' => 10000, 'brand' => 'viva', 'kategori' => 'skincare', 'is_active' => true, 'image' => 'p2.png']);

        $collection = Collection::create([
            'title' => 'Paket Glowing',
            'slug' => 'paket-glowing',
            'harga' => 100000,
            'image' => 'test.png',
            'is_active' => true,
        ]);

        $collection->products()->sync([
            $product1->id => ['sort_order' => 1],
            $product2->id => ['sort_order' => 2],
        ]);

        $response = $this->actingAs($user)->postJson('/checkout', [
            'buyer' => [
                'nama' => 'Test User',
                'wa' => '081234567890',
                'alamat' => 'Jalan',
                'kota' => 'Kota',
                'kodepos' => '12345'
            ],
            'items' => [
                [
                    'type' => 'bundle',
                    'id' => $collection->slug,
                    'qty' => 2,
                ]
            ],
            'shipping' => 'kurir',
            'payment' => 'transfer',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'redirect']);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'total' => 200000 + config('shop.shipping_flat'),
        ]);

        $order = Order::first();
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'collection_id' => $collection->id,
            'qty' => 2,
        ]);

        $this->assertEquals(8, $product1->fresh()->stok);
        $this->assertEquals(8, $product2->fresh()->stok);
    }

    public function test_cannot_checkout_bundle_if_stock_insufficient()
    {
        $user = User::factory()->create();

        $product1 = Product::create(['slug' => 'p3', 'nama' => 'P3', 'stok' => 10, 'harga' => 10000, 'brand' => 'viva', 'kategori' => 'skincare', 'is_active' => true, 'image' => 'p3.png']);
        $product2 = Product::create(['slug' => 'p4', 'nama' => 'P4', 'stok' => 1, 'harga' => 10000, 'brand' => 'viva', 'kategori' => 'skincare', 'is_active' => true, 'image' => 'p4.png']); // Hanya 1 stok

        $collection = Collection::create([
            'title' => 'Paket Glowing',
            'slug' => 'paket-glowing',
            'harga' => 100000,
            'image' => 'test.png',
            'is_active' => true,
        ]);

        $collection->products()->sync([
            $product1->id => ['sort_order' => 1],
            $product2->id => ['sort_order' => 2],
        ]);

        $response = $this->actingAs($user)->postJson('/checkout', [
            'buyer' => [
                'nama' => 'Test User',
                'wa' => '081234567890',
                'alamat' => 'Jalan',
                'kota' => 'Kota',
                'kodepos' => '12345'
            ],
            'items' => [
                [
                    'type' => 'bundle',
                    'id' => $collection->slug,
                    'qty' => 2, // Minta 2, padahal stok produk 2 cuma 1
                ]
            ],
            'shipping' => 'kurir',
            'payment' => 'transfer',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['stok']);
                 
        $this->assertEquals(10, $product1->fresh()->stok);
        $this->assertEquals(1, $product2->fresh()->stok);
    }
}
