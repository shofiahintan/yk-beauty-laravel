<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Support\OwnerNotifications;

class NotificationController extends Controller
{
    public function index()
    {
        $notifs = OwnerNotifications::all(auth()->user());

        return view('owner.notifikasi', [
            'notifs' => $notifs,
            'unread' => OwnerNotifications::unreadCount($notifs),
        ]);
    }

    /**
     * Dipanggil saat notifikasi diklik: tandai sudah dibaca, lalu arahkan ke halaman terkait.
     */
    public function open(string $type, int $id)
    {
        $user = auth()->user();

        if ($type === 'order') {
            $order = Order::findOrFail($id);
            OwnerNotifications::markRead($user, ['order:'.$order->id]);

            return redirect()->route('owner.transaksi.index', ['cari' => $order->order_code]);
        }

        $product = Product::findOrFail($id);
        OwnerNotifications::markRead($user, ['stock:'.$product->id.':'.$product->stok]);

        return redirect()->route('owner.produk.index', ['cari' => $product->nama]);
    }

    public function readAll()
    {
        $user = auth()->user();
        $notifs = OwnerNotifications::all($user, 500);

        OwnerNotifications::markRead($user, $notifs->where('read', false)->pluck('key')->all());

        return back()->with('status', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
