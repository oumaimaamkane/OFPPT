<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Order;
use Carbon\Carbon;

class ConfirmationStatusWidget extends ChartWidget
{
    protected ?string $heading = 'Confirmation Status';
    protected static ?int $sort = 3;

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

        $confirmed = (clone $query)
            ->whereHas('status', function ($q) {
                $q->where('name', 'like', '%confirmed%')
                  ->orWhere('name', 'like', '%confirmé%')
                  ->orWhere('name', 'like', '%confirmer%');
            })->count();

        $pending = (clone $query)
            ->whereHas('status', function ($q) {
                $q->where('name', 'like', '%pending%')
                  ->orWhere('name', 'like', '%en attente%')
                  ->orWhere('name', 'like', '%en cours%');
            })->count();

        $cancelled = (clone $query)
            ->whereHas('status', function ($q) {
                $q->where('name', 'like', '%cancelled%')
                  ->orWhere('name', 'like', '%annulé%');
            })->count();

        return [
            'datasets' => [
                [
                    'data'            => [$confirmed, $pending, $cancelled],
                    'backgroundColor' => ['#22c55e', '#3b82f6', '#ef4444'],
                    'borderWidth'     => 0,
                ],
            ],
            'labels' => ['Confirmé', 'En attente', 'Annulé'],
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
      public static function canView(): bool
    {
        // Only allow users with 'admin' role to see this widget
        return auth()->user()->hasRole('Admin') || auth()->user()->hasRole('Confirmation');
    }
}