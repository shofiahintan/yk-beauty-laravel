<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Midtrans\Config;
use Midtrans\Notification;

class MidtransNotificationController extends Controller
{
    public function handle(Request $request)
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');

        $notification = new Notification();

        $order = Order::where('order_code', $notification->order_id)->first();
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $status = $notification->transaction_status;
        $fraud = $notification->fraud_status ?? null;

        if (($status === 'capture' && $fraud === 'accept') || $status === 'settlement') {
            $order->update([
                'payment_status' => 'paid',
                'payment_method' => $notification->payment_type,
                'paid_at' => now(),
            ]);
        } elseif (in_array($status, ['cancel', 'deny', 'expire'], true)) {
            if ($order->payment_status !== 'paid') {
                $order->update(['payment_status' => $status === 'expire' ? 'expired' : 'failed']);
                if ($order->status === 'diproses') {
                    $order->restoreStock();
                    $order->update(['status' => 'dibatalkan']);
                }
            }
        } elseif ($status === 'pending') {
            $order->update(['payment_status' => 'pending']);
        }

        return response()->json(['message' => 'OK']);
    }
}