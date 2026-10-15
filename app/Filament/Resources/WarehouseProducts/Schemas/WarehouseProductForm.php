<?php

namespace App\Filament\Resources\WarehouseProducts\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use App\Models\Warehouse;
use App\Models\Product;

class WarehouseProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('warehouse_id')
                    ->options(fn () => auth()->user()->hasRole('Admin') ? Warehouse::query()->pluck('name', 'id')->toArray() : auth()->user()->warehouses()->pluck('name', 'id')->toArray())
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('product_id')
                    ->options(fn () => Product::query()->pluck('sku', 'id')->toArray())
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('quantity')
                    ->required()
                    ->numeric()
                    ->minValue(0),
    Section::make('Quantités par Variantes')
    ->schema(function (\Livewire\Component $livewire) {
        $record = method_exists($livewire, 'getRecord') ? $livewire->getRecord() : null;

        if (!$record) return [];

        $record->loadMissing('product.allOptions');

        $options = $record->product?->allOptions ?? collect();
        
        if ($options->isEmpty()) return [];

        $searchComponent = TextInput::make('search_variants')
            ->label('Recherche')
            ->hiddenLabel()
            ->placeholder('Rechercher une variante par nom...')
            ->prefixIcon('heroicon-m-magnifying-glass')
            ->extraAttributes([
                'x-data' => "{
                    filter(val) {
                        val = (val || '').toLowerCase();
                        let scope = this.\$root.closest('section') || document;
                        let items = scope.querySelectorAll('[data-variant-name]');
                        items.forEach(item => {
                            let container = item.closest('.fi-fo-component-container');
                            let target = item;
                            if (container) {
                                while (target.parentElement && target.parentElement !== container) {
                                    target = target.parentElement;
                                }
                            } else {
                                target = item.parentElement;
                            }
                            
                            if (item.getAttribute('data-variant-name').includes(val)){
                                target.style.display = '';
                            } else {
                                target.style.display = 'none';
                            }
                        });
                    }
                }",
            ])
            ->extraInputAttributes([
                'x-on:input' => 'filter($el.value)'
            ])
            ->dehydrated(false)
            ->columnSpanFull();

        $variantComponents = $options->map(fn($option) =>
           TextInput::make('variant_options_data.' . $option->id . '.quantity')
                ->label(strtoupper($option->name))
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->extraFieldWrapperAttributes(['data-variant-name' => strtolower($option->name)])
        )->toArray();

        return array_merge([$searchComponent], $variantComponents);
    })
    ->columns(4)
    ->hiddenOn('create')
    ->columnSpanFull(),
            ]);
    }
}
