<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\ProductReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    /**
     * Item pesanan berikutnya yang wajib direview oleh user yang login:
     * pesanan berstatus "selesai", itemnya produk biasa (bukan bundle),
     * dan belum ada review-nya. Diambil yang paling lama dulu (FIFO) —
     * ini yang bikin modal wajib nampilin "satu-satu bergantian".
     *
     * Dipakai baik untuk render pertama kali (partials.review-required-modal)
     * maupun buat "lanjut ke item berikutnya" setelah submit lewat AJAX.
     */
    public static function nextPendingFor(int $userId): ?OrderItem
    {
        return OrderItem::query()
            ->whereNotNull('product_id')
            ->whereDoesntHave('review')
            ->whereHas('order', function ($q) use ($userId) {
                $q->where('user_id', $userId)->where('status', 'selesai');
            })
            ->with('product:id,slug,nama,image')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->orderBy('orders.created_at')
            ->select('order_items.*')
            ->first();
    }

    public static function pendingPayload(?OrderItem $item): ?array
    {
        if (! $item) {
            return null;
        }

        return [
            'orderItemId' => $item->id,
            'productNama' => $item->nama,
            'productImage' => $item->image,
            'varian' => $item->varian,
            'ukuran' => $item->ukuran,
        ];
    }

    // GET /review/pending — dipanggil modal wajib buat cek "masih ada yang belum direview?"
    public function pending(Request $request)
    {
        return response()->json([
            'item' => self::pendingPayload(self::nextPendingFor($request->user()->id)),
        ]);
    }

    // POST /review — submit satu ulasan (dipakai modal wajib & tombol "Beri Ulasan" di /pesanan)
    public function store(Request $request)
    {
        $data = $request->validate([
            'order_item_id' => 'required|integer',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
        ]);

        $item = OrderItem::with('order')
            ->whereNotNull('product_id')
            ->whereDoesntHave('review')
            ->findOrFail($data['order_item_id']);

        // Pastikan item ini beneran milik user yang login & pesanannya sudah selesai —
        // jangan percaya order_item_id dari klien begitu saja.
        if (! $item->order || $item->order->user_id !== $request->user()->id || $item->order->status !== 'selesai') {
            throw ValidationException::withMessages([
                'order_item_id' => 'Item pesanan ini tidak bisa direview.',
            ]);
        }

        DB::transaction(function () use ($item, $data, $request) {
            ProductReview::create([
                'order_item_id' => $item->id,
                'product_id' => $item->product_id,
                'user_id' => $request->user()->id,
                'rating' => $data['rating'],
                'comment' => $data['comment'],
            ]);

            $item->product?->recalculateRating();
        });

        return response()->json([
            'success' => true,
            'next' => self::pendingPayload(self::nextPendingFor($request->user()->id)),
        ]);
    }
}
