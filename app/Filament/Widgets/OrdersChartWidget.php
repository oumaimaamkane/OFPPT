<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Order;
use Carbon\Carbon;

class OrdersChartWidget extends ChartWidget
{
    protected ?string $heading = 'Orders Chart';
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';

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
        $from = $this->dateFrom ? Carbon::parse($this->dateFrom) : now()->subMonths(11)->startOfMonth();
        $to   = $this->dateTo   ? Carbon::parse($this->dateTo)->endOfDay() : now()->endOfMonth();

        $months = [];
        $cursor = $from->copy()->startOfMonth();
        while ($cursor->lte($to->copy()->startOfMonth())) {
            $months[] = $cursor->copy();
            $cursor->addMonth();
        }

        $labels      = [];
        $todayOrders = [];
        $totalOrders = [];
        $confirmed   = [];
        $delivered   = [];
        $returned    = [];

        foreach ($months as $month) {
            $labels[] = $month->format('M Y');

            $base = Order::query()
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->whereNull('deleted_at');

            if ($this->filterBy === 'product' && $this->productId) {
                $base->whereHas('products', function ($q) {
                    $q->where('product_id', $this->productId);
                });
            }

            if ($this->filterBy === 'confirmer' && $this->confirmerId) {
                $base->where('assign_to', $this->confirmerId);
            }

            $todayOrders[] = (clone $base)
                ->whereDate('created_at', today())
                ->count();

            $totalOrders[] = (clone $base)->count();

            $confirmed[] = (clone $base)
                ->whereHas('status', function ($q) {
                    $q->where('name', 'like', '%confirmed%')
                      ->orWhere('name', 'like', '%confirmé%')
                      ->orWhere('name', 'like', '%confirmer%');
                })->count();

            $delivered[] = (clone $base)
                ->whereHas('delivery_status', function ($q) {
                    $q->where('name', 'like', '%delivered%')
                      ->orWhere('name', 'like', '%livré%')
                      ->orWhere('name', 'like', '%livrer%');
                })->count();

            $returned[] = (clone $base)
                ->whereHas('delivery_status', function ($q) {
                    $q->where('name', 'like', '%returned%')
                      ->orWhere('name', 'like', '%retourné%')
                      ->orWhere('name', 'like', '%retour%');
                })->count();
        }

        return [
            'datasets' => [
                [
                    'label'           => "Commandes d'aujourd'hui",
                    'data'            => $todayOrders,
                    'borderColor'     => '#378ADD',
                    'backgroundColor' => 'rgba(55,138,221,0.08)',
                    'tension'         => 0.4,
                    'pointRadius'     => 3,
                    'fill'            => true,
                ],
                [
                    'label'       => 'Total des commandes',
                    'data'        => $totalOrders,
                    'borderColor' => '#D85A30',
                    'tension'     => 0.4,
                    'pointRadius' => 3,
                ],
                [
                    'label'       => 'Confirmé',
                    'data'        => $confirmed,
                    'borderColor' => '#639922',
                    'tension'     => 0.4,
                    'pointRadius' => 3,
                ],
                [
                    'label'       => 'Livré',
                    'data'        => $delivered,
                    'borderColor' => '#EF9F27',
                    'tension'     => 0.4,
                    'pointRadius' => 3,
                ],
                [
                    'label'       => 'Retourné',
                    'data'        => $returned,
                    'borderColor' => '#888780',
                    'tension'     => 0.4,
                    'pointRadius' => 3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => true, 'position' => 'top'],
            ],
            'scales' => [
                'y' => ['beginAtZero' => true],
            ],
        ];
    }
}