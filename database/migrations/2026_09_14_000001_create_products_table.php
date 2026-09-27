<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique(); // pengganti id string lama, mis. "viva-gold-whitening-serum"
            $table->string('nama');
            $table->string('brand');
            $table->string('kategori');
            $table->unsignedInteger('harga');
            $table->unsignedInteger('harga_coret')->nullable(); // dulu "coret"
            $table->string('badge')->nullable();
            $table->string('badge_class')->nullable();
            $table->decimal('rating', 2, 1)->default(0);
            $table->unsignedInteger('review_count')->default(0); // dulu "count"
            $table->text('deskripsi')->nullable();
            $table->string('image'); // gambar utama, dulu "img"
            $table->boolean('stok')->default(true); // dulu p.stok !== false di wishlist.js
            $table->timestamps();

            $table->index(['kategori', 'brand']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
