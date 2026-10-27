<?php

namespace App\Filament\Resources\CatalogCollections;

use App\Filament\Resources\CatalogCollections\Pages\CreateCatalogCollection;
use App\Filament\Resources\CatalogCollections\Pages\EditCatalogCollection;
use App\Filament\Resources\CatalogCollections\Pages\ListCatalogCollections;
use App\Filament\Resources\CatalogCollections\Schemas\CatalogCollectionForm;
use App\Filament\Resources\CatalogCollections\Tables\CatalogCollectionsTable;
use App\Models\CatalogCollection;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CatalogCollectionResource extends Resource
{
    protected static ?string $model = CatalogCollection::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|\UnitEnum|null $navigationGroup = 'КОНТЕНТ';
    protected static ?string $navigationLabel = 'Подборки товаров';
    protected static ?string $modelLabel = 'Подборка';
    protected static ?string $pluralModelLabel = 'Подборки';
    protected static ?int $navigationSort = 100;

    public static function form(Schema $schema): Schema
    {
        return CatalogCollectionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CatalogCollectionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCatalogCollections::route('/'),
            'create' => CreateCatalogCollection::route('/create'),
            'edit' => EditCatalogCollection::route('/{record}/edit'),
        ];
    }
}
