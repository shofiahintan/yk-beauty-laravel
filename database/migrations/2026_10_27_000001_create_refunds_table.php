<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('sumber', ['pembeli', 'sistem'])->default('pembeli');
            $table->enum('alasan', [
                'batal_sebelum_kirim',
                'barang_rusak',
                'salah_kirim',
                'tidak_sesuai',
                'lainnya',
            ]);
            $table->text('keterangan')->nullable();
            $table->json('bukti_paths')->nullable();
            $table->enum('status', ['diajukan', 'disetujui', 'ditolak', 'selesai'])->default('diajukan');
            $table->unsignedInteger('nominal_barang')->default(0);
            $table->unsignedInteger('nominal_ongkir')->default(0);
            $table->enum('metode_pengembalian', ['midtrans_dashboard', 'transfer_manual'])->nullable();
            $table->string('referensi_pengembalian')->nullable();
            $table->string('rekening_bank')->nullable();
            $table->text('rekening_nomor')->nullable(); // encrypted
            $table->string('rekening_nama')->nullable();
            $table->text('catatan_owner')->nullable();
            $table->foreignId('diproses_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('disetujui_at')->nullable();
            $table->timestamp('selesai_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
