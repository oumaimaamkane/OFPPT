<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use App\Models\Product;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Auth;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\ConfirmationStatusWidget;
use App\Filament\Widgets\DeliveryStatusWidget;
use App\Filament\Widgets\MonthlySalesTrendWidget;
use App\Filament\Widgets\OrdersChartWidget;
use App\Filament\Widgets\ProductStatsWidget;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;
   public static function canView(): bool
    {
        // Only allow users with 'admin' role to see this widget
        return Auth::user()->hasRole('Admin');
    }
    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->heading('Filter')
                    ->schema([
                        DatePicker::make('dateFrom')
                ->label('From')
                ->default(now()->startOfYear())
                ->native(false)
                ->displayFormat('Y-m-d')
                ->maxDate(fn ($get) => $get('dateTo') ?? now()),

            DatePicker::make('dateTo')
                ->label('To')
                ->default(now()->endOfYear())
                ->native(false)
                ->displayFormat('Y-m-d')
                ->minDate(fn ($get) => $get('dateFrom')),

            // --- Filter type ---
            Select::make('filterBy')
                ->label('Filter by')
                ->options(auth()->user()->hasRole('Admin') ? [
                    'all'      => 'All',
                    'product'  => 'Products',
                    'confirmer'   => 'Confirmer',
                ] : [
                    'all'      => 'All',
                    'product'  => 'Products',
                ])
                ->default('all')
                ->live(),

            // --- Product selector (shown when filterBy = product) ---
            Select::make('productId')
                ->label('Product')
                ->options(fn () => Product::query()->pluck('sku', 'id')->toArray())
                ->placeholder('All products')
                ->visible(fn ($get) => $get('filterBy') === 'product')
                ->live()
                ->searchable(),

                Select::make('confirmerId')
                ->label('Confirmers')
                ->options(function () {
                    return \App\Models\User::whereHas('roles', fn ($q) =>
                        $q->where('name', 'Confirmation')
                    )->pluck('name', 'id');
                })
                ->placeholder('All confirmers')
                ->visible(fn ($get) => $get('filterBy') === 'confirmer')
                ->live()
                ->searchable(),
                    ])
                    ->columnSpanFull()
                    ->columns(3)
                    ->collapsible(),
            ]);
    }
     public function getWidgets(): array
    {
        return [
            StatsOverview::class,
            ConfirmationStatusWidget::class,
            DeliveryStatusWidget::class,
            MonthlySalesTrendWidget::class,
            OrdersChartWidget::class,
            // MonthlyBreakdownWidget::class,
            ProductStatsWidget::class,
        ];
    }

    public function getColumns(): array|int
    {
        return [
            'sm'  => 1,
            'md'  => 2,
            'xl'  => 3,
        ];
    }
}
