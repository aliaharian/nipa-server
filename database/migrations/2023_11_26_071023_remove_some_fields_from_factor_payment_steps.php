<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveSomeFieldsFromFactorPaymentSteps extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('factor_payment_steps', function (Blueprint $table) {
            //remove paid_method , paid_price , tracking_code
            $table->dropColumn('paid_method');
            $table->dropColumn('paid_price');
            $table->dropColumn('tracking_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('factor_payment_steps', function (Blueprint $table) {
            //
        });
    }
}
