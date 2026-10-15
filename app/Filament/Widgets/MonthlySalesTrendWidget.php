<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Order;
use Carbon\Carbon;

class MonthlySalesTrendWidget extends ChartWidget
{
    protected ?string $heading = 'Monthly Sales Trend';
    protected static ?int $sort = 5;

    public ?string $dateFrom    = null;
    public ?string $dateTo      = null;
    public ?string $filterBy    = 'all';
    public ?string $productId   = null;
    public ?string $confirmerId = null;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $from = $this->dateFrom ? Carbon::parse($this->dateFrom) : now()->startOfYear();
        $to   = $this->dateTo   ? Carbon::parse($this->dateTo)->endOfDay() : now()->endOfYear();

        $months   = [];
        $profits  = [];
        $revenues = [];
        $costs    = [];

        $cursor = $from->copy()->startOfMonth();

        while ($cursor->lte($to->copy()->startOfMonth())) {
            $months[] = $cursor->format('M Y');

            $query = Order::query()
                ->whereYear('created_at', $cursor->year)
                ->whereMonth('created_at', $cursor->month)
                ->whereNull('deleted_at');

            if ($this->filterBy === 'product' && $this->productId) {
                $query->whereHas('products', function ($q) {
                    $q->where('product_id', $this->productId);
                });
            }

            if ($this->filterBy === 'confirmer' && $this->confirmerId) {
                $query->where('assign_to', $this->confirmerId);
            }

            $rev  = (clone $query)->sum('price');
            $cost = (clone $query)->sum('delivery_fee');

            $revenues[] = round($rev, 2);
            $costs[]    = round($cost, 2);
            $profits[]  = round($rev - $cost, 2);

            $cursor->addMonth();
        }

        return [
            'datasets' => [
                [
                    'label'       => 'Profit',
                    'data'        => $profits,
                    'borderColor' => '#378ADD',
                    'tension'     => 0.4,
                    'pointRadius' => 2,
                ],
                [
                    'label'       => 'Revenue',
                    'data'        => $revenues,
                    'borderColor' => '#639922',
                    'tension'     => 0.4,
                    'pointRadius' => 2,
                ],
                [
                    'label'       => 'Costs',
                    'data'        => $costs,
                    'borderColor' => '#EF9F27',
                    'tension'     => 0.4,
                    'pointRadius' => 2,
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => true, 'position' => 'bottom'],
            ],
            'scales' => [
                'y' => ['beginAtZero' => true],
            ],
        ];
    }
     public static function canView(): bool
    {
        // Only allow users with 'admin' role to see this widget
        return auth()->user()->hasRole('Admin') ;
    }
}