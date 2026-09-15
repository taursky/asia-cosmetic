<?php

namespace App\Filament\Resources\CustomerRoles\Pages;

use App\Filament\Resources\CustomerRoles\CustomerRoleResource;
use Filament\Resources\Pages\ListRecords;

class ListCustomerRoles extends ListRecords
{
    protected static string $resource = CustomerRoleResource::class;

    protected function getHeaderActions(): array
    {
        return [\Filament\Actions\CreateAction::make()->label('Создать роль')];
    }
}
