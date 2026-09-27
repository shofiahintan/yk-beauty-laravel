@extends('layouts.owner')

@section('title', 'Notifikasi')
@section('breadcrumb', 'Semua Notifikasi')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="font-poppins font-semibold text-[24px] text-maroon-900">Notifikasi</h1>
            <p class="text-[13px] text-ink-soft mt-1">
                {{ $unread > 0 ? $unread.' belum dibaca' : 'Semua notifikasi sudah dibaca' }}
            </p>
        </div>

        @if ($unread > 0)
            <form method="POST" action="{{ route('owner.notifikasi.read-all') }}">
                @csrf
                <button type="submit" class="h-11 px-5 rounded-xl bg-white shadow-owner-card text-[13.5px] text-maroon-800 font-medium hover:bg-pink-50">
                    Tandai semua dibaca
                </button>
            </form>
        @endif
    </div>

    <div class="bg-white rounded-[18px] shadow-owner-card overflow-hidden">
        <div class="divide-y divide-pink-50">
            @forelse ($notifs as $notif)
                <a href="{{ $notif['url'] }}" class="flex items-center gap-3 px-5 py-4 hover:bg-pink-50 {{ $notif['read'] ? '' : 'bg-pink-50/70' }}">
                    <span class="h-9 w-9 shrink-0 rounded-full flex items-center justify-center {{ $notif['type'] === 'order' ? 'bg-pink-100 text-maroon-900' : 'bg-maroon-900 text-white' }}">
                        @if ($notif['type'] === 'order')
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2Z"/></svg>
                        @else
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
                        @endif
                    </span>
                    <span class="flex-1 min-w-0">
                        <span class="block text-[13.5px] {{ $notif['read'] ? 'text-ink-soft font-medium' : 'text-ink-900 font-semibold' }}">{{ $notif['title'] }}</span>
                        <span class="block text-[13px] {{ $notif['read'] ? 'text-ink-soft' : 'text-ink-900' }}">{{ $notif['text'] }}</span>
                        <span class="block text-[11.5px] text-ink-soft mt-0.5">{{ $notif['time']->locale('id')->diffForHumans() }}</span>
                    </span>
                    @unless ($notif['read'])
                        <span class="h-2.5 w-2.5 shrink-0 rounded-full bg-maroon-800" title="Belum dibaca"></span>
                    @endunless
                </a>
            @empty
                <p class="px-5 py-12 text-center text-[13.5px] text-ink-soft">Belum ada notifikasi. Pesanan baru dan stok yang menipis akan muncul di sini.</p>
            @endforelse
        </div>
    </div>
@endsection
