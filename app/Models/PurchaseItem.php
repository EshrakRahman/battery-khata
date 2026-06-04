<?php

namespace App\Models;

use App\Enums\BatteryStatus;
use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperPurchaseItem
 */
#[Fillable([
    'purchase_invoice_id',
    'product_id',
    'battery_serial_id',
    'purchase_price',
])]
class PurchaseItem extends Model
{
    public ?string $serial_no = null;

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::retrieved(function (PurchaseItem $item) {
            $item->serial_no = $item->serial?->serial_no;
        });

        static::creating(function (PurchaseItem $item) {
            if ($item->serial_no) {
                $inventoryService = app(InventoryService::class);
                $purchaseInvoice = $item->purchaseInvoice ?? PurchaseInvoice::find($item->purchase_invoice_id);
                $warehouse = $purchaseInvoice->warehouse;
                $creator = $purchaseInvoice->creator ?? auth()->user() ?? User::first();

                $serial = $inventoryService->recordPurchase(
                    product: $item->product,
                    serialNo: $item->serial_no,
                    warehouse: $warehouse,
                    referenceInvoice: $purchaseInvoice,
                    createdBy: $creator
                );

                $item->battery_serial_id = $serial->id;
            }
        });

        static::updating(function (PurchaseItem $item) {
            if ($item->serial_no && ($item->isDirty('serial_no') || $item->serial_no !== $item->serial?->serial_no)) {
                $inventoryService = app(InventoryService::class);
                $purchaseInvoice = $item->purchaseInvoice ?? PurchaseInvoice::find($item->purchase_invoice_id);
                $warehouse = $purchaseInvoice->warehouse;
                $creator = $purchaseInvoice->creator ?? auth()->user() ?? User::first();

                $oldSerialId = $item->getOriginal('battery_serial_id');
                if ($oldSerialId) {
                    InventoryTransaction::query()
                        ->where('battery_serial_id', $oldSerialId)
                        ->where('reference_type', PurchaseInvoice::class)
                        ->where('reference_id', $purchaseInvoice->id)
                        ->delete();

                    $oldSerial = BatterySerial::find($oldSerialId);
                    if ($oldSerial) {
                        $oldSerial->update(['current_status' => BatteryStatus::SupplierReturned]);
                    }
                }

                $newSerial = $inventoryService->recordPurchase(
                    product: $item->product,
                    serialNo: $item->serial_no,
                    warehouse: $warehouse,
                    referenceInvoice: $purchaseInvoice,
                    createdBy: $creator
                );

                $item->battery_serial_id = $newSerial->id;
            }
        });

        static::deleting(function (PurchaseItem $item) {
            $purchaseInvoice = $item->purchaseInvoice ?? PurchaseInvoice::find($item->purchase_invoice_id);
            if ($purchaseInvoice && $item->battery_serial_id) {
                InventoryTransaction::query()
                    ->where('battery_serial_id', $item->battery_serial_id)
                    ->where('reference_type', PurchaseInvoice::class)
                    ->where('reference_id', $purchaseInvoice->id)
                    ->delete();

                $serial = BatterySerial::find($item->battery_serial_id);
                if ($serial) {
                    $serial->update(['current_status' => BatteryStatus::SupplierReturned]);
                }
            }
        });
    }

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function serial(): BelongsTo
    {
        return $this->belongsTo(BatterySerial::class, 'battery_serial_id');
    }
}
