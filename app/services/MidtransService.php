<?php

namespace App\Services;

use Midtrans\Config;
use Midtrans\Snap;
use App\Models\Order;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    public function createSnapToken(Order $order): string
    {
        $orderId = $order->order_code; // sudah unik, dipakai juga sebagai order_id Midtrans

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) $order->total,
            ],
            'customer_details' => [
                'first_name' => $order->buyer_nama ?: 'Customer',
                'email' => $order->user->email ?? '',
                'phone' => $order->buyer_wa,
            ],
        ];

        $snapToken = Snap::getSnapToken($params);

        $order->update([
            'midtrans_order_id' => $orderId,
            'snap_token' => $snapToken,
        ]);

        return $snapToken;
    }
}