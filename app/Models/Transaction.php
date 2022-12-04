<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

	protected $fillable = [
        'order_id',
        'txn_id',
        'user_id',
		'txn_mode',
		'txn_type',
        'txn_status',
		'txn_title',
		'txn_sub_title',
		'txn_admin_title',
		'txn_message',
		'txn_amount',
		'closing_balance',
		'reference_id',
		'account_number',
		'account_name',
		'account_ifsc',
    ];
}
