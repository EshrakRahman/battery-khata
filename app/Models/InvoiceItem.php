<?php

namespace App\Models;

use App\Enums\BatteryStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperInvoiceItem
 */
#[Fillable([
    'invoice_id',
    'product_id',
    'battery_serial_id',
    'sale_price',
    'warranty_months',
])]
class InvoiceItem extends Model
{
    protected function casts(): array
    {
        return [
            'sale_price' => 'decimal:2',
            'warranty_months' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (InvoiceItem $item) {
            $invoice = $item->invoice ?? Invoice::find($item->invoice_id);
            if ($invoice && $item->battery_serial_id) {
                InventoryTransaction::query()
                    ->where('battery_serial_id', $item->battery_serial_id)
                    ->where('reference_type', Invoice::class)
                    ->where('reference_id', $invoice->id)
                    ->delete();

                $serial = BatterySerial::find($item->battery_serial_id);
                if ($serial) {
                    $serial->update(['current_status' => BatteryStatus::InStock]);
                }
            }
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
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
