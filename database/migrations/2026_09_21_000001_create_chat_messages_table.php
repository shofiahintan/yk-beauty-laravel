<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase 5 — Chat Pembeli–Owner.
     *
     * Satu ruang chat per pelanggan: user_id selalu pelanggan-nya (bukan owner),
     * sedangkan `sender` menandai siapa yang menulis pesan itu. Tidak ada tabel
     * thread terpisah — daftar percakapan dihitung dari tabel ini.
     */
    public function up()
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('sender', 10); // 'customer' | 'owner'
            $table->text('body')->nullable();
            $table->string('image_path')->nullable(); // path di disk "local" (storage/app/chat/...)
            $table->timestamp('read_at')->nullable(); // kapan pihak LAWAN membacanya
            $table->timestamps();

            $table->index(['user_id', 'id']);
            $table->index(['sender', 'read_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('chat_messages');
    }
};
