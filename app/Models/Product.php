<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperProduct
 */
#[Fillable([
    'category_id',
    'brand_name',
    'model_name',
    'voltage',
    'capacity_ah',
    'plate_count',
    'warranty_months',
    'mrp_price',
    'dealer_price',
    'set_price',
    'standard_set_qty',
    'purchase_cost',
    'has_serial_tracking',
    'alert_threshold_qty',
    'is_active',
])]
class Product extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'plate_count' => 'integer',
            'warranty_months' => 'integer',
            'mrp_price' => 'decimal:2',
            'dealer_price' => 'decimal:2',
            'set_price' => 'decimal:2',
            'standard_set_qty' => 'integer',
            'purchase_cost' => 'decimal:2',
            'has_serial_tracking' => 'boolean',
            'alert_threshold_qty' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function serials(): HasMany
    {
        return $this->hasMany(BatterySerial::class, 'product_id');
    }
}
