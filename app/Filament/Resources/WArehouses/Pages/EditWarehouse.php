<?php

namespace App\Filament\Resources\WArehouses\Pages;

use App\Filament\Resources\WArehouses\WArehouseResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditWArehouse extends EditRecord
{
    protected static string $resource = WArehouseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
