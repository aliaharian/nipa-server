<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFactorStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('factor_statuses', function (Blueprint $table) {
            $table->id();
            //factor status enum id
            $table->unsignedBigInteger('factor_status_enum_id');
            $table->foreign('factor_status_enum_id')->references('id')->on('factor_status_enums');
            //factor id
            $table->unsignedBigInteger('factor_id');
            $table->foreign('factor_id')->references('id')->on('factors');
            //description
            $table->string('description')->nullable();
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
        Schema::dropIfExists('factor_statuses');
    }
}
