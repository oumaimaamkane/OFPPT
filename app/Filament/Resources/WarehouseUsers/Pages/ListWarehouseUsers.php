<?php

namespace App\Filament\Resources\WarehouseUsers\Pages;

use App\Filament\Resources\WarehouseUsers\WarehouseUserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWarehouseUsers extends ListRecords
{
    protected static string $resource = WarehouseUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
