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
        $orderId = $request->query('id') ?? $request->query('order_id');
        $dbOrder = null;
        if ($orderId) {
            $dbOrder = Order::with(['items.product', 'items.collection', 'items.review', 'refunds', 'statusHistories'])
                ->where('order_code', $orderId)
                ->where('user_id', auth()->id())
                ->first();
        }

        return view('pesanan', [
            'products' => Product::catalogArray(),
            'bundles'  => \App\Models\Collection::catalogArray(),
            'orderId'  => $orderId,
            'dbOrder'  => $dbOrder,
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

        if ($order->status !== Order::STATUS_MENUNGGU) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan hanya bisa dibatalkan saat masih menunggu pembayaran.',
            ], 422);
        }

        \App\Support\OrderLifecycle::cancel($order, 'Dibatalkan oleh pembeli');
        $order->refresh();

        return response()->json(['success' => true, 'status' => $order->status]);
    }
}
