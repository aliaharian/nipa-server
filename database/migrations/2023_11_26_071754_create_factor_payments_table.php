<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFactorPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('factor_payments', function (Blueprint $table) {
            $table->id();
            //payment step id , transaction id , description , payment status id , meta
            $table->unsignedBigInteger('payment_step_id')->index();
            $table->unsignedBigInteger('transaction_id')->index();
            $table->string('description')->nullable();
            $table->unsignedBigInteger('payment_status_id')->index();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('payment_step_id')->references('id')->on('factor_payment_steps')->cascadeOnDelete();
            $table->foreign('transaction_id')->references('id')->on('transactions')->cascadeOnDelete();
            $table->foreign('payment_status_id')->references('id')->on('payment_statuses')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('factor_payments');
    }
}
