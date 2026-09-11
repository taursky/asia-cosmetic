<div class="bg-[#071d5d] text-white">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-4 py-2.5 text-xs sm:px-6 lg:px-8">
        <nav class="hidden gap-6 text-blue-100 md:flex">
            <a href="#about" class="hover:text-white">О компании</a>
            <a href="#hit-products" class="hover:text-white">Хиты</a>
            <a href="#sale-products" class="hover:text-white">Акции</a>
            <a href="#new-products" class="hover:text-white">Новинки</a>
            <a href="#delivery" class="hover:text-white">Доставка</a>
        </nav>
        <div class="ml-auto text-blue-100">Профессиональная косметика • Asia Cosmetic</div>
    </div>
</div>

<header class="sticky top-0 z-40 border-b border-zinc-200/80 bg-white/95 backdrop-blur">
    <div class="mx-auto grid max-w-7xl grid-cols-[auto_1fr_auto] items-center gap-4 px-4 py-4 sm:px-6 lg:grid-cols-[200px_auto_1fr_auto] lg:px-8">
        <a href="{{ route('home') }}" class="text-xl font-semibold tracking-[-.04em] text-[#071d5d] sm:text-2xl">
            ASIA<span class="font-light">·COSMETIC</span>
        </a>

        <a href="{{ route('catalog.index') }}" class="hidden rounded-full bg-[#071d5d] px-5 py-3 text-sm font-semibold text-white hover:bg-[#0d2e84] lg:inline-flex">
            Каталог
        </a>

        <form action="{{ route('catalog.index') }}" method="get" class="col-span-3 row-start-2 flex rounded-full bg-zinc-100 p-1 lg:col-span-1 lg:row-auto">
            <input name="q" type="search" placeholder="Поиск по каталогу" class="min-w-0 flex-1 border-0 bg-transparent px-4 py-2 text-sm outline-none ring-0 placeholder:text-zinc-400 focus:ring-0">
            <button class="grid size-9 place-items-center rounded-full bg-white text-zinc-700 shadow-sm" aria-label="Поиск">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="size-5"><circle cx="11" cy="11" r="7" stroke-width="1.8"/><path d="m20 20-3.5-3.5" stroke-width="1.8" stroke-linecap="round"/></svg>
            </button>
        </form>

        <div class="flex items-center gap-1 sm:gap-2">
            <a href="#" class="grid size-10 place-items-center rounded-full hover:bg-zinc-100" aria-label="Избранное">♡</a>
            <a href="#" class="grid size-10 place-items-center rounded-full hover:bg-zinc-100" aria-label="Корзина">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="size-5"><path d="M3 4h2l2 11h10l2-8H6" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="19" r="1"/><circle cx="17" cy="19" r="1"/></svg>
            </a>
        </div>
    </div>
</header>
