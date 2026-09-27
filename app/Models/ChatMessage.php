<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    public const SENDER_CUSTOMER = 'customer';
    public const SENDER_OWNER = 'owner';

    protected $fillable = [
        'user_id',
        'sender',
        'is_bot',
        'body',
        'image_path',
        'read_at',
    ];

    protected $casts = [
        'is_bot' => 'boolean',
        'read_at' => 'datetime',
    ];

    /** Pelanggan pemilik ruang chat ini (bukan selalu pengirimnya). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Pesan dari pelanggan yang belum dibuka Owner. */
    public function scopeUnreadByOwner($query)
    {
        return $query->where('sender', self::SENDER_CUSTOMER)->whereNull('read_at');
    }

    /** Pesan dari Owner yang belum dibuka pelanggan. */
    public function scopeUnreadByCustomer($query)
    {
        return $query->where('sender', self::SENDER_OWNER)->whereNull('read_at');
    }

    /**
     * Bentuk pesan yang dikirim ke browser (JSON endpoint maupun payload broadcast).
     * Foto tidak diberi URL publik — dilayani lewat route chat.image yang cek pemilik.
     */
    public function toChatArray(): array
    {
        return [
            'id' => (int) $this->id,
            'user_id' => (int) $this->user_id,
            'sender' => $this->sender,
            'is_bot' => (bool) $this->is_bot,
            'body' => $this->body,
            'image_url' => $this->image_path ? route('chat.image', $this->id) : null,
            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
