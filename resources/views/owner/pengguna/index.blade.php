@extends('layouts.owner')

@section('title', 'Kelola Data Pengguna')
@section('breadcrumb', 'Kelola Data Pengguna')

@section('content')
<div x-data="penggunaPanel()">

    <div class="mb-6">
        <h2 class="text-lg font-semibold text-ink-900">Daftar Pelanggan</h2>
    </div>

    <div class="bg-white rounded-[18px] shadow-owner-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-[13.5px]">
                <thead>
                    <tr class="border-b border-pink-100 text-left text-ink-soft text-[12px] uppercase tracking-wide">
                        <th class="px-5 py-3 font-medium">Nama</th>
                        <th class="px-5 py-3 font-medium">Email</th>
                        <th class="px-5 py-3 font-medium">Total Pesanan</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-pink-50">
                    @forelse ($users as $u)
                        <tr class="hover:bg-pink-50/50">
                            <td class="px-5 py-3 font-medium text-ink-900">{{ $u->name }}</td>
                            <td class="px-5 py-3 text-ink-soft">{{ $u->email }}</td>
                                <td class="px-5 py-3 text-ink-900">{{ $u->orders_count }}</td>
                            <td class="px-5 py-3">
                                <x-owner.badge :classes="$u->is_active ? 'bg-pink-100 text-maroon-900' : 'bg-pink-50 text-ink-soft'">
                                    {{ $u->is_active ? 'Aktif' : 'Nonaktif' }}
                                </x-owner.badge>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if ($u->id !== auth()->id())
                                        <form method="POST" action="{{ route('owner.pengguna.toggle-status', $u) }}">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="p-2 rounded-lg text-ink-soft hover:text-maroon-800 hover:bg-pink-50" title="{{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M9 12l2 2 4-4"/></svg>
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-[11.5px] text-ink-soft pr-2">Akunmu</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-ink-soft">Tidak ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $users->links() }}</div>

</div>
@endsection