<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('category'); // dulu hasil t('blog_cat_...'), sekarang teks biasa
            $table->date('published_at');
            $table->string('image');
            $table->string('title');
            $table->text('excerpt');
            $table->json('body'); // array paragraf, dulu array of t('blog_aX_body_pN')
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};
