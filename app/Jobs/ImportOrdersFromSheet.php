<?php

namespace App\Jobs;

use App\Models\Sheet;
use App\Models\Order;
use App\Models\orderProducts;
use App\Models\Product;
use App\Models\User;
use App\Services\GoogleSheetsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;
use Google\Client as Google_Client;
use Google\Service\Sheets as Google_Service_Sheets;
class ImportOrdersFromSheet implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Sheet $sheet) {}

    public function handle(): void
    {
     $client = new Google_Client();
    $client->setAuthConfig(storage_path('app/google-service-account.json'));
    $client->addScope(Google_Service_Sheets::SPREADSHEETS_READONLY);

    $sheetsService = new Google_Service_Sheets($client);
    $spreadsheet = $sheetsService->spreadsheets->get($this->sheet->spreadsheet_id);
    $tabs = collect($spreadsheet->getSheets())
        ->map(fn($s) => $s->getProperties()->getTitle())
        ->toArray();

    Log::info('Available tabs:', $tabs);

    try {
        $spreadsheetId = $this->sheet->spreadsheet_id;
        $headerRow    = $this->sheet->header_row ?? 1;
        $dataStartRow = max($headerRow + 1, ($this->sheet->last_imported_row ?? 0) + 1);
        $sheetName = $this->sheet->sheet_name;
        // ↑ If we've synced before, start from the row after the last one we processed

        $headers = $this->getRows($sheetsService , $this->sheet, $headerRow, $headerRow)[0] ?? [];
        // $headerRange   = "{$sheetName}!A{$headerRow}:{$headerRow}";
        // $headerResponse = $sheetsService->spreadsheets_values->get($spreadsheetId, $headerRange);
        // $headers = $headerResponse->getValues()[0] ?? [];
        if (empty($headers)) {
            Log::warning("Sheet {$this->sheet->id} has no headers at row {$headerRow}.");
            return;
        }

        $rows = $this->getRows($sheetsService , $this->sheet, $dataStartRow);
        // $dataRange    = "{$sheetName}!A{$dataStartRow}:Z";
        // $dataResponse = $sheetsService->spreadsheets_values->get($spreadsheetId, $dataRange);
        // $rows = $dataResponse->getValues() ?? [];

        if (empty($rows)) {
            Log::info("No new rows to import for sheet {$this->sheet->id}.");
            return;
        }

        $mapping = $this->sheet->column_mapping;
        if (empty($mapping)) {
            $mapping = $this->createDefaultMapping($headers);
            $this->sheet->update(['column_mapping' => $mapping]);
        }

        $imported       = 0;
        $skipped        = 0;
        $errors         = [];
        $lastProcessedRow = $this->sheet->last_imported_row ?? 0;

        foreach ($rows as $index => $row) {
            $currentRow = $dataStartRow + $index;

            try {
                $orderData = $this->mapRowToOrder($row, $headers, $mapping);

                if ($this->isEmptyRow($orderData)) {
                    $skipped++;
                    $lastProcessedRow = $currentRow; // still advance past empty rows
                    continue;
                }

                $validation = $this->validateOrderData($orderData);
                if (!$validation['valid']) {
                    $errors[] = "Row {$currentRow}: " . $validation['error'];
                    $lastProcessedRow = $currentRow;
                    continue;
                }

                $sku          = $this->extractSku($orderData);
                $productName  = $this->extractProductName($orderData);
                $productVariantName = $this->extractProductVariant($orderData);
                $preparedData = $this->prepareOrderData($orderData);
                $phone        = $preparedData['phone'] ?? null;

                $product = Product::query()
                    ->when($sku,         fn($q) => $q->where('sku', $sku))
                    ->when($productName, fn($q) => $q->orWhere('name', $productName))
                    ->first();

                if (!$product && $productName) {
                    $product = Product::create([
                        'name' => $productName,
                        'sku' => $sku ?? Str::slug($productName) . '-' . rand(1000, 9999),
                        'price' => $preparedData['price'] ?? 0,
                    ]);
                }

                $variantOptionId = null;
                if ($product && $productVariantName) {
                    $variantOption = \App\Models\VariantOption::firstOrCreate(
                        ['product_id' => $product->id, 'name' => $productVariantName],
                        ['price' => $preparedData['price'] ?? 0, 'quantity' => 1]
                    );
                    $variantOptionId = $variantOption->id;
                }

                $order = Order::create($preparedData);

                if ($product) {
                    orderProducts::create([
                        'order_id'   => $order->id,
                        'product_id' => $product->id,
                        'variant_option_id' => $variantOptionId,
                        'quantity'   => $preparedData['quantity'] ?? 1,
                        'price'      => $preparedData['price'] ?? 0,
                    ]);
                }

                $imported++;
                $lastProcessedRow = $currentRow;

            } catch (\Exception $e) {
                $errors[] = "Row {$currentRow}: " . $e->getMessage();
                Log::error("Row import error", ['row' => $currentRow, 'error' => $e->getMessage()]);
            }
        }

        // ✅ Save progress so the next sync starts where we left off
        $this->sheet->update([
            'last_synced_at'  => now(),
            'last_imported_row' => $lastProcessedRow,
        ]);


    } catch (\Exception $e) {
        Log::error('Import job failed: ' . $e->getMessage());
        throw $e;
    }
}

    protected function mapRowToOrder(array $row, array $headers, array $mapping): array
    {
        $orderData = [];

        foreach ($headers as $index => $header) {
            if (empty(trim($header)) || !isset($row[$index])) continue;

            $value = trim($row[$index]);
            if ($value === '') continue;

            $field = $mapping[$header]
                ?? collect($mapping)->first(fn($v, $k) => strcasecmp(trim($k), trim($header)) === 0)
                ?? strtolower(str_replace([' ', '-'], '_', $header));

            $orderData[$field] = $value;
        }

        return $orderData;
    }

    protected function prepareOrderData(array $rawData): array
    {
        $get = fn(...$keys) => collect($keys)
            ->map(fn($k) => isset($rawData[$k]) && trim($rawData[$k]) !== '' ? trim($rawData[$k]) : null)
            ->first(fn($v) => $v !== null);

        $rawPhone = $get('phone', 'phone_number', 'telephone', 'customer_phone', 'mobile', 'tel');

        return [
            'name'             => $get('first_name', 'name', 'customer_name', 'client_name', 'full_name' ,'Full Name') ?? 'Unknown Customer',
            'phone' => $this->normalizePhone($rawPhone) ?? '0000000000',
            'city'             => $get('city', 'ville', 'adress_city') ?? 'Unknown',
            'address'          => $get('address', 'adresse', 'adress_city'),
            'price'            => $this->parseAmount($get('total_charge', 'price', 'selling_price', 'total') ?? 0),
            'quantity'         => $this->parseInt($get('total_quantity', 'quantity', 'qty') ?? 1),
            'comment'          => $get('comment', 'notes', 'note', 'product_name'),
            'status_id'        => $this->resolveStatusId($get('status', 'statut')) ?? 11,
            // 'delivery_company' => $get('delivery_company', 'carrier', 'livreur', 'shipping_company'),
            // 'tracking_number'  => $trackingNumber,
            'delivery_fee'     => $this->parseAmount($get('delivery_fee', 'shipping_fee', 'frais_livraison') ?? 0),
            'store_name'       => $get('store_name', 'store', 'boutique', 'shop' ,'Store Name'),
            'product_url'      => $get('product_url', 'product url', 'url','url du produit'),
            'brand_name'       => $get('brand_name', 'brand', 'marque'),
        ];
    }

    

    protected function resolveStatusId(?string $statusName): ?int
    {
        if (!$statusName) return null;

        if (is_numeric($statusName)) return (int) $statusName;

        return \App\Models\Status::where('name', 'like', "%{$statusName}%")->first()?->id;
    }

    protected function extractSku(array $orderData): ?string
    {
        return isset($orderData['sku']) && trim($orderData['sku']) !== ''
            ? trim($orderData['sku'])
            : null;
    }

    protected function extractProductName(array $orderData): ?string
    {
        foreach (['product', 'product_name'] as $key) {
            if (isset($orderData[$key]) && trim($orderData[$key]) !== '') {
                return trim($orderData[$key]);
            }
        }
        return null;
    }

    protected function extractProductVariant(array $orderData): ?string
    {
        foreach (['product_variant', 'variant', 'variante'] as $key) {
            if (isset($orderData[$key]) && trim($orderData[$key]) !== '') {
                return trim($orderData[$key]);
            }
        }
        return null;
    }

    protected function findExistingOrder(?string $phone, ?string $sku): ?Order
    {
        if (empty($phone) || empty($sku)) return null;

        return Order::where('phone', $phone)
            ->whereHas('products.product', fn($q) => $q->where('sku', $sku))
            ->first();
    }

    protected function validateOrderData(array $orderData): array
    {
      
        $hasName  = !empty($orderData['first_name'] ?? $orderData['name'] ?? $orderData['customer_name']  ?? null);
        $hasPhone = !empty($orderData['phone'] ?? $orderData['phone_number'] ?? $orderData['customer_phone'] ?? $orderData['tel'] ?? null);

        if (!$hasName)  return ['valid' => false, 'error' => 'Nom manquant'];
        if (!$hasPhone) return ['valid' => false, 'error' => 'Téléphone manquant'];

        return ['valid' => true];
    }

    protected function isEmptyRow(array $orderData): bool
    {
        return empty(array_filter($orderData, fn($v) => !empty($v)));
    }

    protected function createDefaultMapping(array $headers): array
    {
        $fieldMappings = [
            'name'             => ['first name', 'first_name', 'First name' ,'name', 'customer_name', 'client_name', 'full_name' ,'Full name'],
            'phone'            => ['phone', 'telephone', 'Phone', 'tel', 'mobile', 'customer_phone', 'phone_number','whatsapp'],
            'city'             => ['city', 'ville', 'town'],
            'address'          => ['address', 'adresse', 'adress_city', 'address_city',"street address"],
            'price'            => ['price', 'total', 'total_charge', 'selling_price', 'amount'],
            'quantity'         => ['quantity', 'qty', 'total_quantity', 'quantité',"total quantity","Quantity" , "Total quantity" ,"Total charge"],
            'comment'          => ['comment', 'note', 'notes', 'remarque'],
            'status_id'        => ['status', 'statut', 'état', 'state','status de confirmation', 'statut de livraison','status de livraison'],
            'delivery_company' => ['delivery_company', 'carrier', 'livreur', 'shipping_company','société de livraison'],
            'tracking_number'  => ['tracking', 'tracking_number', 'tracking number'],
            'delivery_fee'     => ['delivery_fee', 'shipping_fee', 'frais_livraison', 'frais livraison'],
            'sku'              => ['sku', 'code', 'product_code', 'item_code',"reference" ,"variante api"],
            'product_name'     => ['product', 'product_name', 'product name','Product name' ,'item','product name'],
            'product_variant'  => ['variant', 'variante', 'product_variant', 'product variant','Product variant', 'variante produit', 'variant product'],
            'store_name'       => ['store_name', 'store', 'boutique', 'shop' ,'Store Name'],
            'product_url'      => ['product_url', 'product url', 'url','url du produit'],
        ];

        $mapping = [];

        foreach ($headers as $header) {
            $normalized = strtolower(trim($header));
            $matched    = false;

            foreach ($fieldMappings as $field => $variations) {
                if (in_array($normalized, $variations)) {
                    $mapping[$header] = $field;
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                $mapping[$header] = strtolower(str_replace(' ', '_', $header));
            }
        }

        return $mapping;
    }


    protected function parseAmount($value): float
    {
        if (is_numeric($value)) return (float) $value;
        $cleaned = preg_replace('/[^0-9.,]/', '', $value);
        return (float) str_replace(',', '.', $cleaned) ?: 0.0;
    }

    protected function parseInt($value): int
    {
        if (is_numeric($value)) return max(1, (int) $value);
        preg_match('/\d+/', $value, $m);
        return isset($m[0]) ? max(1, (int) $m[0]) : 1;
    }

    protected function parseBoolean($value): bool
    {
        if (is_bool($value)) return $value;
        return in_array(strtolower(trim((string) $value)), ['true', '1', 'yes', 'y', 'oui', 'on', 'enabled']);
    }

    protected function parseDate($value)
    {
        if (!$value) return null;
        try {
            return \Carbon\Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function normalizePhone(?string $phone): ?string
    {
        if (!$phone) return null;

        // remove everything except digits
        $phone = preg_replace('/\D/', '', $phone);

        if (!$phone) return null;

        // add 0 if not starting with 0
        if (!str_starts_with($phone, '0')) {
            $phone = '0' . $phone;
        }

        return $phone;
    }
    public function createWtspLink($phone, $message)
    {
        $phone = $this->normalizePhone($phone);
        $text = $message ? $message : "Bonjour";
        return "https://wa.me/" . $phone . "?text=" . urlencode($text);
    }
    public function getRows(Google_Service_Sheets $service, Sheet $sheet, int $startRow = 1, ?int $endRow = null): array
{
    // Use first sheet tab if sheet_name not set
    $sheetName = $sheet->sheet_name;
     $spreadsheet = $service->spreadsheets->get($sheet->spreadsheet_id);
    $availableTabs = collect($spreadsheet->getSheets())
        ->map(fn($s) => $s->getProperties()->getTitle());
    
   if (empty($sheetName) || !$availableTabs->contains($sheetName)) {
        $sheetName = $availableTabs->first(
            fn($tab) => strtolower(trim($tab)) === strtolower(trim($sheet->sheet_name))
        ) ?? $availableTabs->first();

        $sheet->update(['sheet_name' => $sheetName]);
    }

    if ($startRow === 1 && $endRow === null) {
        $range = $sheetName;
    } elseif ($endRow !== null) {
        $range = "{$sheetName}!A{$startRow}:Z{$endRow}";
    } else {
        $range = "{$sheetName}!A{$startRow}:Z";
    }

    $response = $service->spreadsheets_values->get(
        $sheet->spreadsheet_id,
        $range
    );

    return $response->getValues() ?? [];
}
}