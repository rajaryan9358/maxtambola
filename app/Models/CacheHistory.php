<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CacheHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_change_cn',
        'ticket_change_cn',
        'bumper_ticket_change_cn',
        'prize_change_cn',
        'setting_change_cn',
        'payment_change_cn'
    ];
}
