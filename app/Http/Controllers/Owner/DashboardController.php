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
        // Pendapatan hanya dihitung dari order yang bukan "dibatalkan"
        $baseQuery = fn () => Order::where('status', '!=', Order::STATUS_DIBATALKAN);

        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $startOfWeek = $today->copy()->startOfWeek();
        $startOfLastWeek = $startOfWeek->copy()->subWeek();

        $pendapatanHariIni = (clone $baseQuery())->whereDate('created_at', $today)->sum('total');
        $pendapatanKemarin = (clone $baseQuery())->whereDate('created_at', $yesterday)->sum('total');
        $pendapatanMingguIni = (clone $baseQuery())->where('created_at', '>=', $startOfWeek)->sum('total');
        $pendapatanMingguLalu = (clone $baseQuery())->whereBetween('created_at', [$startOfLastWeek, $startOfWeek])->sum('total');

        $pesananBaruHariIni = Order::whereDate('created_at', $today)->count();
        $produkStokMenipis = Product::lowStock()->count();

        // Tren penjualan 30 hari terakhir (omset per hari, order yang bukan dibatalkan)
        $mulai = $today->copy()->subDays(29);
        $rows = (clone $baseQuery())
            ->where('created_at', '>=', $mulai)
            ->selectRaw('DATE(created_at) as tanggal, SUM(total) as total')
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
