<?php

namespace App\Filament\Resources\Sheets\Pages;

use App\Filament\Resources\Sheets\SheetResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSheet extends EditRecord
{
    protected static string $resource = SheetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
