<?php

namespace App\Filament\Resources\ProductPrices\Pages;

use App\Filament\Resources\ProductPrices\ProductPriceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductPrice extends CreateRecord
{
    protected static string $resource = ProductPriceResource::class;
}
