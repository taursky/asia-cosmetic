<?php

namespace App\Filament\Resources\Images\Schemas;

use App\Models\Option;
use App\Models\Product;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ImageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Изображение')->schema([
                Select::make('imageable_type')->label('Тип объекта')->options([
                    Product::class => 'Товар',
                    Option::class => 'Опция / SKU',
                ])->required()->live(),
                TextInput::make('imageable_id')->label('ID объекта')->numeric()->required(),
                FileUpload::make('name')->label('Файл')->image()->disk('public')->directory('catalog')->imageEditor()->required()->columnSpanFull(),
                TextInput::make('mime_type')->label('MIME')->maxLength(255),
                TextInput::make('position')->label('Позиция')->numeric()->default(1)->required(),
                Toggle::make('is_primary')->label('Основное изображение'),
            ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }
}
