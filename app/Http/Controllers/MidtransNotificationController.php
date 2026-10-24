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

        $orderId = $request->input('order_id');
        $status = $request->input('transaction_status');
        $fraud = $request->input('fraud_status');
        $paymentType = $request->input('payment_type');

        if (!$orderId) {
            try {
                $notification = new Notification();
                $orderId = $notification->order_id;
                $status = $notification->transaction_status;
                $fraud = $notification->fraud_status ?? null;
                $paymentType = $notification->payment_type ?? null;
            } catch (\Throwable $e) {
                return response()->json(['message' => 'Invalid notification: ' . $e->getMessage()], 400);
            }
        }

        $order = Order::where('order_code', $orderId)->first();
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        \App\Support\OrderLifecycle::applyMidtransStatus($order, (string) $status, $fraud, $paymentType);

        return response()->json(['message' => 'OK']);
    }
}