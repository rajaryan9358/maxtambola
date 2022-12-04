<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_name',
        'phone',
        'user_profile',
		'otp',
        'token',
        'referral_code',
        'contact_id',
        'fund_account_id',
        'kyc_status',
        'wallet_balance',
        'locked_balance',
        'withdrawal_amount',
        'is_blocked',
        'email',
        'address',
        'city',
        'state',
        'pincode',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
}
