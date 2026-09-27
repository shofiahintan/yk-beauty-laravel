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
        // 1. Convert existing admins to inactive customers
        DB::table('users')
            ->where('role', 'admin')
            ->update([
                'role' => 'customer',
                'is_active' => 0
            ]);

        // 2. Change enum to only allow owner and customer
        // Note: doctrine/dbal might have issues with enum, so we use raw statement
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('owner', 'customer') NOT NULL DEFAULT 'customer'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('owner', 'admin', 'customer') NOT NULL DEFAULT 'customer'");
    }
};
