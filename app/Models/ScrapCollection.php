<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'customer_id',
    'invoice_id',
    'warehouse_id',
    'scrap_type',
    'quantity',
    'estimated_weight',
    'unit_value',
    'total_value',
    'status',
    'scrap_disposal_id',
    'notes',
    'created_by',
])]
class ScrapCollection extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'estimated_weight' => 'decimal:2',
            'unit_value' => 'decimal:2',
            'total_value' => 'decimal:2',
        ];
    }

    public function scrapDisposal(): BelongsTo
    {
        return $this->belongsTo(ScrapDisposal::class, 'scrap_disposal_id');
    }
}
