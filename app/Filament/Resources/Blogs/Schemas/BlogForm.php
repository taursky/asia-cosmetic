<?php

namespace App\Filament\Resources\Blogs\Schemas;

use App\Models\Product;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class BlogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('blogTabs')
                ->columnSpanFull()
                ->persistTabInQueryString()
                ->tabs([
                    Tab::make('Основное')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            Section::make('Публикация')
                                ->schema([
                                    Toggle::make('active')
                                        ->label('Опубликована')
                                        ->default(false),

                                    TextInput::make('views_count')
                                        ->label('Просмотры')
                                        ->numeric()
                                        ->default(0)
                                        ->required()
                                        ->minValue(0),
                                ])
                                ->columns(2),

                            Section::make('Связанные товары')
                                ->description('Товары, которые будут показаны внутри или в конце статьи.')
                                ->schema([
                                    Select::make('product_ids')
                                        ->label('Товары')
                                        ->multiple()
                                        ->searchable()
                                        ->preload()
                                        ->options(fn (): array => Product::query()
                                            ->with('lang')
                                            ->where('is_active', true)
                                            ->orderByDesc('id')
                                            ->get()
                                            ->mapWithKeys(fn (Product $product): array => [
                                                $product->id => sprintf(
                                                    '#%d — %s%s',
                                                    $product->id,
                                                    $product->lang?->name ?? 'Без названия',
                                                    $product->sku ? " ({$product->sku})" : '',
                                                ),
                                            ])
                                            ->all())
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Tab::make('Переводы')
                        ->icon('heroicon-o-language')
                        ->schema([
                            Repeater::make('langs')
                                ->relationship('langs')
                                ->label('Языковые версии')
                                ->defaultItems(1)
                                ->addActionLabel('Добавить перевод')
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['title'] ?? $state['lang'] ?? null)
                                ->schema([
                                    Select::make('lang')
                                        ->label('Язык')
                                        ->options([
                                            'ru' => 'Русский',
                                            'en' => 'English',
//                                            'zh' => '中文',
                                        ])
                                        ->default('ru')
                                        ->required(),

                                    TextInput::make('title')
                                        ->label('Название')
                                        ->required()
                                        ->maxLength(255),

                                    TextInput::make('slug')
                                        ->label('Slug')
                                        ->required()
                                        ->maxLength(255)
                                        ->unique(table: 'blog_lang', column: 'slug', ignoreRecord: true),

                                    RichEditor::make('short_description')
                                        ->label('Краткое описание')
                                        ->columnSpanFull(),

                                    RichEditor::make('description')
                                        ->label('Текст статьи')
                                        ->columnSpanFull(),

                                    TextInput::make('meta_title')
                                        ->label('Meta title')
                                        ->maxLength(255)
                                        ->columnSpanFull(),

                                    Textarea::make('meta_description')
                                        ->label('Meta description')
                                        ->rows(3)
                                        ->columnSpanFull(),
                                ])
                                ->columns(2)
                                ->columnSpanFull(),
                        ]),

                    Tab::make('Изображения')
                        ->icon('heroicon-o-photo')
                        ->schema([
                            Repeater::make('images')
                                ->relationship('images')
                                ->label('Изображения статьи')
                                ->addActionLabel('Добавить изображение')
                                ->orderColumn('position')
                                ->reorderable()
                                ->collapsible()
                                ->schema([
                                    FileUpload::make('name')
                                        ->label('Изображение')
                                        ->image()
                                        ->disk('public')
                                        ->directory('blog')
                                        ->visibility('public')
                                        ->imageEditor()
                                        ->required()
                                        ->columnSpan(2),

                                    Toggle::make('is_primary')
                                        ->label('Главное'),

                                    TextInput::make('position')
                                        ->label('Позиция')
                                        ->numeric()
                                        ->default(1)
                                        ->required(),

                                    TextInput::make('mime_type')
                                        ->label('MIME')
                                        ->maxLength(255),
                                ])
                                ->columns(3)
                                ->columnSpanFull(),
                        ]),
                ]),
        ]);
    }
}
