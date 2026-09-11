@props(['title', 'products', 'id' => null, 'moreUrl' => null])

@if ($products->isNotEmpty())
<section @if($id) id="{{ $id }}" @endif class="py-10 sm:py-14">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-7 flex items-end justify-between gap-4">
            <h2 class="text-2xl font-semibold tracking-tight text-zinc-950 sm:text-3xl">{{ $title }}</h2>
            @if($moreUrl)
                <a href="{{ $moreUrl }}" class="hidden text-sm font-medium text-blue-900 hover:underline sm:inline">Смотреть все</a>
            @endif
        </div>

        <div class="grid grid-cols-2 gap-x-4 gap-y-9 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
            @foreach ($products as $product)
                <x-catalog.product-card :product="$product" />
            @endforeach
        </div>
    </div>
</section>
@endif
