<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Dipakai untuk mencocokkan akun saat login/daftar lewat Google.
            // Nullable karena user yang daftar manual (email+password) tidak punya ini.
            $table->string('google_id')->nullable()->unique()->after('email');
        });

        // User yang login via Google tidak mengisi password sendiri, jadi kolom
        // password perlu boleh kosong. Pakai raw statement (bukan ->change())
        // supaya tidak butuh package doctrine/dbal yang belum ter-install.
        DB::statement('ALTER TABLE users MODIFY COLUMN password VARCHAR(255) NULL');
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('google_id');
        });

        DB::statement("UPDATE users SET password = '' WHERE password IS NULL");
        DB::statement('ALTER TABLE users MODIFY COLUMN password VARCHAR(255) NOT NULL');
    }
};
