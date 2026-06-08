<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\Payment;
use Filament\Widgets\ChartWidget as BaseWidget;

class CollectionsChart extends BaseWidget
{
    protected ?string $pollingInterval = '60s';

    public function getHeading(): string
    {
        return __('Collections vs Sales (Last 30 Days)');
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $sales = Invoice::selectRaw('CAST(invoice_date AS DATE) as date, SUM(grand_total) as total')
            ->where('invoice_date', '>=', now()->subDays(29)->startOfDay())
            ->groupBy('date')
            ->pluck('total', 'date');

        $collections = Payment::selectRaw('CAST(payment_date AS DATE) as date, SUM(total_amount) as total')
            ->where('payment_date', '>=', now()->subDays(29)->startOfDay())
            ->groupBy('date')
            ->pluck('total', 'date');

        $salesData = [];
        $collectionsData = [];
        $labels = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->format('M d');

            $salesData[] = (float) ($sales->get($date) ?? 0);
            $collectionsData[] = (float) ($collections->get($date) ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => __('Sales'),
                    'data' => $salesData,
                    'borderColor' => '#f59e0b', // Amber / Gold
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'fill' => 'start',
                    'tension' => 0.3,
                ],
                [
                    'label' => __('Collections'),
                    'data' => $collectionsData,
                    'borderColor' => '#3b82f6', // Blue
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => 'start',
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
