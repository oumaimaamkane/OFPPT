<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Product;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Validation\Rules\Unique;
class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
        ->components([
            Group::make()
                ->schema([
                    Section::make()
                        ->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255),

                            RichEditor::make('description')
                                ->columnSpan('full'),
                        ])
                        ->columns(2),

                    Section::make('Images')
                        ->schema([
                            SpatieMediaLibraryFileUpload::make('media')
                                ->collection('product-images')
                                ->multiple()
                                ->required()
                                ->maxFiles(5)
                                ->reorderable()
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                                ->hiddenLabel(),
                        ])
                        ->collapsible(),

                    Section::make('Product Options & Variants')
                        ->schema([
                            Repeater::make('variants')
                            ->relationship('variants')
                                ->schema([
                                    TextInput::make('name')
                                        ->label('Option')
                                        ->placeholder('e.g. size, color')
                                        ->required(),

                                    TagsInput::make('values')
                                        ->label('Value')
                                        ->placeholder('Type and press Enter')
                                        ,
                                ])
                                ->columns(2)
                                ->reorderable()
                                ->collapsible()
                                ->live()           
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    if (blank($state)) {
                                        $set('options', []);
                                        return;
                                    }

                                    $combinations = [[]];

                                    foreach ($state as $option) {
                                        $values = $option['values'] ?? [];
                                        if (empty($values)) continue;

                                        $next = [];
                                        foreach ($combinations as $combo) {
                                            foreach ($values as $value) {
                                                $next[] = array_merge($combo, [$value]);
                                            }
                                        }
                                        $combinations = $next;
                                    }

                                    if ($combinations === [[]]) {
                                        $set('options', []);
                                        return;
                                    }

                                    $set('options', collect($combinations)
                                        ->map(fn($combo) => [
                                            'name'            => implode(' / ', $combo),
                                            'price'            => null,
                                            'quantity'            => null,
                                        ])
                                        ->values()
                                        ->all()
                                    );
                                    // dd($get('options'));
                                }),

                            Repeater::make('options')
                            // ->relationship('allOptions')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Variant')
                                    ->disabled()
                                    ->dehydrated(),

                                TextInput::make('price')
                                    ->label('Price')
                                    ->numeric()
                                    ->prefix('MAD'),


                                TextInput::make('quantity')
                                    ->label('Stock'),

                            ])
                            ->columns(3)
                            ->addable(false)
                            ->deletable(false),
                                

                           
                        ]),

                ])
                ->columnSpan(['lg' => 2]),

            Group::make()
                ->schema([
                    Section::make('Pricing')
                        ->schema([
                            TextInput::make('price')
                                ->numeric()
                                ->minValue(0)    
                                ->maxValue(99999999.99)
                                ->rules(['regex:/^\d{1,6}(\.\d{0,2})?$/'])
                                ->prefix('MAD')
                                ->required(),
                        ])
                        ->columns(2),

                    Section::make('Inventory')
                        ->schema([
                            TextInput::make('sku')
                                ->label('SKU (Stock Keeping Unit)')
                                ->unique(Product::class, 'sku', ignoreRecord: true , modifyRuleUsing: fn (Unique $rule) => $rule->whereNull('deleted_at'))
                                ->maxLength(255)
                                ->required(),

                            TextInput::make('warehouse_qty')
                                ->label('Quantity')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(18446744073709551615)
                                ->integer()
                                ->required(),
                        ]),
                ])
                ->columnSpan(['lg' => 1]),

        ])
        ->columns(3);
    }
}
