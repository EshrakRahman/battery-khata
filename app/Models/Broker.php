<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperBroker
 */
#[Fillable(['name', 'mobile', 'commission_rate'])]
class Broker extends Model
{
    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
        ];
    }
}
