@extends('layouts.owner')

@section('title', 'Kelola Transaksi Pemesanan')
@section('breadcrumb', 'Kelola Transaksi Pemesanan')

@section('content')
<div x-data="transaksiPanel()">

    <h1 class="font-poppins font-semibold text-[24px] text-maroon-900 mb-6">Kelola Transaksi Pemesanan</h1>

    @php
        $statusFilterOptions = ['' => 'Semua Status'];
        $statusFormOptions = [];
        foreach ($statuses as $key => $meta) {
            $statusFilterOptions[$key] = $meta['label'];
            $statusFormOptions[$key] = $meta['label'];
        }
    @endphp

    <form method="GET" action="{{ route('owner.transaksi.index') }}" data-autofilter class="flex flex-wrap items-center gap-2 mb-6">
        <x-owner.select name="status" :options="$statusFilterOptions" :value="$filters['status'] ?? ''" submit />
    </form>

    <div class="bg-white rounded-[18px] shadow-owner-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-[13.5px]">
                <thead>
                    <tr class="border-b border-pink-100 text-left text-ink-soft text-[12px] uppercase tracking-wide">
                        <th class="px-5 py-3 font-medium">Kode Pesanan</th>
                        <th class="px-5 py-3 font-medium">Pembeli</th>
                        <th class="px-5 py-3 font-medium">Tanggal</th>
                        <th class="px-5 py-3 font-medium">Total</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-pink-50">
                    @forelse ($orders as $order)
                        <tr class="hover:bg-pink-50/50">
                            <td class="px-5 py-3 font-medium text-ink-900 whitespace-nowrap">{{ $order->order_code }}</td>
                            <td class="px-5 py-3 text-ink-900">{{ $order->buyer_nama }}</td>
                            <td class="px-5 py-3 text-ink-soft whitespace-nowrap">{{ $order->created_at->translatedFormat('d M Y, H:i') }}</td>
                            <td class="px-5 py-3 text-ink-900 whitespace-nowrap">{{ formatRupiahOwner($order->total) }}</td>
                            <td class="px-5 py-3">
                                <x-owner.badge :classes="$order->statusBadgeClasses()">{{ $order->statusLabel() }}</x-owner.badge>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <button type="button" @click="openDetail({{ $order->id }})" class="h-8 px-3 rounded-lg border border-pink-200 text-[12.5px] text-maroon-800 font-medium hover:bg-pink-50">Detail</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-ink-soft">Belum ada pesanan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $orders->links() }}</div>

    {{-- SLIDE-OVER DETAIL --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-50">
        <div x-show="open" x-transition.opacity @click="open = false" class="absolute inset-0 bg-black/30"></div>

        <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             class="absolute right-0 top-0 h-full w-full max-w-[440px] bg-white shadow-owner-modal flex flex-col">

            <div class="px-6 py-4 border-b border-pink-100 flex items-center justify-between shrink-0">
                <h2 class="font-poppins font-semibold text-[17px] text-maroon-900">Detail Pesanan</h2>
                <button type="button" @click="open = false" class="p-1.5 rounded-lg text-ink-soft hover:bg-pink-50">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto owner-notif-scroll px-6 py-5" x-show="!loading">
                <template x-if="order">
                    <div class="space-y-5">
                        <div>
                            <p class="font-semibold text-ink-900" x-text="order?.order_code"></p>
                            <p class="text-[12.5px] text-ink-soft" x-text="order?.created_at"></p>
                        </div>

                        {{-- STEPPER --}}
                        <div class="flex items-center gap-1">
                            <template x-for="(s, i) in stepper" :key="s.key">
                                <div class="flex-1 flex items-center">
                                    <div class="h-1.5 flex-1 rounded-full" :class="stepIndex(order?.status) >= i ? 'bg-maroon-800' : 'bg-pink-100'"></div>
                                </div>
                            </template>
                        </div>
                        <p class="text-[12px] text-ink-soft -mt-3" x-text="order ? statusLabel(order.status) : ''"></p>

                        <div>
                            <p class="text-[12.5px] text-ink-soft mb-2">Pembeli</p>
                            <p class="text-[13.5px] text-ink-900" x-text="order?.buyer?.nama"></p>
                            <p class="text-[13px] text-ink-soft" x-text="order?.buyer?.wa"></p>
                            <p class="text-[13px] text-ink-soft" x-text="order?.buyer?.alamat + ', ' + order?.buyer?.kota + ' ' + order?.buyer?.kodepos"></p>
                        </div>

                        <div>
                            <p class="text-[12.5px] text-ink-soft mb-2">Item</p>
                            <div class="space-y-2">
                                <template x-for="it in order?.items" :key="it.nama + it.varian">
                                    <div class="flex items-center gap-2.5">
                                        <img :src="it.image" class="h-10 w-10 rounded-lg object-cover shrink-0">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-[13px] text-ink-900 truncate" x-text="it.nama"></p>
                                            <p class="text-[11.5px] text-ink-soft" x-text="(it.varian ? it.varian + ' · ' : '') + it.qty + 'x'"></p>
                                        </div>
                                        <p class="text-[13px] text-ink-900 whitespace-nowrap" x-text="formatRp(it.line_total)"></p>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="border-t border-pink-100 pt-3 space-y-1">
                            <div class="flex justify-between text-[13px]"><span class="text-ink-soft">Subtotal</span><span x-text="formatRp(order?.subtotal)"></span></div>
                            <div class="flex justify-between text-[13px]"><span class="text-ink-soft">Ongkir</span><span x-text="formatRp(order?.shipping_cost)"></span></div>
                            <div class="flex justify-between text-[14px] font-semibold text-ink-900"><span>Total</span><span x-text="formatRp(order?.total)"></span></div>
                        </div>

                        <div class="border-t border-pink-100 pt-4">
                            <p class="text-[12.5px] text-ink-soft mb-2">Ubah Status</p>
                            <form method="POST" :action="'/owner/transaksi/' + order?.id" class="space-y-3">
                                @csrf @method('PATCH')
                                <x-owner.select name="status" model="newStatus" variant="field" :options="$statusFormOptions" />

                                <template x-if="newStatus === 'dikirim'">
                                    <div class="space-y-2">
                                        <input type="text" name="kurir" :value="order?.kurir" placeholder="Nama kurir (mis. JNE, J&T)" class="w-full h-10 rounded-xl border border-pink-200 px-3 text-[13px]">
                                        <input type="text" name="nomor_resi" :value="order?.nomor_resi" placeholder="Nomor resi" class="w-full h-10 rounded-xl border border-pink-200 px-3 text-[13px]">
                                    </div>
                                </template>

                                <button type="submit" class="w-full h-10 rounded-xl bg-maroon-900 text-white text-[13.5px] font-medium hover:bg-maroon-800">Simpan Status</button>
                            </form>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function transaksiPanel() {
    return {
        open: false,
        loading: false,
        order: null,
        newStatus: '',
        init() {
            @if(request()->filled('cari') && $orders->count() == 1)
                this.openDetail({{ $orders->first()->id }});
            @endif
        },
        stepper: [
            { key: 'menunggu_pembayaran', label: 'Menunggu Pembayaran' },
            { key: 'diproses', label: 'Diproses' },
            { key: 'dikirim', label: 'Dikirim' },
            { key: 'selesai', label: 'Selesai' },
        ],
        stepIndex(status) {
            return this.stepper.findIndex(s => s.key === status);
        },
        statusLabel(status) {
            const found = this.stepper.find(s => s.key === status);
            return found ? found.label : (status === 'dibatalkan' ? 'Dibatalkan' : status);
        },
        formatRp(v) {
            if (v === undefined || v === null) return '';
            return 'Rp ' + Number(v).toLocaleString('id-ID');
        },
        async openDetail(id) {
            this.open = true;
            this.loading = true;
            this.order = null;
            try {
                const res = await fetch('/owner/transaksi/' + id, { headers: { 'Accept': 'application/json' } });
                this.order = await res.json();
                this.newStatus = this.order.status;
            } catch (e) {
                alert('Gagal memuat detail pesanan.');
                this.open = false;
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
@endpush
@endsection