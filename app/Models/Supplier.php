<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'mobile', 'address', 'supplier_type'])]
class Supplier extends Model
{
    //
}
