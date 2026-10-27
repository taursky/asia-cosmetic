<div class="max-w-4xl">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <span class="text-3xl font-semibold">{{ number_format((float) $product->rating, 1, ',', ' ') }}</span>
            <div><div class="text-amber-500 text-xl">★★★★★</div><p class="text-xs text-slate-500">{{ $product->reviews_count }} отзывов</p></div>
        </div>
        <a href="{{ route('reviews.product', $product->lang->slug) }}" class="text-sm font-medium text-[#071d5d] hover:underline">Все отзывы</a>
    </div>
    @if(session('review_success'))<p class="mb-5 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('review_success') }}</p>@endif
    @forelse($product->reviews as $review)
        <article class="border-t border-slate-100 py-5">
            <div class="flex flex-wrap items-center gap-3"><strong>{{ $review->user?->name ?: 'Покупатель' }}</strong><span class="text-amber-500">{{ str_repeat('★', $review->rating) }}<span class="text-slate-300">{{ str_repeat('☆', 5 - $review->rating) }}</span></span><time class="text-xs text-slate-400">{{ $review->published_at?->format('d.m.Y') }}</time></div>
            <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $review->body }}</p>
            @if($review->relationLoaded('media') && $review->media->isNotEmpty())
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach($review->media as $media)
                        @if($media->type === 'image')
                            <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($media->path) }}" target="_blank" rel="noopener"><img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($media->path) }}" alt="Фото отзыва" loading="lazy" class="size-20 rounded-lg object-cover"></a>
                        @else
                            <video controls preload="metadata" class="h-28 max-w-48 rounded-lg"><source src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($media->path) }}" type="{{ $media->mime_type }}"></video>
                        @endif
                    @endforeach
                </div>
            @endif
        </article>
    @empty
        <p class="py-5 text-sm text-slate-500">Пока нет опубликованных отзывов.</p>
    @endforelse
    @auth('web')
        <div x-data="{ formOpen: {{ $errors->any() ? 'true' : 'false' }}, rating: {{ (int) old('rating', 0) }}, hover: 0, files: [] }" class="mt-5">
            <button type="button" @click="formOpen = !formOpen" class="rounded-xl bg-[#071d5d] px-5 py-3 text-sm font-semibold text-white">Оставить отзыв</button>
            <form x-cloak x-show="formOpen" method="POST" action="{{ route('reviews.store', $product) }}" enctype="multipart/form-data" class="mt-5 space-y-5 rounded-2xl bg-slate-50 p-5">
                @csrf
                <fieldset><legend class="mb-2 text-sm font-medium">Ваша оценка</legend>
                    <div class="flex gap-1" @mouseleave="hover = 0">
                        <template x-for="star in [1,2,3,4,5]" :key="star">
                            <button type="button" @mouseenter="hover = star" @click="rating = star" :aria-label="star + ' из 5'" :aria-pressed="rating === star" class="text-4xl transition" :class="star <= (hover || rating) ? 'text-amber-400' : 'text-slate-300'">★</button>
                        </template>
                    </div><input type="hidden" name="rating" :value="rating" required>
                    @error('rating')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                </fieldset>
                <label class="block text-sm font-medium">Отзыв<textarea name="body" required minlength="10" maxlength="5000" rows="4" class="mt-2 w-full rounded-xl border border-slate-300 bg-white p-3">{{ old('body') }}</textarea></label>
                @error('body')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                <div><label for="review-media" class="block text-sm font-medium">Фото или видео (до 5 файлов)</label>
                    <input id="review-media" name="media[]" type="file" multiple accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime" class="mt-2 block w-full text-sm" @change="files = Array.from($event.target.files); if (files.length > 5) { alert('Максимум 5 файлов'); $event.target.value = ''; files = []; }">
                    <p class="mt-1 text-xs text-slate-500">Фото до 10 МБ, видео до 50 МБ. Не более 5 файлов суммарно.</p>
                    <template x-if="files.length"><p class="mt-2 text-xs text-slate-600" x-text="files.map(f => f.name).join(', ')"></p></template>
                    @error('media')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    @error('media.*')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" :disabled="rating < 1" class="rounded-xl bg-[#071d5d] px-5 py-3 text-sm font-semibold text-white disabled:opacity-40">Отправить на модерацию</button>
            </form>
        </div>
    @else
        <p class="mt-5 text-sm"><a href="{{ route('login') }}" class="text-[#071d5d] underline">Войдите</a>, чтобы оставить отзыв.</p>
    @endauth
</div>
