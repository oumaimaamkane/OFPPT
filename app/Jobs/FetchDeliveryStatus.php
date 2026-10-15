<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Order;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Status;
use App\Models\DeliveryCompany;
class FetchDeliveryStatus implements ShouldQueue
{
    use Queueable;

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $orders = Order::with('delivery_company')
            ->whereNotNull('tracking_number')
            ->get();

        foreach ($orders as $order) {

            try {
                $tracking = $order->tracking_number;
                if($order->delivery_company->name == 'digylog'){
                    
                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . config('services.digylog.token'),
                        'Referer' => 'https://apiseller.digylog.com',
                        'Accept' => 'application/json',
                    ])->get('https://api.digylog.com/api/v2/seller/historics?' . http_build_query([
                        'trakings' => $tracking
                    ]));
    
                    $result = $response->json();
    
                    Log::info("Digylog tracking", [
                        'order_id' => $order->id,
                        'tracking' => $tracking,
                        'response' => $result
                    ]);
    
                    $history = $result[$tracking] ?? null;
    
                    if (is_array($history) && count($history) > 0) {
                        $latest = $history[count($history) - 1];
    
                        $statusText = $latest['newvalue']
                            ?? $latest['newValue']
                            ?? 'Unknown';
    
                        $statusId = $latest['newvalue_id']
                            ?? $latest['newValue_id']
                            ?? null;
                        $status = Status::firstOrCreate([
                            'name' => $statusText,
                            'color' => '#3498db'
                        ]);
    
                        $order->update([
                            'sync_message' => $statusText,
                            'delivery_status_id' => $status ? $status->id : null,
                        ]);
    
                    } else {
    
                        Log::warning("No status found", [
                            'order_id' => $order->id,
                            'tracking' => $tracking
                        ]);
                    }
                }
                if($order->delivery_company->name == 'Ameex'){
                    $ameex = DeliveryCompany::where('name', 'Ameex')->first();
                    $response = Http::withHeaders([
                        'C-Api-Id' => $ameex->api_id,
                        'C-Api-Key' => $ameex->token,
                    ])->get('https://api.ameex.app/customer/Delivery/Parcels/Tracking?' . http_build_query([
                        'ParcelCode' => $tracking
                    ]));
                    Log::info("Ameex tracking", [
                        'order_id' => $order->id,
                        'tracking' => $tracking,
                        'response' => $response->json()
                    ]);
                    $result = $response->json();
                    $trackingData = $result['api']['tracking'] ?? null;
    
                    if (is_array($trackingData) && count($trackingData) > 0) {
                        $latest = $trackingData[count($trackingData) - 1];
    
                        $statusText = $latest['statut_name']
                            ?? $latest['statut_name']
                            ?? 'Unknown';
    
                        
                        $status = Status::firstOrCreate([
                            'name' => $statusText,
                            'color' => $latest['statut_color']
                        ]);
    
                        $order->update([
                            'sync_message' => $statusText,
                            'delivery_status_id' => $status ? $status->id : null,
                        ]);
    
                    } else {
    
                        Log::warning("No status found", [
                            'order_id' => $order->id,
                            'tracking' => $tracking
                        ]);
                    }
                }

            } catch (\Exception $e) {

                Log::error("Tracking error", [
                    'order_id' => $order->id,
                    'tracking' => $order->tracking_number,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
