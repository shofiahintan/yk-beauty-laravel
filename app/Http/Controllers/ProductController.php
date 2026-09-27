<?php

namespace App\Http\Controllers;

use App\Models\Product;

class ProductController extends Controller
{
    // Halaman publik /produk: nampilin semua produk aktif, filter & sort-nya
    // dikerjain di sisi client (lihat public/js/produk.js) dari data yang
    // sama kayak di landing page (Product::catalogArray()).
    public function index()
    {
        return view('produk', [
            'products' => Product::catalogArray(),
        ]);
    }

    // Halaman detail /produk/{slug}: slug-nya dibaca dari URL di sisi client
    // (public/js/produk-detail.js), jadi controller ini cukup kirim data
    // yang sama kayak index, nggak perlu query 1 produk spesifik di server.
    public function show(string $slug)
    {
        return view('produk-detail', [
            'products' => Product::catalogArray(),
        ]);
    }
}
