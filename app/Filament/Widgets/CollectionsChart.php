<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\Payment;
use Filament\Widgets\ChartWidget as BaseWidget;

class CollectionsChart extends BaseWidget
{
    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    public ?string $filter = '30';

    public static function canView(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::Admin, UserRole::Manager]);
    }

    public function getHeading(): string
    {
        $days = $this->filter ?? '30';

        return __('Collections vs Sales')." (Last {$days} Days)";
    }

    protected function getFilters(): ?array
    {
        return [
            '7' => __('Last 7 Days'),
            '30' => __('Last 30 Days'),
            '90' => __('Last 90 Days'),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $days = (int) ($this->filter ?? '30');
        $startDate = now()->subDays($days - 1)->startOfDay();

        $sales = Invoice::selectRaw('CAST(invoice_date AS DATE) as date, SUM(grand_total) as total')
            ->where('invoice_date', '>=', $startDate)
            ->groupBy('date')
            ->pluck('total', 'date');

        $collections = Payment::selectRaw('CAST(payment_date AS DATE) as date, SUM(total_amount) as total')
            ->where('payment_date', '>=', $startDate)
            ->groupBy('date')
            ->pluck('total', 'date');

        $salesData = [];
        $collectionsData = [];
        $labels = [];

        for ($i = $days - 1; $i >= 0; $i--) {
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
