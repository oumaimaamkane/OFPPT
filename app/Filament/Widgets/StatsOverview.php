<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Order;
use Carbon\Carbon;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public ?string $filterBy = 'all';
    public ?string $productId = null;
    public ?string $confirmerId = null;

    protected function getStats(): array
    {
        $from = $this->dateFrom ? Carbon::parse($this->dateFrom) : now()->startOfYear();
        $to   = $this->dateTo   ? Carbon::parse($this->dateTo)->endOfDay() : now()->endOfYear();

        $query = Order::query()
            ->whereBetween('created_at', [$from, $to])
            ->whereNull('deleted_at');

        // Filter by product
        if ($this->filterBy === 'product' && $this->productId) {
            $query->whereHas('products', function ($q) {
                $q->where('product_id', $this->productId);
            });
        }

        // Filter by confirmer
        if ($this->filterBy === 'confirmer' && $this->confirmerId) {
            $query->where('assign_to', $this->confirmerId);
        }

        $totalOrders = (clone $query)
        ->when(auth()->user()->hasRole('Confirmation'), function ($q) {
            $q->where('assign_to', auth()->id());
        })
        ->count();

        $todayOrders = Order::whereDate('created_at', today())
        ->when(auth()->user()->hasRole('Confirmation'), function ($q) {
            $q->where('assign_to', auth()->id());
        })
            ->whereNull('deleted_at')
            ->count();

        $totalCost = (clone $query)->sum('delivery_fee');
        $revenue   = (clone $query)->sum('price');
        $profit    = $revenue - $totalCost;

        $expedition = (clone $query)
            ->whereNotNull('delivery_status_id')
            ->count();

        $returned = (clone $query)
            ->whereHas('delivery_status', function ($q) {
                $q->where('name', 'like', '%returned%')
                  ->orWhere('name', 'like', '%retour%');
            })->count();

        $confirmed = (clone $query)
            ->whereHas('status', function ($q) {
                $q->where('name', 'like', '%confirmer%')
                  ->orWhere('name', 'like', '%confirmé%');
            })->count();

        $delivered = (clone $query)
            ->whereHas('delivery_status', function ($q) {
                $q->where('name', 'like', '%livrer%')
                  ->orWhere('name', 'like', '%livré%');
            })->count();

        $confirmRate  = $totalOrders > 0 ? round(($confirmed / $totalOrders) * 100) : 0;
        $deliveryRate = $totalOrders > 0 ? round(($delivered / $totalOrders) * 100) : 0;
        $returnRate   = $totalOrders > 0 ? round(($returned  / $totalOrders) * 100) : 0;

        $stats = auth()->user()->hasRole('Admin') ? [
            Stat::make('Commandes du jour', $todayOrders)
                ->description("Commandes passées aujourd'hui")
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('info'),

            Stat::make('Total des commandes', number_format($totalOrders))
                ->description('Dans la période sélectionnée')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('gray'),

            Stat::make('Total des coûts', number_format($totalCost) . ' dh')
                ->description('Toutes les dépenses')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('danger'),

            Stat::make('Revenu', number_format($revenue) . ' dh')
                ->description('Revenu brut')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Profit', number_format($profit) . ' dh')
                ->description('Revenu - Coût')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color($profit >= 0 ? 'success' : 'danger'),

            Stat::make('Total Expedition', number_format($expedition))
                ->description('Commandes expédiées')
                ->descriptionIcon('heroicon-m-truck')
                ->color('info'),

            Stat::make('Retournées', number_format($returned))
                ->description($returnRate . '% taux de retour')
                ->descriptionIcon('heroicon-m-arrow-uturn-left')
                ->color('danger'),

            Stat::make('Confirmées', number_format($confirmed))
                ->description($confirmRate . '% taux de confirmation')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Livraison', number_format($delivered))
                ->description($deliveryRate . '% taux de livraison')
                ->descriptionIcon('heroicon-m-archive-box-arrow-down')
                ->color('info'),
        ] : ( auth()->user()->hasRole('Confirmation') ? [
            Stat::make('Commandes du jour', $todayOrders)
                ->description("Commandes passées aujourd'hui")
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('info'),

            Stat::make('Total des commandes', number_format($totalOrders))
                ->description('Dans la période sélectionnée')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('gray'),

         

            Stat::make('Total Expedition', number_format($expedition))
                ->description('Commandes expédiées')
                ->descriptionIcon('heroicon-m-truck')
                ->color('info'),

            Stat::make('Retournées', number_format($returned))
                ->description($returnRate . '% taux de retour')
                ->descriptionIcon('heroicon-m-arrow-uturn-left')
                ->color('danger'),

            Stat::make('Confirmées', number_format($confirmed))
                ->description($confirmRate . '% taux de confirmation')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Livraison', number_format($delivered))
                ->description($deliveryRate . '% taux de livraison')
                ->descriptionIcon('heroicon-m-archive-box-arrow-down')
                ->color('info'),
        ] : [
             Stat::make('Commandes du jour', $todayOrders)
                ->description("Commandes passées aujourd'hui")
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('info'),

            Stat::make('Total des commandes', number_format($totalOrders))
                ->description('Dans la période sélectionnée')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('gray'),
                 Stat::make('Total Expedition', number_format($expedition))
                ->description('Commandes expédiées')
                ->descriptionIcon('heroicon-m-truck')
                ->color('info'),

            Stat::make('Retournées', number_format($returned))
                ->description($returnRate . '% taux de retour')
                ->descriptionIcon('heroicon-m-arrow-uturn-left')
                ->color('danger'),

            Stat::make('Confirmées', number_format($confirmed))
                ->description($confirmRate . '% taux de confirmation')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Livraison', number_format($delivered))
                ->description($deliveryRate . '% taux de livraison')
                ->descriptionIcon('heroicon-m-archive-box-arrow-down')
                ->color('info'),
        ]);
        return $stats;
    }
}