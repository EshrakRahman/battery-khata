<?php

namespace App\Filament\Resources\StockTransfers\Pages;

use App\Filament\Resources\StockTransfers\StockTransferResource;
use App\Models\BatterySerial;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateStockTransfer extends CreateRecord
{
    protected static string $resource = StockTransferResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $user = auth()->user();
            $source = Warehouse::findOrFail($data['source_warehouse_id']);
            $destination = Warehouse::findOrFail($data['destination_warehouse_id']);

            $transfer = StockTransfer::create([
                'source_warehouse_id' => $source->id,
                'destination_warehouse_id' => $destination->id,
                'transfer_date' => $data['transfer_date'],
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            $inventoryService = app(InventoryService::class);
            $serialIds = $data['serial_ids'] ?? [];

            foreach ($serialIds as $serialId) {
                $serial = BatterySerial::findOrFail($serialId);

                if ($data['status'] === 'InTransit') {
                    $inventoryService->recordTransferOut($serial, $source, $transfer, $user);
                } elseif ($data['status'] === 'completed') {
                    $inventoryService->recordTransfer($serial, $source, $destination, $transfer, $user);
                }
            }

            return $transfer;
        });
    }
}
