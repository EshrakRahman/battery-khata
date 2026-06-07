<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperScrapDisposal
 */
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
            'payment_method' => PaymentMethod::class,
            'total_received' => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function collections(): HasMany
    {
        return $this->hasMany(ScrapCollection::class, 'scrap_disposal_id');
    }
}
