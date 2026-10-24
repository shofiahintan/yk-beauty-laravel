<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Collection extends Model
{
    protected $fillable = [
        'slug', 'title', 'description', 'image', 'harga', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'harga'     => 'integer',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withPivot('sort_order')
            ->orderBy('collection_product.sort_order');
    }

    public function getTitleAttribute($val)
    {
        return __($val);
    }

    public function getDescriptionAttribute($val)
    {
        return __($val);
    }

    /**
     * Jumlah total harga normal/jual produk-produk satuan di dalam paket.
     */
    public function getCoretAttribute(): int
    {
        return (int) $this->products->sum('harga');
    }

    /**
     * Nilai penghematan (coret - harga paket).
     */
    public function getHematAttribute(): int
    {
        return max(0, $this->coret - (int) $this->harga);
    }

    /**
     * Persentase penghematan.
     */
    public function getHematPersenAttribute(): int
    {
        $coret = $this->coret;
        return $coret > 0 ? (int) round(($this->hemat / $coret) * 100) : 0;
    }

    public function getAttribute($key)
    {
        if ($key === 'harga_' . 'coret') {
            return $this->coret;
        }

        return parent::getAttribute($key);
    }

    /**
     * Paket bundling dianggap tersedia HANYA JIKA:
     * - Memiliki minimal 1 produk di dalamnya
     * - Semua produk di dalamnya aktif (is_active = true)
     * - Semua produk di dalamnya memiliki stok > 0
     */
    public function isInStock(): bool
    {
        if ($this->products->isEmpty()) {
            return false;
        }

        foreach ($this->products as $product) {
            if (! $product->is_active || (int) $product->stok <= 0) {
                return false;
            }
        }

        return true;
    }

    public static function catalogArray()
    {
        $collections = self::with('products')->where('is_active', true)->get();

        return $collections->map(function ($c) {
            $items = $c->products->map(fn($p) => $p->nama)->all();
            $inStock = $c->isInStock();
            $stok = $inStock ? (int) $c->products->min('stok') : 0;

            return [
                'id'          => $c->slug,
                'nama'        => $c->title,
                'harga'       => (int) $c->harga,
                'coret'       => $c->coret,
                'hemat'       => $c->hemat,
                'hematPersen' => $c->hemat_persen,
                'img'         => asset($c->image ?? 'assets/images/landing9.jpg'),
                'items'       => $items,
                'stok'        => $stok,
                'inStock'     => $inStock,
            ];
        })->values()->all();
    }
}
