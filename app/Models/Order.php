<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    // Alur status di Kelola Transaksi Pemesanan (stepper): menunggu -> diproses -> dikirim -> selesai.
    // "dibatalkan" di luar alur linear, bisa terjadi dari menunggu/diproses.
    public const STATUS_MENUNGGU = 'menunggu_pembayaran';
    public const STATUS_DIPROSES = 'diproses';
    public const STATUS_DIKIRIM = 'dikirim';
    public const STATUS_SELESAI = 'selesai';
    public const STATUS_DIBATALKAN = 'dibatalkan';

    // Urutan stepper (dipakai di slide-over detail pesanan)
    public const STEPPER = [self::STATUS_MENUNGGU, self::STATUS_DIPROSES, self::STATUS_DIKIRIM, self::STATUS_SELESAI];

    public const STATUSES = [
        self::STATUS_MENUNGGU => ['label' => 'Menunggu Pembayaran', 'badge' => 'bg-pink-100 text-maroon-900'],
        self::STATUS_DIPROSES => ['label' => 'Diproses', 'badge' => 'bg-pink-200 text-maroon-900'],
        self::STATUS_DIKIRIM => ['label' => 'Dikirim', 'badge' => 'bg-maroon-800 text-white'],
        self::STATUS_SELESAI => ['label' => 'Selesai', 'badge' => 'bg-maroon-900 text-white'],
        self::STATUS_DIBATALKAN => ['label' => 'Dibatalkan', 'badge' => 'bg-pink-50 text-ink-soft'],
    ];

    protected $fillable = [
        'order_code', 'user_id', 'status', 'subtotal', 'shipping_cost', 'total',
        'shipping_method', 'payment_method', 'kurir', 'nomor_resi',
        'buyer_nama', 'buyer_wa', 'buyer_alamat', 'buyer_kota', 'buyer_kodepos',
        'payment_status', 'midtrans_order_id', 'snap_token', 'paid_at', 'expires_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status]['label'] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    public function statusBadgeClasses(): string
    {
        return self::STATUSES[$this->status]['badge'] ?? 'bg-pink-100 text-maroon-900';
    }

    // Butuh kurir + nomor resi sebelum status boleh dipindah ke "Dikirim" (sesuai mockup)
    public function needsShippingInfo(): bool
    {
        return in_array($this->status, [self::STATUS_DIKIRIM, self::STATUS_SELESAI], true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function restoreStock()
    {
        foreach ($this->items as $item) {
            if ($item->bundle_snapshot) {
                foreach ($item->bundle_snapshot as $bItem) {
                    Product::where('id', $bItem['product_id'])->increment('stok', $item->qty);
                }
            } elseif ($item->product_id) {
                Product::where('id', $item->product_id)->increment('stok', $item->qty);
            }
        }
    }

    // Pengganti ykGenerateOrderId() di orders.js — format YKB-20260914-1234
    public static function generateOrderCode(): string
    {
        do {
            $code = 'YKB-' . now()->format('Ymd') . '-' . random_int(1000, 9999);
        } while (self::where('order_code', $code)->exists());

        return $code;
    }

    // Ubah row DB jadi bentuk object persis yang dulu dipakai orders.js/pesanan.js/riwayat.js
    // dari localStorage (id, createdAt, items[], buyer{}, dst). Dengan ini JS lama gak perlu
    // diubah — cuma sumber datanya yang diganti dari localStorage ke window.__YK_DB_ORDERS__.
    public function toJsShape(): array
    {
        return [
            'id' => $this->order_code,
            'createdAt' => $this->created_at->toIso8601String(),
            'status' => $this->status,
            'items' => $this->items->map(fn (OrderItem $it) => [
                'id' => $it->product?->slug ?? $it->collection?->slug,
                'type' => $it->collection_id ? 'bundle' : 'product',
                'nama' => $it->nama,
                'img' => $it->image ? asset($it->image) : null,
                'harga' => (int) $it->harga,
                'qty' => (int) $it->qty,
                'varian' => $it->varian,
                'ukuran' => $it->ukuran,
                'bundle_items' => $it->bundle_snapshot ? array_map(fn($b) => $b['nama'], $it->bundle_snapshot) : null,
                // Dipakai tombol "Beri Ulasan" di halaman detail pesanan
                'orderItemId' => $it->id,
                'reviewable' => $it->isReviewable(),
                'reviewed' => $it->review !== null,
            ])->values(),
            'subtotal' => (int) $this->subtotal,
            'shippingCost' => (int) $this->shipping_cost,
            'total' => (int) $this->total,
            'shippingMethod' => $this->shipping_method,
            'paymentMethod' => $this->payment_method,
            'buyer' => [
                'nama' => $this->buyer_nama,
                'wa' => $this->buyer_wa,
                'alamat' => $this->buyer_alamat,
                'kota' => $this->buyer_kota,
                'kodepos' => $this->buyer_kodepos,
            ],
        ];
    }
}
