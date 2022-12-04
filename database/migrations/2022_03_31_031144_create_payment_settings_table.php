<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_settings', function (Blueprint $table) {
            $table->id();
            $table->string('account_number');
            $table->string('account_name');
            $table->string('account_ifsc');
            $table->string('vpa_id');
            $table->integer('is_payu_active');
            $table->integer('is_upi_active');
            $table->integer('is_manual_upi_active');
            $table->integer('is_manual_bank_active');
            $table->integer('is_reference_id_active');
            $table->integer('is_manual_payment_active');
            $table->integer('is_manual_cancel_active');
            $table->integer('is_withdraw_cancel_active');
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
        Schema::dropIfExists('payment_settings');
    }
}
