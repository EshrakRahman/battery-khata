<?php

namespace App\Models;

use App\Enums\BatteryStatus;
use App\Enums\ClaimStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperWarrantyClaim
 */
#[Fillable([
    'invoice_item_id',
    'battery_serial_id',
    'claim_no',
    'claim_date',
    'supplier_claim_no',
    'customer_issue',
    'claim_status',
    'supplier_sent_date',
    'resolved_date',
    'replacement_battery_serial_id',
    'resolution_notes',
    'created_by',
])]
class WarrantyClaim extends Model
{
    protected static function booted(): void
    {
        static::creating(function (WarrantyClaim $claim) {
            if (empty($claim->claim_no)) {
                $claim->claim_no = 'WRN-'.now()->format('YmdHis').'-'.rand(1000, 9999);
            }
        });

        static::deleting(function (WarrantyClaim $claim) {
            // Restore faulty serial back to Sold if it was WarrantyClaim
            if ($claim->battery_serial_id) {
                $faultySerial = BatterySerial::find($claim->battery_serial_id);
                if ($faultySerial && $faultySerial->current_status === BatteryStatus::WarrantyClaim) {
                    $faultySerial->update(['current_status' => BatteryStatus::Sold]);
                }
            }

            // Restore replacement serial back to InStock if it was Sold
            if ($claim->replacement_battery_serial_id) {
                $replacementSerial = BatterySerial::find($claim->replacement_battery_serial_id);
                if ($replacementSerial && $replacementSerial->current_status === BatteryStatus::Sold) {
                    $replacementSerial->update(['current_status' => BatteryStatus::InStock]);
                }
            }

            // Delete inventory transactions related to this claim
            InventoryTransaction::query()
                ->where('reference_type', WarrantyClaim::class)
                ->where('reference_id', $claim->id)
                ->delete();

            // Delete all buffer battery issues related to this claim (will trigger their deleting hooks)
            $claim->bufferIssues()->get()->each(function (BufferBatteryIssue $bufferIssue) {
                $bufferIssue->delete();
            });
        });
    }

    protected function casts(): array
    {
        return [
            'claim_date' => 'date',
            'supplier_sent_date' => 'date',
            'resolved_date' => 'date',
            'claim_status' => ClaimStatus::class,
        ];
    }

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class, 'invoice_item_id');
    }

    public function faultySerial(): BelongsTo
    {
        return $this->belongsTo(BatterySerial::class, 'battery_serial_id');
    }

    public function replacementSerial(): BelongsTo
    {
        return $this->belongsTo(BatterySerial::class, 'replacement_battery_serial_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function bufferIssues(): HasMany
    {
        return $this->hasMany(BufferBatteryIssue::class, 'warranty_claim_id');
    }
}
