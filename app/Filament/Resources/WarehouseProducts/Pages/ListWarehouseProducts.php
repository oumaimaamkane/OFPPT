<?php

namespace App\Filament\Resources\WarehouseProducts\Pages;

use App\Filament\Resources\WarehouseProducts\WarehouseProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWarehouseProducts extends ListRecords
{
    protected static string $resource = WarehouseProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function updateVariantQuantity($variantOptionId, $addedAmount)
    {
        $addedAmount = (int) $addedAmount;
        if ($addedAmount === 0) return;

        $variant = \App\Models\VariantOption::find($variantOptionId);
        if ($variant) {
            $variant->quantity += $addedAmount;
            $variant->save();

            \Filament\Notifications\Notification::make()
                ->title('Quantité mise à jour')
                ->success()
                ->send();
        }
    }
}
