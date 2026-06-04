<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
