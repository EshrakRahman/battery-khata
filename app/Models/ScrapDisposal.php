<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'supplier_id',
    'disposal_date',
    'payment_method',
    'total_received',
    'notes',
    'created_by',
])]
class ScrapDisposal extends Model
{
    protected function casts(): array
    {
        return [
            'disposal_date' => 'date',
            'total_received' => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
