<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Models\CustomerLedger;
use App\Models\Invoice;
use App\Models\NotificationLog;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class BusinessOverviewStats extends BaseWidget
{
    protected ?string $pollingInterval = '30s';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::Admin, UserRole::Manager]);
    }

    protected function getStats(): array
    {
        // 1. Weekly Collections
        $thisWeekCollections = (float) Payment::whereBetween('payment_date', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ])->sum('total_amount');

        $lastWeekCollections = (float) Payment::whereBetween('payment_date', [
            now()->subWeek()->startOfWeek(),
            now()->subWeek()->endOfWeek(),
        ])->sum('total_amount');

        $thisWeekSales = (float) Invoice::whereBetween('invoice_date', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ])->sum('grand_total');

        // 2. Monthly Collections
        $thisMonthCollections = (float) Payment::whereBetween('payment_date', [
            now()->startOfMonth(),
            now()->endOfMonth(),
        ])->sum('total_amount');

        $thisMonthSales = (float) Invoice::whereBetween('invoice_date', [
            now()->startOfMonth(),
            now()->endOfMonth(),
        ])->sum('grand_total');

        // 3. Outstanding Customer Dues
        $subquery = CustomerLedger::select('customer_id', 'running_balance')
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('customer_ledgers')
                    ->groupBy('customer_id');
            });

        $totalDue = (float) DB::table(DB::raw("({$subquery->toSql()}) as latest_ledgers"))
            ->mergeBindings($subquery->getQuery())
            ->where('running_balance', '>', 0)
            ->sum('running_balance');

        $dueCustomersCount = DB::table(DB::raw("({$subquery->toSql()}) as latest_ledgers"))
            ->mergeBindings($subquery->getQuery())
            ->where('running_balance', '>', 0)
            ->count();

        // 4. SMS logs for current day and month
        $smsToday = NotificationLog::where('notification_type', 'SMS')
            ->whereDate('created_at', now()->today())
            ->count();

        $smsThisMonth = NotificationLog::where('notification_type', 'SMS')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $stats = [
            Stat::make(__('Weekly Collections'), number_format($thisWeekCollections, 2).' BDT')
                ->description(__('Invoiced: ').number_format($thisWeekSales, 2).' BDT')
                ->descriptionIcon($thisWeekCollections >= $lastWeekCollections ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($thisWeekCollections >= $lastWeekCollections ? 'success' : 'warning')
                ->icon('heroicon-m-banknotes'),

            Stat::make(__('Monthly Collections'), number_format($thisMonthCollections, 2).' BDT')
                ->description(__('Invoiced: ').number_format($thisMonthSales, 2).' BDT')
                ->color('info')
                ->icon('heroicon-m-presentation-chart-line'),

            Stat::make(__('Total Outstanding Dues'), number_format($totalDue, 2).' BDT')
                ->description($dueCustomersCount.' '.__('customers with unpaid balance'))
                ->color('danger')
                ->icon('heroicon-m-user-group'),
        ];

        // Conditional display based on user roles
        $user = auth()->user();
        if ($user && in_array($user->role, [UserRole::Admin, UserRole::Manager])) {
            $stats[] = Stat::make(__('SMS Sent Today'), $smsToday)
                ->description(__('Sent this month: ').$smsThisMonth)
                ->color('primary')
                ->icon('heroicon-m-chat-bubble-oval-left');
        }

        return $stats;
    }
}
