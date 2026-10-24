<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Support\OrderLifecycle;
use App\Support\ProductSalesReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OrderLifecyclePhase1Test extends TestCase
{
    use RefreshDatabase;

    private function createCustomer(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'customer',
            'is_active' => true,
        ], $attrs));
    }

    private function createOwner(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'owner',
            'is_active' => true,
        ], $attrs));
    }

    private function createProduct(array $attrs = []): Product
    {
        return Product::create(array_merge([
            'slug' => 'product-' . uniqid(),
            'nama' => 'Produk Uji',
            'brand' => 'Brand Uji',
            'kategori' => 'skincare',
            'harga' => 50000,
            'image' => 'assets/images/test.png',
            'stok' => 10,
            'is_active' => true,
        ], $attrs));
    }

    /**
     * 1. Checkout membuat pesanan berstatus menunggu_pembayaran dengan payment_status pending,
     *    stok berkurang, dan tercatat di riwayat status dan pergerakan stok.
     */
    public function test_01_checkout_creates_pending_order_and_records_history_and_stock_movement(): void
    {
        $user = $this->createCustomer();
        $product = $this->createProduct(['stok' => 10, 'harga' => 50000]);

        $payload = [
            'buyer' => [
                'nama' => 'Budi Santoso',
                'wa' => '081234567890',
                'alamat' => 'Jl. Kaliurang KM 5',
                'kota' => 'Sleman',
                'kodepos' => '55281',
            ],
            'shipping' => 'kurir',
            'payment' => 'transfer',
            'items' => [
                ['id' => $product->slug, 'qty' => 2, 'varian' => null, 'ukuran' => null],
            ],
        ];

        $response = $this->actingAs($user)->postJson('/checkout', $payload);
        $response->assertOk();
        $response->assertJsonPath('success', true);

        $orderCode = $response->json('order_id');
        $order = Order::where('order_code', $orderCode)->first();
        $this->assertNotNull($order);

        // Status pesanan dan pembayaran
        $this->assertEquals(Order::STATUS_MENUNGGU, $order->status);
        $this->assertEquals(Order::PAYMENT_PENDING, $order->payment_status);

        // Stok berkurang
        $product->refresh();
        $this->assertEquals(8, $product->stok);

        // Tercatat di riwayat status
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'status' => Order::STATUS_MENUNGGU,
        ]);

        // Tercatat di pergerakan stok (stock_movements)
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'order_id' => $order->id,
            'delta' => -2,
            'stok_after' => 8,
        ]);
    }

    /**
     * 2. Webhook settlement (atau capture dengan fraud accept): payment_status paid, paid_at terisi,
     *    status jadi diproses. Dikirim dua kali tidak mengubah apa pun dan tidak membuat riwayat ganda.
     */
    public function test_02_webhook_settlement_and_capture_accept_idempotent(): void
    {
        $user = $this->createCustomer();
        $order = Order::create([
            'order_code' => Order::generateOrderCode(),
            'user_id' => $user->id,
            'status' => Order::STATUS_MENUNGGU,
            'subtotal' => 50000,
            'shipping_cost' => 15000,
            'total' => 65000,
            'shipping_method' => 'kurir',
            'payment_method' => 'midtrans',
            'payment_status' => Order::PAYMENT_PENDING,
            'buyer_nama' => 'Budi',
            'buyer_wa' => '0812345678',
            'buyer_alamat' => 'Alamat',
            'buyer_kota' => 'Kota',
            'buyer_kodepos' => '12345',
        ]);
        OrderLifecycle::logCreated($order);

        // Kiriman webhook settlement pertama
        $res1 = $this->postJson('/midtrans/notification', [
            'order_id' => $order->order_code,
            'transaction_status' => 'settlement',
            'payment_type' => 'qris',
        ]);
        $res1->assertOk();

        $order->refresh();
        $this->assertEquals(Order::PAYMENT_PAID, $order->payment_status);
        $this->assertEquals(Order::STATUS_DIPROSES, $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertEquals('qris', $order->payment_method);

        $diprosesHistoriesCount = OrderStatusHistory::where('order_id', $order->id)
            ->where('status', Order::STATUS_DIPROSES)
            ->count();
        $this->assertEquals(1, $diprosesHistoriesCount);

        // Kiriman webhook settlement kedua (duplikat)
        $res2 = $this->postJson('/midtrans/notification', [
            'order_id' => $order->order_code,
            'transaction_status' => 'settlement',
            'payment_type' => 'qris',
        ]);
        $res2->assertOk();

        $order->refresh();
        // Tetap paid & diproses
        $this->assertEquals(Order::PAYMENT_PAID, $order->payment_status);
        $this->assertEquals(Order::STATUS_DIPROSES, $order->status);
        // Riwayat tidak bertambah ganda
        $this->assertEquals(1, OrderStatusHistory::where('order_id', $order->id)->where('status', Order::STATUS_DIPROSES)->count());

        // Verifikasi capture dengan fraud accept
        $order2 = Order::create([
            'order_code' => Order::generateOrderCode(),
            'user_id' => $user->id,
            'status' => Order::STATUS_MENUNGGU,
            'subtotal' => 50000,
            'shipping_cost' => 15000,
            'total' => 65000,
            'shipping_method' => 'kurir',
            'payment_method' => 'midtrans',
            'payment_status' => Order::PAYMENT_PENDING,
            'buyer_nama' => 'Budi',
            'buyer_wa' => '0812345678',
            'buyer_alamat' => 'Alamat',
            'buyer_kota' => 'Kota',
            'buyer_kodepos' => '12345',
        ]);

        $this->postJson('/midtrans/notification', [
            'order_id' => $order2->order_code,
            'transaction_status' => 'capture',
            'fraud_status' => 'accept',
            'payment_type' => 'credit_card',
        ])->assertOk();

        $order2->refresh();
        $this->assertEquals(Order::PAYMENT_PAID, $order2->payment_status);
        $this->assertEquals(Order::STATUS_DIPROSES, $order2->status);
        $this->assertNotNull($order2->paid_at);
    }

    /**
     * 3. Webhook capture dengan fraud challenge tidak melunasi pesanan.
     */
    public function test_03_webhook_capture_with_fraud_challenge_does_not_pay_order(): void
    {
        $user = $this->createCustomer();
        $order = Order::create([
            'order_code' => Order::generateOrderCode(),
            'user_id' => $user->id,
            'status' => Order::STATUS_MENUNGGU,
            'subtotal' => 50000,
            'shipping_cost' => 15000,
            'total' => 65000,
            'shipping_method' => 'kurir',
            'payment_method' => 'midtrans',
            'payment_status' => Order::PAYMENT_PENDING,
            'buyer_nama' => 'Budi',
            'buyer_wa' => '0812345678',
            'buyer_alamat' => 'Alamat',
            'buyer_kota' => 'Kota',
            'buyer_kodepos' => '12345',
        ]);

        $this->postJson('/midtrans/notification', [
            'order_id' => $order->order_code,
            'transaction_status' => 'capture',
            'fraud_status' => 'challenge',
            'payment_type' => 'credit_card',
        ])->assertOk();

        $order->refresh();
        $this->assertEquals(Order::PAYMENT_PENDING, $order->payment_status);
        $this->assertEquals(Order::STATUS_MENUNGGU, $order->status);
        $this->assertNull($order->paid_at);
    }

    /**
     * 4. Webhook expire/cancel/deny pada pesanan belum bayar: status dibatalkan dan stok kembali SEKALI.
     *    Dikirim berulang tidak menambah stok lagi.
     */
    public function test_04_webhook_expire_cancel_deny_restores_stock_once(): void
    {
        $user = $this->createCustomer();
        $product = $this->createProduct(['stok' => 8]);

        $order = Order::create([
            'order_code' => Order::generateOrderCode(),
            'user_id' => $user->id,
            'status' => Order::STATUS_MENUNGGU,
            'subtotal' => 100000,
            'shipping_cost' => 15000,
            'total' => 115000,
            'shipping_method' => 'kurir',
            'payment_method' => 'midtrans',
            'payment_status' => Order::PAYMENT_PENDING,
            'buyer_nama' => 'Budi',
            'buyer_wa' => '0812345678',
            'buyer_alamat' => 'Alamat',
            'buyer_kota' => 'Kota',
            'buyer_kodepos' => '12345',
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

        // Webhook expire pertama
        $this->postJson('/midtrans/notification', [
            'order_id' => $order->order_code,
            'transaction_status' => 'expire',
        ])->assertOk();

        $order->refresh();
        $product->refresh();
        $this->assertEquals(Order::STATUS_DIBATALKAN, $order->status);
        $this->assertEquals(Order::PAYMENT_EXPIRED, $order->payment_status);
        $this->assertEquals(10, $product->stok); // 8 + 2

        // Webhook expire kedua (duplikat)
        $this->postJson('/midtrans/notification', [
            'order_id' => $order->order_code,
            'transaction_status' => 'expire',
        ])->assertOk();

        $product->refresh();
        // Stok tetap 10, tidak bertambah lagi
        $this->assertEquals(10, $product->stok);
    }

    /**
     * 5. Webhook gagal/expire pada pesanan yang sudah paid tidak mengubah apa pun.
     */
    public function test_05_webhook_failed_or_expire_on_paid_order_does_not_change_anything(): void
    {
        $user = $this->createCustomer();
        $order = Order::create([
            'order_code' => Order::generateOrderCode(),
            'user_id' => $user->id,
            'status' => Order::STATUS_DIPROSES,
            'subtotal' => 50000,
            'shipping_cost' => 15000,
            'total' => 65000,
            'shipping_method' => 'kurir',
            'payment_method' => 'midtrans',
            'payment_status' => Order::PAYMENT_PAID,
            'paid_at' => now(),
            'buyer_nama' => 'Budi',
            'buyer_wa' => '0812345678',
            'buyer_alamat' => 'Alamat',
            'buyer_kota' => 'Kota',
            'buyer_kodepos' => '12345',
        ]);

        $this->postJson('/midtrans/notification', [
            'order_id' => $order->order_code,
            'transaction_status' => 'expire',
        ])->assertOk();

        $order->refresh();
        $this->assertEquals(Order::PAYMENT_PAID, $order->payment_status);
        $this->assertEquals(Order::STATUS_DIPROSES, $order->status);
    }

    /**
     * 6. Pembayaran masuk setelah pesanan dibatalkan tidak menghidupkan pesanan (status tetap dibatalkan).
     */
    public function test_06_payment_after_cancellation_does_not_revive_order(): void
    {
        $user = $this->createCustomer();
        $order = Order::create([
            'order_code' => Order::generateOrderCode(),
            'user_id' => $user->id,
            'status' => Order::STATUS_DIBATALKAN,
            'subtotal' => 50000,
            'shipping_cost' => 15000,
            'total' => 65000,
            'shipping_method' => 'kurir',
            'payment_method' => 'midtrans',
            'payment_status' => Order::PAYMENT_EXPIRED,
            'buyer_nama' => 'Budi',
            'buyer_wa' => '0812345678',
            'buyer_alamat' => 'Alamat',
            'buyer_kota' => 'Kota',
            'buyer_kodepos' => '12345',
        ]);

        $this->postJson('/midtrans/notification', [
            'order_id' => $order->order_code,
            'transaction_status' => 'settlement',
            'payment_type' => 'bank_transfer',
        ])->assertOk();

        $order->refresh();
        $this->assertEquals(Order::PAYMENT_PAID, $order->payment_status);
        // Status pesanan HARUS TETAP dibatalkan, tidak boleh bangkit jadi diproses
        $this->assertEquals(Order::STATUS_DIBATALKAN, $order->status);
    }

    /**
     * 7. Owner tidak bisa mengubah pesanan belum paid ke diproses/dikirim/selesai (ada pesan error);
     *    setelah paid bisa. Owner membatalkan: stok kembali sekali, dan pesanan dibatalkan tidak bisa dihidupkan lagi.
     */
    public function test_07_owner_order_status_transitions_and_rules(): void
    {
        $owner = $this->createOwner();
        $product = $this->createProduct(['stok' => 5]);

        $order = Order::create([
            'order_code' => Order::generateOrderCode(),
            'user_id' => $owner->id,
            'status' => Order::STATUS_MENUNGGU,
            'subtotal' => 50000,
            'shipping_cost' => 15000,
            'total' => 65000,
            'shipping_method' => 'kurir',
            'payment_method' => 'midtrans',
            'payment_status' => Order::PAYMENT_PENDING,
            'buyer_nama' => 'Budi',
            'buyer_wa' => '0812345678',
            'buyer_alamat' => 'Alamat',
            'buyer_kota' => 'Kota',
            'buyer_kodepos' => '12345',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'nama' => $product->nama,
            'image' => 'assets/images/test.png',
            'harga' => 50000,
            'qty' => 1,
            'line_total' => 50000,
        ]);

        // Owner coba ubah pesanan belum paid ke diproses -> DITOLAK
        $res = $this->actingAs($owner)->put("/owner/transaksi/{$order->id}", [
            'status' => Order::STATUS_DIPROSES,
        ]);
        $res->assertSessionHasErrors('status');
        $order->refresh();
        $this->assertEquals(Order::STATUS_MENUNGGU, $order->status);

        // Setelah paid, owner bisa ubah ke diproses
        $order->update(['payment_status' => Order::PAYMENT_PAID, 'paid_at' => now()]);
        $resPaid = $this->actingAs($owner)->put("/owner/transaksi/{$order->id}", [
            'status' => Order::STATUS_DIPROSES,
        ]);
        $resPaid->assertSessionHasNoErrors();
        $order->refresh();
        $this->assertEquals(Order::STATUS_DIPROSES, $order->status);

        // Owner membatalkan pesanan: stok kembali
        $resCancel = $this->actingAs($owner)->put("/owner/transaksi/{$order->id}", [
            'status' => Order::STATUS_DIBATALKAN,
        ]);
        $resCancel->assertSessionHasNoErrors();
        $order->refresh();
        $product->refresh();
        $this->assertEquals(Order::STATUS_DIBATALKAN, $order->status);
        $this->assertEquals(6, $product->stok); // 5 + 1

        // Owner tidak bisa menghidupkan kembali pesanan yang sudah dibatalkan
        $resRevive = $this->actingAs($owner)->put("/owner/transaksi/{$order->id}", [
            'status' => Order::STATUS_DIPROSES,
        ]);
        $resRevive->assertSessionHasErrors('status');
        $order->refresh();
        $this->assertEquals(Order::STATUS_DIBATALKAN, $order->status);
    }

    /**
     * 8. Pembeli hanya bisa membatalkan saat menunggu_pembayaran; pesanan milik orang lain menghasilkan 404.
     */
    public function test_08_buyer_cancellation_permissions_and_status_checks(): void
    {
        $userA = $this->createCustomer();
        $userB = $this->createCustomer();

        $orderA = Order::create([
            'order_code' => Order::generateOrderCode(),
            'user_id' => $userA->id,
            'status' => Order::STATUS_MENUNGGU,
            'subtotal' => 50000,
            'shipping_cost' => 15000,
            'total' => 65000,
            'shipping_method' => 'kurir',
            'payment_method' => 'midtrans',
            'payment_status' => Order::PAYMENT_PENDING,
            'buyer_nama' => 'User A',
            'buyer_wa' => '0812345678',
            'buyer_alamat' => 'Alamat',
            'buyer_kota' => 'Kota',
            'buyer_kodepos' => '12345',
        ]);

        // User B coba membatalkan pesanan milik User A -> 404
        $res404 = $this->actingAs($userB)->postJson("/pesanan/{$orderA->order_code}/batal");
        $res404->assertNotFound();

        // User A membatalkan pesanan sendiri saat menunggu_pembayaran -> Berhasil
        $resCancel = $this->actingAs($userA)->postJson("/pesanan/{$orderA->order_code}/batal");
        $resCancel->assertOk();
        $orderA->refresh();
        $this->assertEquals(Order::STATUS_DIBATALKAN, $orderA->status);

        // Pesanan berstatus diproses tidak bisa dibatalkan oleh pembeli
        $orderA2 = Order::create([
            'order_code' => Order::generateOrderCode(),
            'user_id' => $userA->id,
            'status' => Order::STATUS_DIPROSES,
            'subtotal' => 50000,
            'shipping_cost' => 15000,
            'total' => 65000,
            'shipping_method' => 'kurir',
            'payment_method' => 'midtrans',
            'payment_status' => Order::PAYMENT_PAID,
            'paid_at' => now(),
            'buyer_nama' => 'User A',
            'buyer_wa' => '0812345678',
            'buyer_alamat' => 'Alamat',
            'buyer_kota' => 'Kota',
            'buyer_kodepos' => '12345',
        ]);

        $resDiproses = $this->actingAs($userA)->postJson("/pesanan/{$orderA2->order_code}/batal");
        $resDiproses->assertStatus(422);
        $orderA2->refresh();
        $this->assertEquals(Order::STATUS_DIPROSES, $orderA2->status);
    }

    /**
     * 9. Dashboard, laporan, dan CSV: pendapatan = SUM(subtotal) pesanan paid yang tidak dibatalkan,
     *    TANPA ongkir. Pesanan belum bayar dan dibatalkan tidak dihitung.
     *    Timezone Asia/Jakarta dan batas tengah malam benar, rentang [awal, akhir) tidak menghitung ganda.
     */
    public function test_09_revenue_calculations_timezone_and_midnight_boundaries(): void
    {
        $owner = $this->createOwner();

        // Target hari: 2026-10-24 di Asia/Jakarta
        $targetDate = Carbon::create(2026, 10, 24, 12, 0, 0, 'Asia/Jakarta');

        // Order 1: Paid, subtotal 100.000, ongkir 15.000, total 115.000 (Dihitung 100.000)
        Order::create([
            'order_code' => 'YKB-TEST-001',
            'user_id' => $owner->id,
            'status' => Order::STATUS_DIPROSES,
            'subtotal' => 100000,
            'shipping_cost' => 15000,
            'total' => 115000,
            'shipping_method' => 'kurir',
            'payment_method' => 'qris',
            'payment_status' => Order::PAYMENT_PAID,
            'paid_at' => $targetDate,
            'created_at' => $targetDate,
            'buyer_nama' => 'Test 1',
            'buyer_wa' => '0812',
            'buyer_alamat' => 'Alamat',
            'buyer_kota' => 'Kota',
            'buyer_kodepos' => '12345',
        ]);

        // Order 2: Belum bayar (pending), subtotal 50.000 (Tidak boleh dihitung)
        Order::create([
            'order_code' => 'YKB-TEST-002',
            'user_id' => $owner->id,
            'status' => Order::STATUS_MENUNGGU,
            'subtotal' => 50000,
            'shipping_cost' => 15000,
            'total' => 65000,
            'shipping_method' => 'kurir',
            'payment_method' => 'midtrans',
            'payment_status' => Order::PAYMENT_PENDING,
            'paid_at' => null,
            'created_at' => $targetDate,
            'buyer_nama' => 'Test 2',
            'buyer_wa' => '0812',
            'buyer_alamat' => 'Alamat',
            'buyer_kota' => 'Kota',
            'buyer_kodepos' => '12345',
        ]);

        // Order 3: Dibatalkan, subtotal 70.000 (Tidak boleh dihitung)
        Order::create([
            'order_code' => 'YKB-TEST-003',
            'user_id' => $owner->id,
            'status' => Order::STATUS_DIBATALKAN,
            'subtotal' => 70000,
            'shipping_cost' => 15000,
            'total' => 85000,
            'shipping_method' => 'kurir',
            'payment_method' => 'midtrans',
            'payment_status' => Order::PAYMENT_EXPIRED,
            'paid_at' => null,
            'created_at' => $targetDate,
            'buyer_nama' => 'Test 3',
            'buyer_wa' => '0812',
            'buyer_alamat' => 'Alamat',
            'buyer_kota' => 'Kota',
            'buyer_kodepos' => '12345',
        ]);

        // Order 4: Tepat tengah malam berikutnya (2026-10-25 00:00:00 Asia/Jakarta)
        // Dengan rentang [2026-10-24 00:00:00, 2026-10-25 00:00:00), order ini masuk hari berikutnya, BUKAN hari ini.
        $nextDayMidnight = Carbon::create(2026, 10, 25, 0, 0, 0, 'Asia/Jakarta');
        Order::create([
            'order_code' => 'YKB-TEST-004',
            'user_id' => $owner->id,
            'status' => Order::STATUS_DIPROSES,
            'subtotal' => 200000,
            'shipping_cost' => 15000,
            'total' => 215000,
            'shipping_method' => 'kurir',
            'payment_method' => 'qris',
            'payment_status' => Order::PAYMENT_PAID,
            'paid_at' => $nextDayMidnight,
            'created_at' => $nextDayMidnight,
            'buyer_nama' => 'Test 4',
            'buyer_wa' => '0812',
            'buyer_alamat' => 'Alamat',
            'buyer_kota' => 'Kota',
            'buyer_kodepos' => '12345',
        ]);

        $awal = Carbon::create(2026, 10, 24, 0, 0, 0, 'Asia/Jakarta');
        $akhirEksklusif = Carbon::create(2026, 10, 25, 0, 0, 0, 'Asia/Jakarta');

        $pendapatan = (int) Order::paidBetween($awal, $akhirEksklusif)->sum('subtotal');
        $this->assertEquals(100000, $pendapatan);

        // Halaman laporan
        $resLaporan = $this->actingAs($owner)->get('/owner/laporan?mulai=2026-10-24&selesai=2026-10-24');
        $resLaporan->assertOk();
        $resLaporan->assertViewHas('totalPendapatan', 100000);
        $resLaporan->assertViewHas('totalPesanan', 1);

        // Download CSV
        $resCsv = $this->actingAs($owner)->get('/owner/laporan/download?mulai=2026-10-24&selesai=2026-10-24');
        $resCsv->assertOk();
        $content = $resCsv->streamedContent();
        $this->assertStringContainsString('YKB-TEST-001', $content);
        $this->assertStringNotContainsString('YKB-TEST-002', $content);
        $this->assertStringNotContainsString('YKB-TEST-003', $content);
        $this->assertStringNotContainsString('YKB-TEST-004', $content);
    }

    /**
     * 10. Produk terlaris dihitung per product_id (bukan nama), dan item paket diurai ke produk isinya
     *     dengan omzet proporsional.
     */
    public function test_10_top_selling_products_calculated_by_product_id_and_bundle_unpacked_proportionally(): void
    {
        $owner = $this->createOwner();

        $prodA = Product::create([
            'slug' => 'serum-a',
            'nama' => 'Serum Glowing A',
            'brand' => 'Brand A',
            'kategori' => 'skincare',
            'harga' => 60000,
            'stok' => 20,
            'is_active' => true,
            'image' => 'a.png',
        ]);

        $prodB = Product::create([
            'slug' => 'cream-b',
            'nama' => 'Cream Glowing B',
            'brand' => 'Brand B',
            'kategori' => 'skincare',
            'harga' => 40000,
            'stok' => 20,
            'is_active' => true,
            'image' => 'b.png',
        ]);

        $bundle = Collection::create([
            'title' => 'Paket Glowing Duo',
            'slug' => 'paket-glowing-duo',
            'harga' => 80000, // Harga paket 80k (hemat 20k dari total normal 100k)
            'image' => 'bundle.png',
            'is_active' => true,
        ]);

        $bundle->products()->sync([
            $prodA->id => ['sort_order' => 1],
            $prodB->id => ['sort_order' => 2],
        ]);

        $now = Carbon::now('Asia/Jakarta');

        // Pesanan 1: Membeli 1 unit Produk A satuan langsung (harga 60.000)
        $order1 = Order::create([
            'order_code' => Order::generateOrderCode(),
            'user_id' => $owner->id,
            'status' => Order::STATUS_DIPROSES,
            'subtotal' => 60000,
            'shipping_cost' => 15000,
            'total' => 75000,
            'shipping_method' => 'kurir',
            'payment_method' => 'qris',
            'payment_status' => Order::PAYMENT_PAID,
            'paid_at' => $now,
            'created_at' => $now,
            'buyer_nama' => 'Pembeli 1',
            'buyer_wa' => '08123',
            'buyer_alamat' => 'Alamat',
            'buyer_kota' => 'Kota',
            'buyer_kodepos' => '12345',
        ]);

        OrderItem::create([
            'order_id' => $order1->id,
            'product_id' => $prodA->id,
            'collection_id' => null,
            'bundle_snapshot' => null,
            'nama' => $prodA->nama,
            'image' => 'assets/images/test.png',
            'harga' => 60000,
            'qty' => 1,
            'line_total' => 60000,
        ]);

        // Pesanan 2: Membeli 1 unit Paket Bundling (harga 80.000)
        // Komponen: Prod A (normal 60k -> proporsi 60%), Prod B (normal 40k -> proporsi 40%)
        // Omzet proporsional: Prod A = 48.000, Prod B = 32.000
        $order2 = Order::create([
            'order_code' => Order::generateOrderCode(),
            'user_id' => $owner->id,
            'status' => Order::STATUS_DIPROSES,
            'subtotal' => 80000,
            'shipping_cost' => 15000,
            'total' => 95000,
            'shipping_method' => 'kurir',
            'payment_method' => 'qris',
            'payment_status' => Order::PAYMENT_PAID,
            'paid_at' => $now,
            'created_at' => $now,
            'buyer_nama' => 'Pembeli 2',
            'buyer_wa' => '08123',
            'buyer_alamat' => 'Alamat',
            'buyer_kota' => 'Kota',
            'buyer_kodepos' => '12345',
        ]);

        OrderItem::create([
            'order_id' => $order2->id,
            'product_id' => null,
            'collection_id' => $bundle->id,
            'bundle_snapshot' => [
                ['product_id' => $prodA->id, 'nama' => $prodA->nama, 'qty' => 1, 'harga' => 60000],
                ['product_id' => $prodB->id, 'nama' => $prodB->nama, 'qty' => 1, 'harga' => 40000],
            ],
            'nama' => $bundle->title,
            'image' => 'assets/images/bundle.png',
            'harga' => 80000,
            'qty' => 1,
            'line_total' => 80000,
        ]);

        $mulai = $now->copy()->subDay();
        $selesai = $now->copy()->addDay();

        $top = ProductSalesReport::topProducts($mulai, $selesai, 5);

        // Harus ada 2 produk individual (karena paket diurai ke isi produknya)
        $this->assertCount(2, $top);

        // Produk A harus teratas:
        // total_qty = 1 (satuan) + 1 (dari paket) = 2
        // total_omset = 60.000 (satuan) + 48.000 (60% dari 80k paket) = 108.000
        $topA = $top->firstWhere('product_id', $prodA->id);
        $this->assertNotNull($topA);
        $this->assertEquals(2, $topA['total_qty']);
        $this->assertEquals(108000, $topA['total_omset']);

        // Produk B:
        // total_qty = 1 (dari paket) = 1
        // total_omset = 32.000 (40% dari 80k paket)
        $topB = $top->firstWhere('product_id', $prodB->id);
        $this->assertNotNull($topB);
        $this->assertEquals(1, $topB['total_qty']);
        $this->assertEquals(32000, $topB['total_omset']);
    }
}
