<?php

namespace App\Services;

use App\Models\Order;
use App\Models\DeliveryCompany;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Status;
class AmeexService
{    
    protected string $apiUrl = "https://api.ameex.app/customer/Delivery/Parcels/Action/Type/Add";
    protected string $token;
    protected string $api_id;
    public function __construct()
    {
        $ameex = DeliveryCompany::where('name', 'Ameex')->first();
        $this->token = $ameex->token;
        $this->api_id = $ameex->api_id;
    }

    public function sendOrder(Order $order)
    {
        $payload = $this->buildPayload($order);
        $options = [
        'headers' => [
            'C-Api-Id' => $this->api_id,
            'C-Api-Key' => $this->token
        ],
        'payload' => $payload,
        'muteHttpExceptions' => true
        ];
        $response = Http::withHeaders([
    'C-Api-Id' => $this->api_id,
    'C-Api-Key' => $this->token
])->asForm()->post("https://api.ameex.app/customer/Delivery/Parcels/Action/Type/Add", $payload);
        Log::info('Ameex response', [
            'response' => $response->json(),
            'order' => $order->id
        ]);
        $fee = $this->getCityFee($order->delivery_city);
        if($response->json()['api']['type'] == 'success'){
            $info = $this->getOrderInfo($response->json()['api']['data']['code']);
            $status = Status::where('name' , 'like', '%nouveau%')->first();
            $ensystem = Status::where('name', 'like', '%En system%')->first();

            $order->update([
                'tracking_number' => $response->json()['api']['data']['code'],
                'status_id' => $ensystem->id,
                'delivery_fee' => "$fee",
                'sync_status' => 'synced',
                'delivery_status_id' => $status->id,
            ]);
            return $response->json();
        }
        return $response->json();
    }
    
     protected function buildPayload(Order $order): array
    {
        $product = $order->products->first();

        return [
            "type" => "SIMPLE",
            "business" => $this->api_id,
            "replace" => 'false',
            "open" => 'YES',
            "try" => 'YES',
            "fragile" => '0',
            "receiver" => $order->name,
            "phone" => $order->phone,
            "address" => $order->address,
            "city" => $order->delivery_city,
            "cod" => $order->price,
            "comment" => $order->comment,
            "product" => $order->products->first()->product->title,
            "products[0][qty]" => $order->products->first()->quantity,
        ];
    }

    protected function getCityFee($city)
    {
        $response = Http::get('https://api.ameex.app/web/cities_fees');
        return $response->json()['cities'][$city]['d_fees'];
    }

    public function getOrderInfo($tracking){
        $response = Http::withHeaders([
            'C-Api-Id' => $this->api_id,
            'C-Api-Key' => $this->token
        ])->get("https://api.ameex.app/customer/Delivery/Parcels/Info", [
            'ParcelCode' => $tracking
        ]);
        return $response->json();
    }

}
