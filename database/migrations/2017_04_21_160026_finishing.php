<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Finishing extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql')->table('fd_players', function($table) {
            $table->datetime('deleted_at')->nullable();
            $table->integer('slate')->after('fd_id');
            $table->string('pos')->after('slate');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         Schema::connection('mysql')->table('fd_players', function($table) {
            $table->dropColumn('deleted_at');
            $table->dropColumn('slate');
            $table->dropColumn('pos');
        });
    }
}
