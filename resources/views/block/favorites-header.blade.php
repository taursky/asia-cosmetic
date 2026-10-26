
@php
    $favoritesCount = auth()->check()
        ? auth()->user()->favoriteProducts()->count()
        : 0;
@endphp

<a href="{{ route('favorites.index') }}"
    data-favorites-header
    data-count="{{ $favoritesCount }}"
    aria-label="Избранное: {{ $favoritesCount }} товаров"
    class="relative grid size-10 place-items-center rounded-full transition hover:bg-rose-50"
>
    <svg data-favorites-heart
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="{{ $favoritesCount > 0 ? 'currentColor' : 'none' }}"
        stroke="currentColor"
        stroke-width="1.8"
        stroke-linecap="round"
        stroke-linejoin="round"
        class="size-6 transition-colors {{ $favoritesCount > 0 ? 'text-rose-500' : 'text-[#071d5d]' }}"
    >
        <path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/>
    </svg>

    <span
        data-favorites-badge
        @if($favoritesCount === 0) hidden @endif
        class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold leading-none text-white ring-2 ring-white"
    >
        {{ $favoritesCount > 99 ? '99+' : $favoritesCount }}
    </span>
</a>
