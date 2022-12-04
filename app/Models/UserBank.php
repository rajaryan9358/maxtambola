<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserBank extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'account_number',
		'account_name',
        'account_ifsc',
    ];
}
