<?php

namespace App\Filament\Resources\AttributeValues\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class AttributeValueForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('valueTabs')->columnSpanFull()->tabs([
                Tab::make('Основное')->schema([
                    Section::make()->schema([
                        Select::make('attribute_id')->relationship('attribute', 'code')->label('Атрибут')->searchable()->preload()->required(),
                        TextInput::make('code')->label('Код')->maxLength(255),
                        TextInput::make('numeric_value')->label('Числовое значение')->numeric()->step(0.000001),
                        Toggle::make('boolean_value')->label('Логическое значение')->nullable(),
                        TextInput::make('external_id')->label('Внешний ID')->maxLength(255),
                        TextInput::make('one_c_id')->label('ID 1С')->maxLength(36),
                        TextInput::make('sort_order')->label('Сортировка')->numeric()->default(0)->required(),
                    ])->columns(3),
                ]),
                Tab::make('Переводы')->schema([
                    Repeater::make('langs')->relationship('langs')->label('Значения по языкам')->defaultItems(1)
                        ->addActionLabel('Добавить язык')->collapsible()
                        ->schema([
                            Select::make('lang')->label('Язык')->options(['ru'=>'Русский','en'=>'English','zh'=>'中文'])->required(),
                            TextInput::make('value')->label('Значение')->required()->columnSpanFull(),
                        ])->columns(2),
                ]),
            ]),
        ]);
    }
}
