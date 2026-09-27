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
}
