<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_number',
        'account_name',
        'account_ifsc',
		'vpa_id',
        'is_payu_active',
		'is_upi_active',
        'is_manual_upi_active',
        'is_manual_bank_active',
        'is_reference_id_active',
        'is_manual_payment_active',
        'is_manual_cancel_active',
        'is_withdraw_cancel_active',
    ];
}
