<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use App\Models\Order;
use App\Models\WarehouseProduct;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use App\Models\Status;
class ScanBarcode extends Page
{
    protected static string $resource = OrderResource::class;

    protected string $view = 'filament.resources.orders.pages.scan-barcode';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;
    protected static ?string $navigationLabel = 'Scanner le code barre';
    protected static ?string $title = 'Scanner le code barre';
    protected static ?int $navigationSort = 1;
    public string $trackingNumber = '';
    public ?array $scannedOrder = null;
    public ?string $resultMessage = null;
    public ?string $resultType = null; 
    public bool $isProcessing = false;
    public array $scanHistory = [];
 
   public static function canViewAny(): bool
    {
        return Filament::auth()->user()->can('View:ScanBarcode');
    }
    public static function canCreate(): bool
    {
        return Filament::auth()->user()->can('Create:ScanBarcode');
    }
    public static function canEdit(Model $record): bool
    {
        return Filament::auth()->user()->can('Update:ScanBarcode');
    }
    public static function canDelete(Model $record): bool
    {
        return Filament::auth()->user()->can('Delete:ScanBarcode');
    }
    public function scanBarcode(): void
    {
        $this->isProcessing = true;
        $this->resultMessage = null;
        $this->scannedOrder = null;
 
        $tracking = trim($this->trackingNumber);
 
        if (empty($tracking)) {
            $this->resultMessage = 'Veuillez entrer ou scanner un numéro de suivi.';
            $this->resultType = 'warning';
            $this->isProcessing = false;
            return;
        }
 
        // Search for the order by tracking number
        $order = Order::where('tracking_number', $tracking)
            ->with(['products.product'])
            ->first();
 
        if (!$order) {
            $this->resultMessage = "Aucune commande trouvée avec le numéro de suivi : {$tracking}";
            $this->resultType = 'error';
            $this->addToHistory($tracking, null, 'not_found');
            $this->trackingNumber = '';
            $this->isProcessing = false;
            return;
        }
 
        // Process based on delivery status
        DB::beginTransaction();
 
        try {
            $status = strtolower($order->status->name);
        // $delivery_status = strtolower($order->delivery_status->name);
            if ($status === 'returned' || $status === 'retourné'|| $status == 'retour' ) {
                // Increment warehouse quantity for each product in the order
                foreach ($order->products as $item) {
                    $item->product->increment('warehouse_qty', $item->quantity);
                }
 
                $this->resultMessage = "✓ Commande #{$order->order_number} — RETOURNÉE. Stock de l'entrepôt incrémenté pour " . $order->products->count() . " produit(s).";
                $this->resultType = 'success';
                $this->addToHistory($tracking, $order, 'restocked');
 
            } elseif ($status === 'en systeme' || $status === 'in_system' || $status === 'pending') {
                // Decrement warehouse quantity for each product in the order
                foreach ($order->products as $item) {
                    // if ($item->product->warehouse_qty < $item->quantity) {
                    //     throw new \Exception("Insufficient stock for product: {$item->product->name}. Available: {$item->product->warehouse_qty}, Required: {$item->quantity}");
                    // }
                    $item->product->decrement('warehouse_qty', $item->quantity);
                    $newStatus = Status::where('name', 'like', '%sortire de l\'usine%')->first();
                    $order->update([
                        'status_id' => $newStatus->id,
                    ]);
                }
 
                
 
                $this->resultMessage = "↓ Commande #{$order->tracking_number} — EN SYSTÈME. Stock de l'entrepôt décrémenté pour " . $order->products->count() . " produit(s).";
                $this->resultType = 'success';
                $this->addToHistory($tracking, $order, 'dispatched');
 
            } 
 
            // Show order details
            $this->scannedOrder = [
                'id'              => $order->id,
                'order_number'    => $order->tracking_number,
                'tracking_number' => $order->tracking_number,
                'delivery_status' => $order->delivery_status,
                'customer_name'   => $order->name ?? ($order->name ?? 'N/A'),
                'items'           => $order->products->map(fn($item) => [
                    'product_name'       => $item->product->name,
                    'quantity'           => $item->quantity,
                    'warehouse_qty' => $item->product->fresh()->warehouse_qty,
                ])->toArray(),
            ];
 
            DB::commit();
 
        } catch (\Exception $e) {
            DB::rollBack();
            $this->resultMessage = 'Erreur : ' . $e->getMessage();
            $this->resultType = 'error';
        }
 
        $this->trackingNumber = '';
        $this->isProcessing = false;
    }
 
    protected function addToHistory(string $tracking, ?Order $order, string $action): void
    {
        array_unshift($this->scanHistory, [
            'tracking_number' => $tracking,
            'order_number'    => $order?->order_number ?? '—',
            'action'          => $action,
            'scanned_at'      => now()->format('H:i:s'),
        ]);
 
        // Keep only last 10 scans
        $this->scanHistory = array_slice($this->scanHistory, 0, 10);
    }
 
    public function clearHistory(): void
    {
        $this->scanHistory = [];
        $this->scannedOrder = null;
        $this->resultMessage = null;
    }
}
