<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * payment_method awalnya dibuat sebagai ENUM('cash','transfer','qris','ewallet')
     * di migration create_orders_table. Sejak checkout pindah ke Midtrans Snap,
     * nilai yang perlu disimpan jadi bermacam-macam dan tidak lagi cocok dengan
     * enum lama — mis. 'midtrans' (nilai sementara sebelum dibayar), lalu
     * 'bank_transfer', 'qris', 'gopay', 'credit_card', dst (dari
     * $notification->payment_type di MidtransNotificationController).
     *
     * MySQL tidak error keras kalau nilai di luar enum di-insert — dia cuma
     * mengirim WARNING "Data truncated" dan menyimpan string kosong, yang lalu
     * gagal karena kolom ini NOT NULL. Makanya perlu diubah jadi VARCHAR biasa.
     */
    public function up(): void
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        // Pakai raw SQL, bukan ->change(), supaya tidak butuh package doctrine/dbal.
        DB::statement('ALTER TABLE orders MODIFY payment_method VARCHAR(50) NOT NULL DEFAULT \'midtrans\'');
    }

    public function down(): void
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        // Balikin ke bentuk semula. Catatan: kalau sudah ada baris dengan nilai
        // di luar enum lama (mis. 'bank_transfer', 'gopay'), rollback ini akan
        // gagal sampai baris tsb dibersihkan dulu — itu wajar untuk migration down.
        DB::statement("ALTER TABLE orders MODIFY payment_method ENUM('cash','transfer','qris','ewallet') NOT NULL");
    }
};