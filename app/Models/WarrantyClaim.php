<?php

namespace App\Models;

use App\Enums\ClaimStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    protected function casts(): array
    {
        return [
            'claim_date' => 'date',
            'supplier_sent_date' => 'date',
            'resolved_date' => 'date',
            'claim_status' => ClaimStatus::class,
        ];
    }

    public function faultySerial(): BelongsTo
    {
        return $this->belongsTo(BatterySerial::class, 'battery_serial_id');
    }

    public function replacementSerial(): BelongsTo
    {
        return $this->belongsTo(BatterySerial::class, 'replacement_battery_serial_id');
    }
}
