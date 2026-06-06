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
use Throwable;

class InventoryService
{
    /**
     * Record a purchase transaction.
     *
     * @throws Throwable
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
            $serial = BatterySerial::query()->firstOrCreate(
                ['serial_no' => $serialNo],
                ['product_id' => $product->getKey(), 'current_status' => BatteryStatus::InStock]
            );

            // If it existed but wasn't in stock, change it to InStock
            if ($serial->getAttribute('current_status') !== BatteryStatus::InStock) {
                $serial->update(['current_status' => BatteryStatus::InStock]);
            }

            // Log purchase transaction
            InventoryTransaction::query()->create([
                'battery_serial_id' => $serial->getKey(),
                'warehouse_id' => $warehouse->getKey(),
                'transaction_type' => TransactionType::Purchase,
                'reference_type' => get_class($referenceInvoice),
                'reference_id' => $referenceInvoice->getKey(),
                'created_by' => $createdBy->getKey(),
            ]);

            return $serial;
        });
    }

    /**
     * Record a sale transaction.
     *
     * @throws Throwable
     * @throws InvalidSerialStatusException
     */
    public function recordSale(
        BatterySerial $serial,
        Warehouse $warehouse,
        Model $referenceInvoice,
        User $createdBy
    ): BatterySerial {
        return DB::transaction(function () use ($serial, $warehouse, $referenceInvoice, $createdBy) {
            // Lock the serial row to prevent concurrent double-booking/double-sale
            $lockedSerial = BatterySerial::query()->where('id', $serial->getKey())->lockForUpdate()->firstOrFail();

            // Enforce stock check
            if ($lockedSerial->getAttribute('current_status') !== BatteryStatus::InStock) {
                throw new InvalidSerialStatusException(
                    "Battery serial {$lockedSerial->getAttribute('serial_no')} is already ".$lockedSerial->getAttribute('current_status')->value.' and cannot be sold again.'
                );
            }

            // Update status to Sold
            $lockedSerial->update(['current_status' => BatteryStatus::Sold]);

            // Log sale transaction
            InventoryTransaction::query()->create([
                'battery_serial_id' => $lockedSerial->getKey(),
                'warehouse_id' => $warehouse->getKey(),
                'transaction_type' => TransactionType::Sale,
                'reference_type' => get_class($referenceInvoice),
                'reference_id' => $referenceInvoice->getKey(),
                'created_by' => $createdBy->getKey(),
            ]);

            return $lockedSerial;
        });
    }

    /**
     * Record checking out a buffer service battery.
     *
     * @throws Throwable
     * @throws InvalidSerialStatusException
     */
    public function recordBufferIssue(
        BatterySerial $serial,
        Warehouse $warehouse,
        int $referenceId,
        User $createdBy
    ): BatterySerial {
        return DB::transaction(function () use ($serial, $warehouse, $referenceId, $createdBy) {
            // Lock the serial row
            $lockedSerial = BatterySerial::query()->where('id', $serial->getKey())->lockForUpdate()->firstOrFail();

            if ($lockedSerial->getAttribute('current_status') !== BatteryStatus::InStock) {
                throw new InvalidSerialStatusException(
                    "Battery serial {$lockedSerial->getAttribute('serial_no')} must be in_stock to be issued as a buffer."
                );
            }

            $lockedSerial->update(['current_status' => BatteryStatus::BufferIssued]);

            InventoryTransaction::query()->create([
                'battery_serial_id' => $lockedSerial->getKey(),
                'warehouse_id' => $warehouse->getKey(),
                'transaction_type' => TransactionType::BufferIssue,
                'reference_type' => 'WarrantyClaim',
                'reference_id' => $referenceId,
                'created_by' => $createdBy->getKey(),
            ]);

            return $lockedSerial;
        });
    }

    /**
     * Record returning a buffer service battery to stock.
     *
     * @throws Throwable
     * @throws InvalidSerialStatusException
     */
    public function recordBufferReturn(
        BatterySerial $serial,
        Warehouse $warehouse,
        int $referenceId,
        User $createdBy
    ): BatterySerial {
        return DB::transaction(function () use ($serial, $warehouse, $referenceId, $createdBy) {
            // Lock the serial row
            $lockedSerial = BatterySerial::query()->where('id', $serial->getKey())->lockForUpdate()->firstOrFail();

            if ($lockedSerial->getAttribute('current_status') !== BatteryStatus::BufferIssued) {
                throw new InvalidSerialStatusException(
                    "Battery serial {$lockedSerial->getAttribute('serial_no')} must be buffer_issued to be returned to stock."
                );
            }

            $lockedSerial->update(['current_status' => BatteryStatus::InStock]);

            InventoryTransaction::query()->create([
                'battery_serial_id' => $lockedSerial->getKey(),
                'warehouse_id' => $warehouse->getKey(),
                'transaction_type' => TransactionType::BufferReturn,
                'reference_type' => 'WarrantyClaim',
                'reference_id' => $referenceId,
                'created_by' => $createdBy->getKey(),
            ]);

            return $lockedSerial;
        });
    }

