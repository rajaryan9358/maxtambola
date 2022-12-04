<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBumperPrizesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bumper_prizes', function (Blueprint $table) {
            $table->id();
            $table->string('prize_name');
			$table->string('prize_tag');
			$table->integer('prize_count');
			$table->double('prize_amount');
			$table->boolean('status');
			$table->string('prize_status')->default("NEW");
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
        Schema::dropIfExists('bumper_prizes');
    }
}
