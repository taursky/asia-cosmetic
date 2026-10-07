<?php

namespace App\Services\OneC;

use App\Models\Image;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ImageSyncService
{
    private const MAX_BYTES = 20 * 1024 * 1024;

    public function sync(array $data): array
    {
        $source = trim((string) ($data['source'] ?? config('onec.source', '1c-unf'))) ?: '1c-unf';
        $model = $this->resolveModel($data['entity_type'], $data['entity_ref'], $source);

        $binary = base64_decode($data['content_base64'], true);
        if ($binary === false || $binary === '') {
            throw ValidationException::withMessages([
                'content_base64' => 'Изображение 1С содержит некорректные base64-данные.',
            ]);
        }

        if (strlen($binary) > self::MAX_BYTES) {
            throw ValidationException::withMessages([
                'content_base64' => 'Изображение больше 20 МБ и не может быть сохранено.',
            ]);
        }

        $mime = $this->detectMime($binary);
        $extension = $this->extensionForMime($mime);
        if ($extension === null) {
            throw ValidationException::withMessages([
                'mime_type' => "Неподдерживаемый тип изображения: {$mime}.",
            ]);
        }

        $directory = $data['entity_type'] === 'variant'
            ? 'catalog/products/' . $model->product_id . '/variants/' . $model->getKey() . '/1c'
            : 'catalog/products/' . $model->getKey() . '/1c';

        $basePath = $directory . '/' . sha1((string) $data['image_ref']);
        $path = $basePath . '.' . $extension;
        $disk = Storage::disk('public');
        $morphType = $model->getMorphClass();

        return DB::transaction(function () use ($data, $model, $binary, $mime, $path, $basePath, $disk, $morphType): array {
            $existing = Image::query()
                ->where('imageable_type', $morphType)
                ->where('imageable_id', $model->getKey())
                ->where('name', 'like', $basePath . '.%')
                ->first();

            if ($existing && $existing->name !== $path && $disk->exists($existing->name)) {
                $disk->delete($existing->name);
            }

            $disk->put($path, $binary, ['visibility' => 'public']);

            if ((bool) ($data['is_primary'] ?? false)) {
                Image::query()
                    ->where('imageable_type', $morphType)
                    ->where('imageable_id', $model->getKey())
                    ->when($existing, fn ($q) => $q->whereKeyNot($existing->getKey()))
                    ->update(['is_primary' => false]);
            }

            $image = $existing ?? new Image();
            $image->fill([
                'imageable_type' => $morphType,
                'imageable_id' => $model->getKey(),
                'position' => (int) ($data['position'] ?? 1),
                'name' => $path,
                'mime_type' => $mime,
                'is_primary' => (bool) ($data['is_primary'] ?? false),
            ])->save();

            return [
                'id' => $image->id,
                'entity_type' => $data['entity_type'],
                'entity_ref' => $data['entity_ref'],
                'image_ref' => $data['image_ref'],
                'name' => $image->name,
                'url' => $disk->url($image->name),
                'mime_type' => $image->mime_type,
                'position' => $image->position,
                'is_primary' => $image->is_primary,
            ];
        });
    }

    private function resolveModel(string $type, string $ref, string $source): Model
    {
        $model = match ($type) {
            'product' => Product::query()->where('source', $source)->where('one_c_id', $ref)->first(),
            'variant' => ProductVariant::query()->where('source', $source)->where('one_c_id', $ref)->first(),
            default => null,
        };

        if (! $model) {
            throw ValidationException::withMessages([
                'entity_ref' => "Объект {$type} {$source}:{$ref} не найден. Сначала синхронизируйте товары.",
            ]);
        }

        return $model;
    }

    private function detectMime(string $binary): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        return (string) $finfo->buffer($binary);
    }

    private function extensionForMime(string $mime): ?string
    {
        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'image/avif' => 'avif',
            default => null,
        };
    }
}
