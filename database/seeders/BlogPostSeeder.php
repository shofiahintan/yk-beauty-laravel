<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use Illuminate\Database\Seeder;

class BlogPostSeeder extends Seeder
{
    public function run(): void
    {
        $articles = [
            [
                'slug' => 'manfaat-aloe-vera',
                'category' => 'blog_cat_skincare101',
                'published_at' => '2024-10-12',
                'image' => 'assets/images/landing3.jpg',
                'title' => 'blog_a1_title',
                'excerpt' => 'blog_a1_excerpt',
                'body' => ['blog_a1_body_p1', 'blog_a1_body_p2', 'blog_a1_body_p3'],
            ],
            [
                'slug' => 'urutan-skincare-malam',
                'category' => 'blog_cat_rutinitas',
                'published_at' => '2024-10-08',
                'image' => 'assets/images/landing5.jpg',
                'title' => 'blog_a2_title',
                'excerpt' => 'blog_a2_excerpt',
                'body' => ['blog_a2_body_p1', 'blog_a2_body_p2', 'blog_a2_body_p3'],
            ],
            [
                'slug' => 'rahasia-glass-skin',
                'category' => 'blog_cat_tips',
                'published_at' => '2024-10-05',
                'image' => 'assets/images/landing1.jpg',
                'title' => 'blog_a3_title',
                'excerpt' => 'blog_a3_excerpt',
                'body' => ['blog_a3_body_p1', 'blog_a3_body_p2', 'blog_a3_body_p3'],
            ],
            [
                'slug' => 'mitos-fakta-sunscreen',
                'category' => 'blog_cat_proteksi',
                'published_at' => '2024-09-29',
                'image' => 'assets/images/landing7.jpg',
                'title' => 'blog_a4_title',
                'excerpt' => 'blog_a4_excerpt',
                'body' => ['blog_a4_body_p1', 'blog_a4_body_p2', 'blog_a4_body_p3'],
            ],
            [
                'slug' => 'mengenal-niacinamide',
                'category' => 'blog_cat_ingredients',
                'published_at' => '2024-09-22',
                'image' => 'assets/images/landing9.jpg',
                'title' => 'blog_a5_title',
                'excerpt' => 'blog_a5_excerpt',
                'body' => ['blog_a5_body_p1', 'blog_a5_body_p2', 'blog_a5_body_p3'],
            ],
            [
                'slug' => 'waktu-terbaik-anti-aging',
                'category' => 'blog_cat_antiaging',
                'published_at' => '2024-09-15',
                'image' => 'assets/images/landingsec.jpg',
                'title' => 'blog_a6_title',
                'excerpt' => 'blog_a6_excerpt',
                'body' => ['blog_a6_body_p1', 'blog_a6_body_p2', 'blog_a6_body_p3'],
            ],
        ];

        foreach ($articles as $art) {
            BlogPost::updateOrCreate(
                ['slug' => $art['slug']],
                $art
            );
        }
    }
}
