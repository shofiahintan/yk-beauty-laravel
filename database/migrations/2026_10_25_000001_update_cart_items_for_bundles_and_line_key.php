<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Beri index sementara pada user_id agar foreign key constraint tidak menghalangi drop unique index
        Schema::table('cart_items', function (Blueprint $table) {
            $table->index('user_id', 'cart_items_user_id_fk_idx');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique('cart_items_unique_line');
        });

        DB::statement('ALTER TABLE cart_items MODIFY product_id BIGINT UNSIGNED NULL');

        Schema::table('cart_items', function (Blueprint $table) {
            $table->foreignId('collection_id')->nullable()->after('product_id')->constrained('collections')->cascadeOnDelete();
            $table->string('line_key', 64)->default('')->after('ukuran');
            $table->unique(['user_id', 'line_key'], 'cart_items_user_line_unique');
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique('cart_items_user_line_unique');
            $table->dropForeign(['collection_id']);
            $table->dropColumn(['collection_id', 'line_key']);
        });

        DB::statement('ALTER TABLE cart_items MODIFY product_id BIGINT UNSIGNED NOT NULL');

        Schema::table('cart_items', function (Blueprint $table) {
            $table->unique(['user_id', 'product_id', 'varian', 'ukuran'], 'cart_items_unique_line');
            $table->dropIndex('cart_items_user_id_fk_idx');
        });
    }
};
