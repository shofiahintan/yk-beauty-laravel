<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase 5b — Bot balasan FAQ otomatis.
     *
     * Balasan bot disimpan sebagai pesan biasa dengan sender = 'owner' (supaya tampil
     * di sisi "toko" seperti balasan asli), ditandai is_bot = true supaya:
     *   - bisa dikasih label "Balasan otomatis" di UI
     *   - bot berhenti otomatis begitu ada balasan MANUSIA (is_bot = false) dari Owner
     */
    public function up()
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->boolean('is_bot')->default(false)->after('sender');
        });
    }

    public function down()
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropColumn('is_bot');
        });
    }
};
