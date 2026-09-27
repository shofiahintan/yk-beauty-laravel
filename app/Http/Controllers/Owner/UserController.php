<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->where('role', User::ROLE_CUSTOMER);

        if ($cari = $request->query('cari')) {
            $query->where(function ($q) use ($cari) {
                $q->where('name', 'like', "%{$cari}%")
                    ->orWhere('email', 'like', "%{$cari}%");
            });
        }

        $query->withCount('orders');

        $users = $query->orderByDesc('id')->paginate(10)->withQueryString();

        return view('owner.pengguna.index', [
            'users' => $users,
            'filters' => $request->only(['cari']),
        ]);
    }

    public function show(User $user)
    {
        $user->loadCount('orders');

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => $user->is_active,
            'whatsapp' => $user->whatsapp,
            'alamat' => $user->alamat,
            'orders_count' => $user->orders_count,
            'created_at' => $user->created_at->translatedFormat('d M Y'),
        ]);
    }

    public function toggleStatus(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => 'Kamu tidak bisa menonaktifkan akunmu sendiri.']);
        }

        if ($user->isOwner()) {
            return back()->withErrors(['user' => 'Tidak bisa menonaktifkan akun Owner.']);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return back()->with('status', 'Status "'.$user->name.'" berhasil diubah menjadi '.($user->is_active ? 'Aktif' : 'Nonaktif').'.');
    }
}
