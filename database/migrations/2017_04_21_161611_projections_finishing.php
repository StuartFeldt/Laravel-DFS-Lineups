<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ProjectionsFinishing extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql')->table('projections', function($table) {
            $table->dropColumn('fd_id');
        });
        Schema::connection('mysql')->table('projections', function($table) {
            $table->string('fd_id')->after('id');
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
            $table->dropColumn('fd_id');
        });
    }
}
