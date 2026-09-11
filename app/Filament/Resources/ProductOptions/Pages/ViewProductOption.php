<?php

namespace App\Filament\Resources\ProductOptions\Pages;

use App\Filament\Resources\ProductOptions\ProductOptionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProductOption extends ViewRecord
{
    protected static string $resource = ProductOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
