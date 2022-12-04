<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSheetClaimsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sheet_claims', function (Blueprint $table) {
            $table->id();
            $table->integer('game_claim_id');
            $table->integer('ticket_number');
            $table->string('ticket');
            $table->integer('user_id');
            $table->string('game_date');
            $table->string('game_time');
            $table->string('checked_number');
            $table->string('prize_tag');
            $table->string('sheet_type');
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
        Schema::dropIfExists('sheet_claims');
    }
}
