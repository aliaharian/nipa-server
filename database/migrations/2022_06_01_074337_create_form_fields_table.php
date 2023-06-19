<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFormFieldsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('form_field_type_id')->index();
            $table->string('label')->nullable();
            $table->string('placeholder')->nullable();
            $table->string('helper_text')->nullable();
            $table->json('validation')->nullable();
            $table->boolean('required')->default(false);
            $table->unsignedInteger('min')->default(0);
            $table->unsignedInteger('max')->default(100);
            
            $table->timestamps();
        });
        //relation between form_fields and form_field_types
        Schema::table('form_fields', function (Blueprint $table) {
            $table->foreign('form_field_type_id')->references('id')->on('form_field_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('form_fields');
    }
}
