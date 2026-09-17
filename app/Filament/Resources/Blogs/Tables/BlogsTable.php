<?php

namespace App\Filament\Resources\Blogs\Tables;

use App\Models\BlogLang;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BlogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable()
                    ->copyable(),

                TextColumn::make('lang.title')
                    ->label('Название')
                    ->placeholder('Без перевода')
                    ->searchable(
                        query: fn (Builder $query, string $search): Builder =>
                        $query->whereHas('langs', fn (Builder $langQuery): Builder =>
                        $langQuery->where('title', 'like', "%{$search}%"))
                    )
                    ->sortable(
                        query: fn (Builder $query, string $direction): Builder =>
                        $query->orderBy(
                            BlogLang::query()
                                ->select('title')
                                ->whereColumn('blog_lang.blog_id', 'blogs.id')
                                ->where('lang', app()->getLocale())
                                ->limit(1),
                            $direction,
                        )
                    ),

                IconColumn::make('active')
                    ->label('Опубликована')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('views_count')
                    ->label('Просмотры')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->label('Обновлена')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('id')
                    ->label('ID')
                    ->schema([
                        TextInput::make('id')
                            ->label('ID статьи')
                            ->numeric()
                            ->placeholder('Например, 25'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder =>
                    $query->when(
                        filled($data['id'] ?? null),
                        fn (Builder $query): Builder => $query->whereKey((int) $data['id'])
                    ))
                    ->indicateUsing(fn (array $data): ?string =>
                    filled($data['id'] ?? null) ? 'ID: ' . $data['id'] : null),

                Filter::make('title')
                    ->label('Название')
                    ->schema([
                        TextInput::make('title')
                            ->label('Название статьи')
                            ->placeholder('Введите часть названия'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder =>
                    $query->when(
                        filled($data['title'] ?? null),
                        fn (Builder $query): Builder => $query->whereHas(
                            'langs',
                            fn (Builder $langQuery): Builder => $langQuery
                                ->where('lang', app()->getLocale())
                                ->where('title', 'like', '%' . $data['title'] . '%')
                        )
                    ))
                    ->indicateUsing(fn (array $data): ?string =>
                    filled($data['title'] ?? null) ? 'Название: ' . $data['title'] : null),

                TernaryFilter::make('active')
                    ->label('Публикация')
                    ->placeholder('Все статьи')
                    ->trueLabel('Только опубликованные')
                    ->falseLabel('Только черновики'),
            ])
            ->recordActions([
                EditAction::make()->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }
}
