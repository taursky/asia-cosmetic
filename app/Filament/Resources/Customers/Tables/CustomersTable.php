<?php

namespace App\Filament\Resources\Customers\Tables;

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

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label('ID')->sortable()->searchable(),
            TextColumn::make('name')->label('Имя')->searchable()->sortable(),
            TextColumn::make('email')->label('Email')->searchable(),
            TextColumn::make('phone')->label('Телефон')->searchable(),
            TextColumn::make('customerRoles.name')->label('Роли')->badge()->separator(', '),
            IconColumn::make('is_active')->label('Активен')->boolean(),
            TextColumn::make('last_login_at')->label('Последний вход')->dateTime('d.m.Y H:i')->sortable()->toggleable(),
            TextColumn::make('created_at')->label('Создан')->dateTime('d.m.Y H:i')->sortable(),
        ])->filters([
            Filter::make('id')->schema([TextInput::make('id')->label('ID')->numeric()])->query(fn (Builder $q, array $data) => $q->when(filled($data['id'] ?? null), fn (Builder $q) => $q->whereKey((int)$data['id']))),
            TernaryFilter::make('is_active')->label('Активность'),
        ])->recordActions([EditAction::make()])
          ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])])
          ->defaultSort('id', 'desc');
    }
}
