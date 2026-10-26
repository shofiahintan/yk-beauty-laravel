<?php

namespace App\Support;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class RefundService
{
    /**
     * Cek apakah pesanan memenuhi syarat pengajuan refund.
     */
    public static function canApply(Order $order, ?string &$reasonMessage = null): bool
    {
        if ($order->payment_status !== Order::PAYMENT_PAID) {
            $reasonMessage = 'Pengajuan refund hanya berlaku untuk pesanan yang sudah dibayar.';
            return false;
        }

        // Satu pengajuan aktif per pesanan. Setelah ditolak/selesai, pembeli tidak bisa mengajukan lagi lewat form.
        if ($order->refunds()->exists()) {
            $existing = $order->refunds()->first();
            if ($existing && $existing->status === Refund::STATUS_DITOLAK) {
                $reasonMessage = 'Pengajuan refund Anda sebelumnya telah ditolak. Silakan hubungi customer service kami lewat pesan chat.';
            } else {
                $reasonMessage = 'Pesanan ini sudah memiliki riwayat pengajuan refund.';
            }
            return false;
        }

        if ($order->status === Order::STATUS_DIPROSES) {
            // (a) Batal sebelum dikirim
            return true;
        }

        if ($order->status === Order::STATUS_DIKIRIM) {
            // (b) Barang bermasalah saat dikirim (tanpa batas waktu)
            return true;
        }

        if ($order->status === Order::STATUS_SELESAI) {
            // (b) Barang bermasalah maks 48 jam sejak pesanan selesai
            $completedAt = self::getCompletedAt($order);
            if (! $completedAt) {
                $reasonMessage = 'Riwayat status selesai tidak ditemukan.';
                return false;
            }

            $windowHours = (int) config('shop.refund_window_hours', 48);
            $deadline = $completedAt->copy()->addHours($windowHours);

            if (now()->greaterThan($deadline)) {
                $reasonMessage = "Batas waktu pengajuan refund ({$windowHours} jam sejak pesanan selesai) telah berakhir.";
                return false;
            }

            return true;
        }

        $reasonMessage = 'Status pesanan saat ini tidak memenuhi syarat untuk pengajuan refund.';
        return false;
    }

    /**
     * Waktu kapan pesanan pertama kali berubah status ke 'selesai'.
     */
    public static function getCompletedAt(Order $order): ?Carbon
    {
        $history = OrderStatusHistory::where('order_id', $order->id)
            ->where('status', Order::STATUS_SELESAI)
            ->orderBy('id', 'asc')
            ->first();

        return $history?->created_at ?? ($order->status === Order::STATUS_SELESAI ? $order->updated_at : null);
    }

    /**
     * Daftar alasan yang diizinkan untuk pesanan ini.
     *
     * @return array<int, string>
     */
    public static function allowedReasons(Order $order): array
    {
        if (! self::canApply($order)) {
            return [];
        }

        if ($order->status === Order::STATUS_DIPROSES) {
            return [Refund::ALASAN_BATAL_SEBELUM_KIRIM];
        }

        if (in_array($order->status, [Order::STATUS_DIKIRIM, Order::STATUS_SELESAI], true)) {
            return [
                Refund::ALASAN_BARANG_RUSAK,
                Refund::ALASAN_SALAH_KIRIM,
                Refund::ALASAN_TIDAK_SESUAI,
                Refund::ALASAN_LAINNYA,
            ];
        }

        return [];
    }

