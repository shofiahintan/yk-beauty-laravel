<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Pencarian global di topbar Owner Panel: pesanan, produk, dan pelanggan.
     *  - Akses biasa (Enter)      -> halaman hasil lengkap (owner.cari)
     *  - Akses fetch/JSON (ketik) -> daftar saran ringkas untuk dropdown
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        if ($request->expectsJson()) {
            return response()->json(['groups' => $this->search($q, 4)]);
        }

        return view('owner.cari', [
            'q' => $q,
            'groups' => $this->search($q, 10),
        ]);
    }

    /**
     * @return array<int, array{key: string, label: string, items: array<int, array<string, mixed>>}>
     */
    private function search(string $q, int $limit): array
    {
        if (mb_strlen($q) < 2) {
            return [];
        }

        $like = '%'.$q.'%';
        $groups = [];

        $orders = Order::query()
            ->where(function ($w) use ($like) {
                $w->where('order_code', 'like', $like)
                    ->orWhere('buyer_nama', 'like', $like)
                    ->orWhere('buyer_wa', 'like', $like);
            })
            ->orderByDesc('created_at')
            ->take($limit)
            ->get();

        if ($orders->isNotEmpty()) {
            $groups[] = [
                'key' => 'pesanan',
                'label' => 'Pesanan',
                'items' => $orders->map(fn (Order $o) => [
                    'title' => $o->order_code,
                    'subtitle' => $o->buyer_nama.' · '.formatRupiahOwner($o->total),
                    'badge' => $o->statusLabel(),
                    'image' => null,
                    'url' => route('owner.transaksi.index', ['cari' => $o->order_code]),
                ])->values()->all(),
            ];
        }

        $products = Product::query()
            ->where(function ($w) use ($like) {
                $w->where('nama', 'like', $like)
                    ->orWhere('brand', 'like', $like)
                    ->orWhere('kategori', 'like', $like);
            })
            ->orderBy('nama')
            ->take($limit)
            ->get();

        if ($products->isNotEmpty()) {
            $groups[] = [
                'key' => 'produk',
                'label' => 'Produk',
                'items' => $products->map(fn (Product $p) => [
                    'title' => $p->nama,
                    'subtitle' => $p->brand.' · '.formatRupiahOwner($p->harga).' · stok '.$p->stok,
                    'badge' => null,
                    'image' => $p->image ? asset($p->image) : null,
                    'url' => route('owner.produk.index', ['cari' => $p->nama]),
                ])->values()->all(),
            ];
        }

        $customers = User::query()
            ->where('role', User::ROLE_CUSTOMER)
            ->where(function ($w) use ($like) {
                $w->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like);
            })
            ->orderBy('name')
            ->take($limit)
            ->get();

        if ($customers->isNotEmpty()) {
            $groups[] = [
                'key' => 'pelanggan',
                'label' => 'Pelanggan',
                'items' => $customers->map(fn (User $u) => [
                    'title' => $u->name,
                    'subtitle' => $u->email,
                    'badge' => null,
                    'image' => null,
                    'url' => route('owner.pengguna.index', ['tab' => 'customer', 'cari' => $u->email]),
                ])->values()->all(),
            ];
        }

        return $groups;
    }
}
