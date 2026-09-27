{{--
    Dropdown kustom Owner Panel (pengganti <select> bawaan browser).
    Gaya mengikuti dropdown "Urutkan" di halaman produk sisi pelanggan.

    Variant: filter (default, h-11) | field (penuh, untuk form/modal) | compact (kecil, sejajar chip).

    Pemakaian:
      Filter (langsung submit form saat dipilih):
        <x-owner.select name="status" :options="['' => 'Semua Status', 'aktif' => 'Aktif']" :value="$filters['status'] ?? ''" submit />
      Di dalam modal / panel Alpine (terhubung ke variabel Alpine milik parent):
        <x-owner.select name="role" model="form.role" variant="field" :options="['admin' => 'Admin', 'owner' => 'Owner']" />
--}}
@props([
    'name' => null,
    'options' => [],
    'value' => '',
    'model' => null,
    'submit' => false,
    'variant' => 'filter',
    'after' => null,
    'up' => false,
])
@php
    $items = [];
    foreach ($options as $optValue => $optLabel) {
        $items[] = ['value' => (string) $optValue, 'label' => (string) $optLabel];
    }

    $target = $model ?: 'local';

    $pick = $target . ' = opt.value; open = false;';
    if ($after) {
        $pick .= ' ' . $after;
    }
    if ($submit) {
        $pick .= " \$refs.input.value = opt.value; \$el.closest('form').submit();";
    }

    $isField = $variant === 'field';
    $triggerClass = match ($variant) {
        'field' => 'w-full h-11 rounded-xl border border-pink-200 bg-white px-3.5 text-[13.5px]',
        'compact' => 'h-8 min-w-[150px] rounded-full border-0 bg-white shadow-owner-card px-3.5 text-[12px] font-medium text-maroon-800',
        default => 'h-11 min-w-[170px] rounded-xl border-0 bg-white shadow-owner-card px-4 text-[13.5px]',
    };
@endphp
<div class="relative {{ $isField ? 'w-full' : 'inline-block' }}"
     x-data="{
        open: false,
        local: {{ \Illuminate\Support\Js::from((string) $value) }},
        options: {{ \Illuminate\Support\Js::from($items) }},
        label(v) {
            const o = this.options.find(o => o.value === String(v ?? ''));
            return o ? o.label : (this.options[0] ? this.options[0].label : '');
        }
     }"
     @click.outside="open = false"
     @keydown.escape="open = false">

    <input type="hidden" x-ref="input" @if ($name) name="{{ $name }}" @endif value="{{ $value }}" :value="{{ $target }}">

    <button type="button" @click="open = !open"
            class="{{ $triggerClass }} flex items-center justify-between gap-3 text-ink-900 text-left transition focus:outline-none focus:ring-2 focus:ring-maroon-700/30"
            :class="open ? 'ring-2 ring-maroon-700/30' : ''">
        <span class="truncate" x-text="label({{ $target }})"></span>
        <svg class="shrink-0 text-maroon-900 transition-transform duration-150" :class="open ? 'rotate-180' : ''"
             width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
    </button>

    <div x-show="open" x-cloak x-transition.opacity
         class="absolute {{ $up ? 'bottom-full mb-2' : 'top-full mt-2' }} left-0 z-40 w-max min-w-full max-w-[280px] overflow-hidden rounded-xl border border-pink-200 bg-white shadow-owner-modal">
        <div class="max-h-64 overflow-y-auto owner-notif-scroll py-1">
            <template x-for="opt in options" :key="opt.value">
                <button type="button" @click="{{ $pick }}"
                        class="block w-full px-4 py-2.5 text-left text-[13.5px] transition-colors"
                        :class="String({{ $target }} ?? '') === opt.value ? 'bg-maroon-800 text-white font-semibold' : 'text-ink-900 hover:bg-pink-100'"
                        x-text="opt.label"></button>
            </template>
        </div>
    </div>
</div>
