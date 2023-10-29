<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFactorPaymentStepsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('factor_payment_steps', function (Blueprint $table) {
            $table->id();
            //factor id
            $table->unsignedBigInteger('factor_id');
            $table->foreign('factor_id')->references('id')->on('factors');
            //step number
            $table->integer('step_number');
            //payable price
            $table->integer('payable_price');
            //paid price
            $table->integer('paid_price')->nullable();
            //payable methods (give array of "online" or "offline" or both)
            //create two "allow online" and "allow offline" columns
            $table->boolean('allow_online')->default(false);
            $table->boolean('allow_offline')->default(false);
            //paied method
            $table->enum('paid_method', ['online', 'offline'])->nullable();
            //شماره پیگیری
            $table->string('tracking_code')->nullable();
         
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
        Schema::dropIfExists('factor_payment_steps');
    }
}
