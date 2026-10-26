<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Refund;
use App\Support\RefundService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RefundController extends Controller
{
    public function index(Request $request)
    {
        $query = Refund::with(['order', 'user'])->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($cari = $request->query('cari')) {
            $query->whereHas('order', function ($q) use ($cari) {
                $q->where('order_code', 'like', "%{$cari}%")
                    ->orWhere('buyer_nama', 'like', "%{$cari}%");
            });
        }

        $refunds = $query->paginate(15)->withQueryString();

        $counts = [
            'semua'     => Refund::count(),
            'diajukan'  => Refund::where('status', Refund::STATUS_DIAJUKAN)->count(),
            'disetujui' => Refund::where('status', Refund::STATUS_DISETUJUI)->count(),
            'selesai'   => Refund::where('status', Refund::STATUS_SELESAI)->count(),
            'ditolak'   => Refund::where('status', Refund::STATUS_DITOLAK)->count(),
        ];

        return view('owner.refund.index', [
            'refunds'  => $refunds,
            'counts'   => $counts,
            'statuses' => Refund::STATUSES,
            'filters'  => $request->only(['status', 'cari']),
        ]);
    }

    public function show(Refund $refund)
    {
        $refund->load(['order.items', 'order.statusHistories', 'user', 'diprosesOleh']);

        $canMidtrans = Refund::canRefundViaMidtrans($refund->order->payment_method);
        $sisaBarang = max(0, (int) $refund->order->subtotal - (int) $refund->order->refunded_barang);
        $sisaOngkir = max(0, (int) $refund->order->shipping_cost - (int) $refund->order->refunded_ongkir);
        $sisaTotal = max(0, (int) $refund->order->total - (int) $refund->order->refunded_barang - (int) $refund->order->refunded_ongkir);

        return view('owner.refund.show', [
            'refund'      => $refund,
            'order'       => $refund->order,
            'canMidtrans' => $canMidtrans,
            'sisaBarang'  => $sisaBarang,
            'sisaOngkir'  => $sisaOngkir,
            'sisaTotal'   => $sisaTotal,
        ]);
    }

    public function approve(Request $request, Refund $refund)
    {
        $data = $request->validate([
            'nominal_barang'  => ['nullable', 'integer', 'min:0'],
            'sertakan_ongkir' => ['nullable', 'boolean'],
            'catatan'         => ['nullable', 'string', 'max:500'],
        ]);

        $nominalBarang = isset($data['nominal_barang']) ? (int) $data['nominal_barang'] : null;
        $sertakanOngkir = (bool) ($data['sertakan_ongkir'] ?? false);

        RefundService::approve(
            $refund,
            $request->user(),
            $nominalBarang,
            $sertakanOngkir,
            $data['catatan'] ?? null
        );

        return redirect()->route('owner.refund.show', $refund)
            ->with('status', 'Pengajuan refund berhasil disetujui.');
    }

    public function reject(Request $request, Refund $refund)
    {
        $data = $request->validate([
            'catatan' => ['required', 'string', 'max:500'],
        ], [
            'catatan.required' => 'Alasan penolakan wajib diisi.',
        ]);

        RefundService::reject($refund, $request->user(), $data['catatan']);

        return redirect()->route('owner.refund.show', $refund)
            ->with('status', 'Pengajuan refund telah ditolak.');
    }

    public function complete(Request $request, Refund $refund)
    {
        $data = $request->validate([
            'metode_pengembalian'    => ['required', Rule::in([Refund::METODE_MIDTRANS, Refund::METODE_MANUAL])],
            'referensi_pengembalian' => ['required', 'string', 'max:100'],
            'catatan'                => ['nullable', 'string', 'max:500'],
        ], [
            'metode_pengembalian.required'    => 'Pilih metode pengembalian uang.',
            'referensi_pengembalian.required' => 'Nomor referensi / bukti pengembalian wajib diisi.',
        ]);

        RefundService::complete(
            $refund,
            $request->user(),
            $data['metode_pengembalian'],
            $data['referensi_pengembalian'],
            $data['catatan'] ?? null
        );

        return redirect()->route('owner.refund.show', $refund)
            ->with('status', 'Pengembalian dana telah berhasil ditandai selesai.');
    }
}
