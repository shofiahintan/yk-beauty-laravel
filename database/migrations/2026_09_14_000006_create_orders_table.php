<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code')->unique(); // dulu ykGenerateOrderId(), format YKB-20260914-1234
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('diproses'); // diproses | dibatalkan | dst
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('shipping_cost')->default(0);
            $table->unsignedInteger('total');
            $table->enum('shipping_method', ['ambil', 'kurir']);
            $table->enum('payment_method', ['cash', 'transfer', 'qris', 'ewallet']);
            // snapshot data pembeli saat checkout (jangan ambil live dari users, biar histori gak berubah kalau profil diedit)
            $table->string('buyer_nama');
            $table->string('buyer_wa');
            $table->string('buyer_alamat')->nullable();
            $table->string('buyer_kota')->nullable();
            $table->string('buyer_kodepos')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
