<?php

namespace App\Filament\Resources\Invoices\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InvoiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Invoice Details'))
                    ->columns(3)
                    ->schema([
                        TextInput::make('invoice_no')->label(__('Invoice No'))->disabled(),
                        TextInput::make('customer.name')->label(__('Customer'))->disabled(),
                        TextInput::make('invoice_date')->label(__('Invoice Date'))->disabled(),
                        TextInput::make('sub_total')->label(__('Sub Total'))->disabled(),
                        TextInput::make('discount_amount')->label(__('Discount Amount'))->disabled(),
                        TextInput::make('scrap_adjustment')->label(__('Scrap Adjustment'))->disabled(),
                        TextInput::make('grand_total')->label(__('Grand Total'))->disabled(),
                        TextInput::make('invoice_status')->label(__('Status'))->disabled(),
                        TextInput::make('broker.name')->label(__('Broker'))->disabled(),
                        TextInput::make('notes')->label(__('Notes'))->columnSpanFull()->disabled(),
                    ]),

                Section::make(__('Items Sold'))
                    ->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                TextInput::make('product.brand_name')
                                    ->label(__('Brand'))
                                    ->disabled()
                                    ->columnSpan(3),
                                TextInput::make('product.model_name')
                                    ->label(__('Model'))
                                    ->disabled()
                                    ->columnSpan(3),
                                TextInput::make('serial.serial_no')
                                    ->label(__('Serial Number'))
                                    ->disabled()
                                    ->columnSpan(3),
                                TextInput::make('sale_price')
                                    ->label(__('Sale Price'))
                                    ->disabled()
                                    ->columnSpan(2),
                                TextInput::make('warranty_months')
                                    ->label(__('Warranty (Months)'))
                                    ->disabled()
                                    ->columnSpan(1),
                            ])
                            ->columns(12)
                            ->disabled()
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false),
                    ]),
            ]);
    }
}
