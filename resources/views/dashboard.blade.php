@extends('layouts.owner')

@section('title', 'Dashboard')
@section('breadcrumb', 'Ringkasan performa toko')

@section('content')
    <p class="text-[12px] tracking-widest uppercase text-maroon-700 font-semibold mb-1">Apothecary Intelligence · Real-time</p>
    <h1 class="font-poppins text-[28px] font-semibold text-maroon-900 mb-8">Executive Dashboard</h1>

    {{-- Row 1: KPI cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-[18px] shadow-owner-card p-5">
            <p class="text-[12px] text-ink-soft mb-2">Pendapatan Hari Ini</p>
            <p class="font-poppins text-[24px] font-semibold text-ink-900 whitespace-nowrap">{{ formatRupiahOwner($pendapatanHariIni) }}</p>
            <p class="text-[11px] text-ink-soft mt-1">{{ $perbandinganHariIni }}</p>
        </div>
        <div class="bg-white rounded-[18px] shadow-owner-card p-5">
            <p class="text-[12px] text-ink-soft mb-2">Pendapatan Minggu Ini</p>
            <p class="font-poppins text-[24px] font-semibold text-ink-900 whitespace-nowrap">{{ formatRupiahOwner($pendapatanMingguIni) }}</p>
            <p class="text-[11px] text-ink-soft mt-1">{{ $perbandinganMingguIni }}</p>
        </div>
        <div class="bg-white rounded-[18px] shadow-owner-card p-5">
            <p class="text-[12px] text-ink-soft mb-2">Pesanan Baru</p>
            <p class="font-poppins text-[24px] font-semibold text-ink-900">{{ $pesananBaruHariIni }}</p>
            <p class="text-[11px] text-ink-soft mt-1">Hari ini</p>
        </div>
        <div class="bg-white rounded-[18px] shadow-owner-card p-5">
            <p class="text-[12px] text-ink-soft mb-2">Produk Stok Menipis</p>
            <p class="font-poppins text-[24px] font-semibold {{ $produkStokMenipis > 0 ? 'text-maroon-900' : 'text-ink-900' }}">{{ $produkStokMenipis }}</p>
            <p class="text-[11px] text-ink-soft mt-1">{{ $produkStokMenipis > 0 ? 'Perlu restock segera' : 'Semua stok aman' }}</p>
        </div>
    </div>

    {{-- Row 2: chart + latest orders --}}
    <div class="grid grid-cols-1 xl:grid-cols-[65%_1fr] gap-8">
        <div class="bg-white rounded-[18px] shadow-owner-card p-6">
            <h2 class="text-[16px] font-semibold text-ink-900 mb-4">Tren Penjualan (30 Hari Terakhir)</h2>
            <canvas id="trenChart" height="220"></canvas>
        </div>

        <div class="bg-white rounded-[18px] shadow-owner-card p-6">
            <div class="flex items-center justify-between mb-2">
                <h2 class="text-[16px] font-semibold text-ink-900">Pesanan Terbaru</h2>
                <a href="{{ route('owner.transaksi.index') }}" class="text-[13px] text-maroon-700 hover:text-maroon-800 font-medium">Lihat Semua</a>
            </div>
            <div class="divide-y divide-pink-200">
                @forelse ($pesananTerbaru as $order)
                    <div class="py-3 flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[14px] font-medium text-ink-900">{{ $order->buyer_nama }}</p>
                            <x-owner.badge :classes="$order->statusBadgeClasses()" class="mt-1.5">{{ $order->statusLabel() }}</x-owner.badge>
                        </div>
                        <p class="text-[14px] text-ink-900 whitespace-nowrap">{{ formatRupiahOwner($order->total) }}</p>
                    </div>
                @empty
                    <p class="py-6 text-center text-[13px] text-ink-soft">Belum ada pesanan.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    const trenData = @json($tren);
    new Chart(document.getElementById('trenChart'), {
        type: 'line',
        data: {
            labels: trenData.map(d => d.label),
            datasets: [{
                data: trenData.map(d => d.total),
                borderColor: '#A5424F',
                backgroundColor: 'rgba(165,66,79,0.08)',
                borderWidth: 2,
                pointRadius: 0,
                tension: 0.35,
                fill: true,
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { maxTicksLimit: 8, color: '#6B5257', font: { size: 11 } } },
                y: { grid: { color: '#FDF1EF' }, ticks: { color: '#6B5257', font: { size: 11 } } },
            }
        }
    });
</script>
@endpush
