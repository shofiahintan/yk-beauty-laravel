<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::query()->with('user');

        if ($cari = $request->query('cari')) {
            $query->where(function ($q) use ($cari) {
                $q->where('order_code', 'like', "%{$cari}%")
                    ->orWhere('buyer_nama', 'like', "%{$cari}%")
                    ->orWhere('buyer_wa', 'like', "%{$cari}%");
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $orders = $query->orderByDesc('created_at')->paginate(10)->withQueryString();

        return view('owner.transaksi', [
            'orders' => $orders,
            'filters' => $request->only(['cari', 'status']),
            'statuses' => Order::STATUSES,
        ]);
    }

    public function show(Order $order)
    {
        $order->load(['items', 'user']);

        return response()->json([
            'id' => $order->id,
            'order_code' => $order->order_code,
            'status' => $order->status,
            'status_label' => $order->statusLabel(),
            'created_at' => $order->created_at->translatedFormat('d M Y, H:i'),
            'shipping_method' => $order->shipping_method,
            'payment_method' => $order->payment_method,
            'kurir' => $order->kurir,
            'nomor_resi' => $order->nomor_resi,
            'subtotal' => $order->subtotal,
            'shipping_cost' => $order->shipping_cost,
            'total' => $order->total,
            'buyer' => [
                'nama' => $order->buyer_nama,
                'wa' => $order->buyer_wa,
                'alamat' => $order->buyer_alamat,
                'kota' => $order->buyer_kota,
                'kodepos' => $order->buyer_kodepos,
            ],
            'items' => $order->items->map(fn ($it) => [
                'nama' => $it->nama,
                'image' => asset($it->image),
                'harga' => $it->harga,
                'qty' => $it->qty,
                'varian' => $it->varian,
                'ukuran' => $it->ukuran,
                'line_total' => $it->line_total,
                'bundle_items' => $it->bundle_snapshot ? array_map(fn($b) => $b['nama'], $it->bundle_snapshot) : null,
            ]),
        ]);
    }

    public function update(Request $request, Order $order)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Order::STATUSES))],
            'kurir' => ['nullable', 'string', 'max:100'],
            'nomor_resi' => ['nullable', 'string', 'max:100'],
        ]);

        // Pesanan yang sudah dibatalkan stoknya sudah dikembalikan ke produk,
        // jadi tidak boleh dihidupkan lagi (nanti stok jadi tidak sinkron).
        if ($order->status === Order::STATUS_DIBATALKAN && $data['status'] !== Order::STATUS_DIBATALKAN) {
            return back()->withErrors([
                'status' => 'Pesanan yang sudah dibatalkan tidak bisa diaktifkan lagi.',
            ]);
        }

        // Pesanan yang belum paid tidak boleh diubah ke diproses, dikirim, atau selesai
        if (! $order->isPaid() && in_array($data['status'], [Order::STATUS_DIPROSES, Order::STATUS_DIKIRIM, Order::STATUS_SELESAI], true)) {
            return back()->withErrors([
                'status' => 'Pesanan belum dibayar tidak bisa diubah statusnya ke diproses, dikirim, atau selesai.',
            ]);
        }

        // Sesuai alur: status "Dikirim" wajib sudah ada kurir + nomor resi.
        if ($data['status'] === Order::STATUS_DIKIRIM) {
            $kurir = $data['kurir'] ?? $order->kurir;
            $resi = $data['nomor_resi'] ?? $order->nomor_resi;

            if (empty($kurir) || empty($resi)) {
                return back()->withErrors([
                    'nomor_resi' => 'Isi kurir dan nomor resi dulu sebelum ubah status ke "Dikirim".',
                ])->withInput();
            }
        }

        DB::transaction(function () use ($order, $data) {
            if (! empty($data['kurir'])) {
                $order->kurir = $data['kurir'];
            }
            if (! empty($data['nomor_resi'])) {
                $order->nomor_resi = $data['nomor_resi'];
            }
            $order->save();

            if ($data['status'] === Order::STATUS_DIBATALKAN) {
                \App\Support\OrderLifecycle::cancel($order, 'Dibatalkan oleh owner');
            } else {
                \App\Support\OrderLifecycle::transition($order, $data['status'], 'Status diubah oleh owner');
            }
        });

        return redirect()->route('owner.transaksi.index')->with('status', 'Pesanan '.$order->order_code.' diubah ke status "'.$order->statusLabel().'".');
    }
}
