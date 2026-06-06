<?php

namespace App\Filament\Resources\CashRegisterSessions\Schemas;

use App\Enums\PaymentMethod;
use App\Models\CashbookEntry;
use App\Models\CashRegisterSession;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class CashRegisterSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Cash Register Session'))
                    ->schema(function (string $context, ?CashRegisterSession $record) {
                        if ($context === 'create') {
                            return [
                                TextInput::make('opening_cash')
                                    ->label(__('Opening Cash'))
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->default(0.00)
                                    ->prefix('৳'),
                                Textarea::make('notes')
                                    ->label(__('Notes'))
                                    ->columnSpanFull(),
                            ];
                        }

                        $isClosed = $record && $record->closed_at !== null;

                        return [
                            Grid::make(2)
                                ->schema([
                                    Placeholder::make('opened_by')
                                        ->label(__('Opened By'))
                                        ->content($record?->creator?->name ?? '-'),
                                    DateTimePicker::make('opened_at')
                                        ->label(__('Opened At'))
                                        ->disabled(),
                                    DateTimePicker::make('closed_at')
                                        ->label(__('Closed At'))
                                        ->visible($isClosed)
                                        ->disabled(),
                                    TextInput::make('opening_cash')
                                        ->label(__('Opening Cash'))
                                        ->prefix('৳')
                                        ->disabled(),
                                    Placeholder::make('expected_cash_placeholder')
                                        ->label(__('Expected Cash'))
                                        ->content(function () use ($record, $isClosed) {
                                            if (! $record) {
                                                return '0.00 ৳';
                                            }
                                            if ($isClosed) {
                                                return number_format($record->expected_cash, 2).' ৳';
                                            }

                                            // Dynamic expected cash
                                            $aggregates = CashbookEntry::query()
                                                ->where('cash_register_session_id', $record->id)
                                                ->where('payment_method', PaymentMethod::Cash)
                                                ->selectRaw("
                                                    SUM(CASE WHEN LOWER(direction) = 'in' THEN amount ELSE 0 END) as inflow,
                                                    SUM(CASE WHEN LOWER(direction) = 'out' THEN amount ELSE 0 END) as outflow
                                                ")
                                                ->first();
                                            $inflow = $aggregates?->inflow ?? '0.00';
                                            $outflow = $aggregates?->outflow ?? '0.00';
                                            $liveExpected = bcsub(bcadd((string) $record->opening_cash, (string) $inflow, 2), (string) $outflow, 2);

                                            return number_format((float) $liveExpected, 2).' ৳';
                                        }),
                                    TextInput::make('closing_cash')
                                        ->label(__('Closing Cash'))
                                        ->prefix('৳')
                                        ->visible($isClosed)
                                        ->disabled(),
                                    TextInput::make('shortage_excess')
                                        ->label(__('Shortage / Excess'))
                                        ->prefix('৳')
                                        ->visible($isClosed)
                                        ->disabled(),
                                    Textarea::make('notes')
                                        ->label(__('Notes'))
                                        ->disabled($isClosed)
                                        ->columnSpanFull(),
                                    Placeholder::make('recorded_denominations')
                                        ->label(__('Denominations'))
                                        ->visible($isClosed)
                                        ->columnSpanFull()
                                        ->content(function () use ($record) {
                                            if (! $record || ! $record->denominations) {
                                                return '-';
                                            }
                                            $lines = [];
                                            foreach ($record->denominations as $note => $qty) {
                                                if ($qty > 0) {
                                                    $lines[] = "{$note} ৳ x {$qty} = ".number_format($note * $qty, 2).' ৳';
                                                }
                                            }

                                            return count($lines) > 0 ? new HtmlString(implode('<br>', $lines)) : '-';
                                        }),
                                ]),
                        ];
                    }),
            ]);
    }
}
