<?php

namespace App\Filament\Resources\LoanAccounts\Schemas;

use App\Enums\LenderType;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class LoanAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Loan Account Details'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('loan_name')
                            ->label(__('Loan Name'))
                            ->required()
                            ->maxLength(255),

                        Select::make('lender_type')
                            ->label(__('Lender Type'))
                            ->options([
                                LenderType::Supplier->value => __('Supplier'),
                                LenderType::Customer->value => __('Customer'),
                                LenderType::Staff->value => __('Staff'),
                                LenderType::External->value => __('External'),
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('lender_reference_id', null))
                            ->disabled(fn ($operation) => $operation !== 'create')
                            ->dehydrated(),

                        Select::make('lender_reference_id')
                            ->label(__('Lender Reference'))
                            ->options(function (Get $get) {
                                $type = $get('lender_type');
                                if ($type === LenderType::Supplier->value) {
                                    return Supplier::pluck('name', 'id');
                                }
                                if ($type === LenderType::Customer->value) {
                                    return Customer::pluck('name', 'id');
                                }
                                if (in_array($type, [LenderType::Staff->value, LenderType::External->value])) {
                                    return User::pluck('name', 'id');
                                }

                                return [];
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled(fn ($operation) => $operation !== 'create')
                            ->dehydrated(),

                        TextInput::make('principal_amount')
                            ->label(__('Principal Amount'))
                            ->numeric()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?string $state) {
                                $set('outstanding_balance', $state);
                            })
                            ->disabled(fn ($operation) => $operation !== 'create')
                            ->dehydrated(),

                        TextInput::make('outstanding_balance')
                            ->label(__('Outstanding Balance'))
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->default(fn (Get $get) => $get('principal_amount')),

                        DatePicker::make('start_date')
                            ->label(__('Start Date'))
                            ->required()
                            ->default(today())
                            ->disabled(fn ($operation) => $operation !== 'create')
                            ->dehydrated(),

                        Textarea::make('notes')
                            ->label(__('Notes'))
                            ->columnSpanFull()
                            ->rows(2)
                            ->maxLength(65535),
                    ]),
            ]);
    }
}
