<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurrentGame extends Model
{
    use HasFactory;

	protected $fillable = [
        'game_date',
        'game_time',
		'ticket_price',
		'bumper',
        'game_datetime',
        'booking_close_time',
		'game_status'
    ];

}
