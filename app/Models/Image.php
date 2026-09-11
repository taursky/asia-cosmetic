<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Image extends Model
{
    public $guarded = [];

    protected ?string $originalFilePath = null;

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function booted(): void
    {
        static::updating(function (Image $image): void {
            if ($image->isDirty('name')) {
                $image->originalFilePath = $image->getOriginal('name');
            }
        });

        static::updated(function (Image $image): void {
            if ($image->originalFilePath) {
                $image->deletePhysicalFile($image->originalFilePath);
                $image->originalFilePath = null;
            }
        });

        static::deleted(function (Image $image): void {
            $image->deletePhysicalFile($image->name);
        });
    }

    public function deletePhysicalFile(?string $path): void
    {
        $path = trim((string) $path);

        if ($path === '') {
            return;
        }

        // Старые записи могли содержать внешний URL — такие пути не удаляем.
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
