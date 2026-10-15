<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Status;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Http;
use App\Models\orderProducts;
use App\Models\DeliveryCompany;
class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // dd($data);
       if(!isset($data['status'])){
        $status = Status::where('name' ,'like', '%nouvelle%')
        ->orWhere('name' ,'like', '%new%')
        ->orWhere('name' ,'like', '%nouveau%')
        ->first();
        $data['status_id'] = $status->id;
       }
       $deliveryCompany = DeliveryCompany::find($data['delivery_company_id']);
       if($deliveryCompany->name == 'OzonExpress'){
        $response = Http::get('https://ozonexpress.ma/api/files/destinations.php');
        $city = collect($response['cities'])->where('name', 'like', '%'.$data['city'].'%')->first();
        // dd($city);
        $data['delivery_fee'] = $city['delivery']['price'];
       }
       if($deliveryCompany->name == 'Ameex'){
        $response = Http::get('https://api.ameex.app/web/cities_fees');
        $city = collect($response->json()['cities'])
    ->first(fn($item) => str_contains(
        strtolower($item['name']),
        strtolower($data['city'])
    ));
        $data['delivery_fee'] = $city['d_fees'];
       }
       if($deliveryCompany->name == 'DigyLog' || $deliveryCompany->name == 'digylog'){
        $response = Http::get('https://api.digylog.com/publicapi/moroccocities');
        $city = collect($response->json())->where('name', 'like', '%'.$data['city'].'%')->first();
        $data['delivery_fee'] =$city ? $city['fee'] : 0;
       }
       $data['replace'] = $data['replace'] ? 'yes' : 'no';
       $data['open'] = $data['open'] ? 'yes' : 'no';
       $data['try'] = $data['try'] ? 'yes' : 'no';
       if(auth()->user()->hasRole('Confimation')){
        $data['assign_to'] = auth()->user()->id;
       }
       return $data;
    }

    protected function afterCreate(): void
    {
        // dd($this->data);
        $products = $this->data['products'];
        foreach ($products as $product) {
            $product_id = $product['product_id'];
            $option_id = $product['variant_option_id'];
            $quantity = $product['products']['quantity'];
            $order_id = $this->record->id;

            $order_products = orderProducts::create([
            'order_id' => $order_id,
            'product_id' => $product_id,
            'variant_option_id' => $option_id,
            'quantity' => $quantity,
        ]);
    }
}
}
