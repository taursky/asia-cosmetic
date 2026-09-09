<?php

namespace App\Filament\Resources\AdminUsers\Tables;

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

class AdminUsersTable
{
    public static function configure(Table $table): Table
    {
//        return $table
//            ->columns([
//                TextColumn::make('name')
//                    ->searchable(),
//                TextColumn::make('email')
//                    ->label('Email address')
//                    ->searchable(),
//                TextColumn::make('phone')
//                    ->searchable(),
//                TextColumn::make('deleted_at')
//                    ->dateTime()
//                    ->sortable()
//                    ->toggleable(isToggledHiddenByDefault: true),
//                TextColumn::make('created_at')
//                    ->dateTime()
//                    ->sortable()
//                    ->toggleable(isToggledHiddenByDefault: true),
//                TextColumn::make('updated_at')
//                    ->dateTime()
//                    ->sortable()
//                    ->toggleable(isToggledHiddenByDefault: true),
//            ])
//            ->filters([
//                TrashedFilter::make(),
//            ])
//            ->recordActions([
//                ViewAction::make(),
//                EditAction::make(),
//            ])
//            ->toolbarActions([
//                BulkActionGroup::make([
//                    DeleteBulkAction::make(),
//                    ForceDeleteBulkAction::make(),
//                    RestoreBulkAction::make(),
//                ]),
//            ]);

        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->label('ID')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Имя')
                    ->weight('semibold')
                    ->description(fn ($record) => $record->email)
                    ->icon('heroicon-o-user'),

                TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->label('Email')
                    ->copyable()
                    ->copyMessage('Email скопирован')
                    ->icon('heroicon-o-envelope')
                    ->toggleable(),

                TextColumn::make('phone')
                    ->searchable()
                    ->label('Телефон')
                    ->icon('heroicon-o-phone')
                    ->toggleable(),

                TextColumn::make('roles.name')
                    ->label('Роли')
                    ->badge()
                    ->separator(', ')
                    ->searchable()
                    ->colors([
                        'danger' => 'Супер администратор',
                        'warning' => 'Администратор',
                        'info' => 'Менеджер',
                        'success' => 'Наблюдатель',
                        'gray' => 'Заблокированный',
                    ])
                    ->limitList(2)
                    ->expandableLimitedList()
                    ->toggleable(),

                TextColumn::make('roles_count')
                    ->counts('roles')
                    ->label('Кол-во ролей')
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->toggleable(),

                IconColumn::make('has_active_role')
                    ->label('Активный')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->label('Создан')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->label('Удален')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->badge()
                    ->color('danger'),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->label('Фильтр по ролям')
                    ->indicator('Роли'),

                TernaryFilter::make('has_roles')
                    ->label('Наличие ролей')
                    ->placeholder('Все')
                    ->trueLabel('С ролями')
                    ->falseLabel('Без ролей')
                    ->queries(
                        true: fn ($query) => $query->has('roles'),
                        false: fn ($query) => $query->doesntHave('roles'),
                    ),

                Filter::make('active_admin')
                    ->label('Только активные администраторы')
                    ->query(fn ($query) => $query->whereHas('roles', fn ($q) => $q->where('is_active', true)))
                    ->toggle(),

                TrashedFilter::make()
                    ->label('Удаленные')
                    ->visible(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Просмотр')
                        ->icon('heroicon-o-eye'),

                    EditAction::make()
                        ->label('Редактировать')
                        ->icon('heroicon-o-pencil-square'),

                    DeleteAction::make()
                        ->label('Удалить')
                        ->icon('heroicon-o-trash')
                        ->modalDescription('Удаление пользователя может повлиять на связанные данные. Вы уверены?'),

                    RestoreAction::make()
                        ->label('Восстановить')
                        ->icon('heroicon-o-arrow-path'),

                    ForceDeleteAction::make()
                        ->label('Удалить навсегда')
                        ->icon('heroicon-o-no-symbol')
                        ->modalDescription('Это действие необратимо. Вы уверены?'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Удалить выбранные'),

                    RestoreBulkAction::make()
                        ->label('Восстановить выбранные'),

                    ForceDeleteBulkAction::make()
                        ->label('Удалить навсегда'),

                    BulkAction::make('assignRoles')
                        ->label('Назначить роли')
                        ->icon('heroicon-o-user-plus')
                        ->schema([
                            Select::make('roles')
                                ->multiple()
                                ->relationship('roles', 'name')
                                ->preload()
                                ->required()
                                ->label('Выберите роли'),
                        ])
                        ->action(function ($records, array $data) {
                            foreach ($records as $record) {
                                $record->roles()->syncWithoutDetaching($data['roles']);
                            }
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
                        ->modalHeading('Назначение ролей'),

                    BulkAction::make('removeRoles')
                        ->label('Удалить роли')
                        ->icon('heroicon-o-user-minus')
                        ->color('danger')
                        ->schema([
                            Select::make('roles')
                                ->multiple()
                                ->relationship('roles', 'name')
                                ->preload()
                                ->required()
                                ->label('Выберите роли для удаления'),
                        ])
                        ->action(function ($records, array $data) {
                            foreach ($records as $record) {
                                $record->roles()->detach($data['roles']);
                            }
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
                        ->modalHeading('Удаление ролей'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
