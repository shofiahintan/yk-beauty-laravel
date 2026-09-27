<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    /**
     * Dipakai untuk tombol "Login dengan Google" DAN "Daftar dengan Google" —
     * satu alur yang sama, karena hasilnya sama-sama "sudah login", cuma beda
     * apakah user-nya baru dibuat atau sudah ada sebelumnya (lihat callback()).
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            Log::warning('Google OAuth callback gagal: ' . $e->getMessage());

            return redirect()->route('login')
                ->with('error', 'Login dengan Google gagal atau dibatalkan. Silakan coba lagi.');
        }

        // Cocokkan lewat google_id dulu (user yang sebelumnya pernah pakai Google).
        $user = User::where('google_id', $googleUser->getId())->first();

        if (! $user) {
            // Belum pernah login via Google — cek juga lewat email, siapa tahu
            // sebelumnya daftar manual pakai email yang sama. Kalau ada, akun
            // itu yang dipakai (tinggal ditautkan), bukan bikin akun duplikat.
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                $user->forceFill(['google_id' => $googleUser->getId()])->save();
            } else {
                $user = User::create([
                    'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: 'Pengguna Google',
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'password' => null,
                    'role' => User::ROLE_CUSTOMER,
                    'email_verified_at' => now(), // email sudah diverifikasi oleh Google
                ]);
            }
        }

        if (! $user->is_active) {
            return redirect()->route('login')
                ->with('error', 'Akun ini sedang dinonaktifkan. Hubungi admin untuk info lebih lanjut.');
        }

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        if ($user->isOwner()) {
            return redirect()->intended(route('owner.dashboard'));
        }

        return redirect()->intended(RouteServiceProvider::HOME);
    }
}
