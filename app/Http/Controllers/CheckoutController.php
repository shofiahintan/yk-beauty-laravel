<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\MidtransService;
use App\Support\OrderLifecycle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function index()
    {
        return view('checkout', [
            'products'     => Product::catalogArray(),
            'bundles'      => Collection::catalogArray(),
            'shippingFlat' => config('shop.shipping_flat'),
        ]);
    }

    /**
     * Halaman sukses checkout / payment.
     * Wajib login (dilindungi middleware di routes), dan hanya menampilkan
     * pesanan milik user yang sedang login.
     */
    public function success(Request $request)
    {
        $orderId = $request->query('id');
        $dbOrder = null;

        if ($orderId) {
            $dbOrder = Order::with(['items.product', 'items.collection', 'items.review'])
                ->where('order_code', $orderId)
                ->where('user_id', auth()->id())
                ->first();
        }

        return view('payment-sukses', [
            'products'  => Product::catalogArray(),
            'bundles'   => Collection::catalogArray(),
            'orderId'   => $orderId,
            'dbOrders'  => $dbOrder ? [$dbOrder->toJsShape()] : [],
        ]);
    }

    /**
     * Proses checkout — server jadi sumber kebenaran harga.
     *
     * Dari klien kita hanya percaya: data pembeli, metode kirim, dan
     * daftar item berupa {id (slug), qty, varian, ukuran}. Harga, subtotal,
     * total, nama, gambar TIDAK dipercaya — semuanya di-snapshot dari DB.
     */
    public function store(Request $request)
    {
        $request->validate([
            'buyer.nama'    => 'required|string|max:255',
            'buyer.wa'      => 'required|string|max:50',
            'buyer.alamat'  => 'required|string|max:500',
            'buyer.kota'    => 'required|string|max:100',
            'buyer.kodepos' => 'required|string|max:20',
            'items'         => 'required|array|min:1',
            'items.*.id'    => 'required|string',
            'items.*.qty'   => 'required|integer|min:1|max:99',
            'items.*.varian' => 'nullable|string|max:100',
            'items.*.ukuran' => 'nullable|string|max:100',
            // Channel pembayaran (transfer/QRIS/e-wallet/kartu) sekarang dipilih di
            // dalam popup Midtrans Snap, bukan lagi lewat form ini — jadi field ini
            // opsional dan sepenuhnya diabaikan server.
            'payment'       => 'nullable|string',
            'shipping'      => 'nullable|string',
            'subtotal'      => 'nullable',
            'total'         => 'nullable',
            'shippingCost'  => 'nullable',
        ]);

        $order = DB::transaction(function () use ($request) {
            $buyer        = $request->input('buyer', []);
            $shippingCost = (int) config('shop.shipping_flat');

            // --- Gabungkan item duplikat (slug + varian + ukuran sama) ---
            $mergedItems = [];
            foreach ($request->input('items', []) as $it) {
                $slug   = $it['id'] ?? '';
                $varian = isset($it['varian']) && trim($it['varian']) !== '' ? trim($it['varian']) : null;
                $ukuran = isset($it['ukuran']) && trim($it['ukuran']) !== '' ? trim($it['ukuran']) : null;
                $type   = $it['type'] ?? 'product';
                $qty    = max(1, min(99, (int) ($it['qty'] ?? 1)));
                $key    = $type . '|' . $slug . '|' . ($varian ?? '') . '|' . ($ukuran ?? '');

                if (isset($mergedItems[$key])) {
                    $mergedItems[$key]['qty'] = min(99, $mergedItems[$key]['qty'] + $qty);
                } else {
                    $mergedItems[$key] = compact('type', 'slug', 'varian', 'ukuran', 'qty');
                }
            }

            // --- Proses tiap item: validasi produk, stok, varian; snapshot harga dari DB ---
            $orderItems = [];
            $subtotal   = 0;
            $stockDeductions = [];

            foreach ($mergedItems as $it) {
                $qty = $it['qty'];

                if ($it['type'] === 'bundle') {
                    $collection = Collection::with(['products' => function ($q) {
                        $q->where('is_active', true)->lockForUpdate();
                    }])->where('slug', $it['slug'])->where('is_active', true)->first();

                    if (! $collection || $collection->products->isEmpty()) {
                        throw ValidationException::withMessages([
                            'items' => 'Paket Bundling "' . $it['slug'] . '" tidak tersedia.',
                        ]);
                    }

                    $bundleSnapshot = [];
                    foreach ($collection->products as $cp) {
                        if ($cp->stok < $qty) {
                            throw ValidationException::withMessages([
                                'stok' => 'Stok produk "' . $cp->nama . '" dalam paket tidak cukup.',
                            ]);
                        }
                        $stockDeductions[] = [
                            'product' => $cp,
                            'qty'     => $qty,
                            'alasan'  => 'Checkout paket ' . $collection->title,
                        ];
                        $bundleSnapshot[] = ['product_id' => $cp->id, 'nama' => $cp->nama, 'qty' => 1, 'harga' => (int) $cp->harga];
                    }

                    $harga     = (int) $collection->harga;
                    $lineTotal = $harga * $qty;
                    $subtotal += $lineTotal;

                    $orderItems[] = [
                        'collection_id'   => $collection->id,
                        'product_id'      => null,
                        'bundle_snapshot' => $bundleSnapshot,
                        'nama'            => $collection->title,
                        'image'           => $collection->image,
                        'harga'           => $harga,
                        'qty'             => $qty,
                        'varian'          => null,
                        'ukuran'          => null,
                        'line_total'      => $lineTotal,
                    ];
                } else {
                    $product = Product::where('slug', $it['slug'])
                        ->where('is_active', true)
                        ->lockForUpdate()
                        ->first();

                    if (! $product) {
                        throw ValidationException::withMessages([
                            'items' => 'Produk "' . $it['slug'] . '" tidak tersedia atau sudah tidak aktif.',
                        ]);
                    }

                    // Validasi varian: kalau produk punya varian, input harus cocok salah satu
                    $varian         = $it['varian'];
                    $productVariants = $product->variants()->pluck('label')->all();
                    if (count($productVariants) > 0) {
                        if ($varian !== null && ! in_array($varian, $productVariants, true)) {
                            throw ValidationException::withMessages([
                                'items' => 'Varian "' . $varian . '" tidak tersedia untuk produk "' . $product->nama . '".',
                            ]);
                        }
                    } else {
                        // Produk tidak punya varian, abaikan input varian
                        $varian = null;
                    }

                    // Cek stok
                    if ($product->stok < $qty) {
                        throw ValidationException::withMessages([
                            'stok' => $product->stok > 0
                                ? 'Stok "' . $product->nama . '" tidak cukup, tersisa ' . $product->stok . ' unit.'
                                : 'Maaf, "' . $product->nama . '" sedang habis.',
                        ]);
                    }

                    $stockDeductions[] = [
                        'product' => $product,
                        'qty'     => $qty,
                        'alasan'  => 'Checkout produk ' . $product->nama,
                    ];

                    // Snapshot harga dari DB, bukan dari klien
                    $harga     = (int) $product->harga;
                    $lineTotal = $harga * $qty;
                    $subtotal += $lineTotal;

                    $orderItems[] = [
                        'collection_id'   => null,
                        'product_id'      => $product->id,
                        'bundle_snapshot' => null,
                        'nama'            => $product->nama,
                        'image'           => $product->image,
                        'harga'           => $harga,
                        'qty'             => $qty,
                        'varian'          => $varian,
                        'ukuran'          => $it['ukuran'] ?? null,
                        'line_total'      => $lineTotal,
                    ];
                }
            }

            $total     = $subtotal + $shippingCost;
            $orderCode = Order::generateOrderCode();

            // Pesanan baru selalu menunggu_pembayaran + pending (sebelum Midtrans dikonfirmasi)
            $order = Order::create([
                'order_code'      => $orderCode,
                'user_id'         => auth()->id(),
                'status'          => Order::STATUS_MENUNGGU,
                'subtotal'        => $subtotal,
                'shipping_cost'   => $shippingCost,
                'total'           => $total,
                'shipping_method' => 'kurir',
                'payment_method'  => 'midtrans',
                'payment_status'  => Order::PAYMENT_PENDING,
                'buyer_nama'      => $buyer['nama'] ?? '',
                'buyer_wa'        => $buyer['wa'] ?? '',
                'buyer_alamat'    => $buyer['alamat'] ?? null,
                'buyer_kota'      => $buyer['kota'] ?? null,
                'buyer_kodepos'   => $buyer['kodepos'] ?? null,
            ]);

            foreach ($orderItems as $item) {
                OrderItem::create(array_merge($item, ['order_id' => $order->id]));
            }

            // Kurangi stok dan catat di stock_movements
            foreach ($stockDeductions as $deduction) {
                Product::adjustStock($deduction['product'], -$deduction['qty'], $deduction['alasan'], $order->id, auth()->id());
            }

            // Catat riwayat status awal
            OrderLifecycle::logCreated($order);

            // Generate Snap token di dalam transaction: kalau ini gagal (misal Server Key
            // salah), order & pengurangan stok di atas ikut di-rollback otomatis.
            app(MidtransService::class)->createSnapToken($order);

            return $order;
        });

        return response()->json([
            'success'    => true,
            'order_id'   => $order->order_code,
            'total'      => (int) $order->total,
            'snap_token' => $order->snap_token,
            'redirect'   => route('checkout.success', ['id' => $order->order_code]),
        ]);
    }
}