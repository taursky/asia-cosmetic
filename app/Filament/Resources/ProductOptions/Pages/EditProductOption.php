<?php

namespace App\Filament\Resources\ProductOptions\Pages;

use App\Filament\Resources\ProductOptions\ProductOptionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditProductOption extends EditRecord
{
    protected static string $resource = ProductOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
