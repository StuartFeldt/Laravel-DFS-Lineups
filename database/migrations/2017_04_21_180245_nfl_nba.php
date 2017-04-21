<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class NflNba extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::drop('lineups');
        Schema::connection('mysql')->create('nba_lineups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('hash');
            $table->string('slate');
            $table->string('name');
            $table->integer('salary');
            $table->float('points');
            $table->string('projection');
            $table->string('pg1');
            $table->string('pg2');
            $table->string('sg1');
            $table->string('sg2');
            $table->string('sf1');
            $table->string('sf2');
            $table->string('pf1');
            $table->string('pf2');
            $table->string('c');
            $table->timestamps();
        });

        Schema::connection('mysql')->create('lineups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('hash');
            $table->string('slate');
            $table->string('name');
            $table->integer('salary');
            $table->float('points');
            $table->string('projection');
            $table->string('qb');
            $table->string('rb1');
            $table->string('rb2');
            $table->string('wr1');
            $table->string('wr2');
            $table->string('wr3');
            $table->string('te');
            $table->string('k');
            $table->string('d');
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
        Schema::drop('nba_lineups');
    }
}
