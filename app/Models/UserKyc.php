<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserKyc extends Model
{
    use HasFactory;

	protected $fillable = [
        'user_id',
        'user_name',
        'user_profile',
        'pan_front',
        'pan_back',
        'status'
    ];
}
