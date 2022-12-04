<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameClaim extends Model
{
    use HasFactory;

	
	protected $fillable = [
        'game_date',
        'game_time',
		'game_datetime',
        'prize_name',
        'prize_tag',
		'prize_amount',
		'user_id',
        'ticket_number',
		'ticket',
        'checked_number',
    ];
}
