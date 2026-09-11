<?php

namespace App\Filament\Resources\ProductPrices\Pages;

use App\Filament\Resources\ProductPrices\ProductPriceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProductPrice extends EditRecord
{
    protected static string $resource = ProductPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
