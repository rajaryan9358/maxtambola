<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionPayout extends Model
{
    use HasFactory;

	protected $fillable = [
        'txn_id',
        'user_id',
        'account_name',
        'account_ifsc',
        'account_number',
    ];
}
