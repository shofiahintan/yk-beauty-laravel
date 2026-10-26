<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Refund;
use App\Support\RefundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class RefundController extends Controller
{
    /**
     * Ajukan refund oleh pembeli.
     */
    public function store(Request $request, string $orderCode)
    {
        $order = Order::where('order_code', $orderCode)->firstOrFail();

        if ((int) $order->user_id !== (int) auth()->id()) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $validated = $request->validate([
            'alasan'         => ['required', 'string'],
            'keterangan'     => ['nullable', 'string', 'max:1000'],
            'bukti'          => ['nullable', 'array', 'max:3'],
            'bukti.*'        => ['file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'rekening_bank'  => ['nullable', 'string', 'max:50'],
            'rekening_nomor' => ['nullable', 'string', 'max:50'],
            'rekening_nama'  => ['nullable', 'string', 'max:100'],
        ]);

        $files = $request->file('bukti', []);
        if (! is_array($files)) {
            $files = $files ? [$files] : [];
        }

        try {
            $refund = RefundService::apply($order, $request->user(), $validated, $files);
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors'  => $e->errors(),
                ], 422);
            }
            throw $e;
        }

        $message = __('Pengajuan pengembalian dana berhasil dikirim.');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'refund'  => $refund,
            ]);
        }

        return redirect()->route('pesanan', ['order_id' => $order->order_code])
            ->with('status', $message);
    }

    /**
     * Tampilkan foto bukti privat refund (hanya pemilik pesanan & owner).
     */
    public function evidence(Request $request, string $orderCode, int $index)
    {
        $order = Order::where('order_code', $orderCode)->firstOrFail();
        $user = $request->user();

        abort_unless($user->isOwner() || (int) $order->user_id === (int) $user->id, 404);

        $refund = $order->refunds()->firstOrFail();
        $paths = $refund->bukti_paths ?? [];

        abort_unless(isset($paths[$index]), 404);
        $filePath = $paths[$index];

        abort_unless(Storage::disk('local')->exists($filePath), 404);

        return Storage::disk('local')->response($filePath, null, [
            'Cache-Control'          => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
