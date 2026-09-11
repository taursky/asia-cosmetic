<?php

namespace App\Filament\Resources\ProductOptions\Pages;

use App\Filament\Resources\ProductOptions\ProductOptionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProductOptions extends ListRecords
{
    protected static string $resource = ProductOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Создать опцию'),
        ];
    }
}
