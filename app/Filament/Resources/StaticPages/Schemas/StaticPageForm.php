<?php

namespace App\Filament\Resources\StaticPages\Schemas;

use AmidEsfahani\FilamentTinyEditor\TinyEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class StaticPageForm
{
    public const LANGUAGES = [
        'ru' => 'Русский',
        'en' => 'English',
//        'ja' => '日本語',
//        'zh' => '中文',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Страница')
                ->tabs([
                    Tabs\Tab::make('Основные')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->schema([
                            TextInput::make('title')
                                ->label('Название (внутреннее)')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('slug')
                                ->label('Slug (общий для всех языков)')
                                ->maxLength(255)
                                ->unique(table: 'static_pages', column: 'slug', ignoreRecord: true)
                                ->helperText('Если пустой, создаётся из названия при сохранении. Заполненный slug автоматически не меняется.'),
                            Toggle::make('is_active')
                                ->onColor('success')
                                ->offColor('danger')
                                ->label('Опубликована')
                                ->default(true),
                        ])
                        ->columns(2),
                    Tabs\Tab::make('Контент страницы')
                        ->icon('heroicon-o-language')
                        ->schema([
                            Repeater::make('langs')
                                ->label('Переводы страницы')
                                ->relationship('langs')
                                ->schema([
                                    Select::make('lang')
                                        ->label('Язык')
                                        ->options(self::LANGUAGES)
                                        ->default('ru')
                                        ->required()
                                        ->distinct()
                                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                        ->native(false),
                                    TextInput::make('title')
                                        ->label('Заголовок')
                                        ->required()
                                        ->maxLength(255),
//                                    RichEditor::make('content')
//                                        ->label('Содержимое')
//                                        ->columnSpanFull()
//                                        ->fileAttachmentsDisk('public')
//                                        ->fileAttachmentsDirectory(fn (?Model $record): string =>
//                                        ($record?->page_id ?? $record?->id)
//                                            ? 'page/' . ($record->page_id ?? $record->id)
//                                            : 'page/_pending')
//                                        ->fileAttachmentsVisibility('public')
//                                        ->helperText('Изображения должны сохраняться в page/{page_id}/. Добавляйте изображения после создания страницы.'),

                                    TinyEditor::make('content')
                                        ->label('Новость')
                                        ->profile('with_source') // или создайте свой профиль
                                        ->columnSpanFull(),
                                    TextInput::make('meta_title')
                                        ->label('SEO Title')
                                        ->maxLength(255)
                                        ->columnSpanFull(),
                                    Textarea::make('meta_description')
                                        ->label('SEO Description')
                                        ->rows(3)
                                        ->maxLength(255)
                                        ->columnSpanFull(),
                                    TextInput::make('meta_keywords')
                                        ->label('SEO Keywords')
                                        ->maxLength(255)
                                        ->columnSpanFull(),
                                ])
                                ->columns(2)
                                ->defaultItems(1)
                                ->minItems(1)
                                ->addActionLabel('Добавить перевод')
                                ->collapsible()
                                ->reorderable(false)
                                ->itemLabel(fn (array $state): string =>
                                    (self::LANGUAGES[$state['lang'] ?? ''] ?? 'Новый перевод')
                                    . (filled($state['title'] ?? null) ? ' — ' . $state['title'] : '')),
                        ]),
                ])
                ->columnSpanFull()
                ->persistTabInQueryString(),
        ]);
    }
}
