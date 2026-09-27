@extends('layouts.owner')

@section('title', 'Profil & Keamanan')
@section('breadcrumb', 'Profil & Keamanan')

@section('content')
    <h1 class="font-poppins font-semibold text-[24px] text-maroon-900 mb-6">Profil &amp; Keamanan</h1>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 max-w-[1000px]">

        {{-- Informasi akun --}}
        <div class="bg-white rounded-[18px] shadow-owner-card p-6">
            <div class="flex items-center gap-3 mb-5">
                <div class="h-12 w-12 rounded-full bg-maroon-900 text-white flex items-center justify-center text-lg font-semibold">
                    {{ strtoupper(substr($user->name ?? 'O', 0, 1)) }}
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-ink-900">{{ $user->name }}</h2>
                    <x-owner.badge class="mt-1">Owner</x-owner.badge>
                </div>
            </div>

            <form method="POST" action="{{ route('owner.profil.update') }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label class="block text-[12.5px] text-ink-soft mb-1">Nama</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="w-full h-10 rounded-xl border border-pink-200 px-3 text-[13.5px] focus:ring-2 focus:ring-maroon-700/30 focus:border-maroon-700">
                    @error('name') <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-[12.5px] text-ink-soft mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                           class="w-full h-10 rounded-xl border border-pink-200 px-3 text-[13.5px] focus:ring-2 focus:ring-maroon-700/30 focus:border-maroon-700">
                    @error('email') <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-[12.5px] text-ink-soft mb-1">No. WhatsApp (opsional)</label>
                    <input type="text" name="whatsapp" value="{{ old('whatsapp', $user->whatsapp) }}"
                           class="w-full h-10 rounded-xl border border-pink-200 px-3 text-[13.5px] focus:ring-2 focus:ring-maroon-700/30 focus:border-maroon-700">
                    @error('whatsapp') <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="h-10 px-5 rounded-xl bg-maroon-900 text-white text-[13.5px] font-medium hover:bg-maroon-800">Simpan Perubahan</button>
            </form>
        </div>

        {{-- Ganti password --}}
        <div class="bg-white rounded-[18px] shadow-owner-card p-6">
            <h2 class="text-[16px] font-semibold text-ink-900 mb-1">Ganti Password</h2>
            <p class="text-[12.5px] text-ink-soft mb-5">Gunakan password yang kuat dan belum pernah dipakai di tempat lain.</p>

            <form method="POST" action="{{ route('owner.profil.password') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-[12.5px] text-ink-soft mb-1">Password Saat Ini</label>
                    <input type="password" name="current_password" required autocomplete="current-password"
                           class="w-full h-10 rounded-xl border border-pink-200 px-3 text-[13.5px] focus:ring-2 focus:ring-maroon-700/30 focus:border-maroon-700">
                    @error('current_password') <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-[12.5px] text-ink-soft mb-1">Password Baru</label>
                    <input type="password" name="password" required autocomplete="new-password"
                           class="w-full h-10 rounded-xl border border-pink-200 px-3 text-[13.5px] focus:ring-2 focus:ring-maroon-700/30 focus:border-maroon-700">
                    @error('password') <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-[12.5px] text-ink-soft mb-1">Ulangi Password Baru</label>
                    <input type="password" name="password_confirmation" required autocomplete="new-password"
                           class="w-full h-10 rounded-xl border border-pink-200 px-3 text-[13.5px] focus:ring-2 focus:ring-maroon-700/30 focus:border-maroon-700">
                </div>

                <button type="submit" class="h-10 px-5 rounded-xl bg-maroon-900 text-white text-[13.5px] font-medium hover:bg-maroon-800">Ganti Password</button>
            </form>
        </div>
    </div>
@endsection
