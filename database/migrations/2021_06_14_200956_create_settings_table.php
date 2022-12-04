<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->integer('app_version');
			$table->string('account_number');
			$table->boolean('add_money_notification')->default(1);
			$table->boolean('prize_cliam_notification')->default(1);
			$table->boolean('ticket_notification')->default(1);
			$table->boolean('user_signup_notification')->default(1);
			$table->boolean('withdrawal_notification')->default(1);
			$table->integer('call_duration')->default(6);
			$table->double('max_withdrawal')->default(100000);
			$table->double('min_kyc')->default(50000);
			$table->double('min_withdrawal')->default(200);
			$table->string('merchant_id');
			$table->string('merchant_key');
			$table->string('withdraw_mode');
            $table->text('sms_api');
            $table->text('gcm_auth');
            $table->longText('terms_conditions');
            $table->longText('privacy_policy');
            $table->longText('refund_policy');
            $table->longText('about_us');
            $table->longText('contact_us');
            $table->string('contact_email');
            $table->string('contact_whatsapp');

            $table->string('cf_app_id');
            $table->string('cf_secret_key');
            $table->string('cf_webhook_url');
            $table->string('cfp_app_id');
            $table->string('cfp_secret_key');
            $table->text('cfp_auth');
            $table->bigInteger('cfp_auth_expiry');

            $table->integer('is_automatic_pricing');
            $table->integer('early5_percent');
            $table->integer('topline_percent');
            $table->integer('middleline_percent');
            $table->integer('bottomline_percent');
            $table->integer('fullhouse_percent');
            $table->integer('fullhouse2_percent');
            $table->integer('fullhouse3_percent');
            $table->integer('halfsheet_percent');
            $table->integer('corners_percent');

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
        Schema::dropIfExists('settings');
    }
}
