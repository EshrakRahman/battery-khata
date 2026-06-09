<?php

namespace App\Filament\Resources\Expenses\Schemas;

use App\Enums\PaymentMethod;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('expense_category_id')
                    ->label(__('Expense Category'))
                    ->relationship('category', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),

                TextInput::make('amount')
                    ->label(__('Amount'))
                    ->numeric()
                    ->required()
                    ->minValue(0.01)
                    ->step(0.01),

                Select::make('payment_method')
                    ->label(__('Payment Method'))
                    ->options(
                        collect(PaymentMethod::cases())
                            ->filter(fn ($method) => $method !== PaymentMethod::ScrapAdjustment)
                            ->mapWithKeys(fn ($method) => [$method->value => __($method->value)])
                            ->toArray()
                    )
                    ->required(),

                DatePicker::make('expense_date')
                    ->label(__('Expense Date'))
                    ->required()
                    ->default(now()),

                Textarea::make('notes')
                    ->label(__('Notes'))
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }
}
