<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('collection_id')->nullable()->constrained('collections')->nullOnDelete();
            $table->json('bundle_snapshot')->nullable();
        });
        
        // Use raw SQL to make product_id nullable since doctrine/dbal is not installed
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE order_items MODIFY product_id BIGINT UNSIGNED NULL;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['collection_id']);
            $table->dropColumn(['collection_id', 'bundle_snapshot']);
        });
        
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE order_items MODIFY product_id BIGINT UNSIGNED NOT NULL;');
    }
};
