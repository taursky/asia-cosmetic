<?php

namespace App\Filament\Resources\Images;

use App\Filament\Resources\Images\Pages\CreateImage;
use App\Filament\Resources\Images\Pages\EditImage;
use App\Filament\Resources\Images\Pages\ListImages;
use App\Filament\Resources\Images\Schemas\ImageForm;
use App\Filament\Resources\Images\Tables\ImagesTable;
use App\Models\Image;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ImageResource extends Resource
{
    protected static ?string $model = Image::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;
    protected static string|\UnitEnum|null $navigationGroup = 'Продукты';
    protected static ?string $navigationLabel = 'Изображения';
    protected static ?string $modelLabel = 'Изображение';
    protected static ?string $pluralModelLabel = 'Изображения';
    protected static ?int $navigationSort = 70;

    public static function form(Schema $schema): Schema { return ImageForm::configure($schema); }
    public static function table(Table $table): Table { return ImagesTable::configure($table); }

    public static function getPages(): array
    {
        return [
            'index' => ListImages::route('/'),
            'create' => CreateImage::route('/create'),
            'edit' => EditImage::route('/{record}/edit'),
        ];
    }
}
