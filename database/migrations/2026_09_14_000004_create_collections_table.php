<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title'); // dulu koleksi_page_title, mis. "Paket Daily Glowing"
            $table->text('description')->nullable();
            $table->string('image');
            $table->unsignedInteger('harga');
            $table->unsignedInteger('harga_coret')->nullable();
            $table->timestamps();
        });

        // Isi paket = produk-produk yang termasuk dalam satu koleksi/promo
        Schema::create('collection_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_product');
        Schema::dropIfExists('collections');
    }
};
