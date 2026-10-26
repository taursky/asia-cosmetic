<?php

namespace App\Filament\Resources\Banners\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BannerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Изображения баннера')
                ->description('Загрузите отдельные изображения для широкого и узкого экрана. Рекомендуется WebP или JPG.')
                ->schema([
                    FileUpload::make('desktop_image')
                        ->label('Для компьютера')
                        ->image()
                        ->disk('public')
                        ->directory('banners/desktop')
                        ->visibility('public')
                        ->imageEditor()
                        ->maxSize(5120)
                        ->required()
                        ->columnSpanFull(),
                    FileUpload::make('mobile_image')
                        ->label('Для телефона')
                        ->image()
                        ->disk('public')
                        ->directory('banners/mobile')
                        ->visibility('public')
                        ->imageEditor()
                        ->maxSize(5120)
                        ->helperText('Если не заполнено, на телефоне будет показано изображение для компьютера.')
                        ->columnSpanFull(),
                ]),
            Section::make('Параметры')
                ->schema([
                    TextInput::make('link')
                        ->label('Ссылка при нажатии')
                        ->placeholder('/catalog или https://example.com')
                        ->maxLength(255)
                        ->helperText('Можно указать относительный путь внутри сайта или полный адрес.'),
                    TextInput::make('position')
                        ->label('Порядок')
                        ->integer()
                        ->minValue(0)
                        ->default(0)
                        ->required(),
                    Toggle::make('is_active')
                        ->label('Показывать на главной')
                        ->default(true)
                        ->inline(false),
                ])->columns(2),
        ]);
    }
}
