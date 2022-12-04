<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameTicket extends Model
{
    use HasFactory;

	protected $fillable = [
        'game_date',
        'game_time',
        'game_datetime',
        'ticket',
        'ticket_number',
        'ticket_price',
		'sheet_number',
		'sheet_type',
        'user_id',
        'transaction_id',
    ];
}
