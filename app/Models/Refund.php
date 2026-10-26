<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    public const SUMBER_PEMBELI = 'pembeli';
    public const SUMBER_SISTEM  = 'sistem';

    public const STATUS_DIAJUKAN  = 'diajukan';
    public const STATUS_DISETUJUI = 'disetujui';
    public const STATUS_DITOLAK   = 'ditolak';
    public const STATUS_SELESAI   = 'selesai';

    public const ALASAN_BATAL_SEBELUM_KIRIM = 'batal_sebelum_kirim';
    public const ALASAN_BARANG_RUSAK        = 'barang_rusak';
    public const ALASAN_SALAH_KIRIM         = 'salah_kirim';
    public const ALASAN_TIDAK_SESUAI        = 'tidak_sesuai';
    public const ALASAN_LAINNYA             = 'lainnya';

    public const METODE_MIDTRANS = 'midtrans_dashboard';
    public const METODE_MANUAL   = 'transfer_manual';

    public const MIDTRANS_REFUNDABLE_METHODS = [
        'credit_card',
        'gopay',
        'shopeepay',
        'dana',
        'ovo',
        'qris',
        'kredivo',
        'akulaku',
    ];

    public const STATUSES = [
        self::STATUS_DIAJUKAN => [
            'label' => 'Diajukan',
            'badge' => 'bg-amber-100 text-amber-900',
        ],
        self::STATUS_DISETUJUI => [
            'label' => 'Disetujui',
            'badge' => 'bg-pink-200 text-maroon-900',
        ],
        self::STATUS_DITOLAK => [
            'label' => 'Ditolak',
            'badge' => 'bg-pink-50 text-ink-soft',
        ],
        self::STATUS_SELESAI => [
            'label' => 'Selesai',
            'badge' => 'bg-maroon-900 text-white',
        ],
    ];

    public const ALASAN_OPTIONS = [
        self::ALASAN_BATAL_SEBELUM_KIRIM => 'Batal Sebelum Dikirim',
        self::ALASAN_BARANG_RUSAK        => 'Barang Rusak',
        self::ALASAN_SALAH_KIRIM         => 'Salah Kirim',
        self::ALASAN_TIDAK_SESUAI        => 'Barang Tidak Sesuai',
        self::ALASAN_LAINNYA             => 'Lainnya',
    ];

    protected $fillable = [
        'order_id',
        'user_id',
        'sumber',
        'alasan',
        'keterangan',
        'bukti_paths',
        'status',
        'nominal_barang',
        'nominal_ongkir',
        'metode_pengembalian',
        'referensi_pengembalian',
        'rekening_bank',
        'rekening_nomor',
        'rekening_nama',
        'catatan_owner',
        'diproses_oleh',
        'disetujui_at',
        'selesai_at',
    ];

    protected $casts = [
        'bukti_paths'    => 'array',
        'rekening_nomor' => 'encrypted',
        'nominal_barang' => 'integer',
        'nominal_ongkir' => 'integer',
        'disetujui_at'   => 'datetime',
        'selesai_at'     => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function diprosesOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }

    public function totalNominal(): int
    {
        return (int) ($this->nominal_barang + $this->nominal_ongkir);
    }

    /**
     * Helper untuk cek apakah metode bayar bisa di-refund lewat Midtrans dashboard.
     */
    public static function canRefundViaMidtrans(?string $paymentMethod): bool
    {
        if (! $paymentMethod) {
            return false;
        }

        $method = strtolower(trim($paymentMethod));

        return in_array($method, self::MIDTRANS_REFUNDABLE_METHODS, true);
    }

    /**
     * Helper untuk cek apakah metode bayar wajib mengisi rekening pembeli saat refund.
     */
    public static function requiresBankAccount(?string $paymentMethod): bool
    {
        return ! self::canRefundViaMidtrans($paymentMethod);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status]['label'] ?? ucfirst($this->status);
    }

    public function getStatusBadgeAttribute(): string
    {
        return self::STATUSES[$this->status]['badge'] ?? 'bg-pink-100 text-maroon-900';
    }

    public function getAlasanLabelAttribute(): string
    {
        return self::ALASAN_OPTIONS[$this->alasan] ?? ucfirst(str_replace('_', ' ', $this->alasan));
    }
}
