<?php

namespace App\Models;

// Illuminate\Foundation\Auth\User as Authenticatable
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // Role: owner (pemilik, akses penuh /owner), customer (pembeli storefront, default).
    // Satu form login untuk semuanya — redirect setelah auth ditentukan dari kolom ini.
    public const ROLE_OWNER = 'owner';
    public const ROLE_CUSTOMER = 'customer';

    protected $fillable = [
        'name',
        'email',
        'google_id', // diisi kalau user daftar/login lewat Google
        'password',
        'role',
        'is_active',
        'whatsapp', // dulu "wa" di akun.js
        'alamat',
        'foto',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Laravel 9 belum punya cast otomatis "hashed", jadi password di-hash manual
    // saat register/update (bukan lewat $casts) — lihat catatan di README.
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }
}
