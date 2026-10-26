<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Collection;
use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartWishlistDatabaseFase4Test extends TestCase
{
    use RefreshDatabase;

    private function createSampleProduct(string $slug = 'serum-brightening', int $harga = 50000, int $stok = 20): Product
    {
        return Product::create([
            'slug' => $slug,
            'nama' => 'Sample ' . $slug,
            'brand' => 'YK Beauty',
            'kategori' => 'skincare',
            'harga' => $harga,
            'harga_normal' => $harga + 20000,
            'stok' => $stok,
            'is_active' => true,
            'image' => 'products/sample.png',
        ]);
    }

    private function createSampleBundle(string $slug = 'paket-glowing', int $harga = 120000): Collection
    {
        $p1 = $this->createSampleProduct('bundle-p1', 40000, 10);
        $p2 = $this->createSampleProduct('bundle-p2', 50000, 10);

        $collection = Collection::create([
            'title' => 'Paket Glowing',
            'slug' => $slug,
            'harga' => $harga,
            'image' => 'collections/bundle.png',
            'is_active' => true,
        ]);

        $collection->products()->sync([
            $p1->id => ['sort_order' => 1],
            $p2->id => ['sort_order' => 2],
        ]);

        return $collection;
    }

    public function test_user_can_add_item_to_database_cart(): void
    {
        $user = User::factory()->create();
        $product = $this->createSampleProduct();

        $response = $this->actingAs($user)->postJson('/cart/add', [
            'id' => $product->slug,
            'qty' => 2,
            'varian' => 'Rose',
            'ukuran' => '30ml',
            'type' => 'product',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('cart.0.id', $product->slug)
            ->assertJsonPath('cart.0.qty', 2)
            ->assertJsonPath('cart.0.varian', 'Rose')
            ->assertJsonPath('cart.0.ukuran', '30ml')
            ->assertJsonPath('cart.0.type', 'product');

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'collection_id' => null,
            'qty' => 2,
            'varian' => 'Rose',
            'ukuran' => '30ml',
        ]);
    }

    public function test_user_can_update_qty_in_database_cart(): void
    {
        $user = User::factory()->create();
        $product = $this->createSampleProduct();

        $lineKey = CartItem::generateLineKey('product', $product->id, 'Rose', '30ml');
        $cartItem = CartItem::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'qty' => 2,
            'varian' => 'Rose',
            'ukuran' => '30ml',
            'line_key' => $lineKey,
        ]);

        $response = $this->actingAs($user)->postJson('/cart/set-qty', [
            'line_key' => $lineKey,
            'qty' => 5,
        ]);

        $response->assertOk()
            ->assertJsonPath('cart.0.qty', 5);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'qty' => 5,
        ]);
    }

    public function test_setting_qty_outside_1_to_99_is_rejected(): void
    {
        $user = User::factory()->create();
        $product = $this->createSampleProduct();

        $lineKey = CartItem::generateLineKey('product', $product->id, null, null);
        CartItem::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'qty' => 2,
            'line_key' => $lineKey,
        ]);

        // Qty 0 ditolak
        $response0 = $this->actingAs($user)->postJson('/cart/set-qty', [
            'line_key' => $lineKey,
            'qty' => 0,
        ]);
        $response0->assertStatus(422)
            ->assertJsonValidationErrors('qty');

        // Qty 100 ditolak
        $response100 = $this->actingAs($user)->postJson('/cart/set-qty', [
            'line_key' => $lineKey,
            'qty' => 100,
        ]);
        $response100->assertStatus(422)
            ->assertJsonValidationErrors('qty');
    }

    public function test_user_can_remove_item_from_database_cart(): void
    {
        $user = User::factory()->create();
        $product = $this->createSampleProduct();

        $lineKey = CartItem::generateLineKey('product', $product->id, 'Lavender', null);
        $cartItem = CartItem::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'qty' => 1,
            'varian' => 'Lavender',
            'line_key' => $lineKey,
        ]);

        $response = $this->actingAs($user)->postJson('/cart/remove', [
            'line_key' => $lineKey,
        ]);

        $response->assertOk()
            ->assertJsonPath('cart', []);

        $this->assertDatabaseMissing('cart_items', [
            'id' => $cartItem->id,
        ]);
    }

    public function test_duplicate_items_are_merged_in_database_cart(): void
    {
        $user = User::factory()->create();
        $product = $this->createSampleProduct();

        // Tambah pertama kali (qty: 2)
        $this->actingAs($user)->postJson('/cart/add', [
            'id' => $product->slug,
            'qty' => 2,
            'varian' => 'Rose',
            'ukuran' => '50ml',
            'type' => 'product',
        ])->assertOk();

        // Tambah kedua kali dengan varian dan ukuran sama (qty: 3)
        $response = $this->actingAs($user)->postJson('/cart/add', [
            'id' => $product->slug,
            'qty' => 3,
            'varian' => 'Rose',
            'ukuran' => '50ml',
            'type' => 'product',
        ]);

        $response->assertOk()
            ->assertJsonCount(1, 'cart')
            ->assertJsonPath('cart.0.qty', 5);

        // Hanya boleh ada 1 baris di tabel cart_items dengan qty = 5
        $this->assertEquals(1, CartItem::where('user_id', $user->id)->count());
        $this->assertEquals(5, CartItem::where('user_id', $user->id)->first()->qty);
    }

    public function test_bundling_package_can_be_added_to_database_cart(): void
    {
        $user = User::factory()->create();
        $bundle = $this->createSampleBundle();

        $response = $this->actingAs($user)->postJson('/cart/add', [
            'id' => $bundle->slug,
            'qty' => 2,
            'type' => 'bundle',
        ]);

        $response->assertOk()
            ->assertJsonPath('cart.0.id', $bundle->slug)
            ->assertJsonPath('cart.0.qty', 2)
            ->assertJsonPath('cart.0.type', 'bundle');

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_id' => null,
            'collection_id' => $bundle->id,
            'qty' => 2,
        ]);

        $cartItem = CartItem::where('user_id', $user->id)->first();
        $this->assertEquals(2 * $bundle->harga, $cartItem->lineTotal());
    }

    public function test_duplicate_bundles_are_merged_in_database_cart(): void
    {
        $user = User::factory()->create();
        $bundle = $this->createSampleBundle();

        // Tambah bundle pertama
        $this->actingAs($user)->postJson('/cart/add', [
            'id' => $bundle->slug,
            'qty' => 1,
            'type' => 'bundle',
        ])->assertOk();

        // Tambah bundle kedua
        $response = $this->actingAs($user)->postJson('/cart/add', [
            'id' => $bundle->slug,
            'qty' => 2,
            'type' => 'bundle',
        ]);

        $response->assertOk()
            ->assertJsonCount(1, 'cart')
            ->assertJsonPath('cart.0.qty', 3);

        $this->assertEquals(1, CartItem::where('user_id', $user->id)->count());
        $this->assertEquals(3, CartItem::where('user_id', $user->id)->first()->qty);
    }

    public function test_guests_are_redirected_to_login_on_cart_wishlist_and_checkout_pages(): void
    {
        $this->get('/keranjang')->assertRedirect(route('login'));
        $this->get('/wishlist')->assertRedirect(route('login'));
        $this->get('/checkout')->assertRedirect(route('login'));
    }

    public function test_guest_ajax_requests_receive_401_unauthorized_on_cart_wishlist_and_checkout(): void
    {
        $this->getJson('/keranjang')->assertUnauthorized();
        $this->getJson('/wishlist')->assertUnauthorized();
        $this->getJson('/checkout')->assertUnauthorized();
        $this->postJson('/checkout', [
            'buyer' => ['nama' => 'Guest'],
            'items' => [['id' => 'sample', 'qty' => 1]],
        ])->assertUnauthorized();
    }

    public function test_login_redirect_accepts_only_internal_paths_preventing_open_redirect(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        // Path internal valid: harus diterima dan di-redirect ke path tersebut
        $validInternalPaths = [
            '/keranjang',
            '/wishlist',
            '/checkout',
            '/produk/serum-brightening?tab=ulasan',
            '/paket-bundling/paket-glowing',
        ];

        foreach ($validInternalPaths as $path) {
            $response = $this->post('/login', [
                'email' => $user->email,
                'password' => 'password123',
                'redirect' => $path,
            ]);
            $response->assertRedirect($path);
            auth()->logout();
        }

        // Open redirect berbahaya: harus ditolak dan dialihkan ke HOME default
        $maliciousRedirects = [
            'https://evil.com',
            'http://attacker.com/steal-cookie',
            '//evil.com',
            '//evil.com/login',
            '/\\evil.com',
            '/\evil.com',
            'javascript:alert(1)',
        ];

        foreach ($maliciousRedirects as $badUrl) {
            $response = $this->post('/login', [
                'email' => $user->email,
                'password' => 'password123',
                'redirect' => $badUrl,
            ]);
            $response->assertRedirect(\App\Providers\RouteServiceProvider::HOME);
            auth()->logout();
        }
    }

    public function test_authenticated_buyer_is_not_affected_and_can_access_cart_wishlist_and_checkout(): void
    {
        $user = User::factory()->create();
        $product = $this->createSampleProduct('serum-brightening', 50000);

        // Buyer yang login dapat membuka halaman keranjang, wishlist, dan checkout
        $this->actingAs($user)->get('/keranjang')->assertOk();
        $this->actingAs($user)->get('/wishlist')->assertOk();
        $this->actingAs($user)->get('/checkout')->assertOk();

        // Operasi cart & wishlist berjalan normal
        $this->actingAs($user)->postJson('/cart/add', [
            'id' => $product->slug,
            'qty' => 1,
            'type' => 'product',
        ])->assertOk();

        $this->actingAs($user)->postJson('/wishlist/toggle', [
            'id' => $product->slug,
        ])->assertOk();

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'qty' => 1,
        ]);

        $this->assertDatabaseHas('wishlists', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_cart_and_wishlist_user_a_is_isolated_and_invisible_to_user_b(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $productA = $this->createSampleProduct('product-a', 60000);
        $productB = $this->createSampleProduct('product-b', 70000);

        // User A menambahkan product-a
        $this->actingAs($userA)->postJson('/cart/add', [
            'id' => $productA->slug,
            'qty' => 2,
            'type' => 'product',
        ])->assertOk();

        // User B menambahkan product-b
        $this->actingAs($userB)->postJson('/cart/add', [
            'id' => $productB->slug,
            'qty' => 4,
            'type' => 'product',
        ])->assertOk();

        // Ambil keranjang User A: hanya ada product-a
        $respA = $this->actingAs($userA)->getJson('/cart');
        $respA->assertOk()
            ->assertJsonCount(1, 'cart')
            ->assertJsonPath('cart.0.id', 'product-a')
            ->assertJsonPath('cart.0.qty', 2);

        // Ambil keranjang User B: hanya ada product-b
        $respB = $this->actingAs($userB)->getJson('/cart');
        $respB->assertOk()
            ->assertJsonCount(1, 'cart')
            ->assertJsonPath('cart.0.id', 'product-b')
            ->assertJsonPath('cart.0.qty', 4);

        // User B mencoba menghapus item User A dengan line_key milik user A
        $lineKeyA = CartItem::generateLineKey('product', $productA->id, null, null);
        $this->actingAs($userB)->postJson('/cart/remove', [
            'line_key' => $lineKeyA,
        ])->assertOk();

        // Keranjang User A tetap utuh
        $this->assertDatabaseHas('cart_items', [
            'user_id' => $userA->id,
            'product_id' => $productA->id,
            'qty' => 2,
        ]);

        // Wishlist isolasi
        Wishlist::create(['user_id' => $userA->id, 'product_id' => $productA->id]);
        Wishlist::create(['user_id' => $userB->id, 'product_id' => $productB->id]);

        $wishA = $this->actingAs($userA)->getJson('/wishlist-items');
        $wishA->assertOk()->assertExactJson(['success' => true, 'wishlist' => ['product-a']]);

        $wishB = $this->actingAs($userB)->getJson('/wishlist-items');
        $wishB->assertOk()->assertExactJson(['success' => true, 'wishlist' => ['product-b']]);
    }

    public function test_database_cart_is_cleared_after_successful_checkout(): void
    {
        $user = User::factory()->create();
        $product = $this->createSampleProduct('serum-checkout', 80000, 10);

        // Masukkan item ke database cart user
        $lineKey = CartItem::generateLineKey('product', $product->id, null, null);
        CartItem::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'qty' => 2,
            'line_key' => $lineKey,
        ]);

        $this->assertEquals(1, CartItem::where('user_id', $user->id)->count());

        // Lakukan checkout
        $response = $this->actingAs($user)->postJson('/checkout', [
            'buyer' => [
                'nama' => 'Pembeli Terdaftar',
                'wa' => '081299998888',
                'alamat' => 'Jl. Mawar No. 10',
                'kota' => 'Yogyakarta',
                'kodepos' => '55281',
            ],
            'items' => [
                [
                    'id' => $product->slug,
                    'qty' => 2,
                    'type' => 'product',
                ],
            ],
            'shipping' => 'kurir',
            'payment' => 'midtrans',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        // Pastikan keranjang database user sudah dikosongkan setelah checkout berhasil
        $this->assertEquals(0, CartItem::where('user_id', $user->id)->count());
    }

    public function test_user_can_toggle_and_remove_wishlist_in_database(): void
    {
        $user = User::factory()->create();
        $product = $this->createSampleProduct();

        // Toggle 1: simpan
        $res1 = $this->actingAs($user)->postJson('/wishlist/toggle', ['id' => $product->slug]);
        $res1->assertOk()
            ->assertJsonPath('saved', true)
            ->assertJsonPath('wishlist.0', $product->slug);
        $this->assertDatabaseHas('wishlists', ['user_id' => $user->id, 'product_id' => $product->id]);

        // Toggle 2: hapus
        $res2 = $this->actingAs($user)->postJson('/wishlist/toggle', ['id' => $product->slug]);
        $res2->assertOk()
            ->assertJsonPath('saved', false)
            ->assertJsonPath('wishlist', []);
        $this->assertDatabaseMissing('wishlists', ['user_id' => $user->id, 'product_id' => $product->id]);

        // Add back and use remove endpoint
        $this->actingAs($user)->postJson('/wishlist/toggle', ['id' => $product->slug]);
        $this->assertDatabaseHas('wishlists', ['user_id' => $user->id, 'product_id' => $product->id]);

        $resRemove = $this->actingAs($user)->postJson('/wishlist/remove', ['id' => $product->slug]);
        $resRemove->assertOk()->assertJsonPath('wishlist', []);
        $this->assertDatabaseMissing('wishlists', ['user_id' => $user->id, 'product_id' => $product->id]);
    }

    public function test_validation_rejects_inactive_or_unknown_product_on_cart_and_wishlist(): void
    {
        $user = User::factory()->create();
        $inactiveProduct = Product::create([
            'slug' => 'produk-nonaktif',
            'nama' => 'Produk Nonaktif',
            'brand' => 'YK',
            'kategori' => 'skincare',
            'harga' => 30000,
            'stok' => 5,
            'is_active' => false,
            'image' => 'products/sample.png',
        ]);

        // Tambah ke cart dengan slug tidak dikenal -> 422
        $resUnknown = $this->actingAs($user)->postJson('/cart/add', [
            'id' => 'slug-ngawur-tidak-ada',
            'qty' => 1,
            'type' => 'product',
        ]);
        $resUnknown->assertStatus(422)->assertJsonValidationErrors('id');

        // Tambah ke cart dengan produk nonaktif -> 422
        $resInactive = $this->actingAs($user)->postJson('/cart/add', [
            'id' => $inactiveProduct->slug,
            'qty' => 1,
            'type' => 'product',
        ]);
        $resInactive->assertStatus(422)->assertJsonValidationErrors('id');

        // Wishlist dengan produk nonaktif -> 422
        $resWishInactive = $this->actingAs($user)->postJson('/wishlist/toggle', [
            'id' => $inactiveProduct->slug,
        ]);
        $resWishInactive->assertStatus(422)->assertJsonValidationErrors('id');
    }

    public function test_validation_rejects_quantity_outside_1_to_99(): void
    {
        $user = User::factory()->create();
        $product = $this->createSampleProduct();

        // Qty 0 ditolak
        $this->actingAs($user)->postJson('/cart/add', [
            'id' => $product->slug,
            'qty' => 0,
            'type' => 'product',
        ])->assertStatus(422)->assertJsonValidationErrors('qty');

        // Qty 100 ditolak
        $this->actingAs($user)->postJson('/cart/add', [
            'id' => $product->slug,
            'qty' => 100,
            'type' => 'product',
        ])->assertStatus(422)->assertJsonValidationErrors('qty');

        // Qty negatif ditolak
        $this->actingAs($user)->postJson('/cart/add', [
            'id' => $product->slug,
            'qty' => -5,
            'type' => 'product',
        ])->assertStatus(422)->assertJsonValidationErrors('qty');
    }

    public function test_validation_rejects_invalid_variant(): void
    {
        $user = User::factory()->create();
        $product = $this->createSampleProduct();
        $product->variants()->create([
            'label' => 'Original',
            'sort_order' => 1,
        ]);

        // Varian yang tidak terdaftar ditolak
        $response = $this->actingAs($user)->postJson('/cart/add', [
            'id' => $product->slug,
            'qty' => 1,
            'varian' => 'Varian Palsu',
            'type' => 'product',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('varian');
    }

    public function test_validation_rejects_inactive_bundle(): void
    {
        $user = User::factory()->create();
        $bundle = Collection::create([
            'title' => 'Paket Mati',
            'slug' => 'paket-mati',
            'harga' => 50000,
            'is_active' => false,
            'image' => 'collections/bundle.png',
        ]);

        $response = $this->actingAs($user)->postJson('/cart/add', [
            'id' => $bundle->slug,
            'qty' => 1,
            'type' => 'bundle',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('id');
    }

    public function test_cart_and_wishlist_endpoints_reject_guests(): void
    {
        // Semua endpoint cart & wishlist harus menolak tamu yang belum login (401 Unauthorized)
        $this->postJson('/cart/add', ['id' => 'sample', 'qty' => 1])->assertUnauthorized();
        $this->postJson('/cart/set-qty', ['id' => 'sample', 'qty' => 2])->assertUnauthorized();
        $this->postJson('/cart/remove', ['id' => 'sample'])->assertUnauthorized();
        $this->postJson('/cart/clear')->assertUnauthorized();
        $this->postJson('/cart/sync', ['items' => []])->assertUnauthorized();
        $this->getJson('/cart')->assertUnauthorized();

        $this->postJson('/wishlist/toggle', ['id' => 'sample'])->assertUnauthorized();
        $this->postJson('/wishlist/remove', ['id' => 'sample'])->assertUnauthorized();
        $this->getJson('/wishlist-items')->assertUnauthorized();
    }
}
