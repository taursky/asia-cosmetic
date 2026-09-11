<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('categoryTabs')->columnSpanFull()->tabs([
                Tab::make('Основное')->schema([
                    Section::make()->schema([
                        Select::make('parent_id')->relationship('parent', 'id')->label('Родительская категория')->searchable()->preload()->placeholder('Корневая категория'),
                        TextInput::make('external_id')->label('Внешний ID')->maxLength(255),
                        TextInput::make('one_c_id')->label('ID 1С')->maxLength(36),
                        TextInput::make('sort_order')->label('Сортировка')->numeric()->default(0)->required(),
                        Toggle::make('is_active')->label('Активна')->default(true),
                    ])->columns(2),
                ]),
                Tab::make('Переводы')->schema([
                    Repeater::make('langs')->relationship('langs')->label('Языковые версии')->defaultItems(1)->addActionLabel('Добавить язык')->collapsible()->schema([
                        Select::make('lang')->label('Язык')->options(['ru'=>'Русский','en'=>'English','zh'=>'中文'])->required(),
                        TextInput::make('name')->label('Название')->required()->maxLength(255),
                        TextInput::make('slug')->label('Slug')->required()->maxLength(255),
                        Textarea::make('description')->label('Описание')->rows(5)->columnSpanFull(),
                        TextInput::make('seo_title')->label('SEO title')->maxLength(255),
                        Textarea::make('seo_description')->label('SEO description')->rows(3),
                    ])->columns(2),
                ]),
            ]),
        ]);
    }
}
