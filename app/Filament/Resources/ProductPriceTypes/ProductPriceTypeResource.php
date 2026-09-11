<?php

namespace App\Filament\Resources\ProductPriceTypes;

use App\Filament\Resources\ProductPriceTypes\Pages\CreateProductPriceType;
use App\Filament\Resources\ProductPriceTypes\Pages\EditProductPriceType;
use App\Filament\Resources\ProductPriceTypes\Pages\ListProductPriceTypes;
use App\Filament\Resources\ProductPriceTypes\Schemas\ProductPriceTypeForm;
use App\Filament\Resources\ProductPriceTypes\Tables\ProductPriceTypesTable;
use App\Models\ProductPriceType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProductPriceTypeResource extends Resource
{
    protected static ?string $model = ProductPriceType::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;
    protected static string|\UnitEnum|null $navigationGroup = 'Продукты';
    protected static ?string $navigationLabel = 'Типы цен';
    protected static ?string $modelLabel = 'Тип цены';
    protected static ?string $pluralModelLabel = 'Типы цен';
    protected static ?int $navigationSort = 60;

    public static function form(Schema $schema): Schema { return ProductPriceTypeForm::configure($schema); }
    public static function table(Table $table): Table { return ProductPriceTypesTable::configure($table); }

    public static function getPages(): array
    {
        return [
            'index' => ListProductPriceTypes::route('/'),
            'create' => CreateProductPriceType::route('/create'),
            'edit' => EditProductPriceType::route('/{record}/edit'),
        ];
    }
}
