<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Order;
use Carbon\Carbon;

class ConfirmationRate extends ChartWidget
{
    protected ?string $heading = 'Confirmation Rate';

    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public ?string $filterBy = 'all';
    public ?string $productId = null;
    public ?string $confirmerId = null;

    protected function getData(): array
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

        $total = (clone $query)->count();

        $confirmed = (clone $query)->whereHas('status', function ($q) {
            $q->where('name', 'like', '%confirmer%')
              ->orWhere('name', 'like', '%confirmé%')
              ->orWhere('name', 'like', '%confirmed%');
        })->count();

        $notConfirmed = $total - $confirmed;

        return [
            'datasets' => [
                [
                    'data'            => [$confirmed, $notConfirmed],
                    'backgroundColor' => ['#22c55e', '#ef4444'],
                ],
            ],
            'labels' => ['Confirmées', 'Non confirmées'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
     public static function canView(): bool
    {
        // Only allow users with 'admin' role to see this widget
        return auth()->user()->hasRole('Admin') || auth()->user()->hasRole('Confirmation');
    }
}