<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'mobile',
    'national_id',
    'image_path',
    'division',
    'district',
    'upazila',
    'address',
    'customer_type',
    'credit_limit',
    'is_active',
])]
class Customer extends Model
{
    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
