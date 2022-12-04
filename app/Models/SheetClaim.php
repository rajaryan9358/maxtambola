<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SheetClaim extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_claim_id',
        'ticket_number',
        'ticket',
        'user_id',
        'game_date',
        'game_time',
		'checked_number',
		'prize_tag',
		'sheet_type'
    ];
}
