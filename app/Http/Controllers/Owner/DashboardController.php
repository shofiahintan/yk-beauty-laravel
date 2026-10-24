<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today('Asia/Jakarta');
        $tomorrow = $today->copy()->addDay();
        $yesterday = $today->copy()->subDay();
        $startOfWeek = $today->copy()->startOfWeek();
        $nextWeek = $startOfWeek->copy()->addWeek();
        $startOfLastWeek = $startOfWeek->copy()->subWeek();

        $pendapatanHariIni = (int) Order::paidBetween($today, $tomorrow)->sum('subtotal');
        $pendapatanKemarin = (int) Order::paidBetween($yesterday, $today)->sum('subtotal');
        $pendapatanMingguIni = (int) Order::paidBetween($startOfWeek, $nextWeek)->sum('subtotal');
        $pendapatanMingguLalu = (int) Order::paidBetween($startOfLastWeek, $startOfWeek)->sum('subtotal');

        $pesananBaruHariIni = Order::where('created_at', '>=', $today)
            ->where('created_at', '<', $tomorrow)
            ->count();
        $produkStokMenipis = Product::lowStock()->count();

        // Tren penjualan 30 hari terakhir (omset per hari, order paid, tanpa ongkir)
        $mulai = $today->copy()->subDays(29);
        $rows = Order::paidBetween($mulai, $tomorrow)
            ->selectRaw('DATE(COALESCE(paid_at, created_at)) as tanggal, SUM(subtotal) as total')
            ->groupBy('tanggal')
            ->pluck('total', 'tanggal');

        $tren = collect(range(0, 29))->map(function ($i) use ($mulai, $rows) {
            $date = $mulai->copy()->addDays($i);
            return [
                'label' => $date->translatedFormat('d M'),
                'total' => (int) ($rows[$date->toDateString()] ?? 0),
            ];
        });

        $pesananTerbaru = Order::with('user')->latest()->take(5)->get();

        return view('owner.dashboard', [
            'pendapatanHariIni' => $pendapatanHariIni,
            'pendapatanMingguIni' => $pendapatanMingguIni,
            'pesananBaruHariIni' => $pesananBaruHariIni,
            'produkStokMenipis' => $produkStokMenipis,
            'perbandinganHariIni' => $this->percentLabel($pendapatanHariIni, $pendapatanKemarin, 'dari kemarin'),
            'perbandinganMingguIni' => $this->percentLabel($pendapatanMingguIni, $pendapatanMingguLalu, 'dari pekan lalu'),
            'tren' => $tren,
            'pesananTerbaru' => $pesananTerbaru,
        ]);
    }

    private function percentLabel(int|float $now, int|float $prev, string $suffix): string
    {
        if ($prev <= 0) {
            return $now > 0 ? "Baru mulai tercatat {$suffix}" : "Belum ada data {$suffix}";
        }

        $diff = round((($now - $prev) / $prev) * 100, 1);
        $sign = $diff >= 0 ? '+' : '';

        return "{$sign}{$diff}% {$suffix}";
    }
}
