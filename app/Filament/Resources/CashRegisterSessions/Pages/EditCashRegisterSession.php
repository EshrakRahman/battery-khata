<?php

namespace App\Filament\Resources\CashRegisterSessions\Pages;

use App\Exceptions\InvalidDenominationsTotalException;
use App\Filament\Resources\CashRegisterSessions\CashRegisterSessionResource;
use App\Services\CashSessionService;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Validation\ValidationException;

class EditCashRegisterSession extends EditRecord
{
    protected static string $resource = CashRegisterSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('close_session')
                ->label(__('Close Session'))
                ->color('success')
                ->visible(fn ($record) => $record && $record->closed_at === null)
                ->form([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('denominations.1000')->label('1000 ৳')->numeric()->default(0)->minValue(0),
                            TextInput::make('denominations.500')->label('500 ৳')->numeric()->default(0)->minValue(0),
                            TextInput::make('denominations.200')->label('200 ৳')->numeric()->default(0)->minValue(0),
                            TextInput::make('denominations.100')->label('100 ৳')->numeric()->default(0)->minValue(0),
                            TextInput::make('denominations.50')->label('50 ৳')->numeric()->default(0)->minValue(0),
                            TextInput::make('denominations.20')->label('20 ৳')->numeric()->default(0)->minValue(0),
                            TextInput::make('denominations.10')->label('10 ৳')->numeric()->default(0)->minValue(0),
                            TextInput::make('denominations.5')->label('5 ৳')->numeric()->default(0)->minValue(0),
                            TextInput::make('denominations.2')->label('2 ৳')->numeric()->default(0)->minValue(0),
                            TextInput::make('denominations.1')->label('1 ৳')->numeric()->default(0)->minValue(0),
                        ]),
                    TextInput::make('closing_cash')
                        ->label(__('Declared Closing Cash'))
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->prefix('৳')
                        ->rules([
                            fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                $denominations = $get('denominations') ?? [];
                                $denomSum = '0.00';
                                foreach ($denominations as $note => $qty) {
                                    $lineTotal = bcmul((string) $note, (string) (int) $qty, 2);
                                    $denomSum = bcadd($denomSum, $lineTotal, 2);
                                }
                                if (bccomp($denomSum, (string) $value, 2) !== 0) {
                                    $fail(__('The sum of note denominations (:denomSum) does not match the declared closing cash (:closingCash).', [
                                        'denomSum' => number_format((float) $denomSum, 2),
                                        'closingCash' => number_format((float) $value, 2),
                                    ]));
                                }
                            },
                        ]),
                    Textarea::make('closing_notes')
                        ->label(__('Closing Notes'))
                        ->columnSpanFull(),
                ])
                ->action(function ($record, array $data) {
                    $service = app(CashSessionService::class);

                    try {
                        $denominations = collect($data['denominations'] ?? [])
                            ->map(fn ($qty) => (int) $qty)
                            ->filter(fn ($qty) => $qty > 0)
                            ->toArray();

                        $service->closeSession(
                            session: $record,
                            closingCash: $data['closing_cash'],
                            denominations: $denominations,
                            notes: $data['closing_notes'] ?? null
                        );

                        Notification::make()
                            ->title(__('Session Closed'))
                            ->success()
                            ->send();
                    } catch (InvalidDenominationsTotalException $e) {
                        Notification::make()
                            ->title(__('Declined/Invalid Denominations'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();

                        throw ValidationException::withMessages([
                            'closing_cash' => $e->getMessage(),
                        ]);
                    }
                }),
        ];
    }

    protected function getFormActions(): array
    {
        if ($this->record && $this->record->closed_at !== null) {
            return [];
        }

        return parent::getFormActions();
    }
}
