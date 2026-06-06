<?php

namespace App\Filament\Resources\WarrantyClaims\Pages;

use App\Enums\ClaimStatus;
use App\Filament\Resources\WarrantyClaims\WarrantyClaimResource;
use App\Models\BatterySerial;
use App\Models\BufferBatteryIssue;
use App\Models\InvoiceItem;
use App\Models\Warehouse;
use App\Models\WarrantyClaim;
use App\Services\InventoryService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateWarrantyClaim extends CreateRecord
{
    protected static string $resource = WarrantyClaimResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $user = auth()->user();
            $faultySerial = BatterySerial::findOrFail($data['battery_serial_id']);
            $warehouse = Warehouse::findOrFail($data['warehouse_id']);

            // Find matching invoice item
            $invoiceItem = InvoiceItem::where('battery_serial_id', $faultySerial->id)->first();

            // Create claim
            $claim = WarrantyClaim::create([
                'invoice_item_id' => $invoiceItem?->id,
                'battery_serial_id' => $faultySerial->id,
                'claim_date' => $data['claim_date'],
                'customer_issue' => $data['customer_issue'] ?? null,
                'claim_status' => ClaimStatus::Received,
                'created_by' => $user->id,
            ]);

            // Record WarrantyIn transaction
            $inventoryService = app(InventoryService::class);
            $inventoryService->recordWarrantyIn($faultySerial, $warehouse, $claim, $user);

            // Check if buffer battery should be issued
            if (! empty($data['issue_buffer']) && ! empty($data['buffer_battery_serial_id'])) {
                $bufferSerial = BatterySerial::findOrFail($data['buffer_battery_serial_id']);

                // Create BufferBatteryIssue record
                BufferBatteryIssue::create([
                    'warranty_claim_id' => $claim->id,
                    'battery_serial_id' => $bufferSerial->id,
                    'issued_date' => $data['claim_date'],
                    'notes' => $data['buffer_notes'] ?? null,
                    'created_by' => $user->id,
                ]);

                // Record BufferIssue in inventory
                $inventoryService->recordBufferIssue($bufferSerial, $warehouse, $claim->id, $user);
            }

            return $claim;
        });
    }
}
