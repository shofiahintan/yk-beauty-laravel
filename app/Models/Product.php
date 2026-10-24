<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    use HasFactory;

    // Batas "Stok Menipis" — otomatis, bukan diset manual per produk (keputusan owner)
    public const LOW_STOCK_THRESHOLD = 5;

    protected $fillable = [
        'slug', 'nama', 'brand', 'kategori', 'harga', 'harga_normal', 'diskon_persen',
        'badge', 'badge_class', 'rating', 'review_count', 'deskripsi',
        'image', 'stok', 'is_active',
    ];

    protected $casts = [
        'harga'         => 'integer',
        'harga_normal'  => 'integer',
        'diskon_persen' => 'integer',
        'stok'          => 'integer',
        'is_active'     => 'boolean',
        'rating'        => 'decimal:1',
    ];

    // ──────────────────────────────────────────────
    // Relations
    // ──────────────────────────────────────────────

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)
            ->whereNull('product_variant_id')
            ->orderBy('sort_order');
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class)->withPivot('sort_order');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class)->latest();
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->latest();
    }

    // ──────────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────────

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->where('stok', '>', 0)->where('stok', '<', self::LOW_STOCK_THRESHOLD);
    }

    public function scopeOutOfStock(Builder $query): Builder
    {
        return $query->where('stok', '<=', 0);
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    // Dipanggil tiap ada review baru masuk (lihat ReviewController::store()).
    // rating & review_count jadi selalu cermin dari data ulasan asli, bukan angka statis.
    public function recalculateRating(): void
    {
        $this->update([
            'rating'       => (float) $this->reviews()->avg('rating') ?: 0,
            'review_count' => $this->reviews()->count(),
        ]);
    }

    // Dipakai buat cek stok gaya lama: p.stok !== false
    public function isInStock(): bool
    {
        return $this->stok > 0;
    }

    public function isLowStock(): bool
    {
        return $this->stok > 0 && $this->stok < self::LOW_STOCK_THRESHOLD;
    }

    // Badge kolom "Status" di Kelola Produk: Nonaktif (is_active=false) > Stok Menipis (stok<5) > Habis > Aktif
    public function statusLabel(): string
    {
        if (! $this->is_active) {
            return 'Nonaktif';
        }

        if ($this->stok <= 0) {
            return 'Habis';
        }

        if ($this->isLowStock()) {
            return 'Stok Menipis';
        }

        return 'Aktif';
    }

    /**
     * Ubah stok secara atomik dan catat di stock_movements.
     *
     * @param  int       $delta   Positif = tambah, negatif = kurang
     * @param  string    $alasan  Keterangan pergerakan stok
     * @param  int|null  $orderId FK ke orders (opsional)
     * @param  int|null  $userId  FK ke users (opsional)
     */
    public static function adjustStock(self $product, int $delta, string $alasan, ?int $orderId = null, ?int $userId = null): void
    {
        DB::transaction(function () use ($product, $delta, $alasan, $orderId, $userId) {
            $product = self::lockForUpdate()->findOrFail($product->id);

            $newStok = max(0, $product->stok + $delta);
            $product->update(['stok' => $newStok]);

            StockMovement::create([
                'product_id' => $product->id,
                'order_id'   => $orderId,
                'user_id'    => $userId,
                'delta'      => $delta,
                'stok_after' => $newStok,
                'alasan'     => $alasan,
            ]);
        });
    }

    /**
     * Hitung harga jual dari harga normal dan diskon persen.
     * Dibulatkan ke Rp100 terdekat.
     */
    public static function calculateSellingPrice(int $hargaNormal, ?int $diskonPersen = null): int
    {
        $diskon = (int) ($diskonPersen ?? 0);
        if ($diskon > 0) {
            return (int) (round(($hargaNormal * (1 - ($diskon / 100))) / 100) * 100);
        }

        return $hargaNormal;
    }

    /**
     * Accessor harga coret: = harga_normal jika diskon_persen > 0, selain itu null.
     */
    public function getCoretAttribute(): ?int
    {
        return ($this->diskon_persen && $this->diskon_persen > 0) ? (int) $this->harga_normal : null;
    }

    public function getAttribute($key)
    {
        if ($key === 'harga_' . 'coret') {
            return $this->coret;
        }

        return parent::getAttribute($key);
    }

    public static function catalogArray()
    {
        return static::where('is_active', true)
            ->with(['variants.images', 'images'])
            ->orderByDesc('id')
            ->get()
            ->map(function (Product $p) {
                return [
                    'id'            => $p->slug,
                    'nama'          => $p->nama,
                    'brand'         => $p->brand,
                    'kategori'      => $p->kategori,
                    'harga'         => (int) $p->harga,
                    'harga_normal'  => (int) $p->harga_normal,
                    'diskon_persen' => $p->diskon_persen,
                    'coret'         => $p->coret,
                    'badge'         => $p->badge ?? '',
                    'badgeClass'    => $p->badge_class ?? '',
                    'rating'        => (int) round((float) $p->rating), // dibulatkan, bukan dipotong, biar 4.6 tampil 5 bintang bukan 4
                    'count'         => $p->review_count,
                    'img'           => asset($p->image),
                    'stok'          => $p->stok,
                    'galeri'        => $p->images->isNotEmpty()
                        ? $p->images->map(fn ($img) => asset($img->path))->values()
                        : null,
                    'deskripsi'     => $p->deskripsi,
                    'varian'        => $p->variants->isNotEmpty()
                        ? $p->variants->pluck('label')->values()
                        : null,
                    'varianGaleri'  => $p->variants->isNotEmpty()
                        ? $p->variants->mapWithKeys(fn ($v) => [$v->label => $v->images->map(fn ($img) => asset($img->path))->values()])
                        : null,
                    'ukuran'        => null,
                ];
            });
    }
}
