<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

trait DeletesImages
{
    protected static function bootDeletesImages(): void
    {
        static::deleting(function (Model $model): void {
            if (! method_exists($model, 'images')) {
                return;
            }

            $model->images()
                ->get()
                ->each
                ->delete();
        });
    }
}
