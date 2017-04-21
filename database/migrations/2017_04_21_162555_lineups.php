<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Lineups extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql')->create('lineups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('slate');
        });
        Schema::connection('mysql')->create('nhl_lineups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('hash');
            $table->string('slate');
            $table->string('name');
            $table->integer('salary');
            $table->float('points');
            $table->string('projection');
            $table->string('c1');
            $table->string('c2');
            $table->string('w1');
            $table->string('w2');
            $table->string('w3');
            $table->string('w4');
            $table->string('d1');
            $table->string('d2');
            $table->string('g');
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
        Schema::drop('lineups');
        Schema::drop('nhl_lineups');
    }
}
