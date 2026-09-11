<?php

namespace App\Filament\Resources\ProductPriceTypes\Pages;

use App\Filament\Resources\ProductPriceTypes\ProductPriceTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProductPriceType extends EditRecord
{
    protected static string $resource = ProductPriceTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
