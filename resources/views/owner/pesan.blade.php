@extends('layouts.owner')

@section('title', 'Pesan')
@section('breadcrumb', 'Pesan')

@section('content')
<link rel="stylesheet" href="{{ asset('css/chat.css') }}">

<div class="yk-ochat bg-white rounded-[18px] shadow-owner-card overflow-hidden flex" id="ownerChat">

    {{-- ============ DAFTAR PERCAKAPAN ============ --}}
    <aside class="{{ $active ? 'hidden lg:flex' : 'flex' }} w-full lg:w-[340px] shrink-0 flex-col border-r border-pink-100">
        <div class="px-5 pt-5 pb-4 border-b border-pink-100 shrink-0">
            <h2 class="text-lg font-semibold text-ink-900">Pesan</h2>
            <p class="text-[12.5px] text-ink-soft mt-0.5">Percakapan dengan pelanggan</p>
            <input id="ownerChatSearch" type="text" autocomplete="off" placeholder="Cari nama pelanggan..."
                   class="mt-3 w-full h-10 rounded-xl border-0 bg-pink-50 px-3.5 text-[13px] text-ink-900 placeholder:text-ink-soft focus:ring-2 focus:ring-maroon-700/30">
        </div>
        <div id="ownerChatList" class="flex-1 min-h-0 overflow-y-auto owner-notif-scroll"></div>
    </aside>

    {{-- ============ RUANG CHAT ============ --}}
    <section class="{{ $active ? 'flex' : 'hidden lg:flex' }} flex-1 min-w-0 flex-col">
        @if ($active)
            <header class="shrink-0 flex items-center gap-3 px-5 py-3.5 border-b border-pink-100">
                <a href="{{ route('owner.pesan.index') }}" class="lg:hidden -ml-2 p-2 rounded-lg text-ink-900 hover:bg-pink-50" title="Kembali">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
                </a>
                <div class="h-10 w-10 shrink-0 rounded-full bg-maroon-800 text-white flex items-center justify-center text-sm font-semibold">
                    {{ strtoupper(mb_substr($active->name, 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <p class="text-[14.5px] font-semibold text-ink-900 truncate">{{ $active->name }}</p>
                    <p class="text-[12px] text-ink-soft truncate">{{ $active->email }}</p>
                </div>
            </header>

            <div class="yk-chat-scroll" id="ownerChatBox"></div>

            <form class="yk-chat-form" id="ownerChatForm" novalidate>
                <div class="yk-chat-preview" id="ownerChatPreview">
                    <img src="" alt="">
                    <button type="button" class="yk-chat-preview-x" aria-label="Hapus foto">&times;</button>
                </div>
                <p class="yk-chat-error" id="ownerChatError" role="alert"></p>
                <div class="yk-chat-row">
                    <button type="button" class="yk-chat-attach" id="ownerChatAttach" aria-label="Lampirkan foto" title="Lampirkan foto">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2.5"/><circle cx="9" cy="10" r="1.6"/><path d="m21 16-5-5-8 9"/></svg>
                    </button>
                    <input type="file" id="ownerChatFile" accept="image/jpeg,image/png,image/webp" hidden>
                    <textarea id="ownerChatText" rows="1" maxlength="2000" placeholder="Tulis balasan..."></textarea>
                    <button type="submit" class="yk-chat-send" id="ownerChatSend" aria-label="Kirim" title="Kirim">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4 20-7Z"/></svg>
                    </button>
                </div>
            </form>
        @else
            <div class="flex-1 flex flex-col items-center justify-center text-center px-8">
                <span class="h-14 w-14 rounded-full bg-pink-100 text-maroon-800 flex items-center justify-center">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1.1-4.6A8 8 0 1 1 21 12Z"/><path d="M8.5 11h7M8.5 14h4"/></svg>
                </span>
                <p class="mt-4 text-[15px] font-semibold text-ink-900">Pilih percakapan</p>
                <p class="mt-1 text-[13px] text-ink-soft max-w-[280px]">Pilih pelanggan di daftar sebelah kiri untuk membaca dan membalas pesannya.</p>
            </div>
        @endif
    </section>
</div>

@php
$chatPage = [
    'threads' => $threads,
    'activeId' => $active ? $active->id : null,
    'threadsUrl' => route('owner.pesan.threads'),
    'messagesUrl' => $active ? route('owner.pesan.messages', $active) : null,
    'sendUrl' => $active ? route('owner.pesan.send', $active) : null,
    'i18n' => [
        'locale' => 'id-ID',
        'today' => 'Hari ini',
        'yesterday' => 'Kemarin',
        'older' => 'Muat pesan sebelumnya',
        'empty' => 'Belum ada pesan di percakapan ini.',
        'noThreads' => 'Belum ada pelanggan yang mengirim pesan.',
        'noMatch' => 'Tidak ada pelanggan dengan nama itu.',
        'botLabel' => 'Balasan otomatis',
        'errSend' => 'Pesan gagal terkirim. Silakan coba lagi.',
        'errType' => 'Pilih file gambar (JPG, PNG, atau WEBP).',
        'errSize' => 'Ukuran gambar maksimal 5 MB.',
        'errSession' => 'Sesi berakhir. Muat ulang halaman lalu masuk kembali.',
        'errEmpty' => 'Tulis pesan atau lampirkan foto.',
    ],
];
@endphp
<script>
window.YK_OWNER_CHAT_PAGE = @json($chatPage);
</script>
@endsection