<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFactorPaymentStepStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_step_statuses', function (Blueprint $table) {
            $table->id();
            //factor payment step id
            $table->unsignedBigInteger('factor_payment_step_id');
            $table->foreign('factor_payment_step_id')->references('id')->on('factor_payment_steps');
            //factor payment step status enum id
            $table->unsignedBigInteger('payment_step_status_enum_id');
            $table->foreign('payment_step_status_enum_id')->references('id')->on('payment_step_status_enums');
            //description
            $table->string('description')->nullable();
            //attachment file id
            $table->unsignedBigInteger('attachment_file_id')->nullable();
            $table->foreign('attachment_file_id')->references('id')->on('files');
            //یک فیلد که مشخص کنه این مرحله رو ادمین گذرونده یا یوزر
            $table->boolean('is_admin')->default(false);
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
        Schema::dropIfExists('payment_step_statuses');
    }
}
