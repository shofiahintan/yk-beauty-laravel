<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        return view('keranjang', [
            'products' => Product::catalogArray(),
            'bundles' => \App\Models\Collection::catalogArray(),
        ]);
    }
}
