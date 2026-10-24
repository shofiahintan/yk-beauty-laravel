<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function index()
    {
        return view('keranjang', [
            'products' => Product::catalogArray(),
            'bundles' => Collection::catalogArray(),
        ]);
    }

    public function getCart(): JsonResponse
    {
        $items = auth()->user()->cartItems()
            ->with(['product', 'collection'])
            ->get()
            ->map->toCartArray()
            ->values();

        return response()->json([
            'success' => true,
            'cart' => $items,
        ]);
    }

    public function add(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|string',
            'qty' => 'required|integer|min:1|max:99',
            'varian' => 'nullable|string|max:100',
            'ukuran' => 'nullable|string|max:100',
            'type' => 'nullable|string|in:product,bundle',
        ]);

        $type = $validated['type'] ?? 'product';
        $qty = (int) $validated['qty'];
        $varian = !empty($validated['varian']) ? trim($validated['varian']) : null;
        $ukuran = !empty($validated['ukuran']) ? trim($validated['ukuran']) : null;
        $slug = $validated['id'];

        $userId = auth()->id();
        $productId = null;
        $collectionId = null;

        if ($type === 'bundle') {
            $bundle = Collection::where('slug', $slug)->where('is_active', true)->first();
            if (! $bundle) {
                throw ValidationException::withMessages(['id' => 'Paket bundling tidak ditemukan atau tidak aktif.']);
            }
            $collectionId = $bundle->id;
            $lineKey = CartItem::generateLineKey('bundle', $bundle->id, null, null);
        } else {
            $product = Product::with('variants')->where('slug', $slug)->where('is_active', true)->first();
            if (! $product) {
                throw ValidationException::withMessages(['id' => 'Produk tidak ditemukan atau tidak aktif.']);
            }
            $productVariants = $product->variants->pluck('label')->all();
            if (count($productVariants) > 0) {
                if ($varian !== null && ! in_array($varian, $productVariants, true)) {
                    throw ValidationException::withMessages(['varian' => 'Varian yang dipilih tidak valid.']);
                }
            }
            $productId = $product->id;
            $lineKey = CartItem::generateLineKey('product', $product->id, $varian, $ukuran);
        }

        $item = CartItem::where('user_id', $userId)->where('line_key', $lineKey)->first();
        if ($item) {
            $item->qty = min(99, $item->qty + $qty);
            $item->save();
        } else {
            CartItem::create([
                'user_id' => $userId,
                'product_id' => $productId,
                'collection_id' => $collectionId,
                'qty' => $qty,
                'varian' => $varian,
                'ukuran' => $ukuran,
                'line_key' => $lineKey,
            ]);
        }

        return $this->getCart();
    }

    public function setQty(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'index' => 'nullable|integer',
            'line_key' => 'nullable|string',
            'id' => 'nullable|string',
            'varian' => 'nullable|string',
            'ukuran' => 'nullable|string',
            'type' => 'nullable|string',
            'qty' => 'required|integer|min:1|max:99',
        ]);

        $userId = auth()->id();
        $qty = (int) $validated['qty'];

        $item = null;
        if (!empty($validated['line_key'])) {
            $item = CartItem::where('user_id', $userId)->where('line_key', $validated['line_key'])->first();
        } elseif (isset($validated['index'])) {
            $items = CartItem::where('user_id', $userId)->orderBy('id')->get();
            $item = $items->get($validated['index']);
        } elseif (!empty($validated['id'])) {
            $type = $validated['type'] ?? 'product';
            if ($type === 'bundle') {
                $ref = Collection::where('slug', $validated['id'])->first();
                $refId = $ref ? $ref->id : 0;
            } else {
                $ref = Product::where('slug', $validated['id'])->first();
                $refId = $ref ? $ref->id : 0;
            }
            $lineKey = CartItem::generateLineKey($type, $refId, $validated['varian'] ?? null, $validated['ukuran'] ?? null);
            $item = CartItem::where('user_id', $userId)->where('line_key', $lineKey)->first();
        }

        if ($item) {
            $item->update(['qty' => min(99, $qty)]);
        }

        return $this->getCart();
    }

    public function remove(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'index' => 'nullable|integer',
            'line_key' => 'nullable|string',
            'id' => 'nullable|string',
            'varian' => 'nullable|string',
            'ukuran' => 'nullable|string',
            'type' => 'nullable|string',
        ]);

        $userId = auth()->id();
        $item = null;

        if (!empty($validated['line_key'])) {
            $item = CartItem::where('user_id', $userId)->where('line_key', $validated['line_key'])->first();
        } elseif (isset($validated['index'])) {
            $items = CartItem::where('user_id', $userId)->orderBy('id')->get();
            $item = $items->get($validated['index']);
        } elseif (!empty($validated['id'])) {
            $type = $validated['type'] ?? 'product';
            if ($type === 'bundle') {
                $ref = Collection::where('slug', $validated['id'])->first();
                $refId = $ref ? $ref->id : 0;
            } else {
                $ref = Product::where('slug', $validated['id'])->first();
                $refId = $ref ? $ref->id : 0;
            }
            $lineKey = CartItem::generateLineKey($type, $refId, $validated['varian'] ?? null, $validated['ukuran'] ?? null);
            $item = CartItem::where('user_id', $userId)->where('line_key', $lineKey)->first();
        }

        if ($item) {
            $item->delete();
        }

        return $this->getCart();
    }

    public function clear(): JsonResponse
    {
        CartItem::where('user_id', auth()->id())->delete();
        return $this->getCart();
    }

    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'present|array',
            'items.*.id' => 'required|string',
            'items.*.qty' => 'required|integer|min:1|max:99',
            'items.*.varian' => 'nullable|string|max:100',
            'items.*.ukuran' => 'nullable|string|max:100',
            'items.*.type' => 'nullable|string|in:product,bundle',
            'merge' => 'nullable|boolean',
        ]);

        $userId = auth()->id();
        $isMerge = (bool) ($validated['merge'] ?? false);

        if (! $isMerge) {
            CartItem::where('user_id', $userId)->delete();
        }

        foreach ($validated['items'] as $it) {
            $type = $it['type'] ?? 'product';
            $slug = $it['id'];
            $qty = (int) $it['qty'];
            $varian = !empty($it['varian']) ? trim($it['varian']) : null;
            $ukuran = !empty($it['ukuran']) ? trim($it['ukuran']) : null;

            if ($type === 'bundle') {
                $bundle = Collection::where('slug', $slug)->where('is_active', true)->first();
                if (! $bundle) continue;
                $collectionId = $bundle->id;
                $productId = null;
                $lineKey = CartItem::generateLineKey('bundle', $bundle->id, null, null);
            } else {
                $product = Product::with('variants')->where('slug', $slug)->where('is_active', true)->first();
                if (! $product) continue;
                $productVariants = $product->variants->pluck('label')->all();
                if (count($productVariants) > 0 && $varian !== null && ! in_array($varian, $productVariants, true)) {
                    continue;
                }
                $productId = $product->id;
                $collectionId = null;
                $lineKey = CartItem::generateLineKey('product', $product->id, $varian, $ukuran);
            }

            $existing = CartItem::where('user_id', $userId)->where('line_key', $lineKey)->first();
            if ($existing) {
                $existing->qty = min(99, $existing->qty + $qty);
                $existing->save();
            } else {
                CartItem::create([
                    'user_id' => $userId,
                    'product_id' => $productId,
                    'collection_id' => $collectionId,
                    'qty' => $qty,
                    'varian' => $varian,
                    'ukuran' => $ukuran,
                    'line_key' => $lineKey,
                ]);
            }
        }

        return $this->getCart();
    }
}
