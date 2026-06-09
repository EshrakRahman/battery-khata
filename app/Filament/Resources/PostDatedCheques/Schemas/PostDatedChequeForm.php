<?php

namespace App\Filament\Resources\PostDatedCheques\Schemas;

use App\Enums\PdcStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PostDatedChequeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Cheque'))
                    ->columns(2)
                    ->disabled(fn ($record) => $record && in_array($record->status, [PdcStatus::Cleared, PdcStatus::Bounced]))
                    ->schema([
                        Select::make('customer_id')
                            ->label(__('Customer'))
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->required(),

                        TextInput::make('cheque_number')
                            ->label(__('Cheque Number'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('bank_name')
                            ->label(__('Bank Name'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('amount')
                            ->label(__('Amount'))
                            ->numeric()
                            ->required()
                            ->prefix('৳')
                            ->minValue(0),

                        DatePicker::make('maturity_date')
                            ->label(__('Maturity Date'))
                            ->required(),

                        Select::make('status')
                            ->label(__('Status'))
                            ->options(collect(PdcStatus::cases())->mapWithKeys(fn ($status) => [$status->value => __($status->value)])->toArray())
                            ->default(PdcStatus::Pending->value)
                            ->disabled()
                            ->dehydrated(),

                        DatePicker::make('deposit_date')
                            ->label(__('Deposit Date'))
                            ->disabled()
                            ->visible(fn ($record) => $record && in_array($record->status, [PdcStatus::Deposited, PdcStatus::Cleared, PdcStatus::Bounced])),

                        DatePicker::make('cleared_date')
                            ->label(__('Cleared Date'))
                            ->disabled()
                            ->visible(fn ($record) => $record && $record->status === PdcStatus::Cleared),

                        Textarea::make('bounce_reason')
                            ->label(__('Bounce Reason'))
                            ->disabled()
                            ->visible(fn ($record) => $record && $record->status === PdcStatus::Bounced)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
