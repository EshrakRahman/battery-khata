<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Enums\BatteryStatus;
use App\Enums\PaymentMethod;
use App\Filament\Resources\Customers\Schemas\CustomerForm;
use App\Models\BatterySerial;
use App\Models\Broker;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Warehouse;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make(__('Customer Info'))
                        ->schema([
                            Select::make('customer_id')
                                ->label(__('Customer'))
                                ->relationship('customer', 'name')
                                ->searchable(['name', 'mobile', 'national_id'])
                                ->getOptionLabelFromRecordUsing(fn (Customer $record) => "{$record->name} ({$record->mobile})")
                                ->required()
                                ->live()
                                ->createOptionForm(function (Schema $schema) {
                                    return CustomerForm::configure($schema)->getComponents();
                                }),
                            Placeholder::make('customer_details')
                                ->label(__('Customer Details'))
                                ->visible(fn (Get $get) => $get('customer_id') !== null)
                                ->content(function (Get $get) {
                                    $customer = Customer::find($get('customer_id'));
                                    if (! $customer) {
                                        return '';
                                    }

                                    $imgHtml = $customer->image_path
                                        ? '<img src="'.asset('storage/'.$customer->image_path).'" class="w-32 h-32 object-cover rounded-md mb-2 border" />'
                                        : '<div class="w-32 h-32 bg-gray-100 flex items-center justify-center rounded-md mb-2 text-gray-400 border border-dashed">No Photo</div>';

                                    return new HtmlString("
                                        <div class='flex gap-4 items-start p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700'>
                                            {$imgHtml}
                                            <div class='space-y-1 text-sm'>
                                                <p><strong>Mobile:</strong> {$customer->mobile}</p>
                                                <p><strong>NID:</strong> ".($customer->national_id ?: 'N/A')."</p>
                                                <p><strong>Type:</strong> {$customer->customer_type}</p>
                                                <p><strong>Credit Limit:</strong> ৳".number_format($customer->credit_limit, 2).'</p>
                                                <p><strong>Address:</strong> '.($customer->address ?: 'N/A').'</p>
                                            </div>
                                        </div>
                                    ');
                                }),
                            Select::make('broker_id')
                                ->label(__('Broker'))
                                ->relationship('broker', 'name')
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    self::recalculateTotals($get, $set);
                                }),
                        ]),

                    Step::make(__('Serialized Items'))
                        ->schema([
                            Repeater::make('items')
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
                                        ->live()
                                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                            $product = Product::find($state);
                                            if ($product) {
                                                $set('warranty_months', $product->warranty_months);
                                                self::updateItemPrice($get, $set);
                                            }
                                        })
                                        ->columnSpan(4),

                                    Toggle::make('is_set')
                                        ->label(__('Sold as Set'))
                                        ->default(false)
                                        ->live()
                                        ->afterStateUpdated(function (Get $get, Set $set) {
                                            $set('battery_serial_id', null);
                                            $set('battery_serial_ids', []);
                                            self::updateItemPrice($get, $set);
                                        })
                                        ->columnSpan(2),

                                    Select::make('battery_serial_id')
                                        ->label(__('Serial Number'))
                                        ->options(function (Get $get) {
                                            $productId = $get('product_id');
                                            if (! $productId) {
                                                return [];
                                            }

                                            return BatterySerial::query()
                                                ->where('product_id', $productId)
                                                ->where('current_status', BatteryStatus::InStock)
                                                ->pluck('serial_no', 'id');
                                        })
                                        ->required(fn (Get $get) => ! $get('is_set'))
                                        ->visible(fn (Get $get) => ! $get('is_set'))
                                        ->searchable()
                                        ->preload()
                                        ->columnSpan(3),

                                    Select::make('battery_serial_ids')
                                        ->label(__('Serial Numbers (Set)'))
                                        ->options(function (Get $get) {
                                            $productId = $get('product_id');
                                            if (! $productId) {
                                                return [];
                                            }

                                            return BatterySerial::query()
                                                ->where('product_id', $productId)
                                                ->where('current_status', BatteryStatus::InStock)
                                                ->pluck('serial_no', 'id');
                                        })
                                        ->required(fn (Get $get) => (bool) $get('is_set'))
                                        ->visible(fn (Get $get) => (bool) $get('is_set'))
                                        ->multiple()
                                        ->searchable()
                                        ->preload()
                                        ->afterStateUpdated(function (Get $get, Set $set) {
                                            self::recalculateTotals($get, $set);
                                        })
                                        ->columnSpan(3),

                                    TextInput::make('sale_price')
                                        ->label(__('Unit Sale Price'))
                                        ->required()
                                        ->numeric()
                                        ->minValue(0)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (Get $get, Set $set) {
                                            self::recalculateTotals($get, $set);
                                        })
                                        ->columnSpan(2),

                                    TextInput::make('warranty_months')
                                        ->label(__('Warranty (Months)'))
                                        ->required()
                                        ->numeric()
                                        ->minValue(0)
                                        ->columnSpan(1),
                                ])
                                ->columns(12)
                                ->minItems(1)
                                ->defaultItems(1)
                                ->reorderable(false)
                                ->addActionLabel(__('Add Product'))
                                ->live()
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    self::recalculateTotals($get, $set);
                                }),
                        ]),

                    Step::make(__('Scrap Deductions'))
                        ->schema([
                            Repeater::make('scrap_items')
                                ->label('')
                                ->schema([
                                    Select::make('scrap_type')
                                        ->label(__('Scrap Type'))
                                        ->options([
                                            'EasyBike' => __('EasyBike'),
                                            'Car' => __('Car'),
                                            'IPS' => __('IPS'),
                                            'Motorcycle' => __('Motorcycle'),
                                        ])
                                        ->required()
                                        ->columnSpan(3),
                                    TextInput::make('quantity')
                                        ->label(__('Quantity'))
                                        ->required()
                                        ->numeric()
                                        ->default(1)
                                        ->minValue(1)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (Get $get, Set $set) {
                                            $qty = (float) ($get('quantity') ?? 0);
                                            $val = (float) ($get('unit_value') ?? 0);
                                            $set('total_value', number_format($qty * $val, 2, '.', ''));
                                            self::recalculateTotals($get, $set);
                                        })
                                        ->columnSpan(3),
                                    TextInput::make('unit_value')
                                        ->label(__('Unit Value'))
                                        ->required()
                                        ->numeric()
                                        ->minValue(0)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (Get $get, Set $set) {
                                            $qty = (float) ($get('quantity') ?? 0);
                                            $val = (float) ($get('unit_value') ?? 0);
                                            $set('total_value', number_format($qty * $val, 2, '.', ''));
                                            self::recalculateTotals($get, $set);
                                        })
                                        ->columnSpan(3),
                                    TextInput::make('total_value')
                                        ->label(__('Total Value'))
                                        ->required()
                                        ->numeric()
                                        ->disabled()
                                        ->dehydrated()
                                        ->columnSpan(3),
                                    Select::make('warehouse_id')
                                        ->label(__('Warehouse'))
                                        ->options(Warehouse::pluck('name', 'id'))
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->default(fn () => Warehouse::first()?->id)
                                        ->columnSpanFull(),
                                ])
                                ->columns(12)
                                ->addActionLabel(__('Add Scrap Item'))
                                ->live()
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    self::recalculateTotals($get, $set);
                                }),
                        ]),

                    Step::make(__('Summary & Payout'))
                        ->schema([
                            Section::make(__('Invoice Summary'))
                                ->columns(3)
                                ->schema([
                                    TextInput::make('sub_total')
                                        ->label(__('Sub Total'))
                                        ->numeric()
                                        ->disabled()
                                        ->dehydrated()
                                        ->default(0.00),
                                    TextInput::make('discount_amount')
                                        ->label(__('Discount Amount'))
                                        ->numeric()
                                        ->default(0.00)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (Get $get, Set $set) {
                                            self::recalculateTotals($get, $set);
                                        }),
                                    TextInput::make('scrap_adjustment')
                                        ->label(__('Scrap Adjustment'))
                                        ->numeric()
                                        ->disabled()
                                        ->dehydrated()
                                        ->default(0.00),
                                    TextInput::make('grand_total')
                                        ->label(__('Grand Total'))
                                        ->numeric()
                                        ->disabled()
                                        ->dehydrated()
                                        ->default(0.00),
                                    TextInput::make('broker_commission')
                                        ->label(__('Broker Commission'))
                                        ->numeric()
                                        ->default(0.00)
                                        ->helperText(fn (Get $get) => $get('broker_id') ? __('Auto-calculated based on broker commission rate.') : ''),
                                    TextInput::make('remaining_due')
                                        ->label(__('Remaining Due'))
                                        ->numeric()
                                        ->disabled()
                                        ->dehydrated()
                                        ->default(0.00),
                                ]),

                            Section::make(__('Payments Split'))
                                ->schema([
                                    Repeater::make('payments')
                                        ->schema([
                                            Select::make('payment_method')
                                                ->label(__('Payment Method'))
                                                ->options(
                                                    collect(PaymentMethod::cases())
                                                        ->filter(fn ($method) => $method !== PaymentMethod::ScrapAdjustment)
                                                        ->mapWithKeys(fn ($method) => [$method->value => __($method->name)])
                                                )
                                                ->required()
                                                ->live()
                                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                                    $amount = (float) ($get('amount') ?? 0);
                                                    if ($state === PaymentMethod::Bkash->value) {
                                                        $set('service_charge', number_format($amount * 0.0185, 2, '.', ''));
                                                    } elseif ($state === PaymentMethod::Nagad->value) {
                                                        $set('service_charge', number_format($amount * 0.015, 2, '.', ''));
                                                    } else {
                                                        $set('service_charge', '0.00');
                                                    }
                                                })
                                                ->columnSpan(4),
                                            TextInput::make('amount')
                                                ->label(__('Amount'))
                                                ->required()
                                                ->numeric()
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                                    $amount = (float) ($state ?? 0);
                                                    $method = $get('payment_method');
                                                    if ($method === PaymentMethod::Bkash->value) {
                                                        $set('service_charge', number_format($amount * 0.0185, 2, '.', ''));
                                                    } elseif ($method === PaymentMethod::Nagad->value) {
                                                        $set('service_charge', number_format($amount * 0.015, 2, '.', ''));
                                                    }
                                                    self::recalculateTotals($get, $set);
                                                })
                                                ->columnSpan(4),
                                            TextInput::make('service_charge')
                                                ->label(__('MFS Service Charge'))
                                                ->numeric()
                                                ->default(0.00)
                                                ->columnSpan(4),
                                        ])
                                        ->columns(12)
                                        ->addActionLabel(__('Add Payment Method'))
                                        ->live()
                                        ->afterStateUpdated(function (Get $get, Set $set) {
                                            self::recalculateTotals($get, $set);
                                        }),
                                ]),
                        ]),
                ]),
            ]);
    }

    public static function updateItemPrice(Get $get, Set $set): void
    {
        $productId = $get('product_id');
        if (! $productId) {
            return;
        }

        $product = Product::find($productId);
        if (! $product) {
            return;
        }

        $isSet = (bool) $get('is_set');

        if ($isSet) {
            $set('sale_price', $product->set_price ?? $product->mrp_price);
        } else {
            $customerId = $get('../../customer_id');
            $customer = $customerId ? Customer::find($customerId) : null;
            if ($customer && $customer->customer_type === 'Dealer') {
                $set('sale_price', $product->dealer_price);
            } else {
                $set('sale_price', $product->mrp_price);
            }
        }
    }

    public static function recalculateTotals(Get $get, Set $set): void
    {
        // 1. Items subtotal
        $items = $get('items') ?? [];
        $subTotal = 0.00;
        foreach ($items as $item) {
            $price = (float) ($item['sale_price'] ?? 0);
            $isSet = (bool) ($item['is_set'] ?? false);

            if ($isSet) {
                $product = Product::find($item['product_id'] ?? null);
                $qty = $product ? $product->standard_set_qty : 4;
            } else {
                $qty = 1;
            }
            $subTotal += $price * $qty;
        }

        // 2. Scrap value sum
        $scrapItems = $get('scrap_items') ?? [];
        $scrapAdjustment = 0.00;
        foreach ($scrapItems as $sItem) {
            $scrapAdjustment += (float) ($sItem['total_value'] ?? 0);
        }

        // 3. Totals
        $discount = (float) ($get('discount_amount') ?? 0);
        $grandTotal = max(0, $subTotal - $discount - $scrapAdjustment);

        // 4. Broker commission
        $brokerId = $get('broker_id');
        $brokerCommission = 0.00;
        if ($brokerId) {
            $broker = Broker::find($brokerId);
            if ($broker) {
                $brokerCommission = ($subTotal - $discount) * ($broker->commission_rate / 100);
            }
        }

        // 5. Payments sum
        $payments = $get('payments') ?? [];
        $totalPaid = 0.00;
        foreach ($payments as $payment) {
            $totalPaid += (float) ($payment['amount'] ?? 0);
        }

        $remainingDue = max(0, $grandTotal - $totalPaid);

        $set('sub_total', number_format($subTotal, 2, '.', ''));
        $set('scrap_adjustment', number_format($scrapAdjustment, 2, '.', ''));
        $set('grand_total', number_format($grandTotal, 2, '.', ''));
        $set('broker_commission', number_format($brokerCommission, 2, '.', ''));
        $set('remaining_due', number_format($remainingDue, 2, '.', ''));
    }
}
