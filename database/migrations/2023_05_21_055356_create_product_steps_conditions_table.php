<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductStepsConditionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_steps_conditions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_step_id');
            $table->unsignedBigInteger('form_field_id');
            $table->unsignedBigInteger('form_field_option_id');
            $table->unsignedBigInteger('next_product_step_id');

            $table->timestamps();

            $table->foreign('product_step_id')->references('id')->on('product_steps');
            $table->foreign('form_field_id')->references('id')->on('form_fields');
            $table->foreign('form_field_option_id')->references('id')->on('form_field_options');
            $table->foreign('next_product_step_id')->references('id')->on('product_steps');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_steps_conditions');
    }
}