<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Enums\PaymentMethod;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('customer_id')
                    ->label(__('Customer'))
                    ->relationship('customer', 'name')
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

                TextInput::make('total_amount')
                    ->label(__('Amount Received'))
                    ->numeric()
                    ->required()
                    ->minValue(0.01)
                    ->step(0.01),

                TextInput::make('service_charge')
                    ->label(__('Service Charge'))
                    ->numeric()
                    ->default(0.00)
                    ->minValue(0.00)
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
