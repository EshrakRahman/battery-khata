<?php

namespace App\Filament\Resources\StockTransfers\Schemas;

use App\Enums\BatteryStatus;
use App\Models\BatterySerial;
use App\Models\InventoryTransaction;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class StockTransferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Transfer Details'))
                    ->columns(2)
                    ->schema([
                        Select::make('source_warehouse_id')
                            ->label(__('Source Warehouse'))
                            ->options(Warehouse::pluck('name', 'id'))
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('serial_ids', []))
                            ->disabled(fn ($operation) => $operation !== 'create')
                            ->dehydrated(),

                        Select::make('destination_warehouse_id')
                            ->label(__('Destination Warehouse'))
                            ->options(Warehouse::pluck('name', 'id'))
                            ->required()
                            ->different('source_warehouse_id')
                            ->validationMessages([
                                'different' => __('The destination warehouse must be different from the source warehouse.'),
                            ])
                            ->disabled(fn ($operation) => $operation !== 'create')
                            ->dehydrated(),

                        DatePicker::make('transfer_date')
                            ->label(__('Transfer Date'))
                            ->required()
                            ->default(today())
                            ->disabled(fn ($operation) => $operation !== 'create')
                            ->dehydrated(),

                        Select::make('status')
                            ->label(__('Status'))
                            ->options([
                                'Pending' => __('Pending'),
                                'InTransit' => __('InTransit'),
                                'completed' => __('Completed'),
                            ])
                            ->required()
                            ->default('completed'),

                        Select::make('serial_ids')
                            ->label(__('Battery Serials'))
                            ->options(function (Get $get) {
                                $sourceWarehouseId = $get('source_warehouse_id');
                                if (! $sourceWarehouseId) {
                                    return [];
                                }

                                return BatterySerial::query()
                                    ->where(function ($q) use ($sourceWarehouseId, $get) {
                                        $q->where('current_status', BatteryStatus::InStock)
                                            ->whereIn('id', function ($sub) use ($sourceWarehouseId) {
                                                $sub->select('battery_serial_id')
                                                    ->from('inventory_transactions')
                                                    ->whereIn('id', function ($inner) {
                                                        $inner->selectRaw('MAX(id)')
                                                            ->from('inventory_transactions')
                                                            ->groupBy('battery_serial_id');
                                                    })
                                                    ->where('warehouse_id', $sourceWarehouseId);
                                            });

                                        // Include already selected serials for this transfer
                                        $transferId = $get('id');
                                        if ($transferId) {
                                            $alreadySelectedIds = InventoryTransaction::where('reference_type', StockTransfer::class)
                                                ->where('reference_id', $transferId)
                                                ->pluck('battery_serial_id')
                                                ->toArray();
                                            if (! empty($alreadySelectedIds)) {
                                                $q->orWhereIn('id', $alreadySelectedIds);
                                            }
                                        }
                                    })
                                    ->pluck('serial_no', 'id');
                            })
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->required(fn ($operation) => $operation === 'create')
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
