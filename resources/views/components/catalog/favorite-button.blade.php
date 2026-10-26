@props(['product'])
@php
    // The caller must add withExists(['favoritedBy as is_favorite' => ...]) to its query.
    $active = (bool) ($product->is_favorite ?? false);
    $loginUrl = Route::has('login') ? route('login') : url('/login');
@endphp
<button
    type="button"
    data-favorite-button
    data-product-id="{{ $product->getKey() }}"
    data-favorite="{{ $active ? '1' : '0' }}"
    @guest data-login-url="{{ $loginUrl }}?redirect={{ urlencode(url()->current()) }}" @endguest
    aria-label="{{ $active ? 'Удалить из избранного' : 'Добавить в избранное' }}"
    aria-pressed="{{ $active ? 'true' : 'false' }}"
    title="{{ $active ? 'Удалить из избранного' : 'Добавить в избранное' }}"
    class="absolute right-3 top-3 z-10 grid size-10 place-items-center rounded-full bg-white/95 shadow-sm ring-1 ring-slate-200 transition hover:cursor-pointer hover:scale-105 disabled:opacity-50 {{ $active ? 'text-rose-600' : 'text-slate-400' }}"
>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="{{ $active ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.8" class="size-5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78Z"/></svg>
</button>
