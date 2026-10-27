<?php

namespace App\Filament\Resources\ProductReviews;

use App\Filament\Resources\ProductReviews\Pages\EditProductReview;
use App\Filament\Resources\ProductReviews\Pages\ListProductReviews;
use App\Models\ProductReview;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProductReviewResource extends Resource
{
    protected static ?string $model = ProductReview::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;
    protected static string|\UnitEnum|null $navigationGroup = 'Продукты';
    protected static ?string $navigationLabel = 'Отзывы';
    protected static ?string $modelLabel = 'Отзыв';
    protected static ?string $pluralModelLabel = 'Отзывы';
    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('rating')->label('Оценка')->options([1=>'1',2=>'2',3=>'3',4=>'4',5=>'5'])->required(),
            TextInput::make('title')->label('Заголовок')->maxLength(150),
            Textarea::make('body')->label('Текст')->required()->rows(6),
            Select::make('status')->label('Статус')->options([
                'pending'=>'На модерации', 'published'=>'Опубликован', 'rejected'=>'Отклонён',
            ])->required()->live()->afterStateUpdated(function ($state, $set): void {
                $set('published_at', $state === 'published' ? now()->format('Y-m-d H:i:s') : null);
            }),
            DateTimePicker::make('published_at')->label('Дата публикации'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')->columns([
            TextColumn::make('id')->label('ID')->sortable(),
            TextColumn::make('product.lang.name')->label('Товар')->limit(35),
            TextColumn::make('user.name')->label('Автор'),
            TextColumn::make('rating')->label('Оценка')->sortable(),
            TextColumn::make('body')->label('Отзыв')->limit(65),
            TextColumn::make('status')->label('Статус')->badge()->color(fn (string $state) => match ($state) {
                'published'=>'success', 'rejected'=>'danger', default=>'warning',
            }),
            TextColumn::make('created_at')->label('Создан')->dateTime('d.m.Y H:i')->sortable(),
        ])->filters([
            SelectFilter::make('status')->options(['pending'=>'На модерации','published'=>'Опубликован','rejected'=>'Отклонён']),
        ])->recordActions([
            Action::make('publish')->label('Опубликовать')->icon('heroicon-o-check')->color('success')
                ->visible(fn (ProductReview $record) => $record->status !== 'published')
                ->action(fn (ProductReview $record) => $record->update(['status'=>'published','published_at'=>now()])),
            Action::make('reject')->label('Отклонить')->color('warning')
                ->visible(fn (ProductReview $record) => $record->status !== 'rejected')
                ->action(fn (ProductReview $record) => $record->update(['status'=>'rejected','published_at'=>null])),
            EditAction::make()->iconButton(),
            DeleteAction::make()->iconButton(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListProductReviews::route('/'), 'edit' => EditProductReview::route('/{record}/edit')];
    }
}
