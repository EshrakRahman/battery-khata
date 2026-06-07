<?php

namespace App\Filament\Resources\SupplierPayments\Schemas;

use App\Enums\PaymentMethod;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SupplierPaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('supplier_id')
                    ->label(__('Supplier'))
                    ->relationship('supplier', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),

                DatePicker::make('payment_date')
                    ->label(__('Payment Date'))
                    ->required()
                    ->default(now()),

                Select::make('payment_method')
                    ->label(__('Payment Method'))
                    ->options(
                        collect(PaymentMethod::cases())
                            ->filter(fn ($method) => $method !== PaymentMethod::ScrapAdjustment)
                            ->mapWithKeys(fn ($method) => [$method->value => __($method->value)])
                            ->toArray()
                    )
                    ->required(),

                TextInput::make('amount')
                    ->label(__('Amount'))
                    ->numeric()
                    ->required()
                    ->minValue(0.01)
                    ->step(0.01),

                TextInput::make('reference_no')
                    ->label(__('Reference Number'))
                    ->maxLength(255),

                Textarea::make('notes')
                    ->label(__('Notes'))
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }
}
