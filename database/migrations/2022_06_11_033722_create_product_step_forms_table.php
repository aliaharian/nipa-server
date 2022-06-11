<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductStepFormsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_step_forms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_step_id')->index();
            $table->unsignedBigInteger('form_id')->index();
            $table->timestamps();

            //FOREIGN KEY CONSTRAINTS
            $table->foreign('product_step_id')->references('id')->on('product_steps')->onDelete('cascade');
            $table->foreign('form_id')->references('id')->on('forms')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_step_forms');
    }
}
