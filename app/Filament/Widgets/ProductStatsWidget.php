<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Order;
use App\Models\Product;
use App\Models\WarehouseProduct;
use Carbon\Carbon;

class ProductStatsWidget extends StatsOverviewWidget
{
    protected ?string $heading = 'Product Analytics';
    protected static ?int $sort = 7;
    protected int|string|array $columnSpan = 'full';

    public ?string $dateFrom    = null;
    public ?string $dateTo      = null;
    public ?string $filterBy    = 'all';
    public ?string $productId   = null;
    public ?string $confirmerId = null;

    protected function getStats(): array
    {
        $from = $this->dateFrom ? Carbon::parse($this->dateFrom) : now()->startOfYear();
        $to   = $this->dateTo   ? Carbon::parse($this->dateTo)->endOfDay() : now()->endOfYear();

        $query = Order::query()
            ->whereBetween('created_at', [$from, $to])
            ->whereNull('deleted_at');

        if ($this->filterBy === 'product' && $this->productId) {
            $query->whereHas('products', function ($q) {
                $q->where('product_id', $this->productId);
            });
        }

        if ($this->filterBy === 'confirmer' && $this->confirmerId) {
            $query->where('assign_to', $this->confirmerId);
        }

        $delivered = (clone $query)
            ->whereHas('delivery_status', function ($q) {
                $q->where('name', 'like', '%delivered%')
                  ->orWhere('name', 'like', '%livré%')
                  ->orWhere('name', 'like', '%livrer%');
            })->count();

        $totalCost = (clone $query)->sum('delivery_fee');
        $revenue   = (clone $query)->sum('price');
        $profit    = $revenue - $totalCost;

        // Product stock
        $productQuery = Product::query();

        if ($this->filterBy === 'product' && $this->productId) {
            $productQuery->where('id', $this->productId);
        }

        $qtyInitial = (clone $productQuery)->sum('warehouse_qty');
        $qtyCurrent = WarehouseProduct::whereIn(
            'product_id',
            (clone $productQuery)->select('id')
        )->sum('quantity');

        $stats = auth()->user()->hasRole('Admin') ? [
        
            Stat::make('Total Delivered Orders', number_format($delivered))
                ->description('Fully delivered')
                ->descriptionIcon('heroicon-m-archive-box-arrow-down')
                ->color('info'),

            Stat::make('Total Cost', number_format($totalCost) . ' dh')
                ->description('Product expenses')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('danger'),

            Stat::make('Total Revenue', number_format($revenue) . ' dh')
                ->description('Gross income')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Profit', number_format($profit) . ' dh')
                ->description('Net profit')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color($profit >= 0 ? 'success' : 'danger'),

            Stat::make('Initial Quantity', number_format($qtyInitial))
                ->description('Starting stock')
                ->descriptionIcon('heroicon-m-cube')
                ->color('gray'),

            Stat::make('Current Quantity', number_format($qtyCurrent))
                ->description('Stock remaining')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('gray'),
        ] : [
            Stat::make('Total Delivered Orders', number_format($delivered))
                ->description('Fully delivered')
                ->descriptionIcon('heroicon-m-archive-box-arrow-down')
                ->color('info'),

           
            Stat::make('Initial Quantity', number_format($qtyInitial))
                ->description('Starting stock')
                ->descriptionIcon('heroicon-m-cube')
                ->color('gray'),

            Stat::make('Current Quantity', number_format($qtyCurrent))
                ->description('Stock remaining')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('gray'),
        ];
        return $stats;
    }
}