<?php

namespace App\Filament\Resources\WarrantyClaims\Schemas;

use App\Enums\BatteryStatus;
use App\Models\BatterySerial;
use App\Models\InvoiceItem;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class WarrantyClaimForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Warranty Claim Details'))
                    ->columns(2)
                    ->schema([
                        Select::make('battery_serial_id')
                            ->label(__('Faulty Battery Serial'))
                            ->options(function (Get $get) {
                                return BatterySerial::query()
                                    ->where(function ($q) use ($get) {
                                        $q->where('current_status', BatteryStatus::Sold);

                                        // Include currently selected serial in edit mode
                                        $serialId = $get('battery_serial_id');
                                        if ($serialId) {
                                            $q->orWhere('id', $serialId);
                                        }
                                    })
                                    ->pluck('serial_no', 'id');
                            })
                            ->required()
                            ->live()
                            ->searchable()
                            ->preload()
                            ->disabled(fn ($operation) => $operation !== 'create')
                            ->dehydrated()
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                if ($state) {
                                    $serial = BatterySerial::find($state);
                                    if ($serial) {
                                        $warehouse = app(InventoryService::class)->getCurrentWarehouse($serial);
                                        if ($warehouse) {
                                            $set('warehouse_id', $warehouse->id);
                                        }
                                    }
                                }
                            }),

                        Select::make('warehouse_id')
                            ->label(__('Receiving Warehouse'))
                            ->options(Warehouse::pluck('name', 'id'))
                            ->required()
                            ->live()
                            ->default(fn () => Warehouse::first()?->id)
                            ->disabled(fn ($operation) => $operation !== 'create')
                            ->dehydrated(),

                        Placeholder::make('sale_details')
                            ->label(__('Original Sale Details'))
                            ->columnSpanFull()
                            ->content(function (Get $get) {
                                $serialId = $get('battery_serial_id');
                                if (! $serialId) {
                                    return __('Select a serial number to view sale details.');
                                }

                                $invoiceItem = InvoiceItem::where('battery_serial_id', $serialId)
                                    ->with('invoice.customer', 'product')
                                    ->first();

                                if (! $invoiceItem) {
                                    return __('This serial number has no recorded invoice.');
                                }

                                $invoice = $invoiceItem->invoice;
                                $customer = $invoice?->customer;
                                $product = $invoiceItem->product;
                                $saleDate = $invoice?->invoice_date?->format('Y-m-d') ?? 'N/A';
                                $warrantyMonths = $invoiceItem->warranty_months;
                                $expiresAt = $invoice?->invoice_date ? $invoice->invoice_date->addMonths($warrantyMonths)->format('Y-m-d') : 'N/A';
                                $isExpired = $invoice?->invoice_date ? now()->greaterThan($invoice->invoice_date->addMonths($warrantyMonths)) : false;

                                $expiredLabel = __('Expired');
                                $activeLabel = __('Active');
                                $productLabel = __('Product');
                                $customerLabel = __('Customer');
                                $invoiceNoLabel = __('Invoice No');
                                $saleDateLabel = __('Sale Date');
                                $warrantyLabel = __('Warranty');
                                $monthsLabel = __('Months');
                                $expiresLabel = __('Expires');

                                $statusText = $isExpired
                                    ? "<span style='color: red; font-weight: bold;'>{$expiredLabel}</span>"
                                    : "<span style='color: green; font-weight: bold;'>{$activeLabel}</span>";

                                return new HtmlString(
                                    "<strong>{$productLabel}:</strong> {$product?->brand_name} {$product?->model_name}<br>".
                                    "<strong>{$customerLabel}:</strong> {$customer?->name} ({$customer?->mobile})<br>".
                                    "<strong>{$invoiceNoLabel}:</strong> {$invoice?->invoice_no}<br>".
                                    "<strong>{$saleDateLabel}:</strong> {$saleDate}<br>".
                                    "<strong>{$warrantyLabel}:</strong> {$warrantyMonths} {$monthsLabel} ({$expiresLabel}: {$expiresAt}) - {$statusText}"
                                );
                            }),

                        DatePicker::make('claim_date')
                            ->label(__('Claim Date'))
                            ->required()
                            ->default(today())
                            ->disabled(fn ($operation) => $operation !== 'create')
                            ->dehydrated(),

                        Textarea::make('customer_issue')
                            ->label(__('Customer Issue'))
                            ->rows(3)
                            ->columnSpanFull()
                            ->maxLength(65535),
                    ]),

                Section::make(__('Buffer Battery (Optional)'))
                    ->visible(fn ($operation) => $operation === 'create')
                    ->schema([
                        Toggle::make('issue_buffer')
                            ->label(__('Issue Buffer Battery?'))
                            ->live(),

                        Fieldset::make(__('Buffer Battery Details'))
                            ->visible(fn (Get $get) => (bool) $get('issue_buffer'))
                            ->columns(2)
                            ->schema([
                                Select::make('buffer_battery_serial_id')
                                    ->label(__('Buffer Battery Serial'))
                                    ->options(function (Get $get) {
                                        $faultySerialId = $get('battery_serial_id');
                                        if (! $faultySerialId) {
                                            return BatterySerial::where('current_status', BatteryStatus::InStock)
                                                ->pluck('serial_no', 'id');
                                        }

                                        $faultySerial = BatterySerial::find($faultySerialId);
                                        if (! $faultySerial || ! $faultySerial->product) {
                                            return BatterySerial::where('current_status', BatteryStatus::InStock)
                                                ->pluck('serial_no', 'id');
                                        }

                                        return BatterySerial::query()
                                            ->where('current_status', BatteryStatus::InStock)
                                            ->whereHas('product', function ($q) use ($faultySerial) {
                                                $q->where('category_id', $faultySerial->product->category_id);
                                            })
                                            ->pluck('serial_no', 'id');
                                    })
                                    ->required(fn (Get $get) => (bool) $get('issue_buffer'))
                                    ->searchable()
                                    ->preload(),

                                Textarea::make('buffer_notes')
                                    ->label(__('Buffer Notes'))
                                    ->rows(2)
                                    ->columnSpanFull()
                                    ->maxLength(65535),
                            ]),
                    ]),
            ]);
    }
}
