<?php

namespace App\Models;

use App\Enums\BatteryStatus;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperBufferBatteryIssue
 */
#[Fillable([
    'warranty_claim_id',
    'battery_serial_id',
    'issued_date',
    'returned_date',
    'notes',
    'created_by',
])]
class BufferBatteryIssue extends Model
{
    protected static function booted(): void
    {
        static::deleting(function (BufferBatteryIssue $issue) {
            // Restore buffer serial back to InStock if it was BufferIssued
            if ($issue->battery_serial_id) {
                $bufferSerial = BatterySerial::find($issue->battery_serial_id);
                if ($bufferSerial && $bufferSerial->current_status === BatteryStatus::BufferIssued) {
                    $bufferSerial->update(['current_status' => BatteryStatus::InStock]);
                }
            }

            // Delete inventory transactions related to this buffer issue
            InventoryTransaction::query()
                ->where('reference_type', 'WarrantyClaim')
                ->where('reference_id', $issue->warranty_claim_id)
                ->whereIn('transaction_type', [TransactionType::BufferIssue, TransactionType::BufferReturn])
                ->delete();
        });
    }

    protected function casts(): array
    {
        return [
            'issued_date' => 'date',
            'returned_date' => 'date',
        ];
    }

    public function bufferSerial(): BelongsTo
    {
        return $this->belongsTo(BatterySerial::class, 'battery_serial_id');
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(WarrantyClaim::class, 'warranty_claim_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
