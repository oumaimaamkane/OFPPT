<?php

namespace App\Services;

use App\Models\DeliveryCompany;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Order;

class OzonService
{
    protected string $baseUrl;
    protected string $customerId;
    protected string $apiKey;

    public function __construct()
    {
        $ozon = DeliveryCompany::where('name', 'Ozon')->first();
        $this->customerId = $ozon->api_id;
        $this->apiKey = $ozon->token;

        $this->baseUrl = "https://api.ozonexpress.ma/customers/{$this->customerId}/{$this->apiKey}";
    }

    /**
     * Make request to Ozon API
     */
    public function request(string $endpoint, string $method = 'POST', array|string $data = [])
    {
        $url = "{$this->baseUrl}/{$endpoint}";
        // dd($url);
        // If string (like your GAS payload), convert to array
        if (is_string($data)) {
            parse_str($data, $data);
        }
        // dd($data);
        $response = Http::asForm()
    ->withOptions(['http_errors' => false])
    ->post($url, $data);

        $rawText = $response->body();

        Log::info("Ozon API Raw Response: " . $rawText);

        // Fix: sometimes API returns multiple JSON objects glued together
        $parts = preg_split('/}(?={)/', $rawText);

        if (count($parts) > 1) {
            foreach ($parts as $index => $part) {
                if ($index < count($parts) - 1) {
                    $parts[$index] = $part . '}';
                }
            }
        }

        $result = json_decode($parts[0] ?? '{}', true);

        return $result;
    }

    /**
     * Add parcel
     */
    public function addParcel(array $data)
    {
        $response = $this->request('add-parcel', 'POST', $data);

        if (isset($response['ADD-PARCEL']['NEW-PARCEL'])) {
            $parcel = $response['ADD-PARCEL']['NEW-PARCEL'];

            return [
                'success' => true,
                'tracking_number' => $parcel['TRACKING-NUMBER'] ?? null,
                'delivered_price' => $parcel['DELIVERED-PRICE'] ?? 0,
                'refused_price' => $parcel['REFUSED-PRICE'] ?? 0,
                'returned_price' => $parcel['RETURNED-PRICE'] ?? 0,
                'result' => $response['ADD-PARCEL']['RESULT'] ?? 'UNKNOWN',
                'raw' => $response
            ];
        }

        return [
            'success' => false,
            'message' => 'Tracking number not found',
            'raw' => $response
        ];
    }

    /**
     * Get parcel info
     */
    public function getParcelInfo(string $trackingNumber)
    {
        return $this->request('parcel-info', 'POST', [
            'tracking-number' => $trackingNumber
        ]);
    }

    public function sendOrder(Order $order)
    {
        $ozon = DeliveryCompany::where('name', 'Ozon')->first();
        $sendEndpoint = $this->baseUrl . '/add-parcel';

        $payload = [
            'tracking-number' => $order->tracking_number,
            'parcel-receiver' => $order->name,
            'parcel-phone' => $order->phone,
            'parcel-city' => $order->delivery_city,
            'parcel-address' => $order->address,
            'parcel-price' => $order->price,
            'parcel-declared-value' => $order->price,
            'parcel-stock' => 0,
            'parcel-open' => $order->open == 'yes' ? 1 : 2,
            'parcel-fragile' => $order->fragile == 'yes' ? 1 : 0,
            'parcel-replace' => $order->replace == 'yes' ? 1 : 0,
            'products' => $order->products->map(function ($product) {
                return [
                    'ref' => $product->product->sku,
                    'qnty' => $product->quantity,
                ];
            })->toArray(),
        ];

        $response = $this->request('add-parcel', 'POST', $payload);

        if (
            isset($response['ADD-PARCEL']['NEW-PARCEL'])
        ) {
            $parcel = $response['ADD-PARCEL']['NEW-PARCEL'];

            return [
                'success' => true,
                'tracking_number' => $parcel['TRACKING-NUMBER'] ?? null,
                'delivered_price' => $parcel['DELIVERED-PRICE'] ?? 0,
                'refused_price' => $parcel['REFUSED-PRICE'] ?? 0,
                'returned_price' => $parcel['RETURNED-PRICE'] ?? 0,
                'result' => $response['ADD-PARCEL']['RESULT'] ?? 'UNKNOWN',
                'raw' => $response
            ];
        }

        return [
            'success' => false,
            'message' => 'Tracking number not found',
            'raw' => $response
        ];
    }
}
