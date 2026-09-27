<?php

namespace App\Http\Controllers;

use App\Models\Collection;

class CollectionController extends Controller
{
    // Halaman publik /paket-bundling: daftar semua paket bundling yang aktif.
    public function index()
    {
        $collections = Collection::where('is_active', true)
            ->orderByDesc('id')
            ->get();

        return view('koleksi', [
            'collections' => $collections,
        ]);
    }

    // Halaman detail /paket-bundling/{slug}: satu paket beserta produk isinya.
    public function show(string $slug)
    {
        $collection = Collection::where('slug', $slug)
            ->where('is_active', true)
            ->with('products')
            ->firstOrFail();

        return view('koleksi-detail', [
            'collection' => $collection,
        ]);
    }
}
