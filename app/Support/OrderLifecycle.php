<?php

namespace App\Support;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;

/**
 * Satu-satunya tempat mengubah status pesanan/pembayaran.
 * Idempotent, row di-lock, stok kembali sekali, tercatat di riwayat.
 */
class OrderLifecycle
{
    /**
     * Terapkan notifikasi Midtrans ke order.
     * Dipanggil dari MidtransNotificationController.
     */
    public static function applyMidtransStatus(Order $order, string $transactionStatus, ?string $fraudStatus, ?string $paymentType): void
    {
        DB::transaction(function () use ($order, $transactionStatus, $fraudStatus, $paymentType) {
            // Lock row agar tidak ada race condition webhook ganda
            $order = Order::lockForUpdate()->findOrFail($order->id);

            if (($transactionStatus === 'capture' && $fraudStatus === 'accept') || $transactionStatus === 'settlement') {
                // Idempotent: kalau sudah paid, partial_refund, atau refunded, skip
                if ($order->isPaid()) {
                    return;
                }

                // Jika pesanan sudah dibatalkan, pembayaran masuk tidak menghidupkan pesanan (tetap dibatalkan)
                if ($order->status === Order::STATUS_DIBATALKAN) {
                    $order->update([
                        'payment_status' => Order::PAYMENT_PAID,
                        'payment_method' => $paymentType,
                        'paid_at'        => now(),
                    ]);
                    self::logHistory($order, Order::STATUS_DIBATALKAN, 'Pembayaran diterima setelah pesanan dibatalkan');
                    RefundService::createAutomaticRefund($order, Refund::ALASAN_BATAL_SEBELUM_KIRIM, 'Pembayaran diterima setelah pesanan dibatalkan');
                    return;
                }

                $order->update([
                    'payment_status' => Order::PAYMENT_PAID,
                    'payment_method' => $paymentType,
                    'paid_at'        => now(),
                    'status'         => Order::STATUS_DIPROSES,
                ]);

                self::logHistory($order, Order::STATUS_DIPROSES, 'Pembayaran dikonfirmasi oleh Midtrans');

            } elseif (in_array($transactionStatus, ['refund', 'partial_refund'], true)) {
                // Webhook refund Midtrans: tidak ubah status apa pun, catat satu riwayat (idempotent)
                $catatan = 'Notifikasi Midtrans: ' . $transactionStatus;
                $alreadyLogged = OrderStatusHistory::where('order_id', $order->id)
                    ->where('catatan', $catatan)
                    ->exists();

                if (! $alreadyLogged) {
                    self::logHistory($order, $order->status, $catatan);
                }
                return;

            } elseif (in_array($transactionStatus, ['cancel', 'deny', 'expire'], true)) {
                if ($order->isPaid()) {
                    return; // Sudah lunas atau direfund, abaikan
                }

                $newPaymentStatus = $transactionStatus === 'expire'
                    ? Order::PAYMENT_EXPIRED
                    : Order::PAYMENT_FAILED;

                $order->update(['payment_status' => $newPaymentStatus]);

                // Batalkan pesanan dan kembalikan stok (hanya dari menunggu_pembayaran)
                if ($order->status === Order::STATUS_MENUNGGU) {
                    self::cancel($order, 'Pembayaran ' . $transactionStatus . ' via Midtrans');
                }

            } elseif ($transactionStatus === 'pending') {
                $order->update(['payment_status' => Order::PAYMENT_PENDING]);
            }
        });
    }

    /**
     * Batalkan pesanan dan kembalikan stok (idempotent, hanya dari menunggu_pembayaran/diproses).
     */
    public static function cancel(Order $order, string $alasan = 'Dibatalkan'): void
    {
        DB::transaction(function () use ($order, $alasan) {
            $order = Order::lockForUpdate()->findOrFail($order->id);

            if ($order->status === Order::STATUS_DIBATALKAN) {
                return; // Sudah dibatalkan
            }

            $wasPaid = $order->isPaid();

            // Kembalikan stok
            $order->load('items');
            $order->restoreStock();

            $order->update(['status' => Order::STATUS_DIBATALKAN]);
            self::logHistory($order, Order::STATUS_DIBATALKAN, $alasan);

            // Jika pesanan sudah lunas dibatalkan, buat refund otomatis oleh sistem
            if ($wasPaid) {
                RefundService::createAutomaticRefund($order, Refund::ALASAN_BATAL_SEBELUM_KIRIM, 'Pesanan lunas dibatalkan');
            }
        });
    }

    /**
     * Transisi status pesanan (owner mengubah status manual).
     */
    public static function transition(Order $order, string $newStatus, ?string $catatan = null): void
    {
        if ($newStatus === Order::STATUS_DIBATALKAN) {
            self::cancel($order, $catatan ?? 'Status diubah ke dibatalkan oleh owner');
            return;
        }

        DB::transaction(function () use ($order, $newStatus, $catatan) {
            $order = Order::lockForUpdate()->findOrFail($order->id);
            $order->update(['status' => $newStatus]);
            self::logHistory($order, $newStatus, $catatan ?? 'Status diubah oleh owner');
        });
    }

    /**
     * Catat riwayat saat order pertama kali dibuat.
     */
    public static function logCreated(Order $order): void
    {
        self::logHistory($order, $order->status, 'Pesanan dibuat');
    }

    private static function logHistory(Order $order, string $status, string $catatan): void
    {
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status'   => $status,
            'catatan'  => $catatan,
        ]);
    }
}
