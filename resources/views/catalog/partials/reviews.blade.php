<section id="reviews" class="mt-16 border-t border-slate-200 pt-10">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-semibold text-slate-950">Отзывы покупателей</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $product->reviews_count }} отзывов · {{ number_format((float) $product->rating, 1, ',', ' ') }} из 5</p>
        </div>
        <a class="text-sm font-medium text-[#071d5d] hover:underline" href="{{ route('reviews.product', $product->lang->slug) }}">Все отзывы о товаре</a>
    </div>
    @if(session('review_success'))
        <p class="mb-5 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('review_success') }}</p>
    @endif
    @forelse($product->reviews as $review)
        <article class="mb-4 rounded-2xl border border-slate-200 p-5">
            <div class="flex justify-between gap-3"><strong class="text-slate-900">{{ $review->user?->name ?: 'Покупатель' }}</strong><span class="text-amber-500">{{ str_repeat('★', $review->rating) }}<span class="text-slate-300">{{ str_repeat('☆', 5 - $review->rating) }}</span></span></div>
            @if($review->title)<h3 class="mt-2 font-semibold">{{ $review->title }}</h3>@endif
            <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $review->body }}</p>
            <time class="mt-3 block text-xs text-slate-400">{{ $review->published_at?->format('d.m.Y') }}</time>
        </article>
    @empty
        <p class="mb-6 text-sm text-slate-500">Пока нет опубликованных отзывов.</p>
    @endforelse
    @auth('web')
        <form method="POST" action="{{ route('reviews.store', $product) }}" class="mt-8 max-w-2xl space-y-4 rounded-2xl bg-slate-50 p-5">
            @csrf
            <h3 class="text-lg font-semibold">Написать отзыв</h3>
            <label class="block text-sm">Оценка
                <select name="rating" required class="mt-1 w-full rounded-lg border border-slate-300 p-2">
                    @foreach([5,4,3,2,1] as $star)<option value="{{ $star }}" @selected(old('rating', 5) == $star)>{{ $star }} ★</option>@endforeach
                </select>
            </label>
            <label class="block text-sm">Заголовок (необязательно)<input name="title" value="{{ old('title') }}" maxlength="150" class="mt-1 w-full rounded-lg border border-slate-300 p-2"></label>
            <label class="block text-sm">Отзыв<textarea name="body" required minlength="10" maxlength="5000" rows="4" class="mt-1 w-full rounded-lg border border-slate-300 p-2">{{ old('body') }}</textarea></label>
            @if($errors->any())<p class="text-sm text-rose-600">{{ $errors->first() }}</p>@endif
            <button class="rounded-xl bg-[#071d5d] px-5 py-3 text-sm font-semibold text-white">Отправить на модерацию</button>
        </form>
    @else
        <p class="mt-6 text-sm text-slate-600"><a href="{{ route('login') }}" class="text-[#071d5d] underline">Войдите</a>, чтобы оставить отзыв.</p>
    @endauth
</section>
