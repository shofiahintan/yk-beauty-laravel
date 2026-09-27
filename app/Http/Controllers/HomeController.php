<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductReview;

class HomeController extends Controller
{
    // Ambang jumlah ulasan asli sebelum carousel landing page pindah dari testimoni
    // manual ke ulasan asli pembeli. Di bawah ini kelihatan sepi kalau dipaksa tampil.
    private const REAL_REVIEWS_THRESHOLD = 6;

    public function index()
    {
        $realReviews = ProductReview::with(['user:id,name', 'product:id,nama'])
            ->latest()
            ->limit(12)
            ->get();

        return view('index', [
            'products' => Product::catalogArray(),
            'realReviews' => $realReviews->count() >= self::REAL_REVIEWS_THRESHOLD ? $realReviews : null,
        ]);
    }
}
