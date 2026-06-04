<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'recipient',
    'notification_type',
    'payload',
    'delivery_status',
])]
class NotificationLog extends Model
{
    //
}
