<?php

namespace App\Livewire\Catalog;

use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProductReviews extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $productId;

    public int $rating = 5;
    public string $body = '';
    public array $media = [];
    public bool $showForm = false;
    public bool $submitted = false;

    public function mount(int $productId): void
    {
        $this->productId = $productId;
    }

    public function updatedMedia(): void
    {
        $this->validateOnly('media');
    }

    public function removeMedia(int $index): void
    {
        if (! array_key_exists($index, $this->media)) return;
        unset($this->media[$index]);
        $this->media = array_values($this->media);
        $this->resetValidation('media');
    }

    public function submit(): void
    {
        abort_unless(auth('web')->check(), 403);
        $product = Product::query()->active()->findOrFail($this->productId);

        $data = $this->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'body' => ['required', 'string', 'min:10', 'max:5000'],
            'media' => ['array', 'max:5'],
            'media.*' => ['required', File::types(['jpg', 'jpeg', 'png', 'webp', 'mp4', 'webm', 'mov'])->max(50 * 1024)],
        ], [
            'media.max' => 'Можно прикрепить не более 5 файлов.',
            'media.*.max' => 'Файл не должен превышать 50 МБ.',
        ]);

        foreach ($this->media as $index => $file) {
            if (str_starts_with((string) $file->getMimeType(), 'image/') && $file->getSize() > 10 * 1024 * 1024) {
                $this->addError("media.$index", 'Фотография не должна превышать 10 МБ.');
            }
        }
        if ($this->getErrorBag()->isNotEmpty()) return;

        $paths = [];
        try {
            // Store only after validation; rollback newly stored files on failure.
            foreach ($this->media as $file) {
                $paths[] = [
                    'path' => $file->store('product-reviews/' . $product->getKey(), 'public'),
                    'type' => str_starts_with((string) $file->getMimeType(), 'image/') ? 'image' : 'video',
                    'mime_type' => (string) $file->getMimeType(),
                ];
            }
            DB::transaction(function () use ($product, $data, $paths): void {
                $review = ProductReview::query()->firstOrNew([
                    'product_id' => $product->getKey(),
                    'user_id' => auth('web')->id(),
                ]);
                $review->fill(['rating' => $data['rating'], 'body' => $data['body']]);
                $review->title = null;
                $review->status = 'pending';
                $review->published_at = null;
                $review->save();
                // On resubmission replace attachments, but preserve old files until commit.
                $old = $review->media()->pluck('path')->all();
                $review->media()->delete();
                foreach ($paths as $position => $entry) {
                    $review->media()->create($entry + ['sort_order' => $position]);
                }
                DB::afterCommit(fn () => Storage::disk('public')->delete($old));
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete(array_column($paths, 'path'));
            report($exception);
            $this->addError('media', 'Не удалось сохранить отзыв. Попробуйте ещё раз.');
            return;
        }

        $this->reset('body', 'media', 'showForm');
        $this->rating = 5;
        $this->submitted = true;
    }

    public function render()
    {
        $product = Product::query()->with('lang')->findOrFail($this->productId);
        $reviews = $product->reviews()->published()->with(['user:id,name', 'media'])
            ->latest('published_at')->limit(5)->get();

        return view('livewire.catalog.product-reviews', compact('product', 'reviews'));
    }
}
