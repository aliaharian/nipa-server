<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFormFieldFormsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_field_forms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_field_id')->index();
            $table->unsignedBigInteger('form_id')->index();
            $table->timestamps();

            $table->foreign('form_field_id')->references('id')->on('form_fields')->onDelete('cascade');
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
        Schema::dropIfExists('form_field_forms');
    }
}
