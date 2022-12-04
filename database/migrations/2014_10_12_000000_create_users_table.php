<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('user_name')->default("");
            $table->string('phone')->unique();
            $table->string('user_profile')->default("");
			$table->string('otp');
            $table->text('token')->nullable();
            $table->string('referral_code');
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('pincode')->nullable();
            $table->string('contact_id')->nullable();
            $table->string('fund_account_id')->nullable();
            $table->string('kyc_status')->default("REQUIRED");
            $table->double('wallet_balance')->default(0);
            $table->double('locked_balance')->default(0);
            $table->double('withdrawal_amount')->default(0);
            $table->boolean('is_blocked')->default(0);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
