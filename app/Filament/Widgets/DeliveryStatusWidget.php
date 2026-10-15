<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Order;
use Carbon\Carbon;

class DeliveryStatusWidget extends ChartWidget
{
    protected ?string $heading = 'Delivery Status';
    protected static ?int $sort = 4;

    public ?string $dateFrom    = null;
    public ?string $dateTo      = null;
    public ?string $filterBy    = 'all';
    public ?string $productId   = null;
    public ?string $confirmerId = null;

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
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

        $returned = (clone $query)
            ->whereHas('delivery_status', function ($q) {
                $q->where('name', 'like', '%returned%')
                  ->orWhere('name', 'like', '%retourné%')
                  ->orWhere('name', 'like', '%retour%');
            })->count();

        $inTransit = (clone $query)
            ->whereHas('delivery_status', function ($q) {
                $q->where('name', 'like', '%in_transit%')
                  ->orWhere('name', 'like', '%en transit%')
                  ->orWhere('name', 'like', '%en cours%');
            })->count();

        return [
            'datasets' => [
                [
                    'data'            => [$delivered, $returned, $inTransit],
                    'backgroundColor' => ['#639922', '#D85A30', '#EF9F27'],
                    'borderWidth'     => 0,
                ],
            ],
            'labels' => ['Livré', 'Retourné', 'En transit'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['position' => 'bottom'],
            ],
            'cutout' => '65%',
        ];
    }
}