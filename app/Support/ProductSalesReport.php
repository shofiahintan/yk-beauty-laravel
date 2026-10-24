<?php

namespace App\Support;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Laporan produk terlaris per product_id.
 */
class ProductSalesReport
{
    /**
     * Ambil produk terlaris dalam rentang waktu.
     * Menggunakan product_id (bukan nama) agar lebih akurat.
     *
     * @return Collection<int, array{product_id: int, nama: string, total_qty: int, total_omset: int}>
     */
    public static function topProducts(Carbon $mulai, Carbon $selesai, int $limit = 5): Collection
    {
        $items = OrderItem::whereHas('order', function ($q) use ($mulai, $selesai) {
            $q->paidBetween($mulai, $selesai);
        })->with('product')->get();

        $stats = [];

        foreach ($items as $item) {
            if ($item->bundle_snapshot && is_array($item->bundle_snapshot)) {
                // Total nilai komponen paket berdasar snapshot harga satuan
                $totalKomponen = 0;
                foreach ($item->bundle_snapshot as $b) {
                    $bQty = $b['qty'] ?? 1;
                    $bHarga = $b['harga'] ?? 0;
                    $totalKomponen += $bQty * $bHarga;
                }

                foreach ($item->bundle_snapshot as $b) {
                    $pid = $b['product_id'];
                    $bQty = ($b['qty'] ?? 1) * $item->qty;
                    $bHarga = ($b['harga'] ?? 0) * ($b['qty'] ?? 1);
                    $proporsi = $totalKomponen > 0 ? ($bHarga / $totalKomponen) : (1 / count($item->bundle_snapshot));
                    $omzetProporsional = (int) round($item->line_total * $proporsi);

                    if (! isset($stats[$pid])) {
                        $stats[$pid] = [
                            'product_id'  => $pid,
                            'nama'        => $b['nama'] ?? "Produk #{$pid}",
                            'total_qty'   => 0,
                            'total_omset' => 0,
                        ];
                    }

                    $stats[$pid]['total_qty'] += $bQty;
                    $stats[$pid]['total_omset'] += $omzetProporsional;
                }
            } elseif ($item->product_id) {
                $pid = $item->product_id;
                if (! isset($stats[$pid])) {
                    $stats[$pid] = [
                        'product_id'  => $pid,
                        'nama'        => $item->nama,
                        'total_qty'   => 0,
                        'total_omset' => 0,
                    ];
                }

                $stats[$pid]['total_qty'] += $item->qty;
                $stats[$pid]['total_omset'] += (int) $item->line_total;
            }
        }

        return collect($stats)
            ->sortByDesc('total_qty')
            ->take($limit)
            ->values();
    }
}
