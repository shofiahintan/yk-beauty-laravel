<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlogPost extends Model
{
    protected $fillable = [
        'slug', 'category', 'published_at', 'image', 'title', 'excerpt', 'body',
    ];

    protected $casts = [
        'published_at' => 'date',
        'body' => 'array',
    ];

    public function getCategoryAttribute($val)
    {
        return __($val);
    }

    public function getTitleAttribute($val)
    {
        return __($val);
    }

    public function getExcerptAttribute($val)
    {
        return __($val);
    }

    public function getBodyAttribute($val)
    {
        $paragraphs = is_string($val) ? json_decode($val, true) : $val;
        if (!is_array($paragraphs)) {
            return [];
        }
        return array_map(fn($p) => __($p), $paragraphs);
    }

    /**
     * URL foto artikel yang konsisten baik untuk foto seed bawaan (assets/images/...)
     * maupun foto upload panel owner (assets/images/blog/... atau uploads/blog/... via disk public).
     */
    public function getImageUrlAttribute(): string
    {
        if (! $this->image) {
            return asset('assets/images/landing3.jpg');
        }

        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
            return $this->image;
        }

        if (str_starts_with($this->image, 'uploads/')) {
            if (file_exists(public_path($this->image))) {
                return asset($this->image);
            }
            return asset('storage/' . $this->image);
        }

        return asset($this->image);
    }
}
