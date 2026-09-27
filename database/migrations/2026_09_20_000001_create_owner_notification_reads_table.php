<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menyimpan notifikasi Owner Panel mana yang sudah dibaca oleh akun mana.
     */
    public function up()
    {
        Schema::create('owner_notification_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('notif_key', 120);
            $table->timestamps();

            $table->unique(['user_id', 'notif_key']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('owner_notification_reads');
    }
};
