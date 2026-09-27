@extends('layouts.owner')

@section('title', 'Kelola Laporan')
@section('breadcrumb', 'Kelola Laporan')

@section('content')

<h1 class="font-poppins font-semibold text-[24px] text-maroon-900 mb-6">Kelola Laporan</h1>

<form method="GET" action="{{ route('owner.laporan.index') }}" class="flex flex-wrap items-end gap-3 mb-8">
    <div>
        <label class="block text-[12px] text-ink-soft mb-1">Dari Tanggal</label>
        <input type="date" name="mulai" value="{{ $mulai->format('Y-m-d') }}" class="h-11 rounded-xl border-0 bg-white shadow-owner-card px-3 text-[13.5px] focus:ring-2 focus:ring-maroon-700/30">
    </div>
    <div>
        <label class="block text-[12px] text-ink-soft mb-1">Sampai Tanggal</label>
        <input type="date" name="selesai" value="{{ $selesai->format('Y-m-d') }}" class="h-11 rounded-xl border-0 bg-white shadow-owner-card px-3 text-[13.5px] focus:ring-2 focus:ring-maroon-700/30">
    </div>
    <button type="submit" class="h-11 px-5 rounded-xl bg-maroon-900 text-white text-[13.5px] font-medium hover:bg-maroon-800">Tampilkan</button>
    <a href="{{ route('owner.laporan.download', ['mulai' => $mulai->format('Y-m-d'), 'selesai' => $selesai->format('Y-m-d')]) }}"
       class="h-11 px-5 rounded-xl bg-white shadow-owner-card text-[13.5px] text-maroon-800 font-medium flex items-center gap-2">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v13"/><path d="M7 11l5 5 5-5"/><path d="M5 21h14"/></svg>
        Unduh CSV
    </a>
</form>

@php
    $lalu = now()->subMonthNoOverflow();
    $presets = [
        '7 Hari Terakhir' => [now()->subDays(6)->format('Y-m-d'), now()->format('Y-m-d')],
        '30 Hari Terakhir' => [now()->subDays(29)->format('Y-m-d'), now()->format('Y-m-d')],
        'Bulan Ini' => [now()->startOfMonth()->format('Y-m-d'), now()->format('Y-m-d')],
        'Bulan Lalu' => [$lalu->copy()->startOfMonth()->format('Y-m-d'), $lalu->copy()->endOfMonth()->format('Y-m-d')],
    ];

    // Pilihan bulan: 12 bulan terakhir. Terpilih kalau rentang yang tampil pas satu bulan penuh.
    $bulanOptions = ['' => 'Pilih bulan...'];
    for ($i = 0; $i < 12; $i++) {
        $b = now()->startOfMonth()->subMonths($i);
        $bulanOptions[$b->format('Y-m')] = $b->translatedFormat('F Y');
    }
    $satuBulan = $mulai->format('Y-m') === $selesai->format('Y-m')
        && $mulai->day === 1
        && ($selesai->isSameDay($selesai->copy()->endOfMonth()) || $selesai->isSameDay(now()));
    $bulanAktif = $satuBulan ? $mulai->format('Y-m') : '';
@endphp
<div class="flex flex-wrap items-center gap-2 -mt-4 mb-6">
    <p class="text-[12.5px] text-ink-soft mr-2">Menampilkan data {{ $mulai->translatedFormat('d M Y') }} — {{ $selesai->translatedFormat('d M Y') }}</p>
    @foreach ($presets as $label => [$pMulai, $pSelesai])
        @php $aktif = $mulai->format('Y-m-d') === $pMulai && $selesai->format('Y-m-d') === $pSelesai; @endphp
        <a href="{{ route('owner.laporan.index', ['mulai' => $pMulai, 'selesai' => $pSelesai]) }}"
           class="h-8 px-3 rounded-full text-[12px] font-medium flex items-center {{ $aktif ? 'bg-maroon-900 text-white' : 'bg-white shadow-owner-card text-maroon-800 hover:bg-pink-50' }}">{{ $label }}</a>
    @endforeach
    <form method="GET" action="{{ route('owner.laporan.index') }}">
        <x-owner.select name="bulan" variant="compact" :options="$bulanOptions" :value="$bulanAktif" submit />
    </form>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-[18px] shadow-owner-card p-5">
        <p class="text-[12px] text-ink-soft mb-2">Total Pendapatan</p>
        <p class="font-poppins text-[22px] font-semibold text-ink-900 whitespace-nowrap">{{ formatRupiahOwner($totalPendapatan) }}</p>
    </div>
    <div class="bg-white rounded-[18px] shadow-owner-card p-5">
        <p class="text-[12px] text-ink-soft mb-2">Total Pesanan</p>
        <p class="font-poppins text-[22px] font-semibold text-ink-900">{{ $totalPesanan }}</p>
    </div>
    <div class="bg-white rounded-[18px] shadow-owner-card p-5">
        <p class="text-[12px] text-ink-soft mb-2">Rata-rata / Pesanan</p>
        <p class="font-poppins text-[22px] font-semibold text-ink-900 whitespace-nowrap">{{ formatRupiahOwner($rataRata) }}</p>
    </div>
    <div class="bg-white rounded-[18px] shadow-owner-card p-5">
        <p class="text-[12px] text-ink-soft mb-2">Pesanan Dibatalkan</p>
        <p class="font-poppins text-[22px] font-semibold text-ink-900">{{ $pesananDibatalkan }}</p>
    </div>
