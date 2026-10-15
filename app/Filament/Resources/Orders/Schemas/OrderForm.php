<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Icons\Heroicon;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use App\Models\Product;
use App\Models\Variant;
use App\Models\VariantOption;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\Http;
use Filament\Forms\Components\Repeater;
use App\Models\DeliveryCompany;
use Filament\Forms\Components\Toggle;
use App\Models\Store;
class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    
                    Step::make('Delivery')
                        ->completedIcon(Heroicon::HandThumbUp)
                        ->schema([
                            // Products
                            Repeater::make('products')
                            ->label('Produits')
                            ->schema([
                            Select::make('product_id')
                            ->label('Produit')
                            ->options(Product::all()->pluck('name', 'id'))
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('product_id', $state);
                            }),

                            Select::make('variant_option_id')
                                ->label('Option')
                                ->options(function (Get $get) {
                                    return VariantOption::query()
                                        ->where('product_id', $get('product_id'))
                                        ->pluck('name', 'id');
                                })
                                ->disabled(fn (Get $get) => blank($get('product_id')))
                                ->live(),

                                TextInput::make('products.quantity')
                                ->label('Quantité')
                                ->numeric()
                                ->disabled(fn (Get $get) => blank($get('variant_option_id'))),

                            
                                ])
                                ->deletable(fn (Get $get) => count($get('products')) > 1)
                                ->columns(2),

                                Select::make('store_id')
                                ->label('Store')
                                ->options(Store::all()->pluck('name', 'id'))
                                ->required(),
                            TextInput::make('quantity')
                            ->label('Quantité')
                            ->required()
                            ->numeric(),
                            TextInput::make('price')
                            ->label('Prix')
                            ->required()
                            ->numeric(),
                            Select::make('delivery_company_id')
                            ->label('Compagnie de livraison')
                            ->options(
                                DeliveryCompany::all()->pluck('name', 'id')
                            )
                            ->required(),
                           
                        ]),
                    Step::make('Order')
                        ->completedIcon(Heroicon::HandThumbUp)
                        ->schema([
                            TextInput::make('name')
                            ->label('Nom')
                            ->required()
                                ,
                            TextInput::make('phone')
                            ->label('Téléphone')
                            ->required()
                                ->tel(),
                            Select::make('city')
                            ->label('Ville')
                            ->options(function(Get $get){
                                if(blank($get('delivery_company_id'))) return [];
                                $deliveryCompany = DeliveryCompany::find($get('delivery_company_id'));
                                if($deliveryCompany->name == 'OzonExpress'){
                                    $response = Http::get('https://ozonexpress.ma/api/files/destinations.php');
                                    return collect($response['cities'])->mapWithKeys(fn ($city, $key) => [$city['name'] => $city['name']]);
                                    // return OzonExpress::all()->pluck('name', 'id');
                                }elseif($deliveryCompany->name == 'Ameex'){
                                    $response = Http::get('https://api.ameex.app/web/cities_fees');
                                    return collect($response['cities'])->mapWithKeys(fn ($city, $key) => [$city['name']  => $city['name']]);
                                }elseif($deliveryCompany->name == 'DigyLog' || $deliveryCompany->name == 'digylog'){
                                    // \Log::info(collect($response));
                                    $response = Http::get('https://api.digylog.com/publicapi/moroccocities');
                                    return collect($response->json())->pluck('name', 'name');
                                }
                            })
                            ->required()
                            ->searchable()
                            ->disabled(fn(Get $get) => blank($get('delivery_company_id')))
                            ->live(),
                            TextInput::make('address')
                            ->label('Adresse')
                            ->required(),
                           Textarea::make('comment')
                           ->label('Commentaire'),
                           Toggle::make('replace')
                           ->label('Echange'),
                           Toggle::make('open')
                           ->label('Ouvrir'),
                           Toggle::make('try')
                           ->label('Essayer'),

                        ])
                        ->columns(2),
                ])->columnSpanFull(), 
            ]);
    }
}