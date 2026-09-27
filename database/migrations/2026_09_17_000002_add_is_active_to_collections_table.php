<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            // Toggle Aktif di kartu Koleksi & Promo (Kelola Produk, tab Koleksi & Promo)
            $table->boolean('is_active')->default(true)->after('harga_coret');
        });
    }

    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
