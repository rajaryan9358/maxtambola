<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameClaimsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_claims', function (Blueprint $table) {
            $table->id();
			$table->string('game_date');
			$table->string('game_time');
			$table->dateTime('game_datetime');
			$table->string('prize_name');
			$table->string('prize_tag');
			$table->double('prize_amount')->default(0);
			$table->bigInteger('user_id');
			$table->integer('ticket_number');
			$table->text('ticket');
			$table->text('checked_number');
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
        Schema::dropIfExists('game_claims');
    }
}
