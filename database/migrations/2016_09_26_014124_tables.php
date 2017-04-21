<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Tables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::connection('mysql')->create('projections', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('fd_id');
            $table->string('type');
            $table->float('pts');
            $table->timestamps();
        });

        Schema::connection('mysql')->create('fd_players', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('fd_id');
            $table->string('name');
            $table->integer('salary');
            $table->string('team');
            $table->string('opp');
            $table->string('game');
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
        //
    }
}
