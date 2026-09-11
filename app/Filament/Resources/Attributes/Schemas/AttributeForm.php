<?php

namespace App\Filament\Resources\Attributes\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class AttributeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('attributeTabs')->columnSpanFull()->tabs([
                Tab::make('Основное')->schema([
                    Section::make()->schema([
                        TextInput::make('code')->label('Код')->required()->maxLength(255)->unique(ignoreRecord: true),
                        Select::make('type')->label('Тип')->options([
                            'text' => 'Текст',
                            'select' => 'Список',
                            'multiselect' => 'Множественный список',
                            'number' => 'Число',
                            'boolean' => 'Да / Нет',
                        ])->required()->default('text'),
                        TextInput::make('external_id')->label('Внешний ID')->maxLength(255),
                        TextInput::make('one_c_id')->label('ID 1С')->maxLength(36),
                        TextInput::make('sort_order')->label('Сортировка')->numeric()->default(0)->required(),
                        Toggle::make('is_filterable')->label('Использовать в фильтрах'),
                        Toggle::make('is_variant')->label('Атрибут варианта / SKU'),
                        Toggle::make('is_searchable')->label('Участвует в поиске'),
                    ])->columns(3),
                ]),
                Tab::make('Переводы')->schema([
                    Repeater::make('langs')->relationship('langs')->label('Языковые версии')->defaultItems(1)
                        ->addActionLabel('Добавить язык')->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? $state['lang'] ?? null)
                        ->schema([
                            Select::make('lang')->label('Язык')->options(self::locales())->required(),
                            TextInput::make('name')->label('Название')->required()->maxLength(255),
                            TextInput::make('unit')->label('Единица')->maxLength(255),
                            Textarea::make('description')->label('Описание')->rows(3)->columnSpanFull(),
                        ])->columns(2),
                ]),
            ]),
        ]);
    }

    private static function locales(): array { return ['ru' => 'Русский', 'en' => 'English', 'zh' => '中文']; }
}
