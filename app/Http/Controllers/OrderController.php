<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function show(Request $request)
    {
        $orderId = $request->query('id');
        $dbOrder = null;
        if ($orderId) {
            $dbOrder = Order::with(['items.product', 'items.collection', 'items.review'])
                ->where('order_code', $orderId)
                ->where('user_id', auth()->id())
                ->first();
        }

        return view('pesanan', [
            'products' => Product::catalogArray(),
            'bundles' => \App\Models\Collection::catalogArray(),
            'orderId' => $orderId,
            'dbOrders' => $dbOrder ? [$dbOrder->toJsShape()] : [],
        ]);
    }

    public function history()
    {
        $dbOrders = auth()->check()
            ? Order::with(['items.product', 'items.collection', 'items.review'])->where('user_id', auth()->id())->orderByDesc('created_at')->get()
            : collect();

        return view('riwayat-pesanan', [
            'products' => Product::catalogArray(),
            'bundles' => \App\Models\Collection::catalogArray(),
            'dbOrders' => $dbOrders->map->toJsShape()->values(),
        ]);
    }

    public function cancel(Request $request, $orderCode)
    {
        $order = Order::where('order_code', $orderCode)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if ($order->status === 'diproses') {
            // Stok barang di pesanan ini dikembalikan (sekali saja) — sama seperti
            // saat owner membatalkan pesanan dari panel.
            DB::transaction(function () use ($order) {
                $fresh = Order::with('items')->whereKey($order->id)->lockForUpdate()->first();

                if ($fresh && $fresh->status === 'diproses') {
                    $fresh->restoreStock();
                    $fresh->update(['status' => 'dibatalkan']);
                }
            });

            $order->refresh();
        }

        return response()->json(['success' => true, 'status' => $order->status]);
    }
}
