<?php

namespace App\Filament\Resources\Invoices\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
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
                        TextEntry::make('invoice_no')->label(__('Invoice No')),
                        TextEntry::make('customer.name')->label(__('Customer')),
                        TextEntry::make('invoice_date')->label(__('Invoice Date'))->dateTime(),
                        TextEntry::make('sub_total')->label(__('Sub Total'))->money('BDT'),
                        TextEntry::make('discount_amount')->label(__('Discount Amount'))->money('BDT'),
                        TextEntry::make('scrap_adjustment')->label(__('Scrap Adjustment'))->money('BDT'),
                        TextEntry::make('grand_total')->label(__('Grand Total'))->money('BDT'),
                        TextEntry::make('invoice_status')->label(__('Status')),
                        TextEntry::make('broker.name')->label(__('Broker'))->placeholder('-'),
                        TextEntry::make('notes')->label(__('Notes'))->columnSpanFull()->placeholder('-'),
                    ]),

                Section::make(__('Items Sold'))
                    ->schema([
                        RepeatableEntry::make('items')
                            ->schema([
                                TextEntry::make('product.brand_name')
                                    ->label(__('Brand'))
                                    ->columnSpan(3),
                                TextEntry::make('product.model_name')
                                    ->label(__('Model'))
                                    ->columnSpan(3),
                                TextEntry::make('serial.serial_no')
                                    ->label(__('Serial Number'))
                                    ->columnSpan(3),
                                TextEntry::make('sale_price')
                                    ->label(__('Sale Price'))
                                    ->money('BDT')
                                    ->columnSpan(2),
                                TextEntry::make('warranty_months')
                                    ->label(__('Warranty (Months)'))
                                    ->columnSpan(1),
                            ])
                            ->columns(12),
                    ]),
            ]);
    }
}
