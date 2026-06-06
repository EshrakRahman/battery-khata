<?php

namespace App\Filament\Resources\StockTransfers\Pages;

use App\Filament\Resources\StockTransfers\StockTransferResource;
use App\Models\BatterySerial;
use App\Models\InventoryTransaction;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditStockTransfer extends EditRecord
{
    protected static string $resource = StockTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            $oldStatus = $record->getOriginal('status');
            $newStatus = $data['status'];

            $record->update([
                'status' => $newStatus,
                'notes' => $data['notes'] ?? $record->notes,
            ]);

            if ($oldStatus !== $newStatus) {
                $user = auth()->user();
                $source = Warehouse::findOrFail($record->source_warehouse_id);
                $destination = Warehouse::findOrFail($record->destination_warehouse_id);
                $inventoryService = app(InventoryService::class);

                // Fetch serials
                $serialIds = (! empty($data['serial_ids'])) ? $data['serial_ids'] : InventoryTransaction::where('reference_type', StockTransfer::class)
                    ->where('reference_id', $record->id)
                    ->pluck('battery_serial_id')
                    ->unique()
                    ->toArray();

                foreach ($serialIds as $serialId) {
                    $serial = BatterySerial::findOrFail($serialId);

                    if ($oldStatus === 'Pending') {
                        if ($newStatus === 'InTransit') {
                            $inventoryService->recordTransferOut($serial, $source, $record, $user);
                        } elseif ($newStatus === 'completed') {
                            $inventoryService->recordTransfer($serial, $source, $destination, $record, $user);
                        }
                    } elseif ($oldStatus === 'InTransit' && $newStatus === 'completed') {
                        $inventoryService->recordTransferIn($serial, $destination, $record, $user);
                    }
                }
            }

            return $record->refresh();
        });
    }
}
