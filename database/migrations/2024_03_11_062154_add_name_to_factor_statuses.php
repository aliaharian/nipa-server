<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNameToFactorStatuses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('factor_statuses', function (Blueprint $table) {
            //
            $column = Schema::hasColumn('factor_statuses', 'name');


            if (!$column) {
                $table->string("name")->nullable()->after('id');
                // do something
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('factor_statuses', function (Blueprint $table) {
            //
        });
    }
}
