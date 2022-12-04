<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameJoin extends Model
{
    use HasFactory;

	protected $fillable = [
        'played_game_id',
        'user_id',
        'total_tickets',
    ];
}
