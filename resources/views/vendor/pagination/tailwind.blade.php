@if ($paginator->hasPages())
    @php
        $btn      = 'inline-flex h-10 min-w-[40px] items-center justify-center rounded-xl border px-3 text-[13.5px] font-medium leading-none transition';
        $btnIdle  = $btn.' border-pink-200 bg-white text-ink-900 hover:border-maroon-700/40 hover:bg-pink-50 hover:text-maroon-900 focus:outline-none focus:ring-2 focus:ring-maroon-700/25';
        $btnOff   = $btn.' cursor-default border-pink-100 bg-pink-50/60 text-pink-300';
        $btnOn    = $btn.' cursor-default border-maroon-900 bg-maroon-900 text-white shadow-owner-card';
    @endphp

    <nav role="navigation" aria-label="Navigasi halaman" class="flex flex-col-reverse items-center gap-3 sm:flex-row sm:justify-between">
        <p class="text-[13px] text-ink-soft">
            Menampilkan
            <span class="font-medium text-ink-900">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</span>
            dari
            <span class="font-medium text-ink-900">{{ $paginator->total() }}</span>
            data
        </p>

        <div class="flex items-center gap-1.5">
            {{-- Sebelumnya --}}
            @if ($paginator->onFirstPage())
                <span class="{{ $btnOff }}" aria-disabled="true" aria-label="Halaman sebelumnya">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $btnIdle }}" aria-label="Halaman sebelumnya">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                </a>
            @endif

            {{-- Nomor halaman (disembunyikan di layar kecil, diganti penunjuk "x / y") --}}
            <span class="px-2 text-[13px] text-ink-soft sm:hidden">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="hidden h-10 min-w-[28px] items-center justify-center text-[13.5px] text-ink-soft sm:inline-flex" aria-hidden="true">…</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="hidden sm:inline-flex {{ $btnOn }}" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="hidden sm:inline-flex {{ $btnIdle }}" aria-label="Ke halaman {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Berikutnya --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $btnIdle }}" aria-label="Halaman berikutnya">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                </a>
            @else
                <span class="{{ $btnOff }}" aria-disabled="true" aria-label="Halaman berikutnya">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                </span>
            @endif
        </div>
    </nav>
@endif
