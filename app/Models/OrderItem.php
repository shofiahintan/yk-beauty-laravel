<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'collection_id', 'bundle_snapshot', 
        'nama', 'image', 'harga', 'qty', 'varian', 'ukuran', 'line_total',
    ];

    protected $casts = [
        'bundle_snapshot' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(ProductReview::class);
    }

    // Cuma item produk biasa (bukan bundle) yang bisa direview — bundle_snapshot
    // berisi beberapa produk sekaligus jadi tidak jelas produk mana yang direview.
    public function isReviewable(): bool
    {
        return $this->product_id !== null;
    }
}
