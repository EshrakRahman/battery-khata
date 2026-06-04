<?php

namespace App\Services;

use App\Enums\BatteryStatus;
use App\Enums\TransactionType;
use App\Exceptions\InvalidSerialStatusException;
use App\Models\BatterySerial;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Record a purchase transaction.
     */
    public function recordPurchase(
        Product $product,
        string $serialNo,
        Warehouse $warehouse,
        Model $referenceInvoice,
        User $createdBy
    ): BatterySerial {
        return DB::transaction(function () use ($product, $serialNo, $warehouse, $referenceInvoice, $createdBy) {
            // Find or create the serial as InStock
            $serial = BatterySerial::firstOrCreate(
                ['serial_no' => $serialNo],
                ['product_id' => $product->id, 'current_status' => BatteryStatus::InStock]
            );

            // If it existed but wasn't in stock, change it to InStock
            if ($serial->current_status !== BatteryStatus::InStock) {
                $serial->update(['current_status' => BatteryStatus::InStock]);
            }

            // Log purchase transaction
            InventoryTransaction::create([
                'battery_serial_id' => $serial->id,
                'warehouse_id' => $warehouse->id,
                'transaction_type' => TransactionType::Purchase,
                'reference_type' => get_class($referenceInvoice),
                'reference_id' => $referenceInvoice->id,
                'created_by' => $createdBy->id,
            ]);

            return $serial;
        });
    }

    /**
     * Record a sale transaction.
     */
    public function recordSale(
        BatterySerial $serial,
        Warehouse $warehouse,
        Model $referenceInvoice,
        User $createdBy
    ): BatterySerial {
        return DB::transaction(function () use ($serial, $warehouse, $referenceInvoice, $createdBy) {
            // Enforce stock check
            if ($serial->current_status !== BatteryStatus::InStock) {
                throw new InvalidSerialStatusException(
                    "Battery serial {$serial->serial_no} is already {$serial->current_status->value} and cannot be sold again."
                );
            }

            // Update status to Sold
            $serial->update(['current_status' => BatteryStatus::Sold]);

            // Log sale transaction
            InventoryTransaction::create([
                'battery_serial_id' => $serial->id,
                'warehouse_id' => $warehouse->id,
                'transaction_type' => TransactionType::Sale,
                'reference_type' => get_class($referenceInvoice),
                'reference_id' => $referenceInvoice->id,
                'created_by' => $createdBy->id,
            ]);

            return $serial;
        });
    }

    /**
     * Record checking out a buffer service battery.
     */
    public function recordBufferIssue(
        BatterySerial $serial,
        Warehouse $warehouse,
        int $referenceId,
        User $createdBy
    ): BatterySerial {
        return DB::transaction(function () use ($serial, $warehouse, $referenceId, $createdBy) {
            if ($serial->current_status !== BatteryStatus::InStock) {
                throw new InvalidSerialStatusException(
                    "Battery serial {$serial->serial_no} must be in_stock to be issued as a buffer."
                );
            }

            $serial->update(['current_status' => BatteryStatus::BufferIssued]);

            InventoryTransaction::create([
                'battery_serial_id' => $serial->id,
                'warehouse_id' => $warehouse->id,
                'transaction_type' => TransactionType::BufferIssue,
                'reference_type' => 'WarrantyClaim',
                'reference_id' => $referenceId,
                'created_by' => $createdBy->id,
            ]);

            return $serial;
        });
    }

    /**
     * Record returning a buffer service battery back to stock.
     */
    public function recordBufferReturn(
        BatterySerial $serial,
        Warehouse $warehouse,
        int $referenceId,
        User $createdBy
    ): BatterySerial {
        return DB::transaction(function () use ($serial, $warehouse, $referenceId, $createdBy) {
            if ($serial->current_status !== BatteryStatus::BufferIssued) {
                throw new InvalidSerialStatusException(
                    "Battery serial {$serial->serial_no} must be buffer_issued to be returned to stock."
                );
            }

            $serial->update(['current_status' => BatteryStatus::InStock]);

            InventoryTransaction::create([
                'battery_serial_id' => $serial->id,
                'warehouse_id' => $warehouse->id,
                'transaction_type' => TransactionType::BufferReturn,
                'reference_type' => 'WarrantyClaim',
                'reference_id' => $referenceId,
                'created_by' => $createdBy->id,
            ]);

            return $serial;
        });
    }

    /**
     * Record a stock transfer movement.
     */
    public function recordTransfer(
        BatterySerial $serial,
        Warehouse $source,
        Warehouse $destination,
        Model $transfer,
        User $createdBy
    ): BatterySerial {
        return DB::transaction(function () use ($serial, $source, $destination, $transfer, $createdBy) {
            if ($serial->current_status !== BatteryStatus::InStock) {
                throw new InvalidSerialStatusException(
                    "Battery serial {$serial->serial_no} must be in_stock to be transferred."
                );
            }

            // Create TransferOut from source Godown
            InventoryTransaction::create([
                'battery_serial_id' => $serial->id,
                'warehouse_id' => $source->id,
                'transaction_type' => TransactionType::TransferOut,
                'reference_type' => get_class($transfer),
                'reference_id' => $transfer->id,
                'created_by' => $createdBy->id,
            ]);

            // Create TransferIn to destination Showroom
            InventoryTransaction::create([
                'battery_serial_id' => $serial->id,
                'warehouse_id' => $destination->id,
                'transaction_type' => TransactionType::TransferIn,
                'reference_type' => get_class($transfer),
                'reference_id' => $transfer->id,
                'created_by' => $createdBy->id,
            ]);

            return $serial;
        });
    }

    /**
     * Dynamically derive a serial's current location from the latest transaction log.
     */
    public function getCurrentWarehouse(BatterySerial $serial): ?Warehouse
    {
        $latestTransaction = InventoryTransaction::where('battery_serial_id', $serial->id)
            ->latest('id')
            ->first();

        return $latestTransaction ? $latestTransaction->warehouse : null;
    }
}