    /**
     * Pengajuan refund oleh pembeli.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $uploadedFiles
     */
    public static function apply(Order $order, User $user, array $data, array $uploadedFiles = []): Refund
    {
        return DB::transaction(function () use ($order, $user, $data, $uploadedFiles) {
            $lockedOrder = Order::lockForUpdate()->findOrFail($order->id);

            if ((int) $lockedOrder->user_id !== (int) $user->id) {
                throw ValidationException::withMessages(['refund' => 'Anda tidak memiliki hak atas pesanan ini.']);
            }

            if (! self::canApply($lockedOrder, $reasonMessage)) {
                throw ValidationException::withMessages(['refund' => $reasonMessage ?? 'Pesanan tidak memenuhi syarat refund.']);
            }

            $alasan = $data['alasan'] ?? '';
            $allowed = self::allowedReasons($lockedOrder);
            if (! in_array($alasan, $allowed, true)) {
                throw ValidationException::withMessages(['alasan' => 'Alasan pengajuan refund tidak sesuai dengan status pesanan saat ini.']);
            }

            $storedPaths = [];
            // (b) Barang bermasalah wajib 1-3 foto bukti
            if ($alasan !== Refund::ALASAN_BATAL_SEBELUM_KIRIM) {
                if (empty($uploadedFiles) || count($uploadedFiles) < 1 || count($uploadedFiles) > 3) {
                    throw ValidationException::withMessages(['bukti' => 'Wajib melampirkan 1 sampai 3 foto bukti.']);
                }

                foreach ($uploadedFiles as $file) {
                    if (! $file instanceof UploadedFile || ! $file->isValid()) {
                        throw ValidationException::withMessages(['bukti' => 'Foto bukti tidak valid.']);
                    }
                    $storedPaths[] = $file->store('refunds', 'local');
                }
            }

            // Validasi rekening jika metode pembayaran bukan Midtrans direct refund
            if (Refund::requiresBankAccount($lockedOrder->payment_method)) {
                if (empty($data['rekening_bank']) || empty($data['rekening_nomor']) || empty($data['rekening_nama'])) {
                    throw ValidationException::withMessages([
                        'rekening' => 'Informasi rekening bank wajib diisi untuk metode pembayaran ini.',
                    ]);
                }
            }

            $refund = Refund::create([
                'order_id'       => $lockedOrder->id,
                'user_id'        => $user->id,
                'sumber'         => Refund::SUMBER_PEMBELI,
                'alasan'         => $alasan,
                'keterangan'     => $data['keterangan'] ?? null,
                'bukti_paths'    => $storedPaths ?: null,
                'status'         => Refund::STATUS_DIAJUKAN,
                'nominal_barang' => 0,
                'nominal_ongkir' => 0,
                'rekening_bank'  => $data['rekening_bank'] ?? null,
                'rekening_nomor' => $data['rekening_nomor'] ?? null,
                'rekening_nama'  => $data['rekening_nama'] ?? null,
            ]);

            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'status'   => $lockedOrder->status,
                'catatan'  => 'Pengajuan refund diajukan oleh pembeli (' . $refund->alasan_label . ')',
            ]);

            return $refund;
        });
    }

    /**
     * Persetujuan refund oleh owner.
     */
    public static function approve(
        Refund $refund,
        User $owner,
        ?int $nominalBarang = null,
        bool $sertakanOngkir = false,
        ?string $catatan = null
    ): Refund {
        return DB::transaction(function () use ($refund, $owner, $nominalBarang, $sertakanOngkir, $catatan) {
            $lockedRefund = Refund::lockForUpdate()->findOrFail($refund->id);
            $lockedOrder = Order::lockForUpdate()->findOrFail($lockedRefund->order_id);

            if ($lockedRefund->status !== Refund::STATUS_DIAJUKAN) {
                throw ValidationException::withMessages([
                    'refund' => 'Hanya refund berstatus diajukan yang dapat disetujui.',
                ]);
            }

            $sisaBarang = max(0, (int) $lockedOrder->subtotal - (int) $lockedOrder->refunded_barang);
            $sisaOngkir = max(0, (int) $lockedOrder->shipping_cost - (int) $lockedOrder->refunded_ongkir);
            $sisaTotal  = max(0, (int) $lockedOrder->total - (int) $lockedOrder->refunded_barang - (int) $lockedOrder->refunded_ongkir);

            if ($nominalBarang === null) {
                $nominalBarang = $sisaBarang;
            }

            if ($nominalBarang < 0 || $nominalBarang > $sisaBarang) {
                throw ValidationException::withMessages([
                    'nominal_barang' => 'Nominal barang tidak boleh melebihi sisa harga barang (' . formatRupiahOwner($sisaBarang) . ').',
                ]);
            }

            $nominalOngkir = $sertakanOngkir ? $sisaOngkir : 0;

            if (($nominalBarang + $nominalOngkir) > $sisaTotal) {
                throw ValidationException::withMessages([
                    'nominal' => 'Total nominal refund melebihi sisa pesanan (' . formatRupiahOwner($sisaTotal) . ').',
                ]);
            }

            $lockedRefund->update([
                'status'         => Refund::STATUS_DISETUJUI,
                'nominal_barang' => $nominalBarang,
                'nominal_ongkir' => $nominalOngkir,
                'catatan_owner'  => $catatan ?? $lockedRefund->catatan_owner,
                'diproses_oleh'  => $owner->id,
                'disetujui_at'   => now(),
            ]);

            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'status'   => $lockedOrder->status,
                'catatan'  => 'Refund disetujui oleh owner sebesar ' . formatRupiahOwner($lockedRefund->totalNominal())
                    . ($nominalOngkir > 0 ? ' (termasuk ongkir)' : ''),
            ]);

            // Jika alasan batal_sebelum_kirim: batalkan pesanan dan kembalikan stok sekali
            if ($lockedRefund->alasan === Refund::ALASAN_BATAL_SEBELUM_KIRIM) {
                OrderLifecycle::cancel($lockedOrder, 'Pesanan dibatalkan karena refund batal sebelum kirim disetujui');
            }

            return $lockedRefund;
        });
    }

    /**
     * Penolakan refund oleh owner.
     */
    public static function reject(Refund $refund, User $owner, string $catatan): Refund
    {
        return DB::transaction(function () use ($refund, $owner, $catatan) {
            $lockedRefund = Refund::lockForUpdate()->findOrFail($refund->id);
            $lockedOrder = Order::lockForUpdate()->findOrFail($lockedRefund->order_id);

            if ($lockedRefund->status !== Refund::STATUS_DIAJUKAN) {
                throw ValidationException::withMessages([
                    'refund' => 'Hanya refund berstatus diajukan yang dapat ditolak.',
                ]);
            }

            $catatan = trim($catatan);
            if (empty($catatan)) {
                throw ValidationException::withMessages([
                    'catatan_owner' => 'Alasan penolakan refund wajib diisi.',
                ]);
            }

            $lockedRefund->update([
                'status'        => Refund::STATUS_DITOLAK,
                'catatan_owner' => $catatan,
                'diproses_oleh' => $owner->id,
            ]);

            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'status'   => $lockedOrder->status,
                'catatan'  => 'Pengajuan refund ditolak oleh owner. Alasan: ' . $catatan,
            ]);

            return $lockedRefund;
        });
    }

    /**
     * Tandai pengembalian dana telah selesai (manual oleh owner).
     */
    public static function complete(
        Refund $refund,
        User $owner,
        string $metode,
        string $referensi,
        ?string $catatan = null
    ): Refund {
        return DB::transaction(function () use ($refund, $owner, $metode, $referensi, $catatan) {
            $lockedRefund = Refund::lockForUpdate()->findOrFail($refund->id);
            $lockedOrder = Order::lockForUpdate()->findOrFail($lockedRefund->order_id);

            if ($lockedRefund->status === Refund::STATUS_SELESAI) {
                throw ValidationException::withMessages([
                    'refund' => 'Pengembalian dana untuk pengajuan ini sudah berstatus selesai.',
                ]);
            }

            if ($lockedRefund->status !== Refund::STATUS_DISETUJUI) {
                throw ValidationException::withMessages([
                    'refund' => 'Hanya pengajuan refund yang telah disetujui yang dapat ditandai selesai.',
                ]);
            }

            if (! in_array($metode, [Refund::METODE_MIDTRANS, Refund::METODE_MANUAL], true)) {
                throw ValidationException::withMessages([
                    'metode_pengembalian' => 'Metode pengembalian dana tidak valid.',
                ]);
            }

            $referensi = trim($referensi);
            if (empty($referensi)) {
                throw ValidationException::withMessages([
                    'referensi_pengembalian' => 'Nomor referensi / bukti pengembalian dana wajib diisi.',
                ]);
            }

            $lockedRefund->update([
                'status'                 => Refund::STATUS_SELESAI,
                'metode_pengembalian'    => $metode,
                'referensi_pengembalian' => $referensi,
                'catatan_owner'          => $catatan ?? $lockedRefund->catatan_owner,
                'diproses_oleh'          => $owner->id,
                'selesai_at'             => now(),
            ]);

            $newRefundedBarang = (int) $lockedOrder->refunded_barang + (int) $lockedRefund->nominal_barang;
            $newRefundedOngkir = (int) $lockedOrder->refunded_ongkir + (int) $lockedRefund->nominal_ongkir;
            $totalRefunded = $newRefundedBarang + $newRefundedOngkir;

            $newPaymentStatus = ($totalRefunded >= (int) $lockedOrder->total)
                ? Order::PAYMENT_REFUNDED
                : Order::PAYMENT_PARTIAL_REFUND;

            $lockedOrder->update([
                'refunded_barang' => $newRefundedBarang,
                'refunded_ongkir' => $newRefundedOngkir,
                'payment_status'  => $newPaymentStatus,
            ]);

            $metodeLabel = $metode === Refund::METODE_MIDTRANS ? 'Dashboard Midtrans' : 'Transfer Manual';
            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'status'   => $lockedOrder->status,
                'catatan'  => 'Pengembalian dana sebesar ' . formatRupiahOwner($lockedRefund->totalNominal())
                    . ' telah selesai via ' . $metodeLabel . ' (Ref: ' . $referensi . ')',
            ]);

            return $lockedRefund;
        });
    }

    /**
     * Buat refund otomatis oleh sistem (idempotent, tidak dobel).
     */
    public static function createAutomaticRefund(
        Order $order,
        string $alasan = Refund::ALASAN_BATAL_SEBELUM_KIRIM,
        string $keterangan = 'Refund otomatis oleh sistem'
    ): ?Refund {
        return DB::transaction(function () use ($order, $alasan, $keterangan) {
            $lockedOrder = Order::lockForUpdate()->findOrFail($order->id);

            // Jika sudah ada refund (apa pun statusnya), jangan buat lagi (tidak boleh dobel)
            if ($lockedOrder->refunds()->exists()) {
                return null;
            }

            $refund = Refund::create([
                'order_id'       => $lockedOrder->id,
                'user_id'        => $lockedOrder->user_id,
                'sumber'         => Refund::SUMBER_SISTEM,
                'alasan'         => $alasan,
                'keterangan'     => $keterangan,
                'status'         => Refund::STATUS_DISETUJUI,
                'nominal_barang' => (int) $lockedOrder->subtotal,
                'nominal_ongkir' => (int) $lockedOrder->shipping_cost,
                'disetujui_at'   => now(),
            ]);

            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'status'   => $lockedOrder->status,
                'catatan'  => 'Refund otomatis disetujui oleh sistem sebesar ' . formatRupiahOwner($lockedOrder->total) . ' (perlu pengembalian dana manual oleh owner)',
            ]);

            return $refund;
        });
    }
}
