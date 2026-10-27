<div x-data="{ opened: {{ $errors->any() ? "'reviews'" : 'null' }} }" class="mt-16 divide-y divide-slate-200 border-t border-slate-200" id="product-details">
    @foreach(['description' => 'Описание товара', 'specifications' => 'Характеристики', 'reviews' => 'Отзывы покупателей'] as $key => $label)
        <section class="scroll-mt-24">
            <h2>
                <button type="button" @click="opened = opened === '{{ $key }}' ? null : '{{ $key }}'" :aria-expanded="opened === '{{ $key }}'" aria-controls="product-panel-{{ $key }}" class="flex w-full items-center justify-between py-5 text-left text-xl font-semibold text-slate-950 hover:text-[#071d5d]">
                    <span>{{ $label }} @if($key === 'reviews' && $product->reviews_count) <span class="text-sm font-normal text-slate-500">({{ $product->reviews_count }})</span> @endif</span>
                    <svg class="size-5 transition-transform" :class="{ 'rotate-180': opened === '{{ $key }}' }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                </button>
            </h2>
            <div id="product-panel-{{ $key }}" x-show="opened === '{{ $key }}'" x-cloak class="pb-8 pt-2">
                @switch($key)
                    @case('description')
                        @if($product->lang?->description)
                            <div class="prose prose-slate max-w-none">{!! $product->lang->description !!}</div>
                        @else
                            <p class="text-sm text-slate-500">Описание пока не добавлено.</p>
                        @endif
                        @break
                    @case('specifications')
                        @if($product->attributeValues->isNotEmpty())
                            <dl class="max-w-3xl divide-y divide-slate-100">
                                @foreach($product->attributeValues as $value)
                                    <div class="grid grid-cols-2 gap-4 py-3 text-sm"><dt class="text-slate-500">{{ $value->attribute?->lang?->name ?? $value->attribute?->code }}</dt><dd class="font-medium text-slate-900">{{ $value->lang?->value ?? $value->code }}</dd></div>
                                @endforeach
                            </dl>
                        @else
                            <p class="text-sm text-slate-500">Характеристики пока не добавлены.</p>
                        @endif
                        @break
                    @case('reviews')
                        <livewire:catalog.product-reviews :product-id="$product->id" :key="'product-reviews-'.$product->id" />
                        @break
                @endswitch
            </div>
        </section>
    @endforeach
</div>
