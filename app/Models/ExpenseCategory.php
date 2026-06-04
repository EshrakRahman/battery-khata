<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperExpenseCategory
 */
#[Fillable(['name'])]
class ExpenseCategory extends Model
{
    //
}
