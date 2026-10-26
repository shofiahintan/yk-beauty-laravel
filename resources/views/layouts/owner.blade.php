<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title') — Panel Owner · YK Beauty & Skincare</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500;1,600&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

@vite(['resources/css/app.css', 'resources/js/app.js'])

<style>
    /* Scrollbar tipis untuk dropdown notifikasi — CSS biasa (bukan util Tailwind)
       biar dijamin ke-load bareng halaman, gak tergantung Tailwind JIT nge-scan class-nya. */
    .owner-notif-scroll { scrollbar-width: thin; scrollbar-color: #F6D2CD transparent; }
    .owner-notif-scroll::-webkit-scrollbar { width: 6px; }
    .owner-notif-scroll::-webkit-scrollbar-track { background: transparent; }
    .owner-notif-scroll::-webkit-scrollbar-thumb { background-color: #F6D2CD; border-radius: 9999px; }
</style>
</head>
<body class="font-poppins text-ink-900 antialiased bg-cream" x-data="{ sidebarOpen: false, notifOpen: false }">

@php
    $ownerToastError = $errors->any() ? $errors->first() : session('error');
@endphp
@if (session('status') || $ownerToastError)
<div class="fixed top-5 right-5 z-[100] flex flex-col gap-3 max-w-[calc(100vw-2.5rem)] w-full sm:w-[360px]">
    @if (session('status'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 translate-x-4"
             class="rounded-2xl bg-white shadow-xl border-l-4 border-green-600 px-4 py-3 flex items-start gap-3">
            <span class="shrink-0 h-5 w-5 rounded-full bg-green-600 text-white flex items-center justify-center text-[11px] font-bold mt-0.5">&check;</span>
            <p class="text-[13px] text-ink-900 flex-1 leading-snug">{{ session('status') }}</p>
            <button type="button" @click="show = false" class="text-ink-soft hover:text-ink-900 text-sm leading-none">&times;</button>
        </div>
    @endif
    @if ($ownerToastError)
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 translate-x-4"
             class="rounded-2xl bg-white shadow-xl border-l-4 border-red-600 px-4 py-3 flex items-start gap-3">
            <span class="shrink-0 h-5 w-5 rounded-full bg-red-600 text-white flex items-center justify-center text-[11px] font-bold mt-0.5">!</span>
            <p class="text-[13px] text-ink-900 flex-1 leading-snug">{{ $ownerToastError }}</p>
            <button type="button" @click="show = false" class="text-ink-soft hover:text-ink-900 text-sm leading-none">&times;</button>
        </div>
    @endif
</div>
@endif

<div class="min-h-screen lg:flex">

    {{-- ============ SIDEBAR ============ --}}
    <aside
        class="fixed inset-y-0 left-0 z-40 w-[264px] flex flex-col text-white
               bg-gradient-to-br from-[#4A1220] to-[#8C2E3C]
               transform transition-transform duration-200 lg:translate-x-0"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    >
        <div class="px-6 pt-6 pb-5">
            <a href="{{ route('owner.dashboard') }}" class="inline-block">
                <img src="{{ asset('assets/images/logoyk2-white.png') }}" alt="YK Beauty & Skincare"
                     class="h-14 w-auto">
            </a>
        </div>

        <nav class="flex-1 px-4 space-y-1">
            @php
                $navItems = [
                    ['route' => 'owner.dashboard', 'active' => 'owner.dashboard', 'label' => 'Dashboard', 'icon' => 'grid'],
                    ['route' => 'owner.produk.index', 'active' => ['owner.produk.*', 'owner.koleksi.*'], 'label' => 'Kelola Produk', 'icon' => 'box'],
                    ['route' => 'owner.artikel.index', 'active' => 'owner.artikel.*', 'label' => 'Artikel', 'icon' => 'book'],
                    ['route' => 'owner.transaksi.index', 'active' => 'owner.transaksi.*', 'label' => 'Kelola Transaksi Pemesanan', 'icon' => 'receipt'],
                    ['route' => 'owner.refund.index', 'active' => 'owner.refund.*', 'label' => 'Refund', 'icon' => 'refund', 'refund' => true],
                    ['route' => 'owner.laporan.index', 'active' => 'owner.laporan.*', 'label' => 'Kelola Laporan', 'icon' => 'chart'],
                    ['route' => 'owner.pengguna.index', 'active' => 'owner.pengguna.*', 'label' => 'Kelola Data Pengguna', 'icon' => 'users'],
                    ['route' => 'owner.pesan.index', 'active' => 'owner.pesan.*', 'label' => 'Pesan', 'icon' => 'chat', 'chat' => true],
                ];
                $icons = [
                    'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
                    'box' => '<path d="M21 8.5 12 3 3 8.5v7L12 21l9-5.5v-7Z"/><path d="M3 8.5 12 14l9-5.5"/><path d="M12 14v7"/>',
                    'book' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>',
                    'receipt' => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2Z"/><path d="M9 8h6M9 12h6"/>',
                    'refund' => '<path d="M9 14 4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 5.5 5.5v2"/>',
                    'chart' => '<path d="M4 20V10M12 20V4M20 20v-7"/>',
                    'users' => '<circle cx="9" cy="8" r="3.2"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><path d="M16 8.2a3.2 3.2 0 1 1 0 6.3"/><path d="M21 20c0-2.6-1.7-4.8-4-5.6"/>',
                    'chat' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><circle cx="8" cy="10" r="1" fill="currentColor" stroke="none"/><circle cx="12" cy="10" r="1" fill="currentColor" stroke="none"/><circle cx="16" cy="10" r="1" fill="currentColor" stroke="none"/>',
                ];
            @endphp
            @foreach ($navItems as $item)
                @php $isActive = request()->routeIs(...(array) $item['active']); @endphp
                <a href="{{ route($item['route']) }}"
                   class="flex items-center gap-3 rounded-2xl px-4 py-3.5 text-[15px] transition
                          {{ $isActive ? 'bg-white/[0.12] text-white font-medium' : 'text-pink-50/70 hover:text-white hover:bg-white/[0.08]' }}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $icons[$item['icon']] !!}</svg>
                    <span>{{ $item['label'] }}</span>
                    @if (! empty($item['refund']))
                        @php $refCount = (int) ($ownerPendingRefunds ?? 0); @endphp
                        @if ($refCount > 0)
                            <span class="ml-auto min-w-[22px] h-[22px] px-1.5 rounded-full bg-pink-100 text-maroon-900 text-[11px] font-semibold flex items-center justify-center">{{ $refCount > 99 ? '99+' : $refCount }}</span>
                        @endif
                    @endif
                    @if (! empty($item['chat']))
                        @php $chatUnread = (int) ($ownerChatUnread ?? 0); @endphp
                        <span data-chat-badge @if ($chatUnread < 1) style="display:none" @endif
                              class="ml-auto min-w-[22px] h-[22px] px-1.5 rounded-full bg-pink-100 text-maroon-900 text-[11px] font-semibold flex items-center justify-center">{{ $chatUnread > 99 ? '99+' : $chatUnread }}</span>
                    @endif
                </a>
            @endforeach
        </nav>

        <div class="px-4 pb-4 mt-auto">
            <div class="border-t border-white/10 pt-4 flex items-center gap-3">
                <div class="h-9 w-9 rounded-full bg-white/15 flex items-center justify-center text-sm font-semibold">
                    {{ strtoupper(substr(auth()->user()->name ?? 'O', 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-[14px] font-semibold text-white truncate">{{ auth()->user()->name }}</p>
                    <span class="inline-block text-[11px] px-2 py-0.5 rounded-full bg-pink-100/90 text-maroon-900 font-medium mt-0.5">
                        Owner
                    </span>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="p-2 rounded-lg text-pink-50/70 hover:text-white hover:bg-white/10" title="Logout">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- backdrop mobile --}}
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 z-30 bg-black/30 lg:hidden"></div>

    {{-- ============ MAIN ============ --}}
    <div class="flex-1 lg:ml-[264px] min-h-screen flex flex-col">

        {{-- TOPBAR --}}
        <header class="sticky top-0 z-30 h-[72px] shrink-0 bg-cream border-b border-pink-200 flex items-center gap-4 px-5 lg:px-8">
            <button @click="sidebarOpen = true" class="lg:hidden p-2 -ml-2 text-ink-900">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>

            {{-- PENCARIAN (kiri) — bagian tengah sengaja dikosongkan --}}
            <div class="flex-1 min-w-0">
                <div class="relative w-full max-w-[440px]"
                     x-data="ownerSearch('{{ route('owner.cari') }}')"
                     @click.outside="open = false" @keydown.escape="open = false">
                    <form method="GET" action="{{ route('owner.cari') }}" @submit="open = false">
                        <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 text-ink-soft pointer-events-none" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                        <input type="text" name="q" x-model="q" autocomplete="off"
                               @input.debounce.300ms="fetchResults()" @focus="if (searched) open = true"
                               placeholder="Cari pesanan, produk, atau pelanggan..."
                               class="w-full h-11 rounded-2xl border-0 bg-pink-50 pl-10 pr-4 text-[14px] text-ink-900 placeholder:text-ink-soft focus:ring-2 focus:ring-maroon-700/30">
                    </form>

                    <div x-show="open" x-cloak x-transition.opacity
                         class="absolute left-0 right-0 top-full mt-2 z-50 bg-white rounded-2xl shadow-owner-modal border border-pink-200 overflow-hidden">
                        <div class="max-h-[70vh] overflow-y-auto owner-notif-scroll">
                            <template x-for="g in groups" :key="g.key">
                                <div class="py-2 border-b border-pink-50 last:border-0">
                                    <p class="px-4 pb-1 text-[11px] uppercase tracking-wide font-semibold text-maroon-700" x-text="g.label"></p>
                                    <template x-for="it in g.items" :key="it.url">
                                        <a :href="it.url" class="flex items-center gap-3 px-4 py-2 hover:bg-pink-50">
                                            <template x-if="it.image">
                                                <img :src="it.image" class="h-9 w-9 rounded-lg object-cover shrink-0" alt="">
                                            </template>
                                            <span class="min-w-0 flex-1">
                                                <span class="block text-[13px] text-ink-900 truncate" x-text="it.title"></span>
                                                <span class="block text-[11.5px] text-ink-soft truncate" x-text="it.subtitle"></span>
                                            </span>
                                            <template x-if="it.badge">
                                                <span class="shrink-0 rounded-full bg-pink-100 text-maroon-900 text-[11px] font-medium px-2 py-0.5" x-text="it.badge"></span>
                                            </template>
                                        </a>
                                    </template>
                                </div>
                            </template>
                            <p x-show="searched && groups.length === 0" class="px-4 py-6 text-center text-[13px] text-ink-soft">Tidak ada hasil untuk pencarian ini.</p>
                        </div>
                        <a :href="'{{ route('owner.cari') }}?q=' + encodeURIComponent(q.trim())"
                           class="block text-center py-2.5 text-[12.5px] font-medium text-maroon-700 hover:bg-pink-50 border-t border-pink-100">
                            Lihat semua hasil
                        </a>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-1 shrink-0">

                {{-- Notifikasi --}}
                <div class="relative">
                    <button type="button" class="relative p-2 rounded-lg text-ink-900 hover:bg-pink-50" @click="notifOpen = !notifOpen" title="Notifikasi">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6"/><path d="M10 21a2 2 0 0 0 4 0"/></svg>
                        @if (($ownerNotifCount ?? 0) > 0)
                            <span class="absolute top-1.5 right-1.5 h-2 w-2 rounded-full bg-maroon-800"></span>
                        @endif
                    </button>

                    <div x-show="notifOpen" x-cloak @click.outside="notifOpen = false"
                         x-transition
                         class="absolute right-0 mt-2 bg-white rounded-2xl shadow-owner-modal border border-pink-200 z-50 overflow-hidden flex flex-col"
                         style="width: 400px; max-width: calc(100vw - 2rem); max-height: calc(100vh - 96px);">
                        <div class="px-5 py-3.5 border-b border-pink-100 shrink-0 flex items-center justify-between gap-3">
                            <p class="text-[15px] font-semibold text-ink-900">
                                Notifikasi
                                @if (($ownerNotifCount ?? 0) > 0)
                                    <span class="ml-1.5 inline-block rounded-full bg-maroon-900 px-2 py-0.5 text-[11px] font-medium text-white align-middle">{{ $ownerNotifCount }} baru</span>
                                @endif
                            </p>
                            @if (($ownerNotifCount ?? 0) > 0)
                                <form method="POST" action="{{ route('owner.notifikasi.read-all') }}">
                                    @csrf
                                    <button type="submit" class="text-[12.5px] font-medium text-maroon-700 hover:text-maroon-900 whitespace-nowrap">Tandai semua dibaca</button>
                                </form>
                            @endif
                        </div>

                        <div class="owner-notif-scroll overflow-y-auto min-h-0">
                            @forelse (($ownerNotifs ?? collect()) as $notif)
                                <a href="{{ $notif['url'] }}" class="flex items-start gap-3 px-5 py-3.5 border-b border-pink-50 last:border-0 hover:bg-pink-50 {{ $notif['read'] ? '' : 'bg-pink-50/70' }}">
                                    <span class="h-9 w-9 shrink-0 rounded-full flex items-center justify-center {{ $notif['type'] === 'order' ? 'bg-pink-100 text-maroon-900' : 'bg-maroon-900 text-white' }}">
                                        @if ($notif['type'] === 'order')
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2Z"/></svg>
                                        @else
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
                                        @endif
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-[13.5px] {{ $notif['read'] ? 'font-medium text-ink-soft' : 'font-semibold text-ink-900' }}">{{ $notif['title'] }}</span>
                                        <span class="mt-0.5 block text-[12.5px] leading-snug {{ $notif['read'] ? 'text-ink-soft' : 'text-ink-900/80' }}">{{ $notif['text'] }}</span>
                                        <span class="mt-1 block text-[11.5px] text-ink-soft">{{ $notif['time']->locale('id')->diffForHumans() }}</span>
                                    </span>
                                    @unless ($notif['read'])
                                        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-maroon-800"></span>
                                    @endunless
                                </a>
                            @empty
                                <p class="px-5 py-10 text-center text-[13px] text-ink-soft">Belum ada notifikasi.</p>
                            @endforelse
                        </div>

                        <a href="{{ route('owner.notifikasi.index') }}" class="block text-center py-3 text-[13px] font-medium text-maroon-700 hover:bg-pink-50 border-t border-pink-100 shrink-0">
                            Lihat semua notifikasi
                        </a>
                    </div>
                </div>

                {{-- Akun --}}
                <div class="relative ml-1" x-data="{ menuOpen: false }">
                    <button type="button" @click="menuOpen = !menuOpen" title="Akun"
                            class="h-9 w-9 rounded-full bg-maroon-900 text-white flex items-center justify-center text-xs font-semibold hover:bg-maroon-800 focus:outline-none focus:ring-2 focus:ring-maroon-700/40">
                        {{ strtoupper(substr(auth()->user()->name ?? 'O', 0, 1)) }}
                    </button>

                    <div x-show="menuOpen" x-cloak @click.outside="menuOpen = false" x-transition
                         class="absolute right-0 mt-2 w-[230px] bg-white rounded-2xl shadow-owner-modal border border-pink-200 z-50 overflow-hidden">
                        <div class="px-4 py-3 border-b border-pink-100">
                            <p class="text-[13.5px] font-semibold text-ink-900 truncate">{{ auth()->user()->name }}</p>
                            <p class="text-[12px] text-ink-soft truncate">{{ auth()->user()->email }}</p>
                        </div>
                        <a href="{{ route('owner.profil.edit') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] text-ink-900 hover:bg-pink-50">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                            Profil &amp; Keamanan
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="border-t border-pink-100">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-2.5 px-4 py-2.5 text-[13px] text-ink-900 hover:bg-pink-50 text-left">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
                                Keluar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        {{-- PAGE CONTENT --}}
        <main class="flex-1 p-5 lg:p-8">
            @yield('content')
        </main>
    </div>
</div>

<script>
function ownerSearch(url) {
    return {
        q: '',
        open: false,
        searched: false,
        groups: [],
        async fetchResults() {
            const term = this.q.trim();
            if (term.length < 2) {
                this.groups = [];
                this.searched = false;
                this.open = false;
                return;
            }
            try {
                const res = await fetch(url + '?q=' + encodeURIComponent(term), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await res.json();
                if (term !== this.q.trim()) return; // hasil basi, abaikan
                this.groups = data.groups || [];
                this.searched = true;
                this.open = true;
            } catch (e) {
                this.groups = [];
            }
        },
    };
}
</script>

{{-- Filter "cari" di halaman list (Transaksi/Produk/Pengguna): auto-submit saat mengetik
     (debounce), tanpa tombol "Cari" terpisah — biar cuma jadi filter halaman ini, konsisten
     dengan dropdown di sampingnya. Pencarian lintas-halaman tetap lewat kolom di navbar atas. --}}
<script>
document.querySelectorAll('form[data-autofilter] input[name="cari"]').forEach(function (input) {
    var t;
    input.addEventListener('input', function () {
        clearTimeout(t);
        t = setTimeout(function () { input.form.requestSubmit(); }, 500);
    });
});
</script>

{{-- Chat: badge "Pesan" di sidebar ikut real-time di semua halaman Owner --}}
<script>
window.YK_OWNER_CHAT = @json([
    'unreadUrl' => route('owner.pesan.unread'),
    'pusher' => \App\Support\ChatService::pusherConfig(),
]);
</script>
<script src="{{ asset('js/chat.js') }}"></script>
<script src="{{ asset('js/chat-owner.js') }}"></script>

@stack('scripts')
</body>
</html>