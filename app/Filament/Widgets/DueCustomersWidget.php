<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Services\SmsService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DueCustomersWidget extends BaseWidget
{
    protected ?string $pollingInterval = '60s';

    public function getHeading(): string
    {
        return __('Due Customers & SMS Reminders');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Customer::query()
                    ->select('customers.*')
                    ->selectSub(
                        CustomerLedger::select('running_balance')
                            ->whereColumn('customer_ledgers.customer_id', 'customers.id')
                            ->orderByDesc('customer_ledgers.id')
                            ->limit(1),
                        'running_balance'
                    )
                    ->selectSub(
                        CustomerLedger::select('transaction_date')
                            ->whereColumn('customer_ledgers.customer_id', 'customers.id')
                            ->orderByDesc('customer_ledgers.id')
                            ->limit(1),
                        'last_transaction_date'
                    )
                    ->whereRaw('(SELECT running_balance FROM customer_ledgers WHERE customer_ledgers.customer_id = customers.id ORDER BY id DESC LIMIT 1) > 0')
            )
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('mobile')
                    ->label(__('Mobile'))
                    ->searchable(),

                TextColumn::make('customer_type')
                    ->label(__('Customer Type'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Dealer' => 'info',
                        'Garage' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('running_balance')
                    ->label(__('Outstanding Due'))
                    ->money('BDT')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderByRaw('(SELECT running_balance FROM customer_ledgers WHERE customer_ledgers.customer_id = customers.id ORDER BY id DESC LIMIT 1) '.$direction);
                    }),

                TextColumn::make('last_transaction_date')
                    ->label(__('Last Transaction Date'))
                    ->dateTime()
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderByRaw('(SELECT transaction_date FROM customer_ledgers WHERE customer_ledgers.customer_id = customers.id ORDER BY id DESC LIMIT 1) '.$direction);
                    }),
            ])
            ->actions([
                Action::make('sendSMS')
                    ->label(__('Send SMS'))
                    ->icon('heroicon-m-paper-airplane')
                    ->color('primary')
                    ->form([
                        Textarea::make('message')
                            ->label(__('SMS Message'))
                            ->required()
                            ->default(fn ($record) => __('Dear ').$record->name.__(' (').$record->mobile.__('), you have an outstanding due of BDT ').number_format($record->running_balance, 2).__('. Please clear your dues at the earliest. Thank you!')),
                    ])
                    ->action(function ($record, array $data) {
                        $smsService = app(SmsService::class);
                        $smsService->send($record->mobile, $data['message']);

                        Notification::make()
                            ->title(__('SMS Reminder Sent'))
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkAction::make('sendBulkSMS')
                    ->label(__('Send Bulk SMS'))
                    ->icon('heroicon-m-chat-bubble-left-right')
                    ->color('primary')
                    ->action(function (Collection $records) {
                        $smsService = app(SmsService::class);
                        $records->each(function ($record) use ($smsService) {
                            $message = __('Dear ').$record->name.__(' (').$record->mobile.__('), you have an outstanding due of BDT ').number_format($record->running_balance, 2).__('. Please clear your dues at the earliest. Thank you!');
                            $smsService->send($record->mobile, $message);
                        });

                        Notification::make()
                            ->title(__('Bulk SMS Reminders Sent'))
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
