@extends('layouts.owner')

@section('title', 'Detail Refund #' . $order->order_code)
@section('breadcrumb')
    <a href="{{ route('owner.refund.index') }}" class="hover:text-maroon-900">Refund</a>
    <span class="mx-1">/</span>
    <span>#{{ $order->order_code }}</span>
@endsection

@section('content')

<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <div class="flex items-center gap-3">
            <h1 class="font-poppins font-semibold text-[22px] text-maroon-900">
                Refund #{{ $order->order_code }}
            </h1>
            <span class="px-3 py-1 rounded-full text-[12px] font-medium {{ $refund->status_badge }}">
                {{ $refund->status_label }}
            </span>
            @if ($refund->sumber === 'sistem')
                <span class="px-2.5 py-0.5 rounded-full text-[11px] bg-gray-100 text-gray-700 font-medium">Sistem</span>
            @endif
        </div>
        <p class="text-[13px] text-ink-soft mt-1">Diajukan pada {{ $refund->created_at->translatedFormat('d F Y, H:i') }}</p>
    </div>
    <div>
        <a href="{{ route('owner.refund.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white border border-pink-200 text-ink-900 text-[13px] font-medium hover:bg-pink-50 transition">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
            Kembali
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Kolom Kiri / Utama: Detail Pengajuan & Aksi --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- Kartu Detail Pengajuan --}}
        <div class="bg-white rounded-[18px] shadow-owner-card p-6">
            <h2 class="text-[16px] font-semibold text-ink-900 mb-4 pb-3 border-b border-pink-100">
                Informasi Pengajuan
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-[13px] mb-5">
                <div>
                    <span class="text-ink-soft block text-[12px]">Alasan Refund</span>
                    <span class="font-semibold text-maroon-900 text-[14px]">{{ $refund->alasan_label }}</span>
                </div>
                <div>
                    <span class="text-ink-soft block text-[12px]">Pemohon</span>
                    <span class="font-medium text-ink-900">{{ $refund->user->name ?? $order->buyer_nama }} ({{ $refund->user->email ?? '-' }})</span>
                </div>
            </div>

            @if ($refund->keterangan)
                <div class="mb-5 bg-pink-50/50 rounded-xl p-4 text-[13px]">
                    <span class="text-ink-soft block text-[12px] mb-1 font-medium">Keterangan Tambahan:</span>
                    <p class="text-ink-900 whitespace-pre-line">{{ $refund->keterangan }}</p>
                </div>
            @endif

            {{-- Bukti Foto --}}
            @if (! empty($refund->bukti_paths))
                <div class="mb-5">
                    <span class="text-ink-soft block text-[12px] mb-2 font-medium">Foto Bukti ({{ count($refund->bukti_paths) }} file):</span>
                    <div class="grid grid-cols-3 gap-3">
                        @foreach ($refund->bukti_paths as $idx => $path)
                            <a href="{{ route('pesanan.refund.evidence', ['orderCode' => $order->order_code, 'index' => $idx]) }}"
                               target="_blank"
                               class="group block relative aspect-square rounded-xl overflow-hidden border border-pink-200 bg-pink-50 hover:opacity-90 transition">
                                <img src="{{ route('pesanan.refund.evidence', ['orderCode' => $order->order_code, 'index' => $idx]) }}"
                                     alt="Bukti {{ $idx + 1 }}"
                                     class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-maroon-950/30 opacity-0 group-hover:opacity-100 flex items-center justify-center transition">
                                    <svg class="text-white" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h6v6M10 14 21 3M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Rekening Pembeli --}}
            <div class="border-t border-pink-100 pt-4">
                <span class="text-ink-soft block text-[12px] mb-2 font-medium">Rekening Pengembalian Pembeli:</span>
                @if ($refund->rekening_bank || $refund->rekening_nomor)
                    <div class="bg-pink-50/70 border border-pink-100 rounded-xl p-4 text-[13px] grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <span class="text-ink-soft block text-[11.5px]">Bank</span>
                            <span class="font-semibold text-maroon-900 uppercase">{{ $refund->rekening_bank }}</span>
                        </div>
                        <div>
                            <span class="text-ink-soft block text-[11.5px]">Nomor Rekening</span>
                            <span class="font-semibold font-mono text-ink-900">{{ $refund->rekening_nomor }}</span>
                        </div>
                        <div>
                            <span class="text-ink-soft block text-[11.5px]">Atas Nama</span>
                            <span class="font-semibold text-ink-900">{{ $refund->rekening_nama }}</span>
                        </div>
                    </div>
                @else
                    <p class="text-[12.5px] text-ink-soft italic">
                        Tidak ada rekening terlampir (dapat di-refund langsung lewat dashboard payment gateway Midtrans).
                    </p>
                @endif
            </div>

            {{-- Catatan Owner jika ada --}}
            @if ($refund->catatan_owner)
                <div class="border-t border-pink-100 pt-4 mt-4">
                    <span class="text-ink-soft block text-[12px] mb-1 font-medium">Catatan Owner:</span>
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-3 text-[13px] text-ink-900">
                        {{ $refund->catatan_owner }}
                    </div>
                </div>
            @endif
        </div>

        {{-- Form Aksi Sesuai Status --}}
        @if ($refund->status === \App\Models\Refund::STATUS_DIAJUKAN)
            {{-- AKSI: Setujui Refund --}}
            <div class="bg-white rounded-[18px] shadow-owner-card p-6 border-l-4 border-maroon-800">
                <h2 class="text-[16px] font-semibold text-maroon-900 mb-2">Persetujuan Refund</h2>
                <p class="text-[12.5px] text-ink-soft mb-4">
                    Tentukan nominal pengembalian untuk barang dan ongkos kirim.
                </p>

                <form method="POST" action="{{ route('owner.refund.approve', $refund) }}" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label class="block text-[12.5px] font-medium text-ink-900 mb-1">
                            Nominal Barang (Maks {{ formatRupiahOwner($sisaBarang) }})
                        </label>
                        <input type="number" name="nominal_barang" value="{{ old('nominal_barang', $sisaBarang) }}"
                               max="{{ $sisaBarang }}" min="0" required
                               class="w-full h-11 px-3.5 rounded-xl border border-pink-200 text-[13.5px] focus:ring-2 focus:ring-maroon-700/30 focus:border-maroon-900 font-medium">
                    </div>

                    @php
                        // Otomatis centang ongkir untuk batal sebelum kirim, barang rusak, salah kirim
                        $defaultOngkir = in_array($refund->alasan, [
                            \App\Models\Refund::ALASAN_BATAL_SEBELUM_KIRIM,
                            \App\Models\Refund::ALASAN_BARANG_RUSAK,
                            \App\Models\Refund::ALASAN_SALAH_KIRIM,
                        ], true) && $sisaOngkir > 0;
                    @endphp

                    @if ($sisaOngkir > 0)
                        <div class="flex items-start gap-3 p-3.5 rounded-xl bg-pink-50/60 border border-pink-100">
                            <input type="checkbox" id="sertakan_ongkir" name="sertakan_ongkir" value="1"
                                   {{ old('sertakan_ongkir', $defaultOngkir ? '1' : '') ? 'checked' : '' }}
                                   class="mt-0.5 h-4 w-4 rounded border-pink-300 text-maroon-800 focus:ring-maroon-700">
                            <label for="sertakan_ongkir" class="text-[13px] text-ink-900 cursor-pointer">
                                <span class="font-medium block">Sertakan ongkir ({{ formatRupiahOwner($sisaOngkir) }})</span>
                                <span class="text-[11.5px] text-ink-soft">Centang jika kesalahan dari toko atau pembatalan sebelum dikirim.</span>
                            </label>
                        </div>
                    @endif

                    @if ($refund->alasan === \App\Models\Refund::ALASAN_BATAL_SEBELUM_KIRIM)
                        <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-[12px]">
                            <strong>Catatan:</strong> Menyetujui refund dengan alasan batal sebelum kirim akan otomatis membatalkan pesanan dan mengembalikan stok produk.
                        </div>
                    @endif

                    <div>
                        <label class="block text-[12.5px] font-medium text-ink-900 mb-1">
                            Catatan untuk Pembeli (Opsional)
                        </label>
                        <input type="text" name="catatan" value="{{ old('catatan') }}" placeholder="Contoh: Refund disetujui penuh"
                               class="w-full h-11 px-3.5 rounded-xl border border-pink-200 text-[13.5px] focus:ring-2 focus:ring-maroon-700/30 focus:border-maroon-900">
                    </div>

                    <button type="submit"
                            class="w-full h-11 px-5 rounded-xl bg-maroon-900 text-white text-[13.5px] font-semibold hover:bg-maroon-800 transition">
                        Setujui Pengajuan Refund
                    </button>
                </form>

                {{-- AKSI: Tolak Refund --}}
                <div class="mt-8 pt-6 border-t border-pink-100">
                    <h3 class="text-[14px] font-semibold text-ink-soft mb-2">Tolak Pengajuan</h3>
                    <form method="POST" action="{{ route('owner.refund.reject', $refund) }}"
                          onsubmit="return confirm('Yakin ingin menolak pengajuan refund ini? Pembeli tidak dapat mengajukan lagi lewat form.');"
                          class="space-y-3">
                        @csrf
                        @method('PATCH')

                        <div>
                            <textarea name="catatan" rows="2" required placeholder="Tuliskan alasan penolakan (wajib diisi)..."
                                      class="w-full p-3 rounded-xl border border-pink-200 text-[13px] focus:ring-2 focus:ring-maroon-700/30 focus:border-maroon-900">{{ old('catatan') }}</textarea>
                        </div>
                        <button type="submit"
                                class="px-4 py-2 rounded-xl bg-white border border-red-300 text-red-700 text-[13px] font-medium hover:bg-red-50 transition">
                            Tolak Pengajuan Refund
                        </button>
                    </form>
                </div>
            </div>

        @elseif ($refund->status === \App\Models\Refund::STATUS_DISETUJUI)
            {{-- AKSI: Tandai Sudah Dikembalikan --}}
            <div class="bg-white rounded-[18px] shadow-owner-card p-6 border-l-4 border-emerald-600">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-[16px] font-semibold text-emerald-900">Tandai Uang Sudah Dikembalikan</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-[12px] bg-pink-100 text-maroon-900 font-semibold">
                        Total: {{ formatRupiahOwner($refund->totalNominal()) }}
                    </span>
                </div>
                <p class="text-[12.5px] text-ink-soft mb-4">
                    Lakukan transfer uang terlebih dahulu secara manual (lewat Midtrans Dashboard atau transfer bank), kemudian masukkan bukti/referensi di bawah.
                </p>

                <form method="POST" action="{{ route('owner.refund.complete', $refund) }}" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label class="block text-[12.5px] font-medium text-ink-900 mb-1">Metode Pengembalian Uang</label>
                        <select name="metode_pengembalian" required
                                class="w-full h-11 px-3.5 rounded-xl border border-pink-200 text-[13.5px] focus:ring-2 focus:ring-maroon-700/30 focus:border-maroon-900">
                            <option value="midtrans_dashboard" {{ old('metode_pengembalian', $canMidtrans ? 'midtrans_dashboard' : 'transfer_manual') === 'midtrans_dashboard' ? 'selected' : '' }}>
                                Dashboard Midtrans (Direct Refund)
                            </option>
                            <option value="transfer_manual" {{ old('metode_pengembalian', ! $canMidtrans ? 'transfer_manual' : '') === 'transfer_manual' ? 'selected' : '' }}>
                                Transfer Manual ke Rekening Pembeli
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[12.5px] font-medium text-ink-900 mb-1">
                            Nomor Referensi / ID Transaksi Refund (Wajib)
                        </label>
                        <input type="text" name="referensi_pengembalian" value="{{ old('referensi_pengembalian') }}"
                               placeholder="Contoh: REF-MIDTRANS-12345 atau No. Ref Transfer Bank" required
                               class="w-full h-11 px-3.5 rounded-xl border border-pink-200 text-[13.5px] focus:ring-2 focus:ring-maroon-700/30 focus:border-maroon-900 font-mono">
                    </div>

                    <div>
                        <label class="block text-[12.5px] font-medium text-ink-900 mb-1">Catatan Tambahan (Opsional)</label>
                        <input type="text" name="catatan" value="{{ old('catatan') }}" placeholder="Catatan pengembalian"
                               class="w-full h-11 px-3.5 rounded-xl border border-pink-200 text-[13.5px] focus:ring-2 focus:ring-maroon-700/30 focus:border-maroon-900">
                    </div>

                    <button type="submit"
                            class="w-full h-11 px-5 rounded-xl bg-emerald-700 text-white text-[13.5px] font-semibold hover:bg-emerald-800 transition">
                        Tandai Sudah Dikembalikan
                    </button>
                </form>
            </div>

        @elseif ($refund->status === \App\Models\Refund::STATUS_SELESAI)
            <div class="bg-emerald-50 border border-emerald-200 rounded-[18px] p-5">
                <div class="flex items-center gap-3 mb-2">
                    <svg class="text-emerald-700" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                    <h3 class="font-semibold text-emerald-950 text-[15px]">Pengembalian Dana Telah Selesai</h3>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-[12.5px] text-emerald-900 mt-3 pt-3 border-t border-emerald-200">
                    <div>
                        <span class="text-emerald-700 block">Metode Pengembalian:</span>
                        <span class="font-medium font-semibold">{{ $refund->metode_pengembalian === 'midtrans_dashboard' ? 'Dashboard Midtrans' : 'Transfer Manual' }}</span>
                    </div>
                    <div>
                        <span class="text-emerald-700 block">Nomor Referensi:</span>
                        <span class="font-mono font-semibold">{{ $refund->referensi_pengembalian }}</span>
                    </div>
                    <div>
                        <span class="text-emerald-700 block">Total Dikembalikan:</span>
                        <span class="font-semibold">{{ formatRupiahOwner($refund->totalNominal()) }}</span>
                    </div>
                    <div>
                        <span class="text-emerald-700 block">Waktu Selesai:</span>
                        <span>{{ $refund->selesai_at?->translatedFormat('d M Y, H:i') }}</span>
                    </div>
                </div>
            </div>

        @elseif ($refund->status === \App\Models\Refund::STATUS_DITOLAK)
            <div class="bg-pink-50 border border-pink-200 rounded-[18px] p-5">
                <h3 class="font-semibold text-maroon-900 text-[15px] mb-1">Pengajuan Refund Telah Ditolak</h3>
                <p class="text-[12.5px] text-ink-soft">Alasan penolakan: {{ $refund->catatan_owner }}</p>
            </div>
        @endif

    </div>

    {{-- Kolom Kanan: Rincian Pesanan & Pembeli --}}
    <div class="space-y-6">

        {{-- Kartu Pesanan --}}
        <div class="bg-white rounded-[18px] shadow-owner-card p-6">
            <h2 class="text-[16px] font-semibold text-ink-900 mb-3 pb-2 border-b border-pink-100 flex items-center justify-between">
                <span>Data Pesanan</span>
                <a href="{{ route('owner.transaksi.index', ['cari' => $order->order_code]) }}"
                   class="text-[12px] text-maroon-800 hover:underline">Lihat Transaksi &rarr;</a>
            </h2>

            <div class="space-y-2.5 text-[13px] mb-4">
                <div class="flex justify-between">
                    <span class="text-ink-soft">Kode:</span>
                    <span class="font-semibold text-maroon-900 font-mono">#{{ $order->order_code }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-ink-soft">Status Pesanan:</span>
                    <span class="px-2 py-0.5 rounded-full text-[11px] {{ $order->statusBadgeClasses() }}">
                        {{ $order->statusLabel() }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-ink-soft">Status Pembayaran:</span>
                    <span class="px-2 py-0.5 rounded-full text-[11px] {{ $order->paymentStatusBadgeClasses() }}">
                        {{ $order->paymentStatusLabel() }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-ink-soft">Metode Pembayaran:</span>
                    <span class="font-medium text-ink-900 uppercase">{{ $order->payment_method ?? '-' }}</span>
                </div>
            </div>

            {{-- Petunjuk Pengembalian Midtrans --}}
            <div class="p-3.5 rounded-xl text-[12px] mb-4 {{ $canMidtrans ? 'bg-blue-50 border border-blue-200 text-blue-950' : 'bg-amber-50 border border-amber-200 text-amber-950' }}">
                <div class="font-semibold mb-0.5 flex items-center gap-1.5">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                    <span>Petunjuk Pengembalian Dana:</span>
                </div>
                @if ($canMidtrans)
                    <p>Metode bayar (<strong>{{ $order->payment_method }}</strong>) bisa direfund langsung dari <strong>Dashboard Midtrans</strong>.</p>
                @else
                    <p>Metode bayar (<strong>{{ $order->payment_method }}</strong>) tidak mendukung API refund Midtrans. Harap <strong>transfer manual ke rekening pembeli</strong>.</p>
                @endif
            </div>

            {{-- Nominal Pesanan & Sisa --}}
            <div class="border-t border-pink-100 pt-3 space-y-1.5 text-[13px]">
                <div class="flex justify-between">
                    <span class="text-ink-soft">Subtotal Barang:</span>
                    <span class="text-ink-900">{{ formatRupiahOwner($order->subtotal) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-ink-soft">Ongkos Kirim:</span>
                    <span class="text-ink-900">{{ formatRupiahOwner($order->shipping_cost) }}</span>
                </div>
                <div class="flex justify-between font-semibold text-ink-900 pt-1 border-t border-pink-50">
                    <span>Total Pesanan:</span>
                    <span class="text-maroon-900">{{ formatRupiahOwner($order->total) }}</span>
                </div>
                @if ($order->refunded_barang > 0 || $order->refunded_ongkir > 0)
                    <div class="flex justify-between text-ink-soft text-[12px] pt-1">
                        <span>Total Sudah Direfund:</span>
                        <span class="text-red-700 font-medium">- {{ formatRupiahOwner($order->refunded_barang + $order->refunded_ongkir) }}</span>
                    </div>
                    <div class="flex justify-between font-medium text-emerald-800 text-[12.5px]">
                        <span>Sisa Belum Direfund:</span>
                        <span>{{ formatRupiahOwner($sisaTotal) }}</span>
                    </div>
                @endif
            </div>

            {{-- Item Pesanan --}}
            <div class="border-t border-pink-100 pt-3 mt-4">
                <span class="text-ink-soft block text-[12px] mb-2 font-medium">Item Pesanan:</span>
                <div class="space-y-2">
                    @foreach ($order->items as $it)
                        <div class="flex items-center justify-between text-[12.5px] py-1 border-b border-pink-50 last:border-0">
                            <div class="truncate mr-2">
                                <p class="text-ink-900 font-medium truncate">{{ $it->nama }}</p>
                                <p class="text-[11px] text-ink-soft">{{ $it->qty }}x {{ formatRupiahOwner($it->harga) }}</p>
                            </div>
                            <span class="font-medium text-ink-900 whitespace-nowrap">{{ formatRupiahOwner($it->line_total) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Kartu Riwayat Status Pesanan Terkait Refund --}}
        <div class="bg-white rounded-[18px] shadow-owner-card p-6">
            <h2 class="text-[15px] font-semibold text-ink-900 mb-3 pb-2 border-b border-pink-100">
                Riwayat Status Pesanan
            </h2>
            <div class="space-y-3">
                @foreach ($order->statusHistories as $hist)
                    <div class="text-[12px] pb-2 border-b border-pink-50 last:border-0">
                        <div class="flex justify-between items-center text-ink-soft text-[11px]">
                            <span class="font-semibold text-maroon-900">{{ ucfirst($hist->status) }}</span>
                            <span>{{ $hist->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <p class="text-ink-900 mt-0.5">{{ $hist->catatan }}</p>
                    </div>
                @endforeach
            </div>
        </div>

    </div>

</div>

@endsection
