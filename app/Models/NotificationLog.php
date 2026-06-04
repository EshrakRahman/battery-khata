<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperNotificationLog
 */
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
