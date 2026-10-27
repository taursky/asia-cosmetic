<?php

namespace App\Filament\Resources\CatalogCollections\Pages;

use App\Filament\Resources\CatalogCollections\CatalogCollectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCatalogCollections extends ListRecords
{
    protected static string $resource = CatalogCollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
