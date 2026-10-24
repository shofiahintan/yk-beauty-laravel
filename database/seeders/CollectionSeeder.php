<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\Product;
use Illuminate\Database\Seeder;

class CollectionSeeder extends Seeder
{
    public function run(): void
    {
        $coll = Collection::updateOrCreate(
            ['slug' => 'paket-daily-glowing'],
            [
                'title'       => 'Paket Daily Glowing',
                'description' => 'Paket bundling skincare pilihan harian Anda.',
                'image'       => 'assets/images/produk/paket1.png',
                'harga'       => 90000,
            ]
        );

        $this->syncProducts($coll, [
            'viva-glowing-white-serum-triple-brightening',
            'viva-moisturizer-gel-glowing-white-50gr',
            'emina-bright-stuff-face-wash-100ml',
            'viva-white-soothing-aloe-gel-80gr',
        ]);

        $coll2 = Collection::updateOrCreate(
            ['slug' => 'paket-bebas-jerawat'],
            [
                'title'       => 'Paket Bebas Jerawat',
                'description' => 'Rangkaian perawatan wajah berjerawat, dari pembersih sampai serum penenang.',
                'image'       => 'assets/images/produk/paket2.png',
                'harga'       => 140000,
            ]
        );

        $this->syncProducts($coll2, [
            'wardah-lightening-facial-wash',
            'viva-peeling-serum-triple-gentle-exfoliating',
            'viva-acne-serum-triple-clarifying',
            'somethinc-mugwort-anti-acne-serum',
        ]);
    }

    private function syncProducts(Collection $coll, array $slugs): void
    {
        $productIds = Product::whereIn('slug', $slugs)->pluck('id', 'slug');

        if ($productIds->isEmpty()) {
            return;
        }

        $syncData = [];
        foreach ($slugs as $idx => $slug) {
            if (isset($productIds[$slug])) {
                $syncData[$productIds[$slug]] = ['sort_order' => $idx + 1];
            }
        }
        $coll->products()->sync($syncData);
    }
}