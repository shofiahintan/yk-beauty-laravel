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

        $totalPendapatan = (int) Order::paidBetween($mulai, $selesai)->sum('subtotal');
        $totalPesanan = Order::paidBetween($mulai, $selesai)->count();
        $rataRata = $totalPesanan > 0 ? intdiv($totalPendapatan, $totalPesanan) : 0;
        $pesananDibatalkan = Order::where('status', Order::STATUS_DIBATALKAN)
            ->whereRaw('COALESCE(paid_at, created_at) >= ?', [$mulai])
            ->whereRaw('COALESCE(paid_at, created_at) < ?', [$selesai])
            ->count();

        $produkTerlaris = \App\Support\ProductSalesReport::topProducts($mulai, $selesai, 5);

        $orders = Order::paidBetween($mulai, $selesai)->with('user')->orderByDesc('created_at')->get();

        return view('owner.laporan', [
            'tren' => $this->buildTrend($orders, $mulai, $selesai),
            'perBulan' => $mulai->diffInDays($selesai) > 92,
            'mulai' => $mulai,
            'selesai' => $selesai->copy()->subSecond(),
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

        $orders = Order::paidBetween($mulai, $selesai)
            ->orderBy('created_at')
            ->get();

        $filename = 'laporan-penjualan_'.$mulai->format('Ymd').'-'.$selesai->copy()->subSecond()->format('Ymd').'.csv';

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
                    ($order->paid_at ?? $order->created_at)->format('Y-m-d H:i'),
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
            ->groupBy(fn ($o) => ($o->paid_at ?? $o->created_at)->timezone('Asia/Jakarta')->format($keyFormat))
            ->map(fn ($group) => (int) $group->sum('subtotal'));

        $tren = [];
        $cursor = $perBulan ? $mulai->copy()->startOfMonth() : $mulai->copy()->startOfDay();

        while ($cursor->lt($selesai)) {
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
        // ?bulan=2026-08 -> satu bulan penuh
        $bulan = (string) $request->query('bulan');
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $bulan)) {
            $awal = Carbon::createFromFormat('Y-m-d', $bulan.'-01', 'Asia/Jakarta')->startOfDay();
            $akhir = $awal->copy()->addMonth()->startOfDay();

            return [$awal, $akhir];
        }

        $mulai = $request->query('mulai')
            ? Carbon::parse($request->query('mulai'), 'Asia/Jakarta')->startOfDay()
            : Carbon::today('Asia/Jakarta')->subDays(29)->startOfDay();

        $selesai = $request->query('selesai')
            ? Carbon::parse($request->query('selesai'), 'Asia/Jakarta')->addDay()->startOfDay()
            : Carbon::today('Asia/Jakarta')->addDay()->startOfDay();

        if ($mulai->greaterThan($selesai)) {
            [$mulai, $selesai] = [$selesai->copy()->subDay()->startOfDay(), $mulai->copy()->addDay()->startOfDay()];
        }

        return [$mulai, $selesai];
    }
}
