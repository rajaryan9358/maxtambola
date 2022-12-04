<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BumperTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number',
        'ticket',
    ];
}
