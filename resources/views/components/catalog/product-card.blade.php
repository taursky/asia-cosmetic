@props(['product'])

@php
    use Illuminate\Support\Facades\Storage;

    if (! array_key_exists('display_price', $product->getAttributes())) {
        app(\App\Services\Pricing\CatalogPricePresenter::class)
            ->decorateProduct($product, auth()->user());
    }

    $variant = $product->variants->first();
    $image = $product->images->first() ?: $variant?->images?->first();
    $price = $product->display_price;
    $oldPrice = $product->display_old_price;
@endphp

<article class="group flex h-full flex-col">
    <a href="{{ route('catalog.product', $product->lang?->slug) }}" class="block">
        <div class="overflow-hidden rounded-2xl bg-slate-50">
            @if($image)
                <img
                    src="{{ Storage::disk('public')->url($image->name) }}"
                    alt="{{ $product->lang?->name }}"
                    class="aspect-square w-full object-contain p-4 transition duration-300 group-hover:scale-[1.02]"
                    loading="lazy"
                >
            @else
                <div class="flex aspect-square items-center justify-center text-sm text-slate-400">
                    Нет изображения
                </div>
            @endif
        </div>

        <div class="mt-4">
            <div class="line-clamp-2 min-h-10 text-sm font-medium leading-5 text-slate-950">
                {{ $product->lang?->name }}
            </div>

            @if($variant?->sku ?? $product->sku)
                <div class="mt-1 text-xs text-slate-400">
                    {{ $variant?->sku ?? $product->sku }}
                </div>
            @endif

            <div class="mt-3 min-h-12">
                @if($price !== null)
                    <div class="flex flex-wrap items-baseline gap-2">
                        <span class="text-lg font-semibold text-slate-950">
                            @if($product->display_price_from)от @endif{{ number_format((float) $price, 0, ',', ' ') }} ₽
                        </span>

                        @if($oldPrice && $oldPrice > $price)
                            <span class="text-sm text-slate-400 line-through">
                                {{ number_format((float) $oldPrice, 0, ',', ' ') }} ₽
                            </span>
                        @endif
                    </div>

                    <div class="mt-1 text-[11px] font-medium text-slate-400">
                        {{ auth()->check() ? 'Ваша цена' : 'Цена' }} · {{ $product->display_price_type_name }}
                    </div>
                @else
                    <div class="text-sm text-slate-400">Цена по запросу</div>
                @endif
            </div>
        </div>
    </a>

    @if($variant)
        <div class="mt-auto pt-3">
            <x-catalog.add-to-cart :variant="$variant" />
        </div>
    @endif
</article>
