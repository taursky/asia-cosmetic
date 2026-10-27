<section id="reviews" class="space-y-6" aria-label="Отзывы покупателей">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <span class="text-3xl font-semibold text-slate-950">{{ number_format((float) $product->rating, 1, ',', ' ') }}</span>
            <div><div class="text-lg text-amber-500" aria-label="Средняя оценка">★★★★★</div><p class="text-xs text-slate-500">{{ $product->reviews_count }} отзывов</p></div>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('reviews.product', $product->lang?->slug) }}" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-[#071d5d] hover:border-[#071d5d]">Все отзывы</a>
            @auth('web')
                <button type="button" wire:click="$toggle('showForm')" class="rounded-lg bg-[#071d5d] px-4 py-2 text-sm font-semibold text-white">{{ $showForm ? 'Закрыть форму' : 'Оставить отзыв' }}</button>
            @else
                <a href="{{ route('login') }}" class="rounded-lg bg-[#071d5d] px-4 py-2 text-sm font-semibold text-white">Войти, чтобы оставить отзыв</a>
            @endauth
        </div>
    </div>

    @if($submitted)<p role="status" class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800">Отзыв отправлен на модерацию.</p>@endif

    @auth('web')
    @if($showForm || $errors->any())
        <form wire:submit="submit" class="space-y-5 rounded-2xl border border-slate-200 bg-slate-50 p-5" enctype="multipart/form-data">
            <div>
                <div class="mb-2 text-sm font-medium text-slate-700">Ваша оценка</div>
                <div class="flex gap-1" role="radiogroup" aria-label="Оценка от 1 до 5 звёзд">
                    @foreach(range(1, 5) as $star)
                        <button type="button" wire:click="$set('rating', {{ $star }})" role="radio" aria-checked="{{ $rating === $star ? 'true' : 'false' }}" aria-label="{{ $star }} из 5" class="text-3xl leading-none transition hover:scale-110 {{ $rating >= $star ? 'text-amber-400' : 'text-slate-300' }}">★</button>
                    @endforeach
                </div>
                @error('rating')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <label class="block text-sm font-medium text-slate-700">Ваш отзыв
                <textarea wire:model="body" rows="5" maxlength="5000" required class="mt-2 w-full rounded-xl border border-slate-300 bg-white p-3 text-sm focus:border-[#071d5d] focus:ring-[#071d5d]" placeholder="Расскажите о впечатлениях от товара"></textarea>
            </label>
            @error('body')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
            <div>
                <label for="review-media-{{ $productId }}" class="mb-2 block text-sm font-medium text-slate-700">Фото или видео (не более 5 файлов)</label>
                <input id="review-media-{{ $productId }}" type="file" wire:model="media" multiple accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime" class="block w-full rounded-xl border border-slate-300 bg-white p-3 text-sm file:mr-4 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2" />
                <p class="mt-1 text-xs text-slate-500">Фото до 10 МБ, видео до 50 МБ. До 5 вложений суммарно.</p>
                <p wire:loading wire:target="media" class="mt-2 text-sm text-slate-600">Загрузка файлов...</p>
                @error('media')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
                @error('media.*')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
                @if(count($media))
                    <div class="mt-3 flex flex-wrap gap-3">
                        @foreach($media as $index => $file)
                            <div wire:key="upload-{{ $index }}" class="relative w-28 rounded-lg border border-slate-200 bg-white p-2">
                                @if(str_starts_with((string) $file->getMimeType(), 'image/'))
                                    <img src="{{ $file->temporaryUrl() }}" alt="Предпросмотр" class="h-20 w-full rounded object-cover">
                                @else
                                    <div class="flex h-20 items-center justify-center text-center text-xs text-slate-600">Видео</div>
                                @endif
                                <p class="mt-1 truncate text-xs" title="{{ $file->getClientOriginalName() }}">{{ $file->getClientOriginalName() }}</p>
                                <button type="button" wire:click="removeMedia({{ $index }})" aria-label="Убрать файл" class="absolute -right-2 -top-2 rounded-full bg-slate-900 px-2 py-1 text-xs text-white">×</button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            <button type="submit" wire:loading.attr="disabled" wire:target="submit,media" class="rounded-xl bg-[#071d5d] px-6 py-3 text-sm font-semibold text-white disabled:opacity-50">Отправить на модерацию</button>
        </form>
    @endif
    @endauth

    <div class="divide-y divide-slate-200">
        @forelse($reviews as $review)
            <article wire:key="review-{{ $review->id }}" class="py-5">
                <div class="flex flex-wrap items-center justify-between gap-2"><strong class="text-slate-900">{{ $review->user?->name ?: 'Покупатель' }}</strong><span class="text-amber-500">{{ str_repeat('★', $review->rating) }}<span class="text-slate-300">{{ str_repeat('☆', 5 - $review->rating) }}</span></span></div>
                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $review->body }}</p>
                @if($review->media->isNotEmpty())
                    <div class="mt-4 flex flex-wrap gap-3">
                        @foreach($review->media as $attachment)
                            @if($attachment->type === 'image')
                                <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($attachment->path) }}" target="_blank" rel="noopener noreferrer"><img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($attachment->path) }}" alt="Фото покупателя" loading="lazy" class="h-24 w-24 rounded-lg border border-slate-200 object-cover"></a>
                            @else
                                <video controls preload="none" class="h-32 max-w-56 rounded-lg" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($attachment->path) }}"></video>
                            @endif
                        @endforeach
                    </div>
                @endif
                <time class="mt-3 block text-xs text-slate-400">{{ $review->published_at?->format('d.m.Y') }}</time>
            </article>
        @empty
            <p class="py-5 text-sm text-slate-500">Пока нет опубликованных отзывов.</p>
        @endforelse
    </div>
</section>
