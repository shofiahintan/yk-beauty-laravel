@props(['classes' => 'bg-pink-100 text-maroon-900'])
<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[12px] font-medium whitespace-nowrap $classes"]) }}>
    {{ $slot }}
</span>
