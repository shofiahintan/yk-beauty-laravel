<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'collection_id',
        'qty',
        'varian',
        'ukuran',
        'line_key',
    ];

    protected $casts = [
        'qty' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public static function generateLineKey(string $type, int $referenceId, ?string $varian = null, ?string $ukuran = null): string
    {
        $v = $varian ? trim($varian) : '';
        $u = $ukuran ? trim($ukuran) : '';
        return md5("{$type}:{$referenceId}:{$v}:{$u}");
    }

    public function lineTotal(): int
    {
        if ($this->collection_id) {
            return (int) ($this->qty * ($this->collection?->harga ?? 0));
        }

        return (int) ($this->qty * ($this->product?->harga ?? 0));
    }

    public function toCartArray(): array
    {
        $isBundle = !empty($this->collection_id);
        $slug = $isBundle ? $this->collection?->slug : $this->product?->slug;

        return [
            'id'     => $slug ?? '',
            'qty'    => (int) $this->qty,
            'varian' => $this->varian,
            'ukuran' => $this->ukuran,
            'type'   => $isBundle ? 'bundle' : 'product',
        ];
    }
}
