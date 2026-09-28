<?php

namespace App\Filament\Resources\Customers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable()->searchable(),
                TextColumn::make('name')->label('Имя')->searchable()->sortable(),
                TextColumn::make('email')->label('Email')->searchable()->toggleable(),
                TextColumn::make('phone')->label('Телефон')->searchable()->toggleable(),

                TextColumn::make('customerRole.name')
                    ->label('Уровень')
                    ->badge()
                    ->placeholder('Не назначен')
                    ->sortable(),
                TextColumn::make('customerRole.priceType.name')
                    ->label('Тип цены')
                    ->badge()
                    ->placeholder('Не назначен'),
                IconColumn::make('customer_role_locked')
                    ->label('Роль закреплена')
                    ->boolean(),
                TextColumn::make('customer_role_valid_until')
                    ->label('Роль до')
                    ->dateTime('d.m.Y')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('customerProfile.company_name')
                    ->label('Компания')
                    ->placeholder('Физ. лицо')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('customerProfile.inn')
                    ->label('ИНН')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('customerProfile.verification_status')
                    ->label('Реквизиты')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'draft' => 'Черновик',
                        'pending' => 'На проверке',
                        'verified' => 'Подтверждены',
                        'rejected' => 'Исправить',
                        default => 'Не заполнены',
                    }),
                TextColumn::make('customer_documents_count')
                    ->counts('customerDocuments')
                    ->label('Документы')
                    ->badge(),

                IconColumn::make('is_active')->label('Активен')->boolean(),
                TextColumn::make('last_login_at')->label('Последний вход')->dateTime('d.m.Y H:i')->sortable()->toggleable(),
                TextColumn::make('created_at')->label('Создан')->dateTime('d.m.Y H:i')->sortable()->toggleable(),
            ])
            ->filters([
                Filter::make('id')
                    ->schema([TextInput::make('id')->label('ID')->numeric()])
                    ->query(fn (Builder $q, array $data) => $q->when(
                        filled($data['id'] ?? null),
                        fn (Builder $q) => $q->whereKey((int) $data['id']),
                    )),
                TernaryFilter::make('is_active')->label('Активность'),
                TernaryFilter::make('customer_role_locked')->label('Роль закреплена'),
                SelectFilter::make('customer_role_id')
                    ->label('Уровень покупателя')
                    ->relationship('customerRole', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('profile_status')
                    ->label('Статус реквизитов')
                    ->options([
                        'draft' => 'Черновик',
                        'pending' => 'На проверке',
                        'verified' => 'Подтверждены',
                        'rejected' => 'Требуют исправления',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $query): Builder => $query->whereHas(
                            'customerProfile',
                            fn (Builder $profile): Builder => $profile->where('verification_status', $data['value']),
                        ),
                    )),
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
