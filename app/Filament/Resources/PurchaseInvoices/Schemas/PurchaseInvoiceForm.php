<?php

namespace App\Filament\Resources\PurchaseInvoices\Schemas;

use App\Models\Product;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class PurchaseInvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Invoice Details'))
                    ->columns(2)
                    ->schema([
                        Select::make('supplier_id')
                            ->label(__('Supplier'))
                            ->relationship('supplier', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Select::make('warehouse_id')
                            ->label(__('Warehouse'))
                            ->relationship('warehouse', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        TextInput::make('invoice_no')
                            ->label(__('Invoice No'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        DatePicker::make('purchase_date')
                            ->label(__('Purchase Date'))
                            ->required()
                            ->default(today()),
                        Textarea::make('notes')
                            ->label(__('Notes'))
                            ->columnSpanFull()
                            ->rows(2)
                            ->maxLength(65535),
                    ]),

                Section::make(__('Items'))
                    ->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->label('')
                            ->schema([
                                Select::make('product_id')
                                    ->label(__('Product'))
                                    ->options(
                                        Product::query()
                                            ->orderBy('brand_name')
                                            ->get()
                                            ->mapWithKeys(fn (Product $p) => [$p->id => "{$p->brand_name} - {$p->model_name}"])
                                    )
                                    ->required()
                                    ->searchable()
                                    ->columnSpan(4),
                                TextInput::make('serial_no')
                                    ->label(__('Serial Number'))
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(4),
                                TextInput::make('purchase_price')
                                    ->label(__('Purchase Price'))
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        self::recalculateTotals($get, $set);
                                    })
                                    ->columnSpan(4),
                            ])
                            ->columns(12)
                            ->minItems(0)
                            ->defaultItems(0)
                            ->reorderable(false)
                            ->addActionLabel(__('Add Item'))
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::recalculateTotals($get, $set);
                            }),
                    ]),

                Section::make(__('Totals'))
                    ->columns(3)
                    ->schema([
                        TextInput::make('sub_total')
                            ->label(__('Sub Total'))
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->default(0),
                        TextInput::make('discount_amount')
                            ->label(__('Discount Amount'))
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::recalculateTotals($get, $set);
                            }),
                        TextInput::make('grand_total')
                            ->label(__('Grand Total'))
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->default(0),
                    ]),
            ]);
    }

    /**
     * Recalculate sub_total and grand_total from repeater items.
     */
    private static function recalculateTotals(Get $get, Set $set): void
    {
        $items = $get('items') ?? [];

        $subTotal = collect($items)->sum(
            fn (array $item) => (float) ($item['purchase_price'] ?? 0)
        );

        $discount = (float) ($get('discount_amount') ?? 0);
        $grandTotal = max(0, $subTotal - $discount);

        $set('sub_total', number_format($subTotal, 2, '.', ''));
        $set('grand_total', number_format($grandTotal, 2, '.', ''));
    }
}
