<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WishlistController extends Controller
{
    public function index()
    {
        return view('wishlist', [
            'products' => Product::catalogArray(),
        ]);
    }

    public function getWishlist(): JsonResponse
    {
        $items = auth()->user()->wishlists()
            ->with('product')
            ->get()
            ->pluck('product.slug')
            ->filter()
            ->values();

        return response()->json([
            'success' => true,
            'wishlist' => $items,
        ]);
    }

    public function toggle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|string',
        ]);

        $product = Product::where('slug', $validated['id'])->where('is_active', true)->first();
        if (! $product) {
            throw ValidationException::withMessages(['id' => 'Produk tidak ditemukan atau tidak aktif.']);
        }

        $userId = auth()->id();
        $existing = Wishlist::where('user_id', $userId)->where('product_id', $product->id)->first();

        if ($existing) {
            $existing->delete();
            $saved = false;
        } else {
            Wishlist::create([
                'user_id' => $userId,
                'product_id' => $product->id,
            ]);
            $saved = true;
        }

        $items = auth()->user()->wishlists()
            ->with('product')
            ->get()
            ->pluck('product.slug')
            ->filter()
            ->values();

        return response()->json([
            'success' => true,
            'saved' => $saved,
            'wishlist' => $items,
        ]);
    }

    public function remove(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|string',
        ]);

        $product = Product::where('slug', $validated['id'])->first();
        if ($product) {
            Wishlist::where('user_id', auth()->id())->where('product_id', $product->id)->delete();
        }

        return $this->getWishlist();
    }

    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'present|array',
            'items.*' => 'string',
        ]);

        $userId = auth()->id();
        foreach ($validated['items'] as $slug) {
            $product = Product::where('slug', $slug)->where('is_active', true)->first();
            if ($product) {
                Wishlist::firstOrCreate([
                    'user_id' => $userId,
                    'product_id' => $product->id,
                ]);
            }
        }

        return $this->getWishlist();
    }
}
