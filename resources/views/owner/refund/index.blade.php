@extends('layouts.owner')

@section('title', 'Kelola Refund')
@section('breadcrumb', 'Refund')

@section('content')

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="font-poppins font-semibold text-[24px] text-maroon-900">Kelola Refund</h1>
        <p class="text-[13px] text-ink-soft">Daftar pengajuan pengembalian dana pesanan pembeli dan sistem.</p>
    </div>
</div>

{{-- Filter Status Tabs --}}
<div class="flex flex-wrap items-center gap-2 mb-6">
    @php
        $currentStatus = request()->query('status');
        $tabItems = [
            ''          => ['label' => 'Semua', 'count' => $counts['semua'] ?? 0],
            'diajukan'  => ['label' => 'Diajukan', 'count' => $counts['diajukan'] ?? 0],
            'disetujui' => ['label' => 'Disetujui', 'count' => $counts['disetujui'] ?? 0],
            'selesai'   => ['label' => 'Selesai', 'count' => $counts['selesai'] ?? 0],
            'ditolak'   => ['label' => 'Ditolak', 'count' => $counts['ditolak'] ?? 0],
        ];
    @endphp

    @foreach ($tabItems as $statusKey => $tab)
        @php
            $isActive = ($currentStatus === $statusKey) || ($statusKey === '' && ! $currentStatus);
        @endphp
        <a href="{{ route('owner.refund.index', array_merge(request()->except(['status', 'page']), $statusKey ? ['status' => $statusKey] : [])) }}"
           class="h-9 px-4 rounded-full text-[13px] font-medium flex items-center gap-2 transition
                  {{ $isActive ? 'bg-maroon-900 text-white shadow-sm' : 'bg-white shadow-owner-card text-ink-soft hover:text-maroon-900 hover:bg-pink-50' }}">
            <span>{{ $tab['label'] }}</span>
            <span class="text-[11px] px-1.5 py-0.5 rounded-full {{ $isActive ? 'bg-white/20 text-white' : 'bg-pink-100 text-maroon-900' }}">
                {{ $tab['count'] }}
            </span>
        </a>
    @endforeach
</div>

{{-- Search Bar --}}
<div class="bg-white rounded-[18px] shadow-owner-card p-4 mb-6">
    <form method="GET" action="{{ route('owner.refund.index') }}" class="flex flex-col sm:flex-row gap-3">
        @if ($currentStatus)
            <input type="hidden" name="status" value="{{ $currentStatus }}">
        @endif
        <div class="relative flex-1">
            <input type="text" name="cari" value="{{ request()->query('cari') }}"
                   placeholder="Cari nomor pesanan atau nama pembeli..."
                   class="w-full h-11 pl-10 pr-4 rounded-xl border border-pink-200 text-[13.5px] focus:ring-2 focus:ring-maroon-700/30 focus:border-maroon-900">
            <svg class="absolute left-3.5 top-3 text-ink-soft" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
            </svg>
        </div>
        <button type="submit" class="h-11 px-5 rounded-xl bg-maroon-900 text-white text-[13.5px] font-medium hover:bg-maroon-800 transition">
            Cari
        </button>
        @if (request()->hasAny(['cari', 'status']))
            <a href="{{ route('owner.refund.index') }}" class="h-11 px-4 rounded-xl border border-pink-200 text-ink-soft text-[13.5px] font-medium flex items-center justify-center hover:bg-pink-50">
                Reset
            </a>
        @endif
    </form>
</div>

{{-- Table --}}
<div class="bg-white rounded-[18px] shadow-owner-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-[13px]">
            <thead>
                <tr class="border-b border-pink-100 text-ink-soft text-[11.5px] uppercase tracking-wide bg-pink-50/50">
                    <th class="px-5 py-3 font-medium">Tanggal</th>
                    <th class="px-5 py-3 font-medium">Pesanan & Pembeli</th>
                    <th class="px-5 py-3 font-medium">Alasan</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium text-right">Nominal</th>
                    <th class="px-5 py-3 font-medium text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-pink-50">
                @forelse ($refunds as $item)
                    <tr class="hover:bg-pink-50/30 transition">
                        <td class="px-5 py-3.5 whitespace-nowrap text-ink-soft">
                            {{ $item->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-5 py-3.5">
                            <p class="font-semibold text-maroon-900">#{{ $item->order->order_code }}</p>
                            <p class="text-[12px] text-ink-soft">{{ $item->order->buyer_nama }}</p>
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="inline-block px-2.5 py-0.5 rounded-full text-[11.5px] bg-pink-100 text-maroon-900">
                                {{ $item->alasan_label }}
                            </span>
                            @if ($item->sumber === 'sistem')
                                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] bg-gray-100 text-gray-700 font-medium ml-1">Sistem</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 whitespace-nowrap">
                            <span class="inline-block px-2.5 py-0.5 rounded-full text-[11.5px] font-medium {{ $item->status_badge }}">
                                {{ $item->status_label }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-right whitespace-nowrap font-medium text-ink-900">
                            @if ($item->status === 'diajukan')
                                <span class="text-ink-soft text-[12px]">Menunggu persetujuan</span>
                            @else
                                {{ formatRupiahOwner($item->totalNominal()) }}
                                @if ($item->nominal_ongkir > 0)
                                    <span class="block text-[10.5px] text-ink-soft">+ongkir</span>
                                @endif
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-center whitespace-nowrap">
                            <a href="{{ route('owner.refund.show', $item) }}"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-maroon-900 text-white text-[12px] font-medium hover:bg-maroon-800 transition">
                                <span>Detail</span>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-10 text-center text-ink-soft">
                            Tidak ada data pengajuan refund.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($refunds->hasPages())
        <div class="px-6 py-4 border-t border-pink-100">
            {{ $refunds->links() }}
        </div>
    @endif
</div>

@endsection
