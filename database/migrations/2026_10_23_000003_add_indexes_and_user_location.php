<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah index composite pada orders untuk mempercepat query laporan
        Schema::table('orders', function (Blueprint $table) {
            // Hindari duplikat index
            if (! $this->indexExists('orders', 'orders_status_created_at_index')) {
                $table->index(['status', 'created_at'], 'orders_status_created_at_index');
            }
            if (! $this->indexExists('orders', 'orders_payment_status_index')) {
                $table->index('payment_status', 'orders_payment_status_index');
            }
        });

        // Tambah kota dan kodepos ke users kalau belum ada
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'kota')) {
                $table->string('kota', 100)->nullable()->after('alamat');
            }
            if (! Schema::hasColumn('users', 'kodepos')) {
                $table->string('kodepos', 20)->nullable()->after('kota');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndexIfExists('orders_status_created_at_index');
            $table->dropIndexIfExists('orders_payment_status_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumnIfExists('kota');
            $table->dropColumnIfExists('kodepos');
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = \Illuminate\Support\Facades\DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }
};
