<?php

namespace App\Filament\Support;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class ImageUploadFields
{
    public static function schema(string $directory): array
    {
        return [
            FileUpload::make('name')
                ->label('Файл')
                ->image()
                ->disk('public')
                ->directory($directory)
                ->visibility('public')
                ->imageEditor()
                ->required()
                ->columnSpan(2),

            Toggle::make('is_primary')
                ->label('Основное'),

            TextInput::make('position')
                ->label('Позиция')
                ->numeric()
                ->default(1)
                ->required(),

            TextInput::make('mime_type')
                ->label('MIME')
                ->maxLength(255),
        ];
    }
}
