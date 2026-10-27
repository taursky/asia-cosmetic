<?php

namespace App\Filament\Resources\CatalogCollections\Pages;

use App\Filament\Resources\CatalogCollections\CatalogCollectionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCatalogCollection extends EditRecord
{
    protected static string $resource = CatalogCollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
        ];
    }
}
