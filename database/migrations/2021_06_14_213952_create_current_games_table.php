<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCurrentGamesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('current_games', function (Blueprint $table) {
            $table->id();
			$table->string('game_date');
			$table->string('game_time');
			$table->double('ticket_price');
			$table->boolean('bumper');
			$table->datetime('game_datetime');
            $table->datetime('booking_close_time');
			$table->string('game_status')->default("NEW");
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
        Schema::dropIfExists('current_games');
    }
}
