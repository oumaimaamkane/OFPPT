<?php

namespace App\Filament\Resources\Sheets\Pages;

use App\Filament\Resources\Sheets\SheetResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSheet extends CreateRecord
{
    protected static string $resource = SheetResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        return $data;
    }
    // protected function afterCreate(): void
    // {
    //     redirect()->to(route('google.connect', ['sheet_id' => $this->record->id]));
    // }
    
    // protected function getRedirectUrl(): string
    // {
    //     return route('google.connect', ['sheet_id' => $this->record->id]);
    // }
}
