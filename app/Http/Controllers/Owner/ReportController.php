<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        [$mulai, $selesai] = $this->resolveRange($request);

        $baseQuery = fn () => Order::whereBetween('created_at', [$mulai, $selesai])
            ->where('status', '!=', Order::STATUS_DIBATALKAN);

        $totalPendapatan = (clone $baseQuery())->sum('total');
        $totalPesanan = (clone $baseQuery())->count();
        $rataRata = $totalPesanan > 0 ? intdiv($totalPendapatan, $totalPesanan) : 0;
        $pesananDibatalkan = Order::whereBetween('created_at', [$mulai, $selesai])
            ->where('status', Order::STATUS_DIBATALKAN)->count();

        $produkTerlaris = OrderItem::query()
            ->select('nama')
            ->selectRaw('SUM(qty) as total_qty')
            ->selectRaw('SUM(line_total) as total_omset')
            ->whereHas('order', function ($q) use ($mulai, $selesai) {
                $q->whereBetween('created_at', [$mulai, $selesai])
                    ->where('status', '!=', Order::STATUS_DIBATALKAN);
            })
            ->groupBy('nama')
            ->orderByDesc('total_qty')
            ->take(5)
            ->get();

        $orders = (clone $baseQuery())->with('user')->orderByDesc('created_at')->get();

        return view('owner.laporan', [
            'tren' => $this->buildTrend($orders, $mulai, $selesai),
            'perBulan' => $mulai->diffInDays($selesai) > 92,
            'mulai' => $mulai,
            'selesai' => $selesai,
            'totalPendapatan' => $totalPendapatan,
            'totalPesanan' => $totalPesanan,
            'rataRata' => $rataRata,
            'pesananDibatalkan' => $pesananDibatalkan,
            'produkTerlaris' => $produkTerlaris,
            'orders' => $orders,
        ]);
    }

    public function download(Request $request): StreamedResponse
    {
        [$mulai, $selesai] = $this->resolveRange($request);

        $orders = Order::whereBetween('created_at', [$mulai, $selesai])
            ->where('status', '!=', Order::STATUS_DIBATALKAN)
            ->orderBy('created_at')
            ->get();

        $filename = 'laporan-penjualan_'.$mulai->format('Ymd').'-'.$selesai->format('Ymd').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function () use ($orders) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Kode Pesanan', 'Tanggal', 'Pembeli', 'Metode Pembayaran', 'Status', 'Subtotal', 'Ongkir', 'Total']);

            foreach ($orders as $order) {
                fputcsv($handle, [
                    $order->order_code,
                    $order->created_at->format('Y-m-d H:i'),
                    $order->buyer_nama,
                    $order->payment_method,
                    $order->statusLabel(),
                    $order->subtotal,
                    $order->shipping_cost,
                    $order->total,
                ]);
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }

    /**
     * Data grafik pendapatan: per hari, atau per bulan kalau rentangnya lebih dari ~3 bulan.
     *
     * @return array<int, array{label: string, total: int}>
     */
    private function buildTrend($orders, Carbon $mulai, Carbon $selesai): array
    {
        $perBulan = $mulai->diffInDays($selesai) > 92;
        $keyFormat = $perBulan ? 'Y-m' : 'Y-m-d';

        $totals = $orders
            ->groupBy(fn ($o) => $o->created_at->format($keyFormat))
            ->map(fn ($group) => (int) $group->sum('total'));

        $tren = [];
        $cursor = $perBulan ? $mulai->copy()->startOfMonth() : $mulai->copy()->startOfDay();

        while ($cursor->lte($selesai)) {
            $tren[] = [
                'label' => $cursor->translatedFormat($perBulan ? 'M Y' : 'd M'),
                'total' => (int) ($totals[$cursor->format($keyFormat)] ?? 0),
            ];

            $perBulan ? $cursor->addMonthNoOverflow() : $cursor->addDay();
        }

        return $tren;
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveRange(Request $request): array
    {
        // ?bulan=2026-08 -> satu bulan penuh (bulan berjalan dipotong sampai hari ini)
        $bulan = (string) $request->query('bulan');
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $bulan)) {
            $awal = Carbon::createFromFormat('Y-m-d', $bulan.'-01')->startOfDay();

            if ($awal->lte(Carbon::today()->endOfDay())) {
                $akhir = $awal->copy()->endOfMonth();

                return [$awal, $akhir->greaterThan(Carbon::today()->endOfDay()) ? Carbon::today()->endOfDay() : $akhir];
            }
        }

        $mulai = $request->query('mulai')
            ? Carbon::parse($request->query('mulai'))->startOfDay()
            : Carbon::today()->subDays(29)->startOfDay();

        $selesai = $request->query('selesai')
            ? Carbon::parse($request->query('selesai'))->endOfDay()
            : Carbon::today()->endOfDay();

        if ($mulai->greaterThan($selesai)) {
            [$mulai, $selesai] = [$selesai->copy()->startOfDay(), $mulai->copy()->endOfDay()];
        }

        return [$mulai, $selesai];
    }
}
