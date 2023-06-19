<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductStepsRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_steps_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_step_id');
            $table->unsignedBigInteger('role_id');
            $table->timestamps();

            $table->foreign('product_step_id')->references('id')->on('product_steps')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_steps_roles');
    }
}