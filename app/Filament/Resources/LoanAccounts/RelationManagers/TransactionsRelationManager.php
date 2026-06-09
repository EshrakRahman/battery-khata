<?php

namespace App\Filament\Resources\LoanAccounts\RelationManagers;

use App\Enums\PaymentMethod;
use App\Services\CashSessionService;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('transaction_date')
                    ->label(__('Transaction Date'))
                    ->required()
                    ->default(today()),

                Select::make('transaction_type')
                    ->label(__('Transaction Type'))
                    ->options([
                        'Disbursement' => __('Disbursement'),
                        'Repayment' => __('Repayment'),
                    ])
                    ->required()
                    ->live(),

                TextInput::make('amount')
                    ->label(__('Amount'))
                    ->numeric()
                    ->required()
                    ->rules(function (Get $get) {
                        return [
                            function ($attribute, $value, $fail) use ($get) {
                                $type = $get('transaction_type');
                                if ($type === 'Repayment') {
                                    $outstanding = $this->getOwnerRecord()->outstanding_balance;
                                    if ($value > $outstanding) {
                                        $fail(__('The repayment amount cannot exceed the outstanding balance of :amount BDT.', ['amount' => $outstanding]));
                                    }
                                }
                            },
                        ];
                    }),

                Select::make('payment_method')
                    ->label(__('Payment Method'))
                    ->options([
                        PaymentMethod::Cash->value => __('Cash'),
                        PaymentMethod::Bkash->value => __('Bkash'),
                        PaymentMethod::Nagad->value => __('Nagad'),
                        PaymentMethod::Rocket->value => __('Rocket'),
                        PaymentMethod::Bank->value => __('Bank'),
                    ])
                    ->required()
                    ->default(PaymentMethod::Cash->value),

                Textarea::make('notes')
                    ->label(__('Notes'))
                    ->columnSpanFull()
                    ->rows(2)
                    ->maxLength(65535),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('amount')
            ->columns([
                TextColumn::make('transaction_date')
                    ->label(__('Transaction Date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('transaction_type')
                    ->label(__('Transaction Type'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Disbursement' => 'success',
                        'Repayment' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => __($state))
                    ->sortable(),

                TextColumn::make('amount')
                    ->label(__('Amount'))
                    ->money('BDT')
                    ->sortable(),

                TextColumn::make('notes')
                    ->label(__('Notes'))
                    ->limit(50),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->before(function (CreateAction $action) {
                        $activeSession = app(CashSessionService::class)->getActiveSession();
                        if (! $activeSession) {
                            Notification::make()
                                ->title(__('Active Cash Register Session Required'))
                                ->body(__('An active cash register session is required to perform loan transactions.'))
                                ->danger()
                                ->send();

                            $action->halt();
                        }
                    })
                    ->using(function (array $data, string $model): Model {
                        $paymentMethod = $data['payment_method'] ?? null;

                        // Create transaction instance (without payment_method column in DB)
                        $transaction = new $model([
                            'loan_account_id' => $this->getOwnerRecord()->id,
                            'transaction_date' => $data['transaction_date'],
                            'transaction_type' => $data['transaction_type'],
                            'amount' => $data['amount'],
                            'notes' => $data['notes'] ?? null,
                        ]);

                        if ($paymentMethod) {
                            $transaction->payment_method = PaymentMethod::tryFrom($paymentMethod);
                        }

                        $transaction->save();

                        return $transaction;
                    }),
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->defaultSort('transaction_date', 'desc');
    }
}
