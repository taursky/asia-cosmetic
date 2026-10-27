@props(['product', 'large' => false])

@php
    $badges = app(\App\Services\Catalog\ProductBadgeService::class)->forProduct($product);
    $styles = [
        'sale' => 'bg-rose-600 text-white',
        'new' => 'bg-[#071d5d] text-white',
        'hit' => 'bg-amber-400 text-slate-950',
    ];
@endphp

@if($badges !== [])
    <div {{ $attributes->class(['flex flex-wrap items-start gap-1.5 pointer-events-none']) }}>
        @foreach($badges as $badge)
            <span class="inline-flex items-center rounded-md px-2 py-1 font-bold shadow-sm {{ $large ? 'text-xs sm:text-sm' : 'text-[10px] sm:text-xs' }} {{ $styles[$badge['type']] }}">
                {{ $badge['text'] }}
            </span>
        @endforeach
    </div>
@endif