</div>

<div class="bg-white rounded-[18px] shadow-owner-card p-6 mb-8">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-[16px] font-semibold text-ink-900">Tren Pendapatan {{ $perBulan ? '(per bulan)' : '(per hari)' }}</h2>
    </div>
    @if ($totalPesanan > 0)
        <div class="h-[240px]"><canvas id="trenLaporan"></canvas></div>
    @else
        <p class="py-10 text-center text-[13px] text-ink-soft">Belum ada pendapatan di rentang tanggal ini.</p>
    @endif
</div>

<div class="grid grid-cols-1 xl:grid-cols-[38%_1fr] gap-6 mb-8">
    <div class="bg-white rounded-[18px] shadow-owner-card p-6">
        <h2 class="text-[16px] font-semibold text-ink-900 mb-4">Produk Terlaris</h2>
        <div class="divide-y divide-pink-50">
            @forelse ($produkTerlaris as $p)
                <div class="py-3 flex items-center justify-between gap-3">
                    <p class="text-[13.5px] text-ink-900 truncate">{{ $p->nama }}</p>
                    <div class="text-right shrink-0">
                        <p class="text-[13px] text-ink-900">{{ $p->total_qty }} terjual</p>
                        <p class="text-[11.5px] text-ink-soft whitespace-nowrap">{{ formatRupiahOwner($p->total_omset) }}</p>
                    </div>
                </div>
            @empty
                <p class="py-6 text-center text-[13px] text-ink-soft">Belum ada data di rentang ini.</p>
            @endforelse
        </div>
    </div>

    <div class="bg-white rounded-[18px] shadow-owner-card overflow-hidden">
        <div class="px-6 py-4 border-b border-pink-100">
            <h2 class="text-[16px] font-semibold text-ink-900">Rincian Pesanan</h2>
        </div>
        <div class="overflow-x-auto max-h-[420px] overflow-y-auto owner-notif-scroll">
            <table class="w-full text-[13px]">
                <thead class="sticky top-0 bg-white">
                    <tr class="border-b border-pink-100 text-left text-ink-soft text-[11.5px] uppercase tracking-wide">
                        <th class="px-5 py-2.5 font-medium">Kode</th>
                        <th class="px-5 py-2.5 font-medium">Tanggal</th>
                        <th class="px-5 py-2.5 font-medium">Pembeli</th>
                        <th class="px-5 py-2.5 font-medium">Status</th>
                        <th class="px-5 py-2.5 font-medium text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-pink-50">
                    @forelse ($orders as $order)
                        <tr>
                            <td class="px-5 py-2.5 text-ink-900 whitespace-nowrap">{{ $order->order_code }}</td>
                            <td class="px-5 py-2.5 text-ink-soft whitespace-nowrap">{{ $order->created_at->format('d/m/Y') }}</td>
                            <td class="px-5 py-2.5 text-ink-900">{{ $order->buyer_nama }}</td>
                            <td class="px-5 py-2.5"><x-owner.badge :classes="$order->statusBadgeClasses()">{{ $order->statusLabel() }}</x-owner.badge></td>
                            <td class="px-5 py-2.5 text-right text-ink-900 whitespace-nowrap">{{ formatRupiahOwner($order->total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-ink-soft">Tidak ada pesanan di rentang tanggal ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@if ($totalPesanan > 0)
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    const trenLaporan = @json($tren);
    new Chart(document.getElementById('trenLaporan'), {
        type: 'bar',
        data: {
            labels: trenLaporan.map(d => d.label),
            datasets: [{
                data: trenLaporan.map(d => d.total),
                backgroundColor: '#A5424F',
                borderRadius: 6,
                maxBarThickness: 28,
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => 'Rp ' + Number(ctx.parsed.y).toLocaleString('id-ID') } },
            },
            scales: {
                x: { grid: { display: false }, ticks: { maxTicksLimit: 10, color: '#6B5257', font: { size: 11 } } },
                y: { beginAtZero: true, grid: { color: '#FDF1EF' }, ticks: { color: '#6B5257', font: { size: 11 }, callback: v => Number(v).toLocaleString('id-ID') } },
            }
        }
    });
</script>
@endpush
@endif
