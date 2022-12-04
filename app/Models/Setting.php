<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

	protected $fillable = [
        'app_version',
        'account_number',
        'add_money_notification',
        'prize_cliam_notification',
        'ticket_notification',
        'user_signup_notification',
        'withdrawal_notification',
        'call_duration',
        'max_withdrawal',
        'min_kyc',
        'min_withdrawal',
        'merchant_id',
        'merchant_key',
        'sms_api',
        'gcm_auth',
        'withdraw_mode',
        'terms_conditions',
        'privacy_policy',
        'refund_policy',
        'about_us',
        'contact_us',
        'contact_email',
        'contact_whatsapp',
        'cf_app_id',
        'cf_secret_key',
        'cf_webhook_url',
        'cfp_app_id',
        'cfp_secret_key',
        'cfp_auth',
        'cfp_auth_expiry',

        'is_automatic_pricing',
        'early5_percent',
        'topline_percent',
        'middleline_percent',
        'bottomline_percent',
        'fullhouse_percent',
        'fullhouse2_percent',
        'fullhouse3_percent',
        'corners_percent',
        'halfsheet_percent',
    ];
}
