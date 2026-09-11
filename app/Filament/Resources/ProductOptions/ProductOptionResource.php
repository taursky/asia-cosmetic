<?php

namespace App\Filament\Resources\ProductOptions;

use App\Filament\Resources\ProductOptions\Pages\CreateProductOption;
use App\Filament\Resources\ProductOptions\Pages\EditProductOption;
use App\Filament\Resources\ProductOptions\Pages\ListProductOptions;
use App\Filament\Resources\ProductOptions\Pages\ViewProductOption;
use App\Filament\Resources\ProductOptions\Schemas\ProductOptionForm;
use App\Filament\Resources\ProductOptions\Schemas\ProductOptionInfolist;
use App\Filament\Resources\ProductOptions\Tables\ProductOptionsTable;
use App\Models\Option;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProductOptionResource extends Resource
{
    protected static ?string $model = Option::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;
    protected static string|\UnitEnum|null $navigationGroup = 'Продукты';
    protected static ?string $navigationLabel = 'Опции';
    protected static ?string $modelLabel = 'Опция';
    protected static ?string $pluralModelLabel = 'Опции';
    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return ProductOptionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProductOptionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductOptionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductOptions::route('/'),
            'create' => CreateProductOption::route('/create'),
            'view' => ViewProductOption::route('/{record}'),
            'edit' => EditProductOption::route('/{record}/edit'),
        ];
    }
}
