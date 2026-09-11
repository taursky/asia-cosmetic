<?php

namespace App\Filament\Resources\AttributeValues\Pages;

use App\Filament\Resources\AttributeValues\AttributeValueResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAttributeValue extends ViewRecord
{
    protected static string $resource = AttributeValueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
