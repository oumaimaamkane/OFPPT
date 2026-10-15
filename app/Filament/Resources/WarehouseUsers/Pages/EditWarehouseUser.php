<?php

namespace App\Filament\Resources\WarehouseUsers\Pages;

use App\Filament\Resources\WarehouseUsers\WarehouseUserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWarehouseUser extends EditRecord
{
    protected static string $resource = WarehouseUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