    /**
     * Record a faulty battery being received for warranty.
     *
     * @throws Throwable
     * @throws InvalidSerialStatusException
     */
    public function recordWarrantyIn(
        BatterySerial $serial,
        Warehouse $warehouse,
        Model $referenceClaim,
        User $createdBy
    ): BatterySerial {
        return DB::transaction(function () use ($serial, $warehouse, $referenceClaim, $createdBy) {
            $lockedSerial = BatterySerial::query()->where('id', $serial->getKey())->lockForUpdate()->firstOrFail();

            if ($lockedSerial->getAttribute('current_status') !== BatteryStatus::Sold) {
                throw new InvalidSerialStatusException(
                    "Battery serial {$lockedSerial->getAttribute('serial_no')} must be sold to claim warranty."
                );
            }

            $lockedSerial->update(['current_status' => BatteryStatus::WarrantyClaim]);

            InventoryTransaction::query()->create([
                'battery_serial_id' => $lockedSerial->getKey(),
                'warehouse_id' => $warehouse->getKey(),
                'transaction_type' => TransactionType::WarrantyIn,
                'reference_type' => get_class($referenceClaim),
                'reference_id' => $referenceClaim->getKey(),
                'created_by' => $createdBy->getKey(),
            ]);

            return $lockedSerial;
        });
    }

    /**
     * Record a replacement battery (or returned faulty battery) leaving stock/warehouse.
     *
     * @throws Throwable
     * @throws InvalidSerialStatusException
     */
    public function recordWarrantyOut(
        BatterySerial $serial,
        Warehouse $warehouse,
        Model $referenceClaim,
        User $createdBy,
        BatteryStatus $targetStatus = BatteryStatus::Sold
    ): BatterySerial {
        return DB::transaction(function () use ($serial, $warehouse, $referenceClaim, $createdBy, $targetStatus) {
            $lockedSerial = BatterySerial::query()->where('id', $serial->getKey())->lockForUpdate()->firstOrFail();

            if (! in_array($lockedSerial->getAttribute('current_status'), [BatteryStatus::InStock, BatteryStatus::WarrantyClaim])) {
                throw new InvalidSerialStatusException(
                    "Battery serial {$lockedSerial->getAttribute('serial_no')} status must be in_stock or warranty_claim to be resolved."
                );
            }

            $lockedSerial->update(['current_status' => $targetStatus]);

            InventoryTransaction::query()->create([
                'battery_serial_id' => $lockedSerial->getKey(),
                'warehouse_id' => $warehouse->getKey(),
                'transaction_type' => TransactionType::WarrantyOut,
                'reference_type' => get_class($referenceClaim),
                'reference_id' => $referenceClaim->getKey(),
                'created_by' => $createdBy->getKey(),
            ]);

            return $lockedSerial;
        });
    }

    /**
     * Record a transfer out of a source warehouse.
     *
     * @throws Throwable
     * @throws InvalidSerialStatusException
     */
    public function recordTransferOut(
        BatterySerial $serial,
        Warehouse $source,
        Model $transfer,
        User $createdBy
    ): BatterySerial {
        return DB::transaction(function () use ($serial, $source, $transfer, $createdBy) {
            $lockedSerial = BatterySerial::query()->where('id', $serial->getKey())->lockForUpdate()->firstOrFail();

            if ($lockedSerial->getAttribute('current_status') !== BatteryStatus::InStock) {
                throw new InvalidSerialStatusException(
                    "Battery serial {$lockedSerial->getAttribute('serial_no')} must be in_stock to be transferred."
                );
            }

            InventoryTransaction::query()->create([
                'battery_serial_id' => $lockedSerial->getKey(),
                'warehouse_id' => $source->getKey(),
                'transaction_type' => TransactionType::TransferOut,
                'reference_type' => get_class($transfer),
                'reference_id' => $transfer->getKey(),
                'created_by' => $createdBy->getKey(),
            ]);

            return $lockedSerial;
        });
    }

    /**
     * Record a transfer into a destination warehouse.
     *
     * @throws Throwable
     */
    public function recordTransferIn(
        BatterySerial $serial,
        Warehouse $destination,
        Model $transfer,
        User $createdBy
    ): BatterySerial {
        return DB::transaction(function () use ($serial, $destination, $transfer, $createdBy) {
            $lockedSerial = BatterySerial::query()->where('id', $serial->getKey())->lockForUpdate()->firstOrFail();

            InventoryTransaction::query()->create([
                'battery_serial_id' => $lockedSerial->getKey(),
                'warehouse_id' => $destination->getKey(),
                'transaction_type' => TransactionType::TransferIn,
                'reference_type' => get_class($transfer),
                'reference_id' => $transfer->getKey(),
                'created_by' => $createdBy->getKey(),
            ]);

            return $lockedSerial;
        });
    }

    /**
     * Record a stock transfer movement.
     *
     * @throws Throwable
     * @throws InvalidSerialStatusException
     */
    public function recordTransfer(
        BatterySerial $serial,
        Warehouse $source,
        Warehouse $destination,
        Model $transfer,
        User $createdBy
    ): BatterySerial {
        return DB::transaction(function () use ($serial, $source, $destination, $transfer, $createdBy) {
            $this->recordTransferOut($serial, $source, $transfer, $createdBy);

            return $this->recordTransferIn($serial, $destination, $transfer, $createdBy);
        });
    }

    /**
     * Dynamically derive a serial's current location from the latest transaction log.
     */
    public function getCurrentWarehouse(BatterySerial $serial): ?Warehouse
    {
        $latestTransaction = InventoryTransaction::query()->where('battery_serial_id', $serial->getKey())
            ->latest('id')
            ->first();

        if ($latestTransaction && $latestTransaction->transaction_type === TransactionType::TransferOut) {
            return null;
        }

        return $latestTransaction?->getAttribute('warehouse');
    }
}
