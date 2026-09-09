<?php

namespace App\Filament\Resources\Roles\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->label('ID')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Название')
                    ->weight('semibold')
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->iconColor(fn ($record) => $record->is_active ? 'success' : 'danger'),

                TextColumn::make('slug')
                    ->searchable()
                    ->sortable()
                    ->label('Slug')
                    ->copyable()
                    ->copyMessage('Slug скопирован')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('description')
                    ->limit(50)
                    ->toggleable()
                    ->label('Описание')
                    ->searchable(),

                TextColumn::make('admin_users_count')
                    ->counts('adminUsers')
                    ->label('Пользователей')
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Активна')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Создана'),

                TextColumn::make('updated_at')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Обновлена'),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Активность')
                    ->placeholder('Все')
                    ->trueLabel('Только активные')
                    ->falseLabel('Только неактивные'),

                Filter::make('has_users')
                    ->label('С пользователями')
                    ->query(fn ($query) => $query->has('adminUsers'))
                    ->toggle(),

                Filter::make('no_users')
                    ->label('Без пользователей')
                    ->query(fn ($query) => $query->doesntHave('adminUsers'))
                    ->toggle(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Просмотр'),

                    EditAction::make()
                        ->label('Редактировать'),

                    DeleteAction::make()
                        ->label('Удалить')
                        ->modalDescription('Удаление роли отвяжет её от всех пользователей. Вы уверены?'),

                    Action::make('toggleActive')
                        ->label(fn ($record) => $record->is_active ? 'Деактивировать' : 'Активировать')
                        ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                        ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                        ->action(function ($record) {
                            $record->update(['is_active' => !$record->is_active]);
                        })
                        ->requiresConfirmation()
                        ->modalHeading(fn ($record) => $record->is_active ? 'Деактивировать роль' : 'Активировать роль')
                        ->modalDescription(fn ($record) => $record->is_active
                            ? 'Роль будет деактивирована. Пользователи потеряют эту роль.'
                            : 'Роль будет активирована. Пользователи смогут её использовать.'
                        ),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Удалить выбранные'),

                    BulkAction::make('activateBulk')
                        ->label('Активировать')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->update(['is_active' => true]);
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('deactivateBulk')
                        ->label('Деактивировать')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each->update(['is_active' => false]);
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('name', 'asc');
    }
}
