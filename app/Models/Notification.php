<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

	protected $fillable = [
        'notification_time',
        'notification_title',
        'notification_message',
        'send_to',
        'status',
    ];
}
