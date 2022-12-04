<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;

	protected $fillable = [
        'game_date',
        'game_time',
		'game_datetime',
        'type',
        'ticket_price',
        'status',
        'bumper',
        'booking_close_minutes',
    ];
}
