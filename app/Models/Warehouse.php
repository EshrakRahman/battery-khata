<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperWarehouse
 */
#[Fillable(['name', 'location'])]
class Warehouse extends Model
{
    //
}
