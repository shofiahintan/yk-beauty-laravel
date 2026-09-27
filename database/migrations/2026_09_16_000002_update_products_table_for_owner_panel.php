<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catatan: sengaja tidak pakai renameColumn()/change() karena keduanya
     * butuh doctrine/dbal, yang tidak ter-install di project ini. Jadi kolom
     * `stok` (boolean lama) di-drop lalu dibuat ulang sebagai integer baru,
     * dengan kolom sementara buat jembatan datanya.
     */
    public function up(): void
    {
        // 1. Kolom sementara buat nyimpen nilai stok baru (angka) dulu
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('stok_qty_tmp')->default(0)->after('stok');
        });

        // 2. Migrasi data: true -> 50 (asumsi stok wajar), false -> 0
        DB::table('products')->where('stok', true)->update(['stok_qty_tmp' => 50]);
        DB::table('products')->where('stok', false)->update(['stok_qty_tmp' => 0]);

        // 3. Buang kolom boolean lama
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('stok');
        });

        // 4. Buat kolom `stok` baru bertipe integer, isi dari kolom sementara
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('stok')->default(0)->after('image');
            // Toggle Aktif/Nonaktif — independen dari stok (produk bisa aktif
            // walau stok 0, atau sengaja dinonaktifkan owner walau stok masih ada)
            $table->boolean('is_active')->default(true)->after('stok');
        });

        DB::statement('UPDATE products SET stok = stok_qty_tmp');

        // 5. Buang kolom sementara
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('stok_qty_tmp');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('stok_bool_tmp')->default(true)->after('stok');
        });

        DB::table('products')->where('stok', '>', 0)->update(['stok_bool_tmp' => true]);
        DB::table('products')->where('stok', 0)->update(['stok_bool_tmp' => false]);

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['stok', 'is_active']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->boolean('stok')->default(true)->after('image');
        });

        DB::statement('UPDATE products SET stok = stok_bool_tmp');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('stok_bool_tmp');
        });
    }
};
