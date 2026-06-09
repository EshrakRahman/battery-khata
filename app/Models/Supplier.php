<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperSupplier
 */
#[Fillable(['name', 'mobile', 'address', 'supplier_type'])]
class Supplier extends Model
{
    public function ledgers(): HasMany
    {
        return $this->hasMany(SupplierLedger::class, 'supplier_id');
    }
}
