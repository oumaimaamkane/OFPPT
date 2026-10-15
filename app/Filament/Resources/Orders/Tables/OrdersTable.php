<?php

namespace App\Filament\Resources\Orders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Filament\Tables\Columns\SelectColumn;
use Illuminate\Support\Facades\Http;
use App\Models\Status;
use Filament\Tables\Columns\TextInputColumn;
use App\Models\DeliveryCompany;
use App\Models\Product;
use App\Models\Variant;
use App\Models\VariantOption;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Action;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use App\Services\DigylogService;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use App\Services\AmeexService;
use App\Models\Store;
use Filament\Facades\Filament;
use App\Filament\Tables\Columns\Whatsapp;
use Illuminate\Database\Eloquent\Builder;
use App\Services\OzonService;
class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tracking_number')
                    ->searchable()
                    ->sortable()
                     ->copyable()
                    ->copyMessage('Tracking est copié')
                    ->copyMessageDuration(1500),
                TextColumn::make('created_at')
                ->label('Date')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('brand_name')
                ->label('Marque')
                    ->searchable()
                    ->sortable(),
                TextInputColumn::make('name')
                ->label('Client')
                    ->searchable()
                    ->sortable()
                    ->disabled(fn() => auth()->user()->hasRole('Warehouse manager') || auth()->user()->hasRole('Warehouse Manager') || auth()->user()->hasRole('warehouse manager')),
                TextInputColumn::make('phone')
                ->label('Téléphone')
                    ->searchable()
                    ->sortable()
                    ->disabled(fn() => auth()->user()->hasRole('Warehouse manager') || auth()->user()->hasRole('Warehouse Manager') || auth()->user()->hasRole('warehouse manager')),
                Whatsapp::make('whatsapp_link')
                ->label('Lien WhatsApp')
                ,
                TextColumn::make('city')
                ->label('Ville')
                    ->searchable()
                    ->sortable(),
                SelectColumn::make('delivery_city')
                    ->label('Ville de livraison')
                    ->options(function($record){
                       if ($record->delivery_company && str_contains($record->delivery_company->name, 'Ozon')) {
                            $response = Http::get('https://ozonexpress.ma/api/files/destinations.php');
                            return collect($response['cities'])->mapWithKeys(fn ($city, $key) => [$city['id'] => $city['name']]);
                        }elseif($record->delivery_company && str_contains($record->delivery_company->name, 'Ameex')){
                            $response = Http::get('https://api.ameex.app/web/cities_fees');
                            return collect($response['cities'])->mapWithKeys(fn ($city, $key) => [$key => $city['name']]);
                        }elseif($record->delivery_company && str_contains($record->delivery_company->name, 'digylog')){
                            // \Log::info(collect($response));
                            $response = Http::withHeaders([
                                'Authorization' => 'Bearer ' . config('services.digylog.token'),
                                'Referer' => "https://apiseller.digylog.com"
                            ])->get('https://api.digylog.com/api/v2/seller/cities');
                            return collect($response->json())->pluck('name', 'id');
                        }
                    })
                    ->searchable()
                    ->disabled(fn($record) => blank($record->delivery_company) || auth()->user()->hasRole('Warehouse manager') || auth()->user()->hasRole('Warehouse Manager') || auth()->user()->hasRole('warehouse manager'))
                    ,
               
                TextInputColumn::make('address')
                ->label('Adresse')
                    ->searchable()
                    ->sortable()
                    ->disabled(fn() => auth()->user()->hasRole('Warehouse manager') || auth()->user()->hasRole('Warehouse Manager') || auth()->user()->hasRole('warehouse manager')),

                TextColumn::make('products.product.name')
                ->label('Produit')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->listWithLineBreaks()
                    ->disabled(fn() => auth()->user()->hasRole('Warehouse manager') || auth()->user()->hasRole('Warehouse Manager') || auth()->user()->hasRole('warehouse manager')),
                
                TextColumn::make('products.option.name')
                ->label('Option')
                    ->searchable()
                    ->sortable()
                    ->disabled(fn() => auth()->user()->hasRole('Warehouse manager') || auth()->user()->hasRole('Warehouse Manager') || auth()->user()->hasRole('warehouse manager'))
                    ->listWithLineBreaks(),

                TextColumn::make('products.quantity')
                ->label('Quantités')
                ->badge()
                ->listWithLineBreaks()
                ->disabled(fn() => auth()->user()->hasRole('Warehouse manager') || auth()->user()->hasRole('Warehouse Manager') || auth()->user()->hasRole('warehouse manager')),
                TextColumn::make('products.product_url')
                ->label('URL du produit')
                    ->searchable()
                    ->sortable()
                    ->disabled(fn() => auth()->user()->hasRole('Warehouse manager') || auth()->user()->hasRole('Warehouse Manager') || auth()->user()->hasRole('warehouse manager')),
                    
                TextInputColumn::make('price')
                ->label('Prix')
                    ->searchable()
                    ->sortable()
                    ->disabled(fn() => auth()->user()->hasRole('Warehouse manager') || auth()->user()->hasRole('Warehouse Manager') || auth()->user()->hasRole('warehouse manager')),
               
                SelectColumn::make('status_id')
                ->label('Statut')
                ->options(Status::pluck('name', 'id'))
                    ->searchable()
                    ->sortable()
                    ->disabled(fn() => auth()->user()->hasRole('Warehouse manager') || auth()->user()->hasRole('Warehouse Manager') || auth()->user()->hasRole('warehouse manager')),

                TextColumn::make('delivery_status.name')
                ->label('Statut de livraison')
                ->badge()
                ->color(fn($record) => $record->delivery_status && $record->delivery_status->color 
                    ? \Filament\Support\Colors\Color::hex($record->delivery_status->color) 
                    : null)
                    ->searchable()
                    ->sortable()
                    ->disabled(fn() => auth()->user()->hasRole('Warehouse manager') || auth()->user()->hasRole('Warehouse Manager') || auth()->user()->hasRole('warehouse manager')),
                SelectColumn::make('delivery_company_id')
                ->label('Compagnie de livraison')
                    ->searchable()
                    ->sortable()
                    ->options(DeliveryCompany::pluck('name', 'id'))
                    ->disabled(fn($record) => $record->delivery_status_id != null || auth()->user()->hasRole('Warehouse manager') || auth()->user()->hasRole('Warehouse Manager') || auth()->user()->hasRole('warehouse manager'))
                    ,
                SelectColumn::make('store_id')
                ->label('Boutique')
                ->options(Store::pluck('name', 'id'))
                    ->searchable()
                    ->sortable()
                    ->disabled(fn() => auth()->user()->hasRole('Warehouse manager') || auth()->user()->hasRole('Warehouse Manager') || auth()->user()->hasRole('warehouse manager')),
           
            ])
            ->modifyQueryUsing(function (Builder $query) {
                // dd(auth()->user()->roles);
                if (auth()->user()->hasRole('Warehouse Manager') || auth()->user()->hasRole('Warehouse manager') || auth()->user()->hasRole('warehouse manager')) {
                    $query->whereHas('status', function ($q) {
                        $q->where(function ($q) {
                            $q->where('name', 'like', '%confirmer%')
                            ->orWhere('name', 'like', '%confirmé%');
                        });
                    });
                }
                if(auth()->user()->hasRole('Confirmation')){
                    $query->where('assign_to', '=', auth()->id());
                }

                return $query;
            })
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status_id')
                ->label('Statut')
                ->options(Status::pluck('name', 'id'))
                ->searchable()
                ,
                SelectFilter::make('delivery_company_id')
                ->label('Compagnie de livraison')
                ->options(DeliveryCompany::pluck('name', 'id'))
                ->searchable()
                ,
                SelectFilter::make('delivery_status_id')
                ->label('Statut de livraison')
                ->options(Status::pluck('name', 'id'))
                ->searchable()
                ,
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make()
                ->visible(fn() => Filament::auth()->user()->can('Update:Order')),
                Action::make('clone')
                ->label('Dupliquer')
                ->icon('heroicon-o-document-duplicate')
                ->color('primary')
                ->action(function ($record) {
                    $newOrder = $record->replicate();
                    $newOrder->save();
                })
                ->visible(fn() => Filament::auth()->user()->can('Update:Order')),
                Action::make('update_products')
                ->label('Mettre à jour les produits')
                ->icon('heroicon-o-pencil')
                ->color('primary')
                ->fillForm(function ($record) {
                    // Pre-load existing products into the repeater
                    return [
                        'products' => $record->products->map(fn ($p) => [
                            'id'                => $p->id,
                            'product_id'        => $p->product_id,
                            'variant_option_id' => $p->variant_option_id,
                            'quantity'          => $p->quantity,
                        ])->toArray(),
                    ];
                })
                ->schema([
                    Repeater::make('products')
                        ->schema([
                            Select::make('product_id')
                                ->label('Produit')
                                ->options(Product::all()->pluck('name', 'id'))
                                ->live()
                                ->afterStateUpdated(function (callable $set) {
                                    $set('variant_option_id', null);
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

                            TextInput::make('quantity')  // ← fixed name
                                ->label('Quantité')
                                ->numeric()
                                ->minValue(1)
                                ->default(1)
                                ->disabled(fn (Get $get) => blank($get('product_id'))),
                        ])
                        ->columnSpanFull(),
                ])
                ->action(function ($record, $data) {
                    $submittedIds = [];

                    foreach ($data['products'] as $productData) {
                        if (isset($productData['id'])) {
                            // Update existing row
                            $record->products()
                                ->where('id', $productData['id'])
                                ->update([
                                    'product_id'        => $productData['product_id'],
                                    'variant_option_id' => $productData['variant_option_id'],
                                    'quantity'          => $productData['quantity'],
                                ]);

                            $submittedIds[] = $productData['id'];
                        } else {
                            // Create new row
                            $new = $record->products()->create([
                                'product_id'        => $productData['product_id'],
                                'variant_option_id' => $productData['variant_option_id'],
                                'quantity'          => $productData['quantity'],
                            ]);

                            $submittedIds[] = $new->id;
                        }
                    }

                    // Delete rows removed from the repeater
                    $record->products()
                        ->whereNotIn('id', $submittedIds)
                        ->delete();
                })
                ->visible(fn() => Filament::auth()->user()->can('Update:Order')),
                
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                    ->visible(fn() => Filament::auth()->user()->can('Delete:Order')),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ExportBulkAction::make(),
                    BulkAction::make('send_to_delivery_company')
                    ->label('Envoyer a la societe de livraison')
                    ->icon('heroicon-o-arrow-up-on-square')
                    ->color('primary')
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            if($record->delivery_company_id === null){
                                Notification::make()
                                ->title('Commande n\'a pas de societe de livraison')
                                ->body("L'une des commandes n'a pas de societe de livraison")
                                ->danger()
                                ->send();
                                return;
                            }
                            if($record->delivery_city === null){
                                Notification::make()
                                ->title('Commande n\'a pas de ville de livraison')
                                ->body("L'une des commandes n'a pas de ville de livraison")
                                ->danger()
                                ->send();
                                return;
                            }
                        if($record->delivery_company->name == 'Ameex'){
                            $ameexService = new AmeexService();
                            $response = $ameexService->sendOrder($record);
                            if($response['login'] === 'success'){
                                return Notification::make()
                                ->title('Commande sync avec succes')
                                ->body("Commande a ete sync avec succes")
                                ->success()
                                ->send();
                            }else{
                                return Notification::make()
                                ->title('Commande non sync avec ameex')
                                ->body($response['message'])
                                ->danger()
                                ->send();
                            }
                        }elseif($record->delivery_company->name == 'DigyLog' || $record->delivery_company->name == 'digylog'){
                            $digyLogService = new DigyLogService();
                            $response = $digyLogService->sendOrder($record);
                            if(isset($response[0]['isSuccess']) && $response[0]['isSuccess'] == true){
                             Notification::make()
                             ->title('Commande sync avec succes')
                             ->body("Commande a ete sync avec succes")
                             ->success()
                             ->send();
                             $record->update([
                                 'tracking_number' => $response[0]['traking'],
                                 'delivery_fee' => $response[0]['cost'],
                                 'sync_status' => 'synced',
                                 'sync_message' => 'Commande sync avec succes',
                             ]);
                            }else{
                            //  dd($response['error']);
                                   Notification::make()
                                   ->title('Commande non sync avec digylog')
                                   ->body($response['error']['message'])
                                   ->danger()
                                   ->send();
                            }
                        }elseif($record->delivery_company->name == 'OzonExpress' || $record->delivery_company->name == 'Ozon'){
                            $ozonExpressService = new OzonService();
                            $response = $ozonExpressService->sendOrder($record);
                            if($response['success']){
                                Notification::make()
                                ->title('Commande sync avec succes')
                                ->body("Commande a ete sync avec succes")
                                ->success()
                                ->send();
                                $record->update([
                                    'tracking_number' => $response['tracking_number'],
                                    'delivery_fee' => $response['delivered_price'],
                                    'sync_status' => 'synced',
                                    'sync_message' => 'Commande sync avec succes',
                                ]);
                            }else{
                                Notification::make()
                                ->title('Commande non sync avec Ozon')
                                ->body($response['message'])
                                ->danger()
                                ->send();
                            }
                        }

                           
                           
                        }
                    })
                    ->deselectRecordsAfterCompletion()
                    ->visible(fn() => Filament::auth()->user()->can('Update:Order')),
                ]),
            ])
           ;
    }
}
