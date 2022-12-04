<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCacheHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cache_histories', function (Blueprint $table) {
            $table->id();
            $table->integer('game_change_cn')->default(0);
            $table->integer('ticket_change_cn')->default(0);
            $table->integer('bumper_ticket_change_cn')->default(0);
            $table->integer('prize_change_cn')->default(0);
            $table->integer('setting_change_cn')->default(0);
            $table->integer('payment_change_cn')->default(0);
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
        Schema::dropIfExists('cache_histories');
    }
}
