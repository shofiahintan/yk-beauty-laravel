<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OrderSeeder extends Seeder
{
    protected array $customers = [
        ['name' => 'Nadia Safitri', 'email' => 'nadia.safitri@gmail.com', 'wa' => '+62 812-3456-7890', 'kota' => 'Kota Malang', 'alamat' => 'Jl. Ijen No. 45 RT 02 / RW 05, Kel. Gading Kasri, Kec. Klojen'],
        ['name' => 'Citra Dewi', 'email' => 'citra.dewi@outlook.com', 'wa' => '+62 857-1290-3341', 'kota' => 'Kota Malang', 'alamat' => 'Jl. Soekarno Hatta No. 12'],
        ['name' => 'Maya Putri', 'email' => 'maya.putri99@yahoo.co.id', 'wa' => '+62 821-9904-2211', 'kota' => 'Kabupaten Malang', 'alamat' => 'Jl. Raya Pakisaji No. 8'],
        ['name' => 'Anisa Rahma', 'email' => 'anisa.rahma@gmail.com', 'wa' => '+62 878-1109-4432', 'kota' => 'Kota Malang', 'alamat' => 'Jl. Bendungan Sigura-gura No. 20'],
        ['name' => 'Anindya Putri', 'email' => 'anindya.putri@gmail.com', 'wa' => '+62 813-2211-9987', 'kota' => 'Kota Batu', 'alamat' => 'Jl. Panglima Sudirman No. 5'],
        ['name' => 'Clara Dewanti', 'email' => 'clara.dewanti@gmail.com', 'wa' => '+62 822-4455-6677', 'kota' => 'Kota Malang', 'alamat' => 'Jl. Veteran No. 33'],
        ['name' => 'Siti Rahmawati', 'email' => 'siti.rahmawati@gmail.com', 'wa' => '+62 856-7788-1122', 'kota' => 'Kabupaten Malang', 'alamat' => 'Jl. Kebonagung No. 17'],
        ['name' => 'Ratna Wulandari', 'email' => 'ratna.wulandari@gmail.com', 'wa' => '+62 817-3344-5566', 'kota' => 'Kota Malang', 'alamat' => 'Jl. Ijen Boulevard No. 9'],
    ];

    protected array $kurirList = ['JNE Express Reguler (1-2 Hari)', 'J&T Express Regular (1-2 Hari)', 'SiCepat Reguler (2-3 Hari)'];

    public function run(): void
    {
        $products = Product::all();

        if ($products->isEmpty()) {
            $this->command?->warn('OrderSeeder dilewati: belum ada produk. Jalankan ProductSeeder dulu.');

            return;
        }

        // Pastikan tiap dummy customer punya akun User (biar Kelola Data Pengguna > Pelanggan ada datanya)
        $users = collect($this->customers)->map(fn ($c) => User::firstOrCreate(
            ['email' => $c['email']],
            [
                'name' => $c['name'],
                'role' => User::ROLE_CUSTOMER,
                'password' => Hash::make('password'),
                'whatsapp' => $c['wa'],
                'alamat' => $c['alamat'].', '.$c['kota'],
            ]
        ));

        // Distribusi status realistis: sebagian besar sudah selesai, sisanya lagi jalan
        $statusPool = array_merge(
            array_fill(0, 3, Order::STATUS_MENUNGGU),
            array_fill(0, 4, Order::STATUS_DIPROSES),
            array_fill(0, 5, Order::STATUS_DIKIRIM),
            array_fill(0, 28, Order::STATUS_SELESAI),
            array_fill(0, 2, Order::STATUS_DIBATALKAN),
        );

        foreach ($statusPool as $status) {
            $customer = $users->random();
            $customerData = collect($this->customers)->firstWhere('email', $customer->email);
            $createdAt = now()->subDays(random_int(0, 44))->subMinutes(random_int(0, 1439));

            $items = $products->random(random_int(1, 3));
            $subtotal = 0;
            $orderItemsData = [];

            foreach ($items as $product) {
                $qty = random_int(1, 2);
                $lineTotal = $product->harga * $qty;
                $subtotal += $lineTotal;

                $orderItemsData[] = [
                    'product_id' => $product->id,
                    'nama' => $product->nama,
                    'image' => $product->image,
                    'harga' => $product->harga,
                    'qty' => $qty,
                    'line_total' => $lineTotal,
                ];
            }

            $shippingCost = 15000 + (random_int(0, 2) * 5000);
            $needsShipping = in_array($status, [Order::STATUS_DIKIRIM, Order::STATUS_SELESAI], true);

            $order = Order::create([
                'order_code' => Order::generateOrderCode(),
                'user_id' => $customer->id,
                'status' => $status,
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'total' => $subtotal + $shippingCost,
                'shipping_method' => 'kurir',
                'payment_method' => collect(['transfer', 'qris', 'ewallet'])->random(),
                'kurir' => $needsShipping ? collect($this->kurirList)->random() : null,
                'nomor_resi' => $needsShipping ? strtoupper(fake()->bothify('??######??##')) : null,
                'buyer_nama' => $customerData['name'],
                'buyer_wa' => $customerData['wa'],
                'buyer_alamat' => $customerData['alamat'],
                'buyer_kota' => $customerData['kota'],
                'buyer_kodepos' => '65100',
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            foreach ($orderItemsData as $itemData) {
                OrderItem::create($itemData + ['order_id' => $order->id]);
            }
        }
    }
}
