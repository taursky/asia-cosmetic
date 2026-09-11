@props(['product'])

@php
    $variant = $product->variants->first();
    $prices = $variant?->prices->isNotEmpty() ? $variant->prices : $product->prices;
    $retail = $prices->first(fn ($price) => $price->priceType?->code === 'retail') ?? $prices->first();
    $image = $product->images->first() ?? $variant?->images->first();
    $imageUrl = $image?->name ? Storage::disk('public')->url($image->name) : null;
    $name = $product->lang?->name ?? 'Товар';
    $slug = $product->lang?->slug;
    $url = $slug ? route('catalog.product', $slug) : '#';
    $inStock = $product->variants->isEmpty() || $product->variants->sum('stock') > 0;
    $discount = $retail?->old_amount && $retail->amount && $retail->old_amount > $retail->amount
        ? (int) round((1 - ($retail->amount / $retail->old_amount)) * 100)
        : null;
@endphp

<article class="group flex h-full min-w-0 flex-col">
    <a href="{{ $url }}" class="relative block aspect-square overflow-hidden rounded-2xl bg-white ring-1 ring-black/5">
        @if ($imageUrl)
            <img
                src="{{ $imageUrl }}"
                alt="{{ $name }}"
                loading="lazy"
                class="h-full w-full object-contain p-4 transition duration-500 group-hover:scale-[1.035]"
            >
        @else
            <div class="flex h-full items-center justify-center bg-zinc-50 text-sm text-zinc-400">Нет изображения</div>
        @endif

        @if ($discount)
            <span class="absolute left-3 top-3 rounded-full bg-rose-600 px-2.5 py-1 text-xs font-semibold text-white">-{{ $discount }}%</span>
        @endif

        <span @class([
            'absolute bottom-3 left-3 rounded-full px-2.5 py-1 text-[11px] font-medium',
            'bg-emerald-50 text-emerald-700' => $inStock,
            'bg-zinc-100 text-zinc-500' => ! $inStock,
        ])>
            {{ $inStock ? 'В наличии' : 'Нет в наличии' }}
        </span>
    </a>

    <div class="flex flex-1 flex-col pt-4">
        <a href="{{ $url }}" class="line-clamp-2 min-h-12 text-sm font-medium leading-6 text-zinc-900 transition hover:text-blue-900">
            {{ $name }}
        </a>

        <div class="mt-auto flex items-end justify-between gap-3 pt-4">
            <div>
                @if ($retail?->old_amount && $retail->old_amount > $retail->amount)
                    <div class="text-xs text-zinc-400 line-through">{{ number_format((float) $retail->old_amount, 0, ',', ' ') }} ₽</div>
                @endif
                <div class="text-lg font-semibold tracking-tight text-zinc-950">
                    {{ $retail?->amount !== null ? number_format((float) $retail->amount, 0, ',', ' ') . ' ₽' : 'Цена по запросу' }}
                </div>
            </div>

            <a
                href="{{ $url }}"
                aria-label="Открыть {{ $name }}"
                class="grid size-10 shrink-0 place-items-center rounded-full bg-[#071d5d] text-white transition hover:bg-[#0d2e84]"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="size-5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 12h14m-6-6 6 6-6 6"/>
                </svg>
            </a>
        </div>
    </div>
</article>
