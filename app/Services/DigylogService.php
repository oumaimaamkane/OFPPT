<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DigylogService
{
    protected string $apiUrl = "https://api.digylog.com/api/v2/seller";

    public function sendOrders()
    {
        $orders = Order::with('delivery_company' , function($q){
            $q->where('name','LIKE', '%Digylog%');
        })->where('status_id', 3)
        ->whereNull('tracking_number') // not synced yet
        ->get();

        foreach ($orders as $order) {

            $payload = $this->buildPayload($order);

            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . config('services.digylog.token'),
                    'Referer' => 'https://apiseller.digylog.com',
                ])->post($this->apiUrl, $payload);

                $body = $response->json();

                Log::info("Digylog response", [
                    'order_id' => $order->id,
                    'response' => $body
                ]);

                $result = $body[0] ?? null;

                if (!$result) {
                    throw new \Exception('Invalid response');
                }

                $tracking = $result['traking'] ?? null;
                $isSuccess = $result['isSuccess'] ?? false;
                $message = $isSuccess
                    ? 'Success'
                    : implode(', ', $result['errors'] ?? ['Failed']);
                $status = $this->getOrderHistory($tracking);
                $ensystem = Status::where('name', 'like', '%En system%')->first();
                $order->update([
                    'tracking_number'=> $tracking,
                    'status_id' => $ensystem->id,
                    'sync_status'  => $isSuccess ? 'synced' : 'failed',
                    'delivery_status_id' => $status['delivery_status_id'],
                    'sync_message' => $status['sync_message'],
                ]);

            } catch (\Exception $e) {

                Log::error("Digylog error", [
                    'order_id' => $order->id,
                    'error' => $e->getMessage()
                ]);

                $order->update([
                    'sync_status'  => 'error',
                    'sync_message' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function buildPayload(Order $order): array
{
    $product = $order->products->first();
   
    $networks = $this->getNetworks();

    $network = collect($networks)
        ->first(fn($n) => str_contains(
            strtolower($n['name']),
            'Agadir'
        ));
        $network_id = $network['id'] ?? 1;
        $city_fee = $this->getCityFee($order->delivery_city , $network_id);
    return [
        "network" => $network_id,
        "store" => $order->store->name ?? "",
        "mode" => 1,
        "status" => 0,
        "orders" => [
            [
                "num" => $order->id,
                "type" => 1,
                "name" => $order->name,
                "phone" => preg_replace('/\D/', '', $order->phone),
                "city" => $city_fee['city'],
                "address" => $order->address,
                "price" => $order->price,
                "refs" => [
                    [
                        "designation" => $product->product->name ?? 'Product',
                        "quantity" => $product->quantity ?? $order->quantity ?? 1,
                    ]
                ],
                "port" => 1,
                "note" => $order->comment
            ]
        ]
    ];
}
    public function getNetworks()
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.digylog.token'),
            'Referer' => 'https://apiseller.digylog.com',
        ])->get($this->apiUrl.'/networks');
        return $response->json();
    }
  
    public function sendOrder(Order $order)
    {
        $payload = $this->buildPayload($order);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.digylog.token'),
            'Referer' => 'https://apiseller.digylog.com',
        ])->post($this->apiUrl.'/orders', $payload);
        return $response->json();
    }

    public function getOrderInfo($tracking_number)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.digylog.token'),
            'Referer' => 'https://apiseller.digylog.com',
        ])->get($this->apiUrl.'/orders/'.$tracking_number.'/infos');
        return $response->json();
    }

    public function getCityFee($city_id , $network_id){
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.digylog.token'),
            'Referer' => 'https://apiseller.digylog.com',
        ])->withQueryParameters([
            'network' => $network_id,
            'city' => $city_id,
        ])->get($this->apiUrl.'/deliverycost');
        return $response->json();
    }

    public function getOrderHistory($tracking_number)
    {
        $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . config('services.digylog.token'),
                    'Referer' => 'https://apiseller.digylog.com',
                    'Accept' => 'application/json',
                ])->get('https://api.digylog.com/api/v2/seller/historics?' . http_build_query([
                    'trakings' => $tracking_number
                ]));
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

            return response->json([
                'sync_message' => $statusText,
                'delivery_status_id' => $status ? $status->id : null,
            ]);

        }
    }
}
