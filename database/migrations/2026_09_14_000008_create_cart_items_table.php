<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('qty')->default(1);
            $table->string('varian')->nullable();
            $table->string('ukuran')->nullable();
            $table->timestamps();

            // pengganti logic "existing" di ykAddToCart (id + varian + ukuran sama = 1 baris)
            $table->unique(['user_id', 'product_id', 'varian', 'ukuran'], 'cart_items_unique_line');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
