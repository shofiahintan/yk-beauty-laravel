<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Sebelum fitur ulasan asli ada, kolom rating & review_count produk diisi
     * angka dummy dari seeder (mis. rating 4, review_count 124). Sekarang fitur
     * ulasan asli sudah jalan (lihat create_product_reviews_table), angka-angka
     * lama itu di-reset ke 0 supaya tidak nyampur sama hasil hitung ulasan asli
     * (lihat Product::recalculateRating(), dipanggil tiap ada review baru).
     */
    public function up(): void
    {
        DB::table('products')->update(['rating' => 0, 'review_count' => 0]);
    }

    public function down(): void
    {
        // Sengaja tidak dikembalikan — angka dummy lama tidak berarti apa-apa lagi.
    }
};
