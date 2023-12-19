<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ConverUnitPriceToFloatInFactorItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('factor_items', function (Blueprint $table) {
            //
            $table->float('unit_price')->change();
            $table->float('off_price')->change();
            $table->float('additional_price')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('float_in_factor_items', function (Blueprint $table) {
            //
        });
    }
}
