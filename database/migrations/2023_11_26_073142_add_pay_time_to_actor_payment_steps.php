<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPayTimeToActorPaymentSteps extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('factor_payment_steps', function (Blueprint $table) {
            //
            $table->dateTime('pay_time')->after('allow_offline')->nullable()->comment("مهلت پرداخت");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('actor_payment_steps', function (Blueprint $table) {
            //
        });
    }
}
