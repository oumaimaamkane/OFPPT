<?php

namespace App\Filament\Resources\WarehouseProducts\Pages;

use App\Filament\Resources\WarehouseProducts\WarehouseProductResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditWarehouseProduct extends EditRecord
{
    protected static string $resource = WarehouseProductResource::class;

    public array $variantQuantitiesToUpdate = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $product = $this->record->product;
        if ($product && $product->allOptions) {
            $gridData = [];
            foreach ($product->allOptions as $o) {
                $gridData[$o->id] = ['quantity' => $o->quantity];
            }
            $data['variant_options_data'] = $gridData;
        }
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['variant_options_data'])) {
            $this->variantQuantitiesToUpdate = $data['variant_options_data'];
            unset($data['variant_options_data']);
        }
        return $data;
    }

    protected function afterSave(): void
    {
        if (!empty($this->variantQuantitiesToUpdate) && is_array($this->variantQuantitiesToUpdate)) {
            foreach ($this->variantQuantitiesToUpdate as $id => $item) {
                if (isset($item['quantity'])) {
                    \App\Models\VariantOption::where('id', $id)
                        ->update(['quantity' => (int) $item['quantity']]);
                }
            }
        }
    }
}
