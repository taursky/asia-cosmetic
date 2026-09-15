<?php

namespace App\Filament\Resources\CustomerRoles\Pages;

use App\Filament\Resources\CustomerRoles\CustomerRoleResource;
use Filament\Resources\Pages\EditRecord;

class EditCustomerRole extends EditRecord
{
    protected static string $resource = CustomerRoleResource::class;

    protected function getHeaderActions(): array
    {
        return [\Filament\Actions\DeleteAction::make()];
    }
}
