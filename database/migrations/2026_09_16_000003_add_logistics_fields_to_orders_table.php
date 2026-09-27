<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom `status` di tabel orders adalah string biasa (bukan enum DB),
     * jadi nilai baru (menunggu_pembayaran, dikirim, selesai) tidak butuh
     * perubahan skema — cukup diatur di level app (lihat Order::STATUSES).
     * Data lama berstatus "diproses" dibiarkan apa adanya; "dibatalkan" tetap ada.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('kurir')->nullable()->after('shipping_method');
            $table->string('nomor_resi')->nullable()->after('kurir');
            $table->index('status');
        });

        // Order baru default-nya menunggu pembayaran dulu, bukan langsung diproses
        DB::statement("ALTER TABLE orders ALTER COLUMN status SET DEFAULT 'menunggu_pembayaran'");
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['kurir', 'nomor_resi']);
        });

        DB::statement("ALTER TABLE orders ALTER COLUMN status SET DEFAULT 'diproses'");
    }
};
