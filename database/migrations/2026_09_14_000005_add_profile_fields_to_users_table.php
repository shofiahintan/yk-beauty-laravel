<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('whatsapp')->nullable()->after('email'); // dulu "wa" di akun.js
            $table->text('alamat')->nullable()->after('whatsapp');
            $table->string('foto')->nullable()->after('alamat'); // path foto profil
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['whatsapp', 'alamat', 'foto']);
        });
    }
};
