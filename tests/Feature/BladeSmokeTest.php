<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\Collection;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BladeSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function createProduct(): Product
    {
        return Product::create([
            'slug' => 'smoke-product',
            'nama' => 'Produk Smoke Test',
            'brand' => 'YK Beauty',
            'kategori' => 'skincare',
            'harga' => 50000,
            'stok' => 20,
            'image' => 'assets/images/test.png',
            'is_active' => true,
        ]);
    }

    private function createBundle(Product $product): Collection
    {
        $collection = Collection::create([
            'slug' => 'smoke-bundle',
            'title' => 'Paket Bundle Smoke Test',
            'description' => 'Deskripsi paket bundle untuk smoke test.',
            'image' => 'assets/images/bundle.png',
            'harga' => 45000,
            'is_active' => true,
        ]);

        $collection->products()->attach($product->id, ['sort_order' => 1]);

        return $collection;
    }

    private function createBlogPost(): BlogPost
    {
        return BlogPost::create([
            'slug' => 'smoke-artikel',
            'category' => 'Skincare Tips',
            'published_at' => now(),
            'image' => 'assets/images/blog.png',
            'title' => 'Tips Merawat Kulit',
            'excerpt' => 'Ringkasan artikel smoke test.',
            'body' => ['Paragraf 1 artikel.', 'Paragraf 2 artikel.'],
        ]);
    }

    private function createOwner(): User
    {
        return User::factory()->create([
            'role' => 'owner',
            'is_active' => true,
        ]);
    }

    private function createCustomer(): User
    {
        return User::factory()->create([
            'role' => 'customer',
            'is_active' => true,
        ]);
    }

    public function test_public_pages_render_without_errors(): void
    {
        $product = $this->createProduct();
        $bundle = $this->createBundle($product);
        $blog = $this->createBlogPost();

        // 1. Beranda (/)
        $this->get('/')
            ->assertOk();

        // 2. Produk (list & detail)
        $this->get('/produk')
            ->assertOk();
        $this->get("/produk/{$product->slug}")
            ->assertOk();

        // 3. Koleksi / Paket Bundling (list & detail)
        $this->get('/paket-bundling')
            ->assertOk();
        $this->get("/paket-bundling/{$bundle->slug}")
            ->assertOk();

        // 4. Blog (list & detail)
        $this->get('/blog')
            ->assertOk();
        $this->get("/blog/{$blog->slug}")
            ->assertOk();

        // 5. Tentang
        $this->get('/tentang')
            ->assertOk();

        // 6. Syarat & Ketentuan
        $this->get('/syarat-ketentuan')
            ->assertOk();

        // 7. Login
        $this->get('/login')
            ->assertOk();
    }

    public function test_owner_pages_render_without_errors(): void
    {
        $owner = $this->createOwner();
        $customer = $this->createCustomer();
        $product = $this->createProduct();

        $order = Order::create([
            'order_code' => Order::generateOrderCode(),
            'user_id' => $customer->id,
            'status' => Order::STATUS_DIPROSES,
            'payment_status' => Order::PAYMENT_PAID,
            'payment_method' => 'gopay',
            'subtotal' => 100000,
            'shipping_cost' => 15000,
            'total' => 115000,
            'shipping_method' => 'kurir',
            'buyer_nama' => $customer->name,
            'buyer_wa' => '08123456789',
            'buyer_alamat' => 'Jl. Smoke No. 1',
            'buyer_kota' => 'Yogyakarta',
            'buyer_kodepos' => '55281',
            'paid_at' => now(),
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'nama' => $product->nama,
            'image' => 'assets/images/test.png',
            'harga' => 50000,
            'qty' => 2,
            'line_total' => 100000,
        ]);

        Refund::create([
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'sumber' => Refund::SUMBER_PEMBELI,
            'alasan' => Refund::ALASAN_BATAL_SEBELUM_KIRIM,
            'status' => Refund::STATUS_DIAJUKAN,
            'nominal_barang' => 0,
            'nominal_ongkir' => 0,
        ]);

        $this->createBlogPost();

        // Halaman Owner:
        // 1. Dashboard
        $this->actingAs($owner)
            ->get('/owner/dashboard')
            ->assertOk();

        // 2. Produk
        $this->actingAs($owner)
            ->get('/owner/produk')
            ->assertOk();

        // 3. Transaksi
        $this->actingAs($owner)
            ->get('/owner/transaksi')
            ->assertOk();

        // 4. Laporan
        $this->actingAs($owner)
            ->get('/owner/laporan')
            ->assertOk();

        // 5. Pengguna
        $this->actingAs($owner)
            ->get('/owner/pengguna')
            ->assertOk();

        // 6. Refund
        $this->actingAs($owner)
            ->get('/owner/refund')
            ->assertOk();

        // 7. Artikel
        $this->actingAs($owner)
            ->get('/owner/artikel')
            ->assertOk();
    }
}
