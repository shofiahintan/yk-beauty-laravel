<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function index()
    {
        return view('akun', [
            'user' => Auth::user(),
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'wa' => 'nullable|string|max:30',
            'alamat' => 'nullable|string|max:1000',
            'kota' => 'nullable|string|max:100',
            'kodepos' => 'nullable|string|max:20',
            'foto' => 'nullable|image|max:2048',
        ]);

        $user->name = $validated['nama'];
        $user->email = $validated['email'];
        $user->whatsapp = $validated['wa'] ?? null;
        $user->alamat = $validated['alamat'] ?? null;
        $user->kota = $validated['kota'] ?? null;
        $user->kodepos = $validated['kodepos'] ?? null;

        if ($request->hasFile('foto')) {
            $path = $request->file('foto')->store('avatars', 'public');
            $user->foto = 'storage/' . $path;
        }

        $user->save();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Profil berhasil disimpan.'),
                'user' => [
                    'nama' => $user->name,
                    'email' => $user->email,
                    'wa' => $user->whatsapp,
                    'alamat' => $user->alamat,
                    'kota' => $user->kota,
                    'kodepos' => $user->kodepos,
                    'foto' => $user->foto ? asset($user->foto) : null,
                ],
            ]);
        }

        return redirect()->back()->with('status', 'profile-updated');
    }

    public function settings()
    {
        return view('pengaturan', [
            'user' => Auth::user(),
        ]);
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'pass_lama' => ['required', 'current_password'],
            'pass_baru' => ['required', Password::defaults(), 'confirmed'],
            'pass_baru_confirmation' => ['required'],
        ]);

        $user->update([
            'password' => Hash::make($request->input('pass_baru')),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Kata sandi berhasil diperbarui.'),
            ]);
        }

        return redirect()->back()->with('status', 'password-updated');
    }
}
