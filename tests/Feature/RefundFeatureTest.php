<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\Refund;
use App\Models\User;
use App\Support\OrderLifecycle;
use App\Support\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RefundFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

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
            'nama' => 'Serum Uji Coba',
            'brand' => 'YK Beauty',
            'kategori' => 'skincare',
            'harga' => 50000,
            'image' => 'assets/images/test.png',
            'stok' => 10,
            'is_active' => true,
        ], $attrs));
    }

    private function createPaidOrder(User $customer, array $attrs = []): Order
    {
        $product = $this->createProduct(['stok' => 10, 'harga' => 50000]);

        $order = Order::create(array_merge([
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
            'buyer_alamat' => 'Jl. Uji No. 123',
            'buyer_kota' => 'Yogyakarta',
            'buyer_kodepos' => '55281',
            'paid_at' => now(),
        ], $attrs));

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'nama' => $product->nama,
            'image' => 'assets/images/test.png',
            'harga' => 50000,
            'qty' => 2,
            'line_total' => 100000,
        ]);

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $order->status,
            'catatan' => 'Pesanan dibayar',
        ]);

        return $order;
    }

    // ──────────────────────────────────────────────
    // 1. Pengajuan Refund Pembeli: Batal Sebelum Kirim
    // ──────────────────────────────────────────────

    public function test_buyer_can_apply_refund_for_batal_sebelum_kirim_when_status_diproses(): void
    {
        $customer = $this->createCustomer();
        $order = $this->createPaidOrder($customer, ['status' => Order::STATUS_DIPROSES]);

        $response = $this->actingAs($customer)
            ->post("/pesanan/{$order->order_code}/refund", [
                'alasan' => Refund::ALASAN_BATAL_SEBELUM_KIRIM,
                'keterangan' => 'Saya ingin membatalkan pesanan ini sebelum dikirim.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('refunds', [
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'sumber' => Refund::SUMBER_PEMBELI,
            'alasan' => Refund::ALASAN_BATAL_SEBELUM_KIRIM,
            'status' => Refund::STATUS_DIAJUKAN,
        ]);

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'status' => Order::STATUS_DIPROSES,
        ]);
    }

    public function test_cannot_apply_refund_for_unpaid_order(): void
    {
        $customer = $this->createCustomer();
        $order = $this->createPaidOrder($customer, [
            'status' => Order::STATUS_MENUNGGU,
            'payment_status' => Order::PAYMENT_PENDING,
        ]);

        $response = $this->actingAs($customer)
            ->post("/pesanan/{$order->order_code}/refund", [
                'alasan' => Refund::ALASAN_BATAL_SEBELUM_KIRIM,
            ]);

        $response->assertSessionHasErrors(['refund']);
        $this->assertDatabaseMissing('refunds', ['order_id' => $order->id]);
    }

    public function test_buyer_cannot_apply_batal_sebelum_kirim_when_order_is_dikirim(): void
    {
        $customer = $this->createCustomer();
        $order = $this->createPaidOrder($customer, ['status' => Order::STATUS_DIKIRIM]);

        $response = $this->actingAs($customer)
            ->post("/pesanan/{$order->order_code}/refund", [
                'alasan' => Refund::ALASAN_BATAL_SEBELUM_KIRIM,
            ]);

        $response->assertSessionHasErrors(['alasan']);
        $this->assertDatabaseMissing('refunds', ['order_id' => $order->id]);
    }

    // ──────────────────────────────────────────────
    // 2. Pengajuan Refund Pembeli: Barang Bermasalah & Bukti Foto
    // ──────────────────────────────────────────────

    public function test_buyer_can_apply_refund_for_damaged_item_when_dikirim(): void
    {
        $customer = $this->createCustomer();
        $order = $this->createPaidOrder($customer, ['status' => Order::STATUS_DIKIRIM]);

        $file1 = UploadedFile::fake()->image('bukti1.jpg');
        $file2 = UploadedFile::fake()->image('bukti2.png');

        $response = $this->actingAs($customer)
            ->post("/pesanan/{$order->order_code}/refund", [
                'alasan' => Refund::ALASAN_BARANG_RUSAK,
                'keterangan' => 'Botol serum pecah saat dibuka.',
                'bukti' => [$file1, $file2],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('refunds', [
            'order_id' => $order->id,
            'alasan' => Refund::ALASAN_BARANG_RUSAK,
            'status' => Refund::STATUS_DIAJUKAN,
        ]);

        $refund = Refund::where('order_id', $order->id)->first();
        $this->assertCount(2, $refund->bukti_paths);
        Storage::disk('local')->assertExists($refund->bukti_paths[0]);
    }

    public function test_buyer_cannot_apply_refund_for_damaged_item_without_photos(): void
    {
        $customer = $this->createCustomer();
        $order = $this->createPaidOrder($customer, ['status' => Order::STATUS_DIKIRIM]);

        $response = $this->actingAs($customer)
            ->post("/pesanan/{$order->order_code}/refund", [
                'alasan' => Refund::ALASAN_BARANG_RUSAK,
                'keterangan' => 'Barang rusak tapi tidak ada foto.',
                'bukti' => [],
            ]);

        $response->assertSessionHasErrors(['bukti']);
        $this->assertDatabaseMissing('refunds', ['order_id' => $order->id]);
    }

    // ──────────────────────────────────────────────
    // 3. Batas Waktu 48 Jam (Pesanan Selesai)
    // ──────────────────────────────────────────────

    public function test_buyer_can_apply_refund_when_selesai_within_48_hours(): void
    {
        $customer = $this->createCustomer();
        $order = $this->createPaidOrder($customer, ['status' => Order::STATUS_DIPROSES]);
        $order->update(['status' => Order::STATUS_SELESAI]);

        DB::table('order_status_histories')->insert([
            'order_id' => $order->id,
            'status' => Order::STATUS_SELESAI,
            'catatan' => 'Pesanan selesai',
            'created_at' => now()->subHours(20),
            'updated_at' => now()->subHours(20),
        ]);

        $file = UploadedFile::fake()->image('bukti.jpg');

        $response = $this->actingAs($customer)
            ->post("/pesanan/{$order->order_code}/refund", [
                'alasan' => Refund::ALASAN_SALAH_KIRIM,
                'keterangan' => 'Barang yang dikirim salah varian.',
                'bukti' => [$file],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('refunds', [
            'order_id' => $order->id,
            'status' => Refund::STATUS_DIAJUKAN,
        ]);
    }

    public function test_buyer_cannot_apply_refund_when_selesai_past_48_hours(): void
    {
        $customer = $this->createCustomer();
        $order = $this->createPaidOrder($customer, ['status' => Order::STATUS_DIPROSES]);
        $order->update(['status' => Order::STATUS_SELESAI]);

        // Status selesai tercatat 50 jam yang lalu
        DB::table('order_status_histories')->insert([
            'order_id' => $order->id,
            'status' => Order::STATUS_SELESAI,
            'catatan' => 'Pesanan selesai',
            'created_at' => now()->subHours(50),
            'updated_at' => now()->subHours(50),
        ]);

        $file = UploadedFile::fake()->image('bukti.jpg');

        $response = $this->actingAs($customer)
            ->post("/pesanan/{$order->order_code}/refund", [
                'alasan' => Refund::ALASAN_BARANG_RUSAK,
                'bukti' => [$file],
            ]);

        $response->assertSessionHasErrors(['refund']);
        $this->assertDatabaseMissing('refunds', ['order_id' => $order->id]);
    }

    // ──────────────────────────────────────────────
    // 4. Rekening Bank (Wajib vs Opsional)
    // ──────────────────────────────────────────────

    public function test_rekening_is_required_for_bank_transfer_or_va(): void
    {
        $customer = $this->createCustomer();
        $order = $this->createPaidOrder($customer, [
            'status' => Order::STATUS_DIPROSES,
            'payment_method' => 'bank_transfer',
        ]);

        // Tanpa rekening -> gagal
        $response = $this->actingAs($customer)
            ->post("/pesanan/{$order->order_code}/refund", [
                'alasan' => Refund::ALASAN_BATAL_SEBELUM_KIRIM,
            ]);

        $response->assertSessionHasErrors(['rekening']);

        // Dengan rekening -> sukses
        $response2 = $this->actingAs($customer)
            ->post("/pesanan/{$order->order_code}/refund", [
                'alasan' => Refund::ALASAN_BATAL_SEBELUM_KIRIM,
                'rekening_bank' => 'BCA',
                'rekening_nomor' => '1234567890',
                'rekening_nama' => 'Budi Santoso',
            ]);

        $response2->assertRedirect();
        $refund = Refund::where('order_id', $order->id)->first();
        $this->assertEquals('BCA', $refund->rekening_bank);
        $this->assertEquals('1234567890', $refund->rekening_nomor); // decrypted by cast
    }

    public function test_rekening_is_optional_for_gopay_or_credit_card(): void
    {
        $customer = $this->createCustomer();
        $order = $this->createPaidOrder($customer, [
            'status' => Order::STATUS_DIPROSES,
            'payment_method' => 'gopay',
        ]);

        $response = $this->actingAs($customer)
            ->post("/pesanan/{$order->order_code}/refund", [
                'alasan' => Refund::ALASAN_BATAL_SEBELUM_KIRIM,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('refunds', [
            'order_id' => $order->id,
            'status' => Refund::STATUS_DIAJUKAN,
        ]);
    }

    // ──────────────────────────────────────────────
    // 5. Satu Pengajuan Aktif & Tidak Bisa Mengajukan Lagi Setelah Ditolak
    // ──────────────────────────────────────────────

    public function test_only_one_active_refund_allowed_per_order(): void
    {
        $customer = $this->createCustomer();
        $order = $this->createPaidOrder($customer, ['status' => Order::STATUS_DIPROSES]);

        // Pengajuan pertama
        $this->actingAs($customer)
            ->post("/pesanan/{$order->order_code}/refund", [
                'alasan' => Refund::ALASAN_BATAL_SEBELUM_KIRIM,
            ])
            ->assertRedirect();

        // Pengajuan kedua
        $response = $this->actingAs($customer)
            ->post("/pesanan/{$order->order_code}/refund", [
                'alasan' => Refund::ALASAN_BATAL_SEBELUM_KIRIM,
            ]);

        $response->assertSessionHasErrors(['refund']);
        $this->assertEquals(1, Refund::where('order_id', $order->id)->count());
    }

    public function test_rejected_refund_cannot_be_reapplied_by_buyer(): void
    {
        $customer = $this->createCustomer();
        $owner = $this->createOwner();
        $order = $this->createPaidOrder($customer, ['status' => Order::STATUS_DIPROSES]);

        $refund = RefundService::apply($order, $customer, ['alasan' => Refund::ALASAN_BATAL_SEBELUM_KIRIM]);
        RefundService::reject($refund, $owner, 'Pesanan sudah mulai dikemas.');

        $this->assertEquals(Refund::STATUS_DITOLAK, $refund->fresh()->status);

        // Pembeli mencoba mengajukan lagi
        $response = $this->actingAs($customer)
            ->post("/pesanan/{$order->order_code}/refund", [
                'alasan' => Refund::ALASAN_BATAL_SEBELUM_KIRIM,
            ]);

        $response->assertSessionHasErrors(['refund']);
        $this->assertEquals(1, Refund::where('order_id', $order->id)->count());
    }

    // ──────────────────────────────────────────────
    // 6. Hak Akses & Privasi Bukti
    // ──────────────────────────────────────────────

    public function test_other_buyers_cannot_apply_or_view_others_refund(): void
    {
        $buyer1 = $this->createCustomer(['email' => 'buyer1@example.com']);
        $buyer2 = $this->createCustomer(['email' => 'buyer2@example.com']);
        $owner = $this->createOwner();

        $order1 = $this->createPaidOrder($buyer1, ['status' => Order::STATUS_DIKIRIM]);

        // Buyer2 coba ajukan refund untuk order1 -> 403
        $this->actingAs($buyer2)
            ->post("/pesanan/{$order1->order_code}/refund", [
                'alasan' => Refund::ALASAN_BARANG_RUSAK,
            ])
            ->assertForbidden();

        // Buyer1 ajukan dengan bukti
        $file = UploadedFile::fake()->image('bukti.jpg');
        $refund = RefundService::apply($order1, $buyer1, [
            'alasan' => Refund::ALASAN_BARANG_RUSAK,
            'keterangan' => 'Barang pecah di perjalanan.',
        ], [$file]);

        // Buyer2 coba akses foto bukti -> 404 (tidak boleh tahu URL-nya)
        $this->actingAs($buyer2)
            ->get("/pesanan/{$order1->order_code}/refund/bukti/0")
            ->assertNotFound();

        // Buyer1 akses -> 200 OK
        $this->actingAs($buyer1)
            ->get("/pesanan/{$order1->order_code}/refund/bukti/0")
            ->assertOk();

        // Owner akses -> 200 OK
        $this->actingAs($owner)
            ->get("/pesanan/{$order1->order_code}/refund/bukti/0")
            ->assertOk();
    }

    // ──────────────────────────────────────────────
    // 7. Persetujuan Owner, Stok & Nominal
    // ──────────────────────────────────────────────

    public function test_nominal_exceeding_remaining_is_rejected(): void
    {
        $customer = $this->createCustomer();
        $owner = $this->createOwner();
        $order = $this->createPaidOrder($customer, [
            'status' => Order::STATUS_DIPROSES,
            'subtotal' => 100000,
            'shipping_cost' => 15000,
            'total' => 115000,
        ]);

        $refund = RefundService::apply($order, $customer, ['alasan' => Refund::ALASAN_BATAL_SEBELUM_KIRIM]);

        // Coba setujui nominal barang 120.000 (melebihi subtotal 100.000)
        $response = $this->actingAs($owner)
            ->patch("/owner/refund/{$refund->id}/setujui", [
                'nominal_barang' => 120000,
            ]);

        $response->assertSessionHasErrors(['nominal_barang']);
        $this->assertEquals(Refund::STATUS_DIAJUKAN, $refund->fresh()->status);
    }

    public function test_approve_batal_sebelum_kirim_cancels_order_and_restores_stock_once(): void
    {
        $customer = $this->createCustomer();
        $owner = $this->createOwner();
        $product = $this->createProduct(['stok' => 8, 'harga' => 50000]);

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
            'buyer_nama' => 'Test',
            'buyer_wa' => '08123',
            'buyer_alamat' => 'Alamat',
            'buyer_kota' => 'Kota',
            'buyer_kodepos' => '12345',
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

        $refund = RefundService::apply($order, $customer, ['alasan' => Refund::ALASAN_BATAL_SEBELUM_KIRIM]);

        // Owner setujui refund
        $this->actingAs($owner)
            ->patch("/owner/refund/{$refund->id}/setujui", [
                'nominal_barang' => 100000,
                'sertakan_ongkir' => 1,
            ])
            ->assertRedirect();

        $order->refresh();
        $product->refresh();

        // Status pesanan menjadi dibatalkan
        $this->assertEquals(Order::STATUS_DIBATALKAN, $order->status);
        // Stok dikembalikan (8 + 2 = 10)
        $this->assertEquals(10, $product->stok);

        // Refund disetujui
        $refund->refresh();
        $this->assertEquals(Refund::STATUS_DISETUJUI, $refund->status);
        $this->assertEquals(100000, $refund->nominal_barang);
        $this->assertEquals(15000, $refund->nominal_ongkir);
    }

    public function test_approve_other_reasons_does_not_change_order_status_or_stock(): void
    {
        $customer = $this->createCustomer();
        $owner = $this->createOwner();
        $product = $this->createProduct(['stok' => 8, 'harga' => 50000]);

        $order = Order::create([
            'order_code' => Order::generateOrderCode(),
            'user_id' => $customer->id,
            'status' => Order::STATUS_DIKIRIM,
            'payment_status' => Order::PAYMENT_PAID,
            'payment_method' => 'gopay',
            'subtotal' => 100000,
            'shipping_cost' => 15000,
            'total' => 115000,
            'shipping_method' => 'kurir',
            'buyer_nama' => 'Test',
            'buyer_wa' => '08123',
            'buyer_alamat' => 'Alamat',
            'buyer_kota' => 'Kota',
            'buyer_kodepos' => '12345',
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

        $file = UploadedFile::fake()->image('bukti.jpg');
        $refund = RefundService::apply($order, $customer, ['alasan' => Refund::ALASAN_BARANG_RUSAK], [$file]);

        $this->actingAs($owner)
            ->patch("/owner/refund/{$refund->id}/setujui", [
                'nominal_barang' => 50000,
                'sertakan_ongkir' => 0,
            ])
            ->assertRedirect();

        $order->refresh();
        $product->refresh();

        // Status pesanan tetap dikirim, stok tidak berubah
        $this->assertEquals(Order::STATUS_DIKIRIM, $order->status);
        $this->assertEquals(8, $product->stok);
    }

    // ──────────────────────────────────────────────
    // 8. Tandai Selesai & Perubahan payment_status
    // ──────────────────────────────────────────────

    public function test_mark_complete_only_once_and_sets_payment_status(): void
    {
        $customer = $this->createCustomer();
        $owner = $this->createOwner();
        $order = $this->createPaidOrder($customer, [
            'status' => Order::STATUS_DIPROSES,
            'subtotal' => 100000,
            'shipping_cost' => 15000,
            'total' => 115000,
        ]);

        $refund = RefundService::apply($order, $customer, ['alasan' => Refund::ALASAN_BATAL_SEBELUM_KIRIM]);
        RefundService::approve($refund, $owner, 50000, false); // refund sebagian (50k dari 115k)

        // Tandai selesai
        $this->actingAs($owner)
            ->patch("/owner/refund/{$refund->id}/selesai", [
                'metode_pengembalian' => 'midtrans_dashboard',
                'referensi_pengembalian' => 'REF-MIDTRANS-12345',
            ])
            ->assertRedirect();

        $order->refresh();
        $refund->refresh();

        $this->assertEquals(Refund::STATUS_SELESAI, $refund->status);
        $this->assertEquals(50000, $order->refunded_barang);
        $this->assertEquals(Order::PAYMENT_PARTIAL_REFUND, $order->payment_status);

        // Tandai selesai kedua kali -> gagal
        $response = $this->actingAs($owner)
            ->patch("/owner/refund/{$refund->id}/selesai", [
                'metode_pengembalian' => 'midtrans_dashboard',
                'referensi_pengembalian' => 'REF-MIDTRANS-12345',
            ]);

        $response->assertSessionHasErrors(['refund']);
    }

    public function test_full_refund_sets_payment_status_to_refunded(): void
    {
        $customer = $this->createCustomer();
        $owner = $this->createOwner();
        $order = $this->createPaidOrder($customer, [
            'subtotal' => 100000,
            'shipping_cost' => 15000,
            'total' => 115000,
        ]);

        $refund = RefundService::apply($order, $customer, ['alasan' => Refund::ALASAN_BATAL_SEBELUM_KIRIM]);
        RefundService::approve($refund, $owner, 100000, true); // 100k + 15k = 115k (full)

        $this->actingAs($owner)
            ->patch("/owner/refund/{$refund->id}/selesai", [
                'metode_pengembalian' => 'transfer_manual',
                'referensi_pengembalian' => 'TRF-BANK-9999',
            ])
            ->assertRedirect();

        $order->refresh();
        $this->assertEquals(Order::PAYMENT_REFUNDED, $order->payment_status);
        $this->assertEquals(100000, $order->refunded_barang);
        $this->assertEquals(15000, $order->refunded_ongkir);
    }

    // ──────────────────────────────────────────────
    // 9. Refund Otomatis Saat Owner Membatalkan Pesanan Lunas
    // ──────────────────────────────────────────────

    public function test_automatic_refund_when_owner_cancels_paid_order_and_no_duplicates(): void
    {
        $customer = $this->createCustomer();
        $owner = $this->createOwner();
        $order = $this->createPaidOrder($customer, [
            'status' => Order::STATUS_DIPROSES,
            'payment_status' => Order::PAYMENT_PAID,
            'subtotal' => 100000,
            'shipping_cost' => 15000,
            'total' => 115000,
        ]);

        // Owner membatalkan pesanan lewat OrderLifecycle::cancel
        OrderLifecycle::cancel($order, 'Dibatalkan oleh owner');

        $this->assertDatabaseHas('refunds', [
            'order_id' => $order->id,
            'sumber' => Refund::SUMBER_SISTEM,
            'status' => Refund::STATUS_DISETUJUI,
            'nominal_barang' => 100000,
            'nominal_ongkir' => 15000,
        ]);

        $this->assertEquals(1, Refund::where('order_id', $order->id)->count());

        // Cancel lagi -> tidak boleh dobel
        OrderLifecycle::cancel($order, 'Dibatalkan lagi');
        $this->assertEquals(1, Refund::where('order_id', $order->id)->count());
    }

    public function test_automatic_refund_when_payment_arrives_after_order_canceled(): void
    {
        $customer = $this->createCustomer();
        $order = $this->createPaidOrder($customer, [
            'status' => Order::STATUS_MENUNGGU,
            'payment_status' => Order::PAYMENT_PENDING,
            'subtotal' => 100000,
            'shipping_cost' => 15000,
            'total' => 115000,
        ]);

        // Pesanan dibatalkan sebelum bayar
        OrderLifecycle::cancel($order, 'Expired');
        $this->assertEquals(Order::STATUS_DIBATALKAN, $order->fresh()->status);
        $this->assertEquals(0, Refund::where('order_id', $order->id)->count());

        // Webhook settlement masuk setelah dibatalkan
        OrderLifecycle::applyMidtransStatus($order, 'settlement', null, 'gopay');

        $order->refresh();
        $this->assertEquals(Order::STATUS_DIBATALKAN, $order->status);
        $this->assertEquals(Order::PAYMENT_PAID, $order->payment_status);

        $this->assertDatabaseHas('refunds', [
            'order_id' => $order->id,
            'sumber' => Refund::SUMBER_SISTEM,
            'status' => Refund::STATUS_DISETUJUI,
            'nominal_barang' => 100000,
            'nominal_ongkir' => 15000,
        ]);
    }

    // ──────────────────────────────────────────────
    // 10. Webhook Midtrans refund / partial_refund
    // ──────────────────────────────────────────────

    public function test_midtrans_webhook_refund_does_not_change_order_status(): void
    {
        $customer = $this->createCustomer();
        $order = $this->createPaidOrder($customer, [
            'status' => Order::STATUS_DIPROSES,
            'payment_status' => Order::PAYMENT_PAID,
        ]);

        OrderLifecycle::applyMidtransStatus($order, 'refund', null, 'gopay');

        $order->refresh();
        $this->assertEquals(Order::STATUS_DIPROSES, $order->status);
        $this->assertEquals(Order::PAYMENT_PAID, $order->payment_status);

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'catatan' => 'Notifikasi Midtrans: refund',
        ]);

        // Panggil lagi -> idempotent, tidak dobel log
        OrderLifecycle::applyMidtransStatus($order, 'refund', null, 'gopay');
        $this->assertEquals(1, OrderStatusHistory::where('order_id', $order->id)->where('catatan', 'Notifikasi Midtrans: refund')->count());
    }

    // ──────────────────────────────────────────────
    // 11. Dashboard dan Laporan
    // ──────────────────────────────────────────────

    public function test_dashboard_and_report_revenue_subtracts_refunded_barang(): void
    {
        $owner = $this->createOwner();
        $customer = $this->createCustomer();

        $today = Carbon::today('Asia/Jakarta');

        // Pesanan 1: subtotal 100.000, ongkir 15.000, refunded_barang 30.000 -> net pendapatan = 70.000
        $order1 = $this->createPaidOrder($customer, [
            'order_code' => 'YKB-TEST-REF-01',
            'subtotal' => 100000,
            'shipping_cost' => 15000,
            'total' => 115000,
            'refunded_barang' => 30000,
            'refunded_ongkir' => 0,
            'payment_status' => Order::PAYMENT_PARTIAL_REFUND,
            'paid_at' => $today->copy()->addHours(2),
            'created_at' => $today->copy()->addHours(2),
        ]);

        // Pesanan 2: dibatalkan (sudah tidak dihitung, tidak boleh dikurangi dua kali)
        $order2 = $this->createPaidOrder($customer, [
            'order_code' => 'YKB-TEST-REF-02',
            'status' => Order::STATUS_DIBATALKAN,
            'subtotal' => 50000,
            'shipping_cost' => 15000,
            'total' => 65000,
            'refunded_barang' => 50000,
            'paid_at' => $today->copy()->addHours(3),
            'created_at' => $today->copy()->addHours(3),
        ]);

        // Dashboard test
        $resDashboard = $this->actingAs($owner)->get('/owner/dashboard');
        $resDashboard->assertOk();
        $resDashboard->assertViewHas('pendapatanHariIni', 70000);

        // Laporan index test
        $tglStr = $today->format('Y-m-d');
        $resLaporan = $this->actingAs($owner)->get("/owner/laporan?mulai={$tglStr}&selesai={$tglStr}");
        $resLaporan->assertOk();
        $resLaporan->assertViewHas('totalPendapatan', 70000);
        $resLaporan->assertViewHas('totalPesanan', 1);

        // CSV download test
        $resCsv = $this->actingAs($owner)->get("/owner/laporan/download?mulai={$tglStr}&selesai={$tglStr}");
        $resCsv->assertOk();
        $csvContent = $resCsv->streamedContent();

        $this->assertStringContainsString('Refund', $csvContent);
        $this->assertStringContainsString('Pendapatan Bersih', $csvContent);
        $this->assertStringContainsString('YKB-TEST-REF-01', $csvContent);
        $this->assertStringNotContainsString('YKB-TEST-REF-02', $csvContent);

        // Pastikan nominal refund (30000) dan pendapatan bersih (70000) tercantum di baris CSV
        $this->assertStringContainsString('30000', $csvContent);
        $this->assertStringContainsString('70000', $csvContent);
    }

    // ──────────────────────────────────────────────
    // 12. Idempotensi Settlement pada Pesanan Refund
    // ──────────────────────────────────────────────

    public function test_resent_midtrans_settlement_does_not_alter_partially_or_fully_refunded_order(): void
    {
        $customer = $this->createCustomer();
        $originalPaidAt = Carbon::now()->subHours(10)->startOfSecond();

        // 1. Pesanan partial_refund
        $orderPartial = $this->createPaidOrder($customer, [
            'status' => Order::STATUS_DIKIRIM,
            'payment_status' => Order::PAYMENT_PARTIAL_REFUND,
            'paid_at' => $originalPaidAt,
            'refunded_barang' => 30000,
            'refunded_ongkir' => 15000,
        ]);

        OrderLifecycle::applyMidtransStatus($orderPartial, 'settlement', null, 'gopay');

        $orderPartial->refresh();
        $this->assertEquals(Order::PAYMENT_PARTIAL_REFUND, $orderPartial->payment_status);
        $this->assertEquals(Order::STATUS_DIKIRIM, $orderPartial->status);
        $this->assertEquals(30000, $orderPartial->refunded_barang);
        $this->assertEquals(15000, $orderPartial->refunded_ongkir);
        $this->assertEquals($originalPaidAt->timestamp, $orderPartial->paid_at->timestamp);

        // 2. Pesanan fully refunded
        $orderRefunded = $this->createPaidOrder($customer, [
            'status' => Order::STATUS_SELESAI,
            'payment_status' => Order::PAYMENT_REFUNDED,
            'paid_at' => $originalPaidAt,
            'refunded_barang' => 100000,
            'refunded_ongkir' => 15000,
        ]);

        OrderLifecycle::applyMidtransStatus($orderRefunded, 'settlement', null, 'gopay');

        $orderRefunded->refresh();
        $this->assertEquals(Order::PAYMENT_REFUNDED, $orderRefunded->payment_status);
        $this->assertEquals(Order::STATUS_SELESAI, $orderRefunded->status);
        $this->assertEquals(100000, $orderRefunded->refunded_barang);
        $this->assertEquals(15000, $orderRefunded->refunded_ongkir);
        $this->assertEquals($originalPaidAt->timestamp, $orderRefunded->paid_at->timestamp);
    }

    // ──────────────────────────────────────────────
    // 13. Pesanan Dikirim yang Direfund Sebagian Bisa Diselesaikan
    // ──────────────────────────────────────────────

    public function test_owner_can_update_dikirim_order_to_selesai_when_partially_refunded(): void
    {
        $owner = $this->createOwner();
        $customer = $this->createCustomer();

        $order = $this->createPaidOrder($customer, [
            'status' => Order::STATUS_DIKIRIM,
            'payment_status' => Order::PAYMENT_PARTIAL_REFUND,
            'kurir' => 'JNE',
            'nomor_resi' => 'JNE12345678',
            'refunded_barang' => 25000,
            'refunded_ongkir' => 0,
        ]);

        $response = $this->actingAs($owner)
            ->put("/owner/transaksi/{$order->id}", [
                'status' => Order::STATUS_SELESAI,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertEquals(Order::STATUS_SELESAI, $order->status);
    }
}
