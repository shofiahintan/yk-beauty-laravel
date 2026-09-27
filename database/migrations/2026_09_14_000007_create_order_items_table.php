<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            // nullable: produk mungkin dihapus di masa depan, tapi histori order harus tetap utuh
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            // snapshot detail produk saat dibeli (harga/nama bisa berubah nanti)
            $table->string('nama');
            $table->string('image');
            $table->unsignedInteger('harga');
            $table->unsignedInteger('qty');
            $table->string('varian')->nullable();
            $table->string('ukuran')->nullable();
            $table->unsignedInteger('line_total');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
