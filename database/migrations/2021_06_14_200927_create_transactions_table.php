<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
			$table->string('order_id');
			$table->string('txn_id');
			$table->bigInteger('user_id');
			$table->string('txn_mode');
			$table->string('txn_type');
			$table->string('txn_status');
			$table->string('txn_title');
			$table->string('txn_sub_title');
			$table->string('txn_admin_title');
			$table->string('txn_message');
			$table->double('txn_amount')->default(0);
			$table->double('closing_balance');
            $table->string('reference_id')->nullable();
            $table->string('account_number');
            $table->string('account_name');
            $table->string('account_ifsc');
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
        Schema::dropIfExists('transactions');
    }
}
