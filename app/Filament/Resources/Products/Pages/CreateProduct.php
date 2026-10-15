<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Milon\Barcode\DNS1D;
use Illuminate\Support\Facades\Storage;
use App\Models\Product;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
    protected array $optionsToSync = [];
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // dd($data);
        //generate barcode using random string and create the image using milon/barcode 
       $barcode_code = $data['sku'] . '-' . Str::random(10);
        $barcode = new DNS1D();

        // Generate barcode as PNG string
        $barcodeContent = $barcode->getBarcodePNG($barcode_code, 'C128');

        // Store in public disk under barcodes folder
        $path = 'barcodes/' . strtolower($barcode_code) . '.png';
        Storage::disk('public')->put($path, base64_decode($barcodeContent));

        $data['barcode'] = $path;
         $options = $data['options'] ?? [];
    unset($data['options']); // Remove from main data so Filament doesn't try to save it

    // Sync options to DB after save
    $this->optionsToSync = $options;
        
        return $data;
    }
    protected function afterCreate(): void
    {
        $this->record->allOptions()->createMany($this->optionsToSync);
    }

    
}
