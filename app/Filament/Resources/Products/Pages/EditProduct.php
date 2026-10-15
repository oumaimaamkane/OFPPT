<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;
    protected array $optionsToSync = [];

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
{
    $record = $this->getRecord();

    // Always generate combinations from variants
    $state = $record->variants()->get()->toArray();

    if (blank($state)) {
        $data['options'] = $record->allOptions()
            ->get(['id', 'name', 'price', 'quantity'])
            ->toArray();
        return $data;
    }

    $combinations = [[]];

    foreach ($state as $option) {
        $values = $option['values'] ?? [];
        if (empty($values)) continue;

        $next = [];
        foreach ($combinations as $combo) {
            foreach ($values as $value) {
                $next[] = array_merge($combo, [$value]);
            }
        }
        $combinations = $next;
    }

    if ($combinations === [[]]) {
        $data['options'] = $record->allOptions()
            ->get(['id', 'name', 'price', 'quantity'])
            ->toArray();
        return $data;
    }

    // Build the full set of generated names
    $generatedNames = collect($combinations)
        ->map(fn($combo) => implode(' / ', $combo))
        ->values();

    // Load existing options keyed by name for quick lookup
    $existingOptions = $record->allOptions()
        ->get(['id', 'name', 'price', 'quantity'])
        ->keyBy('name');

    // Upsert: create missing combinations, preserve existing price/quantity
    foreach ($generatedNames as $name) {
        if (!$existingOptions->has($name)) {
            $record->allOptions()->create([
                'name'     => $name,
                'price'    => null,
                'quantity' => null,
            ]);
        }
    }

    // Delete options that no longer match any generated combination
    $record->allOptions()
        ->whereNotIn('name', $generatedNames->all())
        ->delete();

    // Reload fresh from DB to get IDs and reflect any changes
    $data['options'] = $record->allOptions()
        ->get(['id', 'name', 'price', 'quantity'])
        ->toArray();
    // dd($data['options']);
    return $data;
}

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->optionsToSync = $data['options'] ?? [];
        unset($data['options']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->allOptions()->delete();
        $this->record->allOptions()->createMany($this->optionsToSync);
    }
}