<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_tickets', function (Blueprint $table) {
            $table->id();
			$table->string('game_date');
			$table->string('game_time');
            $table->dateTime('game_datetime');
			$table->text('ticket');
			$table->integer('ticket_number');
			$table->double('ticket_price');
			$table->integer('sheet_number');
			$table->string('sheet_type');
			$table->bigInteger('user_id');
			$table->string('transaction_id');
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
        Schema::dropIfExists('game_tickets');
    }
}
