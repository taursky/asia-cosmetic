<?php

namespace App\Filament\Resources\ProductPriceTypes\Pages;

use App\Filament\Resources\ProductPriceTypes\ProductPriceTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProductPriceTypes extends ListRecords
{
    protected static string $resource = ProductPriceTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
