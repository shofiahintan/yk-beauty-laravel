<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Notifikasi Owner Panel.
 *
 * Isi notifikasi diturunkan langsung dari kondisi toko (bukan disimpan satu-satu):
 *  - pesanan baru berstatus "Diproses" (sudah masuk, tinggal dikemas/dikirim)
 *  - produk aktif yang stoknya menipis atau habis
 *
 * Yang disimpan hanya status "sudah dibaca" per akun, di tabel owner_notification_reads.
 * Kunci notifikasi stok memuat jumlah stok saat itu, jadi kalau stok berubah lagi
 * (mis. 4 -> 3) notifikasinya muncul lagi sebagai belum dibaca.
 */
class OwnerNotifications
{
    /**
     * @return Collection<int, array<string, mixed>> belum dibaca di atas, lalu yang terbaru
     */
    public static function all(User $user, int $limit = 50): Collection
    {
        $items = collect();

        $orders = Order::where('status', Order::STATUS_DIPROSES)
            ->latest()
            ->take($limit)
            ->get();

        foreach ($orders as $order) {
            $items->push([
                'key' => 'order:'.$order->id,
                'type' => 'order',
                'title' => 'Pesanan baru '.$order->order_code,
                'text' => 'Dari '.$order->buyer_nama.' · '.formatRupiahOwner($order->total).' — perlu diproses.',
                'time' => $order->created_at ?? now(),
                'url' => route('owner.notifikasi.open', ['type' => 'order', 'id' => $order->id]),
            ]);
        }

        $refunds = \App\Models\Refund::with(['order', 'user'])
            ->where('status', \App\Models\Refund::STATUS_DIAJUKAN)
            ->latest()
            ->take($limit)
            ->get();

        foreach ($refunds as $refund) {
            $items->push([
                'key' => 'refund:'.$refund->id,
                'type' => 'refund',
                'title' => 'Pengajuan refund baru',
                'text' => 'Pesanan #'.$refund->order->order_code.' · '.$refund->alasan_label.' ('.$refund->user->name.')',
                'time' => $refund->created_at ?? now(),
                'url' => route('owner.notifikasi.open', ['type' => 'refund', 'id' => $refund->id]),
            ]);
        }

        $products = Product::where('is_active', true)
            ->where('stok', '<', Product::LOW_STOCK_THRESHOLD)
            ->orderBy('stok')
            ->take($limit)
            ->get();

        foreach ($products as $product) {
            $items->push([
                'key' => 'stock:'.$product->id.':'.$product->stok,
                'type' => 'stock',
                'title' => $product->stok <= 0 ? 'Stok habis' : 'Stok menipis',
                'text' => $product->stok <= 0
                    ? $product->nama.' sudah habis.'
                    : $product->nama.' tersisa '.$product->stok.' unit.',
                'time' => $product->updated_at ?? now(),
                'url' => route('owner.notifikasi.open', ['type' => 'stock', 'id' => $product->id]),
            ]);
        }

        $readKeys = $items->isEmpty()
            ? []
            : DB::table('owner_notification_reads')
                ->where('user_id', $user->id)
                ->whereIn('notif_key', $items->pluck('key')->all())
                ->pluck('notif_key')
                ->all();

        return $items
            ->map(fn (array $item) => $item + ['read' => in_array($item['key'], $readKeys, true)])
            ->sort(function (array $a, array $b) {
                if ($a['read'] !== $b['read']) {
                    return $a['read'] <=> $b['read']; // belum dibaca (false) duluan
                }

                return $b['time']->timestamp <=> $a['time']->timestamp;
            })
            ->values();
    }

    public static function unreadCount(Collection $items): int
    {
        return $items->where('read', false)->count();
    }

    /**
     * @param  array<int, string>  $keys
     */
    public static function markRead(User $user, array $keys): void
    {
        foreach (array_unique($keys) as $key) {
            DB::table('owner_notification_reads')->updateOrInsert(
                ['user_id' => $user->id, 'notif_key' => $key],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
