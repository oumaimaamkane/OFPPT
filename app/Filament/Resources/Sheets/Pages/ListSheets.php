<?php

namespace App\Filament\Resources\Sheets\Pages;

use App\Filament\Resources\Sheets\SheetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSheets extends ListRecords
{
    protected static string $resource = SheetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
