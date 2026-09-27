<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // owner: pemilik toko, akses penuh ke panel /owner
            // admin: staf yang dibantu owner kelola operasional (ditambahkan lewat Kelola Data Pengguna > Staff & Admin)
            // customer: pembeli biasa di storefront (default, kompatibel dengan akun yang sudah ada)
            $table->enum('role', ['owner', 'admin', 'customer'])
                ->default('customer')
                ->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
